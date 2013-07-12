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
 * Worker class for the termination of jobs
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Terminate implements JobInterface
{
    /**
     * Stops the execution of a job, either immediately or as a background
     * process.
     *
     * @param   Furnace\Entity\Job
     * @return  void
     */
    public function run(JobEntity $job)
    {
        if (!$pid = $job->getPidOf()) {
            throw new RuntimeException('Cannot stop job, no pid available.');
        }

        if (file_exists(sprintf('/proc/%d/status', $pid))) {
            $pids = implode(' ', $this->getPidTree($pid));
            
            exec("kill $pids", $output, $retval);

            if ($retval != 0) {
                exec("/bin/sudo kill $pids", $output, $retval);

                if ($retval != 0) {
                    throw new RuntimeException(sprintf(
                        'Cannot terminate pid %d (exit code %d) possibly permission denied '
                        . ' for user \'%s\' on \'%s\'.',
                        $pid,
                        $retval,
                        get_current_user(),
                        gethostname()
                    ));
                }
            }
        }

        $job->clear(array(
            'startedAt',
            'completedAt',
            'queuedAt',
            'error',
            'numErrors',
            'percentComplete',
            'pidCmd',
            'pidOf',
        ));
    }

    /**
     * Gets an array of all process ids associated (via child pids) with
     * a given pid.
     *
     * @param   integer                 Process ID (pid)
     * @return  array
     */
    protected function getPidTree($pid)
    {
        $pids = array($pid);

        exec("/bin/ps h --ppid $pid -o pid", $output, $retval);

        if ($retval != 0 || empty($output[0])) {
            return $pids;
        }

        foreach ($output as $line) {
            if ($child = intval(trim($line))) {
                foreach ($this->getPidTree($child) as $subChild) {
                    $pids[] = $subChild;
                }
            }
        }

        return $pids;
    }
}
