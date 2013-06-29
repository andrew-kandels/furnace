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
            $this->flashMessenger()->addErrorMessage('Job with name \'' . $param . '\' does not exist');
            return false;
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
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
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
        if (!$job = $this->getJobFromRoute()) {
            return $this->redirect()->toRoute('furnace-crud');
        }

        $index   = abs($this->params()->fromRoute('param2') - 1);
        $history = $job->atHistory($index);

        $viewModel = new ViewModel(array(
            'history' => $history,
        ));
        $viewModel->setTerminal(true);

        return $viewModel;
    }
}
