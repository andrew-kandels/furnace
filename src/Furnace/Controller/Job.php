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
use Zend\View\Model\ViewModel;
use Zend\EventManager\EventManagerInterface;

/**
 * Furnace job controller.
 *
 * @category   Site
 * @package    Site
 * @subpackage Site\Controller
 */
class Job extends AbstractActionController
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
     * Called before routing actions.
     *
     * @param   Zend\EventManager\EventManagerInterface
     * @return  void
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);

        $controller = $this;
        $events->attach('dispatch', function ($e) use ($controller) {
            $controller->layout('furnace/layout');
        }, 100);
    }

    /**
     * Displays the jobs in a pagination list.
     *
     * @return  Zend\View\Model\ViewModel
     */
    public function listAction()
    {
        switch ($schedule = $this->params()->fromRoute('param')) {
            case 'daily':
            case 'weekly':
            case 'monthly':
            case 'once':
                break;

            default:
                $schedule = 'all';
                break;
        }

        $where = array();
        if ($schedule != 'all') {
            $where = array(
                'schedule' => $schedule,
            );
        }

        $jobs = $this->service
            ->sort(array('priority' => 1))
            ->find($where);

        return new ViewModel(array(
            'jobs' => $jobs,
            'schedule' => $schedule,
        ));
    }

    /**
     * Interface for creating a new job.
     *
     * @return  Zend\View\Model\ViewModel
     */
    public function createAction()
    {
        $form = $this->getServiceLocator()->get('FurnaceJobForm');

        if ($this->getRequest()->isPost()) {
            if ($return = $this->onCreate($form)) {
                return $return;
            }
        } else {
            $form->setData(array(
                'startAt' => date('Y-m-d'),
            ));
        }

        return array(
            'form' => $form,
        );
    }

    /**
     * Post to the createAction()
     *
     * @param   Zend\Form\AbstractForm
     * @return  mixed
     */
    protected function onCreate($form)
    {
        $form->setData($this->getRequest()->getPost());

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData(); // filtered, clean data

        $job = new JobEntity($data);
        $this->setDependencies($job);

        if ($this->service->findByName($job->getName())) {
            $this->flashMessenger()->addErrorMessage('Name already in use by another job.');
            $this->flashMessenger()->getContainer()->setExpirationHops(0, null, true);
            return false;
        }

        try {
            $this->service->save($job);
        } catch (\MongoException $e) {
            if (false === strpos($e->getMessage(), 'duplicate key error')) {
                throw $e;
            }

            $this->flashMessenger()->addErrorMessage('Name already in use by another job.');
            $this->flashMessenger()->getContainer()->setExpirationHops(0, null, true);
            return false;
        }

        $this->flashMessenger()->addSuccessMessage('Job created successfully.');

        return $this->redirect()->toRoute('furnace-crud', array(
            'action' => 'view',
            'param'  => $job->getName(),
        ));
    }

    /**
     * Views a single job.
     *
     * @return  array
     */
    public function viewAction()
    {
        if (!$param = $this->params()->fromRoute('param')) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        if (!$job = $this->service->findByName($param)) {
            $this->flashMessenger()->addErrorMessage('The job you were trying to view no longer exists.');
            return $this->redirect()->toRoute('furnace-crud');
        }

        $dependencies = array();
        if ($arr = $job->getDependencies()) {
            foreach ($arr as $dependency) {
                if ($subJob = $this->service->findByName($dependency)) {
                    $dependencies[] = $subJob;
                }
            }
        }

        return array(
            'job' => $job,
            'elapsed' => $job->getLastRunningTime(),
            'dependencies' => $dependencies,
        );
    }

    /**
     * Edits a single job.
     *
     * @return  array
     */
    public function editAction()
    {
        if (!$param = $this->params()->fromRoute('param')) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        if (!$job = $this->service->findByName($param)) {
            $this->flashMessenger()->addErrorMessage('The job you were trying to view no longer exists.');
            return $this->redirect()->toRoute('furnace-crud');
        }

        $form = $this->getServiceLocator()->get('FurnaceJobForm');
        $form->get('name')->setAttribute('readonly', true);

        if ($this->getRequest()->isPost()) {
            if ($return = $this->onEdit($job, $form)) {
                return $return;
            }
        } else {
            $form->setData(array(
                'startAt' => $job->getStartAt()->format('Y-m-d'),
            ) + $job->export());
        }

        return array(
            'form' => $form,
            'job'  => $job,
        );
    }

    /**
     * Post to the editAction()
     *
     * @param   Furnace\Entity\Job
     * @param   Zend\Form\AbstractForm
     * @return  mixed
     */
    protected function onEdit(JobEntity $job, $form)
    {
        $form->setData($this->getRequest()->getPost());

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData(); // filtered, clean data

        $job->fromArray(array('name' => $job->getName()) + $data);
        $this->setDependencies($job);

        $this->service->save($job);

        $this->flashMessenger()->addSuccessMessage('Changes to job have been saved successfully.');

        return $this->redirect()->toRoute('furnace-crud', array(
            'action' => 'view',
            'param'  => $job->getName(),
        ));
    }

    /**
     * Gets a list of dependencies from the post data to save to the 
     * job entity.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    protected function setDependencies(JobEntity $job)
    {
        if (!$dependencies = explode(',', $this->params()->fromPost('dependencies'))) {
            return array();
        }

        if ($arr = $this->service->validateNames($job->getName(), $dependencies)) {
            $job->setDependencies($arr);
        } else {
            $job->clear('dependencies');
        }

        return $this;
    }
}
