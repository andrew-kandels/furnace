<?php
return array(
    'furnace' => array(
        'assets' => array(
            // Web Path to Application or Twitter Bootstrap CSS File
            'css' => '/assets/css/network.css',
        ),
        'database' => array(
            'adapter' => 'Mongo',
            'parameters' => array(
                'host' => 'localhost',
                'name' => 'CB',
            ),
        ),

        'jobs' => array(
            'class_template' => 'Furnace%sJob',
            'default' => 'Main',
        ),
    ),
    'service_manager' => array(
        'invokables' => array(
            'FurnaceMainJob' => 'Furnace\Jobs\Main',
        ),
        'factories' => array(
            'FurnaceJobService' => 'Furnace\Factory\Service\Job',
            'FurnaceJobMapper' => 'Furnace\Factory\Mapper\Job',
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
        ),
    ),
    'controllers' => array(
        'factories' => array(
            'FurnaceJobController' => 'Furnace\Factory\Controller\Job',
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
    'router' => array(
        'routes' => array(
            'furnace-crud' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/furnace[/:action][/:param]',
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
                'type' => 'segment',
                'options' => array(
                    'route'    => '/furnace/js[/:file]',
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
