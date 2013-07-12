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
use Zend\View\Model\JsonModel;
use Zend\EventManager\EventManagerInterface;
use Zend\Session\Container as SessionContainer;

/**
 * Furnace ajax controller.
 *
 * @category   Site
 * @package    Site
 * @subpackage Site\Controller
 */
class Ajax extends AbstractActionController
{
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

        $service = $this->getServiceLocator()
            ->get('FurnaceJobService');

        if (!$job = $service->findByName($param)) {
            return $this->getResponse()
                ->setStatusCode(400)
                ->setContent('Job no longer exists, was missing or not specified correctly');
        }

        return $job;
    }

    /**
     * Gets a job's status messages.
     *
     * @return  string
     */
    public function getStatusAction()
    {
        if (!($job = $this->getJobFromRoute()) instanceof JobEntity) {
            return $job;
        }

        $viewModel = new ViewModel(array(
            'job' => $job,
        ));
        $viewModel->setTerminal(true);

        return $viewModel;
    }

    /**
     * Gets a job's history entry's stats.
     *
     * @return  string
     */
    public function getUsageStatsAction()
    {
        if (!($job = $this->getJobFromRoute()) instanceof JobEntity) {
            return $job;
        }

        $index   = abs($this->params()->fromRoute('param2') - 1);
        $history = $job->atHistory($index);

        $viewModel = new ViewModel(array(
            'history' => $history,
        ));
        $viewModel->setTerminal(true);

        return $viewModel;
    }

    /**
     * Changes the session's refresh value.
     *
     * @return  array
     */
    public function setRefreshAction()
    {
        $checked = $this->params()->fromPost('checked') == 'yes';
        $session = new SessionContainer('jobrefresh');

        $session->checked = $checked ? 'yes' : 'no';

        return $this->getResponse()->setStatusCode(200)->setContent('');
    }

    /**
     * Refreshes a log when viewing a job.
     *
     * @return  array
     */
    public function getLogAction()
    {
        if (!($job = $this->getJobFromRoute()) instanceof JobEntity) {
            return $job;
        }

        if (!$log = $this->params()->fromRoute('param2')) {
            return $this->getResponse()
                ->setStatusCode(400)
                ->setContent('Log not specified');
        }

        $log = base64_decode($log);

        if (!in_array($log, $job->getLogs() ?: array())) {
            return $this->getResponse()
                ->setStatusCode(400)
                ->setContent('Log specified is not valid for the job');
        }

        $config = $this->getServiceLocator()->get('config');
        $maxSize = $config['furnace']['log']['maxBytes'];

        if (!file_exists($log)) {
            $content = '** File no longer exists **';
        } elseif (($bytes = filesize($log)) > $maxSize) {
            $fp = fopen($log, 'rt');
            fseek($fp, $bytes - $maxSize);
            fgets($fp, 1024); // fix broken line
            $content = '** Tailing File (exceeds ' . $maxSize . ' bytes) **' . PHP_EOL . fread($fp, $maxSize);
            fclose($fp);
        } else {
            $content = file_get_contents($log, false);
        }

        $statusCode = 200;
        if ($job->isCompleted()) {
            $statusCode = 205; // reset content
        }

        $response = $this->getResponse()
            ->setStatusCode($statusCode)
            ->setContent($content);
  
        $response->getHeaders()
            ->addHeaderLine('Content-Type', 'text/plain; charset=UTF-8')
            ->addHeaderLine('Cache-Control', 'private, max-age=0')
            ->addHeaderLine('Expires', '-1');

        return $response;
    }

    /**
     * The client UI is polling for job changes to check if a reload is required.
     *
     * @return  Response
     */
    public function pollChangesAction()
    {
        if (!($job = $this->getJobFromRoute()) instanceof JobEntity) {
            return $job;
        }

        if (!is_array($uiJob = $this->params()->fromPost('job'))) {
            return $this->getResponse()
                ->setStatusCode(400)
                ->setContent('Displayed job data not posted or invalid');
        }

        $uiJob = new JobEntity($uiJob);

        $compareFields = array(
            'queuedAt',
            'startedAt',
            'completedAt',
            'error',
            'logs',
            'pidOf',
            'dependencies',
            'priority',
            'schedule',
        );

        if ($job->export($compareFields) != $uiJob->export($compareFields)) {
            return $this->getResponse()
                ->setStatusCode(205)
                ->setContent('Changes present');
        }

        return $this->getResponse()
            ->setStatusCode(200)
            ->setContent('No changes');
    }

    /**
     * Sets notes for a history item.
     *
     * @return  Response
     */
    public function setHistoryNotesAction()
    {
        if (!($job = $this->getJobFromRoute()) instanceof JobEntity) {
            return $job;
        }

        $history = $job->getHistory();
        $index   = (int) $this->params()->fromRoute('param2');
        $notes   = $this->params()->fromPost('notes');

        foreach ($job->getHistory() as $ind => $item) {
            if ($index !== $ind) {
                continue;
            }

            $item->setNotes($notes);

            if (!$item->isValid()) {
                return $this->getResponse()
                    ->setStatusCode(400)
                    ->setContent('Invalid notes specified: ' . $item->messages());
            }

            break;
        }

        $this->getServiceLocator()
            ->get('FurnaceJobService')
            ->save($job);

        return new JsonModel(array(
            'notes' => $item->getNotes(),
        ));
    }

    /**
     * Deletes a history item.
     *
     * @return  Response
     */
    public function deleteHistoryAction()
    {
        if (!($job = $this->getJobFromRoute()) instanceof JobEntity) {
            return $job;
        }

        $history = $job->getHistory()->toArray();
        $index   = (int) $this->params()->fromRoute('param2');

        if (!isset($history[$index])) {
            return $this->getResponse()
                ->setStatusCode(400)
                ->setContent('History index specified out of bounds');
        }

        unset($history[$index]);
        $job->setHistory(array_values($history));

        $this->getServiceLocator()
            ->get('FurnaceJobService')
            ->save($job);

        return $this->getResponse()
            ->setStatusCode(200)
            ->setContent('Success');
    }
}
