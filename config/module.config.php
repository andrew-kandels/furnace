<?php
return array(
    'furnace' => array(
        'database' => array(
            'adapter' => 'Mongo',
            'parameters' => array(
                'host' => 'localhost',
                'name' => 'CB',
            ),
        ),

        'log' => array(
            'maxBytes' => 1024 * 1024, // 1 KB
        ),

        'jobs' => array(
            'class_template' => 'Furnace%sJob',
            'class_terminate' => 'FurnaceTerminateJob',
            'default' => 'Main',
        ),

        'maxErrors' => 3,
    ),
    'service_manager' => array(
        'invokables' => array(
            'FurnaceMainJob' => 'Furnace\Jobs\Main',
            'FurnaceTerminateJob' => 'Furnace\Jobs\Terminate',
        ),
        'factories' => array(
            'FurnaceJobService' => 'Furnace\Factory\Service\Job',
            'FurnaceJobMapper' => 'Furnace\Factory\Mapper\Job',
            'FurnaceHeartbeatMapper' => 'Furnace\Factory\Mapper\Heartbeat',
            'FurnaceJobForm' => 'Furnace\Factory\Form\Job',

            // Mapper Adapters
            'FurnaceMongoAdapter' => 'Furnace\Factory\Adapter\Mongo',
        ),
    ),
    'view_helpers' => array(
        'factories' => array(
            'furnaceStatus' => 'Furnace\Factory\View\Helper\Status',
            'furnaceFrequency' => 'Furnace\Factory\View\Helper\Frequency',
            'furnaceFormat' => 'Furnace\Factory\View\Helper\Format',
            'furnaceRefresh' => 'Furnace\Factory\View\Helper\Refresh',
            'furnaceDependency' => 'Furnace\Factory\View\Helper\Dependency',
            'furnaceJobList' => 'Furnace\Factory\View\Helper\JobList',
            'furnaceLog' => 'Furnace\Factory\View\Helper\Log',
        ),
    ),
    'controllers' => array(
        'factories' => array(
            'FurnaceJobController' => 'Furnace\Factory\Controller\Job',
            'FurnaceCliController' => 'Furnace\Factory\Controller\Cli',
            'FurnaceCommandController' => 'Furnace\Factory\Controller\Command',
        ),
        'invokables' => array(
            'FurnaceAssetController' => 'Furnace\Controller\Asset',
            'FurnaceAjaxController' => 'Furnace\Controller\Ajax',
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
            'furnace/layout'             => __DIR__ . '/../view/layout/layout.phtml',
        ),
        'template_path_stack'            => array(__DIR__ . '/../view'),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'furnace-heartbeat' => array(
                    'options' => array(
                        'route' => 'furnace',
                        'defaults' => array(
                            'controller' => 'FurnaceCliController',
                            'action' => 'heartbeat',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'router' => array(
        'routes' => array(
            'furnace-crud' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/furnace[/:action[/:param]]',
                    'defaults' => array(
                        'controller' => 'FurnaceJobController',
                        'action'     => 'list',
                    ),
                ),
            ),
            'furnace-cmd' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/furnace/cmd/:action[/:param]',
                    'defaults' => array(
                        'controller' => 'FurnaceCommandController',
                    ),
                ),
            ),
            'furnace-css' => array(
                'type' => 'literal',
                'options' => array(
                    'route'    => '/furnace/css',
                    'defaults' => array(
                        'controller' => 'FurnaceAssetController',
                        'action'     => 'css',
                    ),
                ),
            ),
            'furnace-js' => array(
                'type' => 'literal',
                'options' => array(
                    'route'    => '/furnace/js',
                    'defaults' => array(
                        'controller' => 'FurnaceAssetController',
                        'action'     => 'js',
                    ),
                ),
            ),
            'furnace-ajax' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/furnace/ajax/:action[/:param][/:param2]',
                    'defaults' => array(
                        'controller' => 'FurnaceAjaxController',
                    ),
                ),
            ),
        ),
    ),
);
