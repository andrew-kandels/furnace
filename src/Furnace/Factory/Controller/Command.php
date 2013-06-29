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

namespace Furnace\Factory\Controller;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Furnace\Controller\Command as CommandController;

/**
 * Factory for the command controller.
 *
 * @category   CaringNetwork
 * @package    CaringNetwork
 * @subpackage CaringNetwork\Controller
 */
class Command implements FactoryInterface
{
    /**
     * Creates the job controller.
     *
     * @param   Zend\ServiceManager\ServiceLocatorInterface
     * @return  Furnace\Controller\Job
     */
    public function createService(ServiceLocatorInterface $sm)
    {
        return new CommandController(
            $sm->getServiceLocator()->get('FurnaceJobService')
        );
    }
}
