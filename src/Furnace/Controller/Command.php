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

namespace Furnace\Controller;

use Furnace\Service\Job as JobService;
use Furnace\Entity\Job as JobEntity;
use Zend\Mvc\Controller\AbstractActionController;
use Closure;

/**
 * Furnace command controller.
 *
 * @category   Site
 * @package    Site
 * @subpackage Site\Controller
 */
class Command extends AbstractActionController
{
    /**
     * @var Furnace\Service\Job
     */
    protected $service;

    /**
     * Constructor
     *
     * @param   Furnace\Service\Job
     * @return  void
     */
    public function __construct(JobService $service)
    {
        $this->service = $service;
    }

    /**
     * Gets the job from the route parameter.
     *
     * @return Furnace\Entity\Job|false
     */
    protected function getJobFromRoute()
    {
        if (!$param = $this->params()->fromRoute('param')) {
            $this->flashMessenger()->addErrorMessage('No job specified');
            return false;
        }

        if (!$job = $this->service->findByName($param)) {
            $this->flashMessenger()->addErrorMessage('Job with name \'' . $param . '\' does not exist');
            return false;
        }

        return $job;
    }

    /**
     * Gets the job from the route parameter.
     *
     * @return Furnace\Entity\Job[]|false
     */
    protected function getJobsFromRoute()
    {
        $params = explode(',', $this->params()->fromRoute('param'));

        if (!$params || !$jobs = $this->service->validateNames('', $params)) {
            $this->flashMessenger()->addErrorMessage('You must check one or more checkboxes to perform that '
                . 'mass action.'
            );
            return false;
        }

        foreach ($jobs as $index => $job) {
            $jobs[$index] = $this->service->findByName($job);
        }

        return $jobs;
    }

    /**
     * Performs a service level call, wrapped in exception trapping for 
     * common workflow problems. It's better to display these in clean flash 
     * messages rather than unhandled exceptions.
     *
     * @param   Closure                             Service Call
     * @param   Furnace\Entity\Job|null
     * @return  Redirect|true
     */
    protected function makeServiceCall(Closure $func, JobEntity $job = null)
    {
        try {
            $func();
            return true;

        } catch (\RuntimeException $e) {
            $exception = $e;

        } catch (\InvalidArgumentException $e) {
            $exception = $e;
        }

        $this->flashMessenger()->addErrorMessage($exception->getMessage());

        if ($job) {
            return $this->redirect()->toRoute('furnace-crud', array(
                'action' => 'view',
                'param'  => $job->getName(),
            ));
        }

        return $this->redirect()->toRoute('furnace-crud');
    }

    /**
     * Runs a job.
     *
     * @return  Redirect
     */
    public function runAction()
    {
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        if (!$this->service->hasDependencies($job)) {
            $this->flashMessenger()->addErrorMessage($this->service->getLastError());

            return $this->redirect()->toRoute('furnace-crud', array(
                'action' => 'view',
                'param'  => $job->getName(),
            ));
        }

        $service  = $this->service;
        $response = $this->makeServiceCall(function() use ($service, $job) {
            $service->run($job);
        }, $job);

        if ($response !== true) {
            return $response;
        }

        $this->flashMessenger()->addSuccessMessage('The job has been started.');

        return $this->redirect()->toRoute('furnace-crud', array(
            'action' => 'view',
            'param'  => $job->getName(),
        ));
    }

    /**
     * Resets a job.
     *
     * @return  array
     */
    public function resetAction()
    {
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $job->clear(array(
            'startedAt',
            'completedAt',
            'queuedAt',
            'error',
            'history',
            'messages',
            'logs',
            'percentComplete',
            'pidCmd',
            'pidOf',
        ));

        $this->service->save($job);

        $this->flashMessenger()->addSuccessMessage('The meta-data for this job has been reset.');

        return $this->redirect()->toRoute('furnace-crud', array(
            'action' => 'view',
            'param' => $job->getName(),
        ));
    }

    /**
     * Deletes a job.
     *
     * @return  array
     */
    public function deleteAction()
    {
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $service  = $this->service;
        $response = $this->makeServiceCall(function() use ($service, $job) {
            $service->delete($job);
        }, $job);

        if ($response !== true) {
            return $response;
        }

        $this->flashMessenger()->addSuccessMessage('The job has been deleted.');

        return $this->redirect()->toRoute('furnace-crud');
    }

