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

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;
use Furnace\Service\Job as JobService;

/**
 * Command Line Interface (CLI) Controller
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Cli extends AbstractActionController
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
     * Executes furnace's heartbeat.
     *
     * @return  string
     */
    public function heartbeatAction()
    {
        if (!$this->getRequest() instanceof ConsoleRequest) {
            throw new \RuntimeException('You can only use this action from a console!');
        }

        $startedAt = time();

        $this->service->heartbeat();

        return sprintf('All done, took %d seconds.%s', time() - $startedAt, PHP_EOL);
    }
}
