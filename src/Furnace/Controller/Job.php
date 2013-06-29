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
        $jobs = $this->service->find();

        return new ViewModel(array(
            'jobs' => $jobs,
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

        if ($this->getRequest()->isPost() && $return = $this->onCreate($form)) {
            return $return;
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

        return $this->redirect()->toRoute('furnace-crud');
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

        $history = $job->getHistory() ?: array();
        $elapsed = false;

        foreach ($history as $item) {
            $started   = $item->getStartedAt();
            $completed = $item->getCompletedAt();

            if ($started && $completed) {
                $elapsed = $completed->getTimestamp() - $started->getTimestamp();
            }
        }

        return array(
            'job' => $job,
            'elapsed' => $elapsed,
        );
    }

    /**
     * Exports the CSS for the jobs module (since we don't know how the application
     * is configured to serve per-module files.
     *
     * @return  Response
     */
    public function cssAction()
    {
        $response = $this->getResponse();
        $response->setContent(file_get_contents(__DIR__ . '/../../../public/css/style.css'))
            ->setStatusCode(200);

        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'text/css; charset=UTF-8')
            ->addHeaderLine('Cache-Control', 'private, max-age=0')
            ->addHeaderLine('Expires', '-1');

        return $response;
    }
}
