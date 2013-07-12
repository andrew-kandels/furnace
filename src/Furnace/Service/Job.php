<?php
/**
 * Furnace Project
 *
 * This source file is subject to the BSD license bundled with
 * this package in the LICENSE.txt file. It is also available
 * on the world-wide-web at http://www.opensource.org/licenses/bsd-license.php.
 * If you are unable to receive a copy of the license or have
 * questions concerning the terms, please send an email to
 * me@andrewkandels.com.
 *
 * @category    akandels
 * @package     furnace
 * @author      Andrew Kandels (me@andrewkandels.com)
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link        http://contain-project.org/furnace
 */

namespace Furnace\Service;

use ContainMapper\Service\AbstractService;
use ContainMapper\Mapper;
use ContainMapper\Cursor;
use RuntimeException;
use Furnace\Entity\Job as JobEntity;
use Furnace\Entity\Heartbeat as HeartbeatEntity;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Furnace\Jobs\JobInterface;

/**
 * Service for managing and tracking jobs through their entities.
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Job extends AbstractService
{
    /**
     * @var ContainMapper\Mapper
     */
    protected $mapper;

    /**
     * @var ContainMapper\Mapper
     */
    protected $heartbeat;

    /**
     * @var ContainMapper\Mapper
     */
    protected $config;

    /**
     * @var Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @var string
     */
    protected $lastError;

    /**
     * Constructor
     *
     * @param   ContainMapper\Mapper
     * @return  void
     */
    public function __construct(Mapper $mapper, Mapper $heartbeat, array $config, ServiceLocatorInterface $sm)
    {
        $this->mapper = $mapper;
        $this->heartbeat = $heartbeat;
        $this->config = $config;
        $this->serviceLocator = $sm; // purely for instantiating workers
    }

    /**
     * Finds a single job by its name/primary key value.
     *
     * @param   string                          Name
     * @return  Furnace\Entity\Job
     */
    public function findByName($name)
    {
        return $this->prepare($this->mapper)->findOne(array(
            '_id' => $name,
        ));
    }

    /**
     * Finds a single job by its name/primary key value.
     *
     * @param   array                           Job Names
     * @return  Furnace\Entity\Job
     */
    public function findByNames(array $names)
    {
        return $this->prepare($this->mapper)->find(array(
            '_id' => array('$in' => $names),
        ));
    }

    /**
     * Finds jobs by criterion.
     *
     * @param   array                           Search Criterion
     * @return  Furnace\Entity\Job[] (via ContainMapper\Cursor)
     */
    public function find(array $where = array())
    {
        return $this->prepare($this->mapper)->find($where);
    }

    /**
     * Finds all jobs not by a given name.
     *
     * @param   string                              Job Name to Exclude
     * @return  Furnace\Entity\Job[] (via ContainMapper\Cursor)
     */
    public function findNotNamed($name)
    {
        return $this->prepare($this->mapper)->find(array(
            '_id' => array('$ne' => $name),
            'schedule' => array('$in' => array('daily', 'monthly', 'weekly')),
        ));
    }

    /**
     * Finds many jobs that match a given schedule (monthly, daily, etc.).
     *
     * @param   string                          Schedule
     * @return  Furnace\Entity\Job[] (via ContainMapper\Cursor)
     */
    public function findBySchedule($schedule)
    {
        return $this->prepare($this->mapper)->find(array(
            'schedule' => $schedule,
        ));
    }

    /**
     * Finds incomplete jobs that match a given schedule (monthly, daily, etc.).
     *
     * @param   string                          Schedule
     * @param   DateTime|string|integer
     * @return  Furnace\Entity\Job[] (via ContainMapper\Cursor)
     */
    public function findIncompleteBySchedule($schedule, $when = null)
    {
        $cursor  = $this->findBySchedule($schedule);
        $results = array();

        foreach ($cursor as $job) {
            if (!$job->isCompleted($when ?: time())) {
                $results[] = $job->export();
            }
        }

        return new Cursor($this->mapper, $results);
    }

    /**
     * Adds or saves an existing job entity.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function save(JobEntity $job)
    {
        $this->prepare($this->mapper)->persist($job);
        return $this;
    }

    /**
     * Adds or saves an existing job entity.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function delete(JobEntity $job)
    {
        $this->prepare($this->mapper)->delete($job);
        return $this;
    }

    /**
     * Executes a job.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function run(JobEntity $job)
    {
        if ($job->isStarted()) {
            throw new RuntimeException('$job is already running');
        }

        if ($job->isQueued()) {
            throw new RuntimeException('$job is already queued');
        }

        $worker = $this->findWorker($job);

        $this->queue($job);
        $worker->run($job);

        return $this;
    }

    /**
     * Update a job to reflect it has been queued.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function queue(JobEntity $job)
    {
        $job->queue();
        $this->save($job);
        return $this;
    }

    /**
     * Checks to see if a job's dependencies have been met.
     *
     * @param   Furnace\Entity\Job
     * @return  boolean
     */
    public function hasDependencies(JobEntity $job)
    {
        if (!$dependencies = $job->getDependencies()) {
            return true;
        }

        $rs = $this->findByNames($dependencies);
        $failed = array();

        foreach ($rs as $dependency) {
            if (!$dependency->isCompleted()) {
                $failed[] = $dependency->getName();
            }
        }
            
        if ($failed) {
            $this->lastError = sprintf(
                'Cannot start job \'%s\' as %d dependenc%s not been met: %s',
                $job->getName(),
                number_format($num = count($failed), 0),
                $num != 1 ? 'ies have' : 'y has',
                implode(', ', $failed)
            );

            return false;
        }

        return true;
    }

    /**
     * Update a job to reflect it has been started.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function start(JobEntity $job)
    {
        if (!$this->hasDependencies($job)) {
            throw new RuntimeException($this->lastError);
        }

        $job->clear('logs');
        $job->start();
        $this->save($job);
        return $this;
    }

    /**
     * Update a job to reflect it has been completed.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function complete(JobEntity $job)
    {
        $job->complete();
        $this->save($job);
        return $this;
    }

    /**
     * Update a job to reflect it has not been completed.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function incomplete(JobEntity $job)
    {
        $job->incomplete();
        $this->save($job);
        return $this;
    }

    /**
     * Update a job to reflect it failed.
     *
     * @param   Furnace\Entity\Job
     * @param   string                              Optional message
     * @return  $this
     */
    public function fail(JobEntity $job, $message = '')
    {
        $job->fail($message);
        $this->save($job);
        return $this;
    }

    /**
     * Updates a job's progress.
     *
     * @param   Furnace\Entity\Job
     * @param   integer                             Percent Complete
     * @param
     * @return  $this
     */
    public function progress(JobEntity $job, $pct)
    {
        $job->progress($pct);
        $this->save($job);
        return $this;
    }

    /**
     * Finds the worker responsible for terminating running jobs. 
     *
     * @return  Furnace\Jobs\JobInterface
     */
    public function findTerminationWorker()
    {
        $className = $this->config['jobs']['class_terminate'];

        try {
            $worker = $this->serviceLocator->get($className);
        } catch (ServiceNotFoundException $e) {
            throw RuntimeException('Cannot create termination worker, configuration furnish '
                . '-> jobs -> class_terminate (' . $className . ') invalid or not found by the '
                . 'service locator'
            );
        }

        if (!$worker instanceof JobInterface) {
            throw RuntimeException('$worker of class ' . get_class($worker) . ' does not '
                . 'extend Furnace\Jobs\JobInterface'
            );
        }

        return $worker;
    }

    /**
     * Finds and instantiates a worker class as configured in this application's
     * class_template based on the name of the job first, using a backup/general
     * name if none is defined. This is the only instance that uses the service
     * locator.
     *
     * @param   Furnace\Entity\Job
     * @param   string                                  Class Template (defaults to job name)
     * @return  Furnace\Jobs\JobInterface
     */
    public function findWorker(JobEntity $job, $name = null)
    {
        if ($name === null) {
            $name = implode('', array_map(function($a) {
                return ucfirst($a);
            }, explode('-', $job->getName())));
        }

        $className = sprintf($this->config['jobs']['class_template'], $name);
        $defaultClassName = sprintf($this->config['jobs']['class_template'],
            $this->config['jobs']['default']
        );

        try {
            $worker = $this->serviceLocator->get($className);
        } catch (ServiceNotFoundException $e) {
            $worker = $this->serviceLocator->get($defaultClassName);
        }

        if (!$worker instanceof JobInterface) {
            throw RuntimeException('$worker of class ' . get_class($worker) . ' does not '
                . 'extend Furnace\Jobs\JobInterface'
            );
        }

        return $worker;
    }

    /**
     * Given a list of job names, return a similar list of items in the original list
     * that exist as valid jobs and that don't match a given name.
     *
     * @param   string                          Excluded job name
     * @param   array                           List
     * @return  array                           Validated list
     */
    public function validateNames($exclude, array $names)
    {
        $names = array_flip($names);
        unset($names[$exclude]);
        $names = array_flip($names);

        $rs = $this->properties('_id')->findByNames($names);

        $return = array();
        foreach ($rs as $job) {
            $return[] = $job->getName();
        }

        return $return;
    }

    /**
     * Gets the last error message.
     *
     * @return  string
     */
    public function getLastError()
    {
        return $this->lastError ?: '';
    }

    /**
     * Attempts to acquire an exclusive work lock.
     *
     * @return  boolean
     */
    public function acquireLock()
    {
        if ($heartbeat = $this->heartbeat->findOne(array('_id' => 'main'))) {
            $status = sprintf('/proc/%d/status', $heartbeat->getPidOf());

            if (file_exists($status)) {
                $this->lastError = sprintf('Cannot acquire lock, lock already acquired by pid #%d at %s (still running)',
                    $heartbeat->getPidOf(),
                    $heartbeat->getAt()->format('Y-m-d H:i:s')
                );

                return false;
            } else {
                $this->releaseLock();
            }
        }

        $heartbeat = new HeartbeatEntity(array(
            'name' => 'main',
            'at' => time(),
            'pidOf' => getmypid(),
            'user' => get_current_user(),
            'hostname' => gethostname(),
        ));

        $this->heartbeat->persist($heartbeat);

        return true;
    }

    /**
     * Releases a previously acquired lock from acquireLock().
     *
     * @return  $this
     */
    public function releaseLock()
    {
        $this->heartbeat->getConnection()->getCollection()->remove(array('_id' => 'main'));
        return $this;
    }

    /**
     * Called routinely through cron or a scheduler to trigger our scheduling
     * checks.
     *
     * @return  void
     */
    public function heartbeat()
    {
        if (!$this->acquireLock()) {
            throw new RuntimeException($this->lastError);
        }

        $numQueued = $this->mapper
            ->getConnection()
            ->getCollection()
            ->count(array(
                'queuedAt' => array('$ne' => null),
            ));

        if ($numQueued) {
            $this->lastError = sprintf('Nothing to do -- %s job%s still queued.',
                number_format($numQueued, 0),
                $numQueued != 1 ? 's are' : ' is'
            );

            return;
        }

        $rs = $this->mapper
            ->sort(array('priority' => 1))
            ->find(array(
                'numErrors' => array('$lt' => $config['maxErrors']),
            ));

        foreach ($rs as $job) {
            if (!$job->isQueued() && !$job->isStarted() && !$job->isCompleted() && $this->hasDependencies($job)) {
                $this->lastError = sprintf('Queueing job %s (priority %s, schedule %s).',
                    $job->getName(),
                    $job->getPriority(),
                    $job->getSchedule()
                );

                $this->run($job);
                $this->releaseLock();
                return;
            }
        }

        $this->lastError = 'Nothing to do.';
    }

    /**
     * Stops the execution of a job.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function terminate(JobEntity $job)
    {
        if (!$job->isStarted()) {
            throw new RuntimeException('Cannot stop job as it\'s not running.');
        }

        $worker = $this->findTerminationWorker();
        $worker->run($job);

        $this->save($job);

        return $this;
    }
}
