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

namespace Furnace\Jobs;

use Furnace\Entity\Job as JobEntity;
use RuntimeException;

/**
 * Default Job
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Main implements JobInterface
{
    /**
     * Starts the execution of a job, either immediately or as a background
     * process.
     *
     * @param   Furnace\Entity\Job
     * @return  void
     */
    public function run(JobEntity $job)
    {
        throw new RuntimeException(
            'Furnace is using the default job interface which doesn\'t do anything. You '
                . 'should configure your worker interface in your application\'s configuration '
                . 'and re-run the job.'
        );
    }
}
