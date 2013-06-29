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
    ),
    'service_manager' => array(
        'factories' => array(
            'FurnaceJobService' => 'Furnace\Factory\Service\Job',
            'FurnaceJobMapper' => 'Furnace\Factory\Mapper\Job',
            'FurnaceJobController' => 'Furnace\Factory\Controller\Job',

            // Mapper Adapters
            'FurnaceMongoAdapter' => 'Furnace\Factory\Adapter\Mongo',
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
            'furnace' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/job[/:action][/:param]',
                    'defaults' => array(
                        'controller' => 'FurnaceJobController',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
);
