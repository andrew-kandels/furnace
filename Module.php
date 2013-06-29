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

namespace Furnace;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\MvcEvent;

/**
 * ZF2 Module Bootstrap
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Module implements AutoloaderProviderInterface
{
    /**
     * @var Zend\ServiceManager
     */
    protected $serviceManager;

    /**
     * Get Configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Gets the configuration for autloading Furnace classes.
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * ZF2 Bootstrap Handling
     *
     * @param   Zend\Mvc\MvcEvent
     * @return  void
     */
    public function onBootstrap(MvcEvent $e)
    {
        $app = $e->getParam('application');
        $this->serviceManager = $app->getServiceManager();
        $app->getEventManager()->attach('dispatch', array($this, 'onDispatch'), -100);
    }

    /**
     * Called when dispatching a controller this module handles.
     *
     * @param   Zend\Mvc\MvcEvent
     * @return  void
     */
    public function onDispatch(MvcEvent $e)
    {
        $matches    = $e->getRouteMatch();
        $controller = $matches->getParam('controller');

        if (strpos($controller, __NAMESPACE__) === 0) {
            $config = $this->serviceManager->get('config');

            $viewModel = $this->serviceManager
                ->get('ViewManager')
                ->getViewModel();

            $viewModel->bootstrapCss = isset($config['furnace']['assets']['css'])
                ? $config['furnace']['assets']['css']
                : false;

            $viewModel->bootstrapJs = isset($config['furnace']['assets']['js'])
                ? $config['furnace']['assets']['js']
                : false;
        }
    }
}
