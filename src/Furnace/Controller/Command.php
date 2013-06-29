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
     * Runs a job.
     *
     * @return  Redirect
     */
    public function runAction()
    {
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $this->service->run($job);

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

        $this->service->delete($job);

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

        $this->service
            ->queue($job)
            ->start($job)
            ->complete($job);

        $this->flashMessenger()->addSuccessMessage('The job has been marked as completed.');

        return $this->redirect()->toRoute('furnace-crud', array(
            'action' => 'view',
            'param'  => $job->getName(),
        ));
    }

}
