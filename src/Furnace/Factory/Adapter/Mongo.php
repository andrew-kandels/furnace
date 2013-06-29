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

namespace Furnace\Factory\Adapter;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use MongoClient;
use RuntimeException;

/**
 * Factory class for instantiating a connection to MongoDB
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Mongo implements FactoryInterface
{
    /**
     * Creates a Contain connection class for MongoDB.
     *
     * @param   Zend\ServiceManager\ServiceLocatorInterface
     * @return  SupportPlanner\Model\Planner|null
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = $services->get('config');
        $config = $config['furnace']['database']['parameters'];

        if (!extension_loaded('mongo')) {
            throw new RuntimeException('Mongo extension is not loaded, required by the '
                . 'Furnace Mongo adapter.'
            );
        }

        if (empty($config['name'])) {
            throw new InvalidArgumentException('Mongo configuration requires a name parameter.');
        }

        if (empty($config['host'])) {
            $config['host'] = 'localhost';
        }

        $dsn = sprintf('mongodb://%s%s/%s',
            isset($config['username']) && isset($config['password'])
                ? sprintf('%s:%s@', $config['username'], $config['password'])
                : '',
            $config['host'],
            $config['name']
        );

        $options = array(
            'connect' => false, // open connection only when necessary
        );

        if (isset($config['w'])) {
            $options['w'] = $config['w'];
        }

        return new MongoClient($dsn, $options);
    }
}
