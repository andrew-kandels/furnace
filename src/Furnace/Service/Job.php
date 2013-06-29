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
    protected $config;

    /**
     * @var Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Constructor
     *
     * @param   ContainMapper\Mapper
     * @return  void
     */
    public function __construct(Mapper $mapper, array $config, ServiceLocatorInterface $sm)
    {
        $this->mapper = $mapper;
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
     * Update a job to reflect it has been started.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function start(JobEntity $job)
    {
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
     * Update a job to reflect it failed.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function fail(JobEntity $job)
    {
        $job->fail();
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
     * Finds and instantiates a worker class as configured in this application's
     * class_template based on the name of the job first, using a backup/general
     * name if none is defined. This is the only instance that uses the service
     * locator.
     *
     * @param   Furnace\Entity\Job
     * @return  Furnace\Jobs\JobInterface
     */
    public function findWorker(JobEntity $job)
    {
        $name = implode('', array_map(function($a) {
            return ucfirst($a);
        }, explode('-', $job->getName())));

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
}
