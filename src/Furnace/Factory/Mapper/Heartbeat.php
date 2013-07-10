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

namespace Furnace\Factory\Mapper;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ContainMapper\Driver\MongoDB\Driver;
use ContainMapper\Mapper;
use ContainMapper\Driver\MongoDB\Connection;
use Furnace\Service\Job as JobService;
use RuntimeException;

/**
 * Factory class for the job service's heartbeat mapper.
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Heartbeat implements FactoryInterface
{
    /**
     * Create the service (factory)
     *
     * @param   Zend\ServiceManager\ServiceLocatorInterface
     * @return  Service|null
     */
    public function createService(ServiceLocatorInterface $sm)
    {
        $config =  $sm->get('config');
        $config =  $config['furnace']['database'];

        $camelCase = implode('', array_map(function($a) {
            return ucfirst($a);
        }, explode('-', $config['adapter'])));

        if (!$dbh = $sm->get('Furnace' . $camelCase . 'Adapter')) {
            throw new RuntimeException('Adapter \'' . $adapter . '\' is not currently '
                . 'supported.'
            );
        }

        $connection = new Connection($dbh, $config['parameters']['name'], 'heartbeat');
        $driver     = new Driver($connection);
        $mapper     = new Mapper('Furnace\Entity\Heartbeat', $driver);

        return $mapper;
    }
}