    /**
     * Marks a job as completed.
     *
     * @return  array
     */
    public function markCompletedAction()
    {
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $service  = $this->service;
        $response = $this->makeServiceCall(function() use ($service, $job) {
            if ($job->isQueued()) {
                $job->clear('queuedAt');
            }

            $service
                ->queue($job)
                ->start($job)
                ->complete($job);
        }, $job);

        if ($response !== true) {
            return $response;
        }

        $this->flashMessenger()->addSuccessMessage('The job has been marked as completed.');

        return $this->redirect()->toRoute('furnace-crud', array(
            'action' => 'view',
            'param'  => $job->getName(),
        ));
    }

    /**
     * Marks a job as incomplete.
     *
     * @return  array
     */
    public function markIncompleteAction()
    {
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $service  = $this->service;
        $response = $this->makeServiceCall(function() use ($service, $job) {
            $service->incomplete($job);
        }, $job);

        if ($response !== true) {
            return $response;
        }

        $this->flashMessenger()->addSuccessMessage('The job has been marked as incomplete.');

        return $this->redirect()->toRoute('furnace-crud', array(
            'action' => 'view',
            'param'  => $job->getName(),
        ));
    }

    /**
     * Runs many jobs.
     *
     * @return  Redirect
     */
    public function runManyAction()
    {
        if (!$jobs = $this->getJobsFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $failed  = $success = 0;
        $service = $this->service;
        foreach ($jobs as $job) {
            if (!$this->service->hasDependencies($job)) {
                $failed++;
                continue;
            }

            $response = $this->makeServiceCall(function() use ($service, $job) {
                $service->run($job);
            }, $job);

            if ($response !== true) {
                $failed++;
            } else {
                $success++;
            }
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Completed your mass action with %s success%s '
            . 'and %s failure%s.',
            number_format($success, 0),
            $success != 1 ? 'es' : '',
            number_format($failed, 0),
            $failed != 1 ? 's' : ''
        ));

        return $this->redirect()->toRoute('furnace-crud');
    }

    /**
     * Resets one or more jobs.
     *
     * @return  array
     */
    public function resetManyAction()
    {
        if (!$jobs = $this->getJobsFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        foreach ($jobs as $job) {
            $job->clear(array(
                'startedAt',
                'completedAt',
                'queuedAt',
                'error',
                'history',
                'messages',
                'logs',
                'percentComplete',
                'pidCmd',
                'pidOf',
            ));

            $this->service->save($job);
        }

        $this->flashMessenger()->addSuccessMessage('The jobs you selected have been reset.');

        return $this->redirect()->toRoute('furnace-crud');
    }

    /**
     * Deletes one or more jobs.
     *
     * @return  array
     */
    public function deleteManyAction()
    {
        if (!$jobs = $this->getJobsFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $failed  = $success = 0;
        $service = $this->service;
        foreach ($jobs as $job) {
            $response = $this->makeServiceCall(function() use ($service, $job) {
                $service->delete($job);
            }, $job);

            if ($response !== true) {
                $failed++;
            } else {
                $success++;
            }
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Completed your mass action with %s success%s '
            . 'and %s failure%s.',
            number_format($success, 0),
            $success != 1 ? 'es' : '',
            number_format($failed, 0),
            $failed != 1 ? 's' : ''
        ));

        return $this->redirect()->toRoute('furnace-crud');
    }

    /**
     * Marks one or more jobs as completed.
     *
     * @return  array
     */
    public function markCompletedManyAction()
    {
        if (!$jobs = $this->getJobsFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $failed  = $success = 0;
        $service = $this->service;
        foreach ($jobs as $job) {
            $response = $this->makeServiceCall(function() use ($service, $job) {
                $service
                    ->queue($job)
                    ->start($job)
                    ->complete($job);
            }, $job);

            if ($response !== true) {
                $failed++;
            } else {
                $success++;
            }
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Completed your mass action with %s success%s '
            . 'and %s failure%s.',
            number_format($success, 0),
            $success != 1 ? 'es' : '',
            number_format($failed, 0),
            $failed != 1 ? 's' : ''
        ));

        return $this->redirect()->toRoute('furnace-crud');
    }

    /**
     * Marks one or more jobs as incomplete.
     *
     * @return  array
     */
    public function markIncompleteManyAction()
    {
        if (!$jobs = $this->getJobsFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $failed  = $success = 0;
        $service = $this->service;
        foreach ($jobs as $job) {
            $response = $this->makeServiceCall(function() use ($service, $job) {
                $service->incomplete($job);
            }, $job);

            if ($response !== true) {
                $failed++;
            } else {
                $success++;
            }
        }

        $this->flashMessenger()->addSuccessMessage(sprintf('Completed your mass action with %s success%s '
            . 'and %s failure%s.',
            number_format($success, 0),
            $success != 1 ? 'es' : '',
            number_format($failed, 0),
            $failed != 1 ? 's' : ''
        ));

        return $this->redirect()->toRoute('furnace-crud');
    }
}
