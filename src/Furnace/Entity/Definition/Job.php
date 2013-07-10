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

namespace Furnace\Entity\Definition;

use Contain\Entity\Definition\AbstractDefinition;

/**
 * Contain definition class describing a Furnace job.
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Job extends AbstractDefinition
{
    /**
     * Configure the entity properties.
     *
     * @return  void
     */
    public function setUp()
    {
        $this->registerMethod('isStarted')
             ->registerMethod('isQueued')
             ->registerMethod('isCompleted')
             ->registerMethod('getTimestampFromArgument')
             ->registerMethod('getStats')
             ->registerMethod('queue')
             ->registerMethod('start')
             ->registerMethod('progress')
             ->registerMethod('complete')
             ->registerMethod('incomplete')
             ->registerMethod('fail')
             ->registerMethod('schedule')
             ->registerTarget(AbstractDefinition::ENTITY, __DIR__ . '/..')
             ->registerTarget(AbstractDefinition::FILTER, __DIR__ . '/../Filter')
             ->registerTarget(AbstractDefinition::FORM, __DIR__ . '/../Form');

        $this->setProperty('name', 'string', array(
            'required' => true,
            'primary' => true,
            'filters' => array(
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array('name' => 'Regex', 'options' => array(
                    'pattern' => '/^[a-zA-Z][a-zA-Z0-9-_]*$/',
                    'messages' => array(
                        'regexNotMatch' => 'Job names should start with a letter and contain '
                            . 'only letters, numbers, underscores and dashes.',
                    ),
                )),
                array('name' => 'StringLength', array(
                    'min' => 0,
                    'max' => 60,
                )),
            ),
            'attributes' => array(
                'required' => true,
                'class' => 'input-large',
            ),
            'options' => array(
                'label' => 'Name',
                'help-block' => 'Unique name used to identify the job.',
            ),
        ));

        $this->setProperty('description', 'string', array(
            'filters' => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array('name' => 'StringLength', array(
                    'min' => 0,
                    'max' => 140,
                )),
            ),
            'type' => 'textarea',
            'attributes' => array(
                'rows' => 2,
                'class' => 'input-xlarge',
            ),
            'options' => array(
                'label' => 'Description',
                'help-block' => 'Longer description describing what the job does.',
            ),
        ));

        $options = array();
        for ($i = 1; $i <= 100; $i++) {
            $options[$i] = number_format($i, 0);
        }

        $this->setProperty('priority', 'integer', array(
            'required' => true,
            'defaultValue' => 10,
            'type' => 'select',
            'filters' => array(
                array('name' => 'Digits'),
            ),
            'validators' => array(
                array('name' => 'Digits'),
            ),
            'options' => array(
                'label' => 'Priority',
                'help-block' => 'Job priority in relation to other jobs, jobs with higher priorities '
                    . 'are started first.',
                'value_options' => $options,
            ),
            'attributes' => array(
                'class' => 'input-small',
            ),
        ));

        $this->setProperty('schedule', 'string', array(
            'required' => true,
            'defaultValue' => 'daily',
            'type' => 'select',
            'options' => array(
                'label' => 'Schedule',
                'value_options' => array(
                    'once' => 'Once',
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                ),
            ),
            'attributes' => array(
                'class' => 'input-medium',
            ),
        ));

        $this->setProperty('dependencies', 'list', array(
            'type' => 'string',
        ));

        $this->setProperty('startAt', 'dateTime', array(
            'type' => 'date',
            'required' => true,
            'attributes' => array(
                'required' => true,
                'class' => 'input-medium',
            ),
            'options' => array(
                'label' => 'Starts',
                'class' => 'input-large',
                'help-block' => 'Uses the time and/or date depending on the type of schedule.',
            ),
        ));

        $this->setProperty('queuedAt', 'dateTime');
        $this->setProperty('startedAt', 'dateTime');
        $this->setProperty('completedAt', 'dateTime');
        $this->setProperty('percentComplete', 'integer');
        $this->setProperty('error', 'boolean');
        $this->setProperty('pidOf', 'integer');
        $this->setProperty('pidCmd', 'string');
        $this->setProperty('messages', 'list', array(
            'type' => 'string',
        ));

        $this->setProperty('history', 'listEntity', array(
            'className' => 'Furnace\Entity\History',
        ));
        $this->setProperty('logs', 'list', array(
            'type' => 'string',
        ));
    }

    /**
     * Marks the internal properties for the entity to reflect it has been
     * queued and is waiting to be started by the next available worker.
     *
     * @return  $this
     */
    public function queue()
    {
        if ($this->getQueuedAt()) {
            throw new \RuntimeException('Cannot queue job as it has already been queued.');
        }

        if ($this->getStartedAt()) {
            throw new \RuntimeException('Cannot queue job as it has already been started.');
        }

        $this->setQueuedAt(time());
        $this->setError(false);
        $this->setMessages(array(
            sprintf('Queued job at %s', date('Y-m-d H:i:s', $this->getQueuedAt()->getTimestamp())),
        ));

        return $this;
    }

    /**
     * Marks the internal properties for the entity to reflect that it has
     * been picked up by a worker and started.
     *
     * @param   boolean                 Scan *nix /proc filesystem for PID/cmdline tracking
     * @return  $this
     */
    public function start($incPidLookup = true)
    {
        if ($this->getStartedAt()) {
            throw new \RuntimeException('Cannot start job as it has already been started.');
        }

        $this->clear(array('completedAt', 'queuedAt', 'messages', 'error'));
        $this->setPercentComplete(0);
        $this->setStartedAt(time());

        $message = sprintf('Started job at %s',
            date('Y-m-d H:i:s', $this->getStartedAt()->getTimestamp())
        );

        if ($incPidLookup) {
            $pid    = getmypid();
            $status = sprintf('/proc/%d/cmdline', $pid);

            if (!file_exists($status)) {
                throw new \RuntimeException('Cannot locate this process in the /proc filesystem, '
                    . 'perhaps we\'re not on *nix?'
                );
            }

            $this->setPidOf($pid);
            $this->setPidCmd(file_get_contents($status));

            $message .= sprintf(' (pid: %d)', $pid);
        }

        $this->addMessages($message);

        return $this;
    }

    /**
     * Tracks progress for a started job. Optionally looks up resource utilization
     * in the *nix /proc filesystem.
     *
     * @param   boolean                 Include *nix /proc filesystem status report
     * @return  $this
     */
    public function progress($pct, $incPidLookup = true)
    {
        $this->setPercentComplete($pct);

        if (!$this->isStarted($incPidLookup)) {
            throw new \RuntimeException('Cannot set progress, job is no longer running.');
        }

        $message = sprintf('Job reporting %d%% progress', $pct);

        if ($incPidLookup && $this->getPidOf()) {
            $stats  = $this->getStats();
            $report = array();

            if (isset($stats['Name'])) {
                $report[] = sprintf('\'%s\' running', $stats['Name']);
            }

            if (isset($stats['Pid'])) {
                $report[] = sprintf('on pid %d', $stats['Pid']);
            }

            if (isset($stats['VmSize'])) {
                $report[] = sprintf('total memory %s', $stats['VmSize']);
            }

            if (isset($stats['VmStk']) && isset($stats['VmSwap']) && isset($stats['VmData'])) {
                $report[] = sprintf('(stack: %s, heap: %s, swap: %s)',
                    $stats['VmStk'],
                    $stats['VmData'],
                    $stats['VmSwap']
                );
            }

            if (isset($stats['Threads'])) {
                $report[] = sprintf('on %d threads', $stats['Threads']);
            }

            if (isset($stats['State'])) {
                $report[] = sprintf('reporting state: %s', $stats['State']);
            }

            if ($report) {
                $message .= '. ' . implode(' ', $report);
            }
        }

        $this->addMessages($message);

        return $this;
    }

    /**
     * Marks the internal properties for the entity to reflect that it has
     * been completed.
     *
     * @param   boolean                 Include *nix /proc filesystem stats
     * @return  $this
     */
    public function complete($incStats = true)
    {
        if ($this->getCompletedAt()) {
            throw new \RuntimeException('Cannot complete job as the completedAt property is empty.');
        }

        if (!$this->getStartedAt()) {
            throw new \RuntimeException('Cannot complete job as the startedAt property is empty.');
        }

        $this->setCompletedAt(time());

        $history = new \Furnace\Entity\History(array(
            'startedAt' => $this->getStartedAt(),
            'completedAt' => $this->getCompletedAt(),
        ));

        if ($incStats && $this->getPidOf()) {
            $history->setStats($hash = $this->getStats());
        }

        $this->unshiftHistory($history);

        $this->addMessages(sprintf('Job completed at %s',
            date('Y-m-d H:i:s', $this->getCompletedAt()->getTimestamp()))
        );

        $this->clear('startedAt');

        return $this;
    }

    /**
     * Marks the internal properties for the entity to reflect that it has
     * not been completed.
     *
     * @return  $this
     */
    public function incomplete()
    {
        if (!$this->isCompleted()) {
            throw new \RuntimeException('Job is already marked as incomplete.');
        }

        $this->clear(array(
            'queuedAt',
            'startedAt',
            'completedAt',
            'error',
        ));

        if ($history = $this->getHistory() ?: array()) {
            if ($history instanceof \ContainMapper\Cursor) {
                $history = $history->export();
            }

            array_pop($history);
            $this->setHistory($history);
        }

        return $this;
    }

    /**
     * Marks the internal properties for the entity to reflect that it has
     * completed, but failed with an error.
     *
     * @param   string                  Message describing the error
     * @param   boolean                 Include *nix /proc filesystem stats
     * @return  $this
     */
    public function fail($message = '', $incStats = true)
    {
        if ($this->getCompletedAt()) {
            throw new \RuntimeException('Cannot fail job as it\'s been completed');
        }

        $this->setCompletedAt(time());

        $history = new \Furnace\Entity\History(array(
            'startedAt' => $this->getStartedAt() ?: time(),
            'failedAt' => $this->getCompletedAt(),
            'message' => $message,
        ));

        if ($incStats && $this->getPidOf()) {
            $history->setStats($this->getStats());
        }

        $this->unshiftHistory($history);

        $this->clear(array('startedAt', 'queuedAt'));
        $this->setError(true);

        $this->addMessages(sprintf('Job failed at %s: %s',
            date('Y-m-d H:i:s', $this->getCompletedAt()->getTimestamp()),
            $message
        ));

        return $this;
    }

    /**
     * Retrieves resource utilization stats for the current job by
     * looking them up in the /proc filesystem on *nix systems.
     *
     * @return  array(name => value, ...)
     */
    public function getStats()
    {
        $status = sprintf('/proc/%d/status', $this->getPidOf());

        if (!file_exists($status)) {
            return array('poop' => 'bandit');
        }

        $lines = file($status, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $hash  = array();

        foreach ($lines as $line) {
            if (preg_match('/^([^\:]+)\:(.*)$/', trim($line), $matches)) {
                $hash[$matches[1]] = trim($matches[2]);
            }
        }

        return $hash;
    }

    /**
     * Checks to see if the job is currently queued to run.
     *
     * @return  boolean
     */
    public function isQueued()
    {
        return $this->getQueuedAt() && !$this->getStartedAt();
    }

    /**
     * Checks to see if the job has run for a given date (defaults to
     * current date/time).
     *
     * @param   DateTime                Date/Time to check
     * @return  boolean
     */
    public function isCompleted($when = null)
    {
        $when = $this->getTimestampFromArgument($when);

        if (!$startAt = $this->getStartAt()) {
            throw new \RuntimeException('This job has no startAt, unable to determine '
                . 'whether it has been completed.'
            );
        }

        if (!$schedule = $this->getSchedule()) {
            throw new \RuntimeException('This job has no schedule, unable to determine '
                . 'whether it has been completed.'
            );
        }

        $runTimes = array();
        foreach ($this->getHistory() ?: array() as $history) {
            if ($history->getCompletedAt()) {
                $runTimes[] = $history->getCompletedAt()->getTimestamp();
            }
        }

        if ($this->getCompletedAt() && !$this->getError()) {
            $runTimes[] = $this->getCompletedAt()->getTimestamp();
        }

        switch ($schedule) {
            case 'once':
                return (boolean) $this->getCompletedAt();
                break;

            case 'daily':
                foreach ($runTimes as $runTime) {
                    if (date('Y-m-d', $runTime) == date('Y-m-d', $when)) {
                        return true;
                    }
                }
                break;

            case 'weekly':
                $oneWeek = 86400 * 7;

                foreach ($runTimes as $runTime) {
                    $diff = abs($runTime - $when);

                    if ($diff < $oneWeek) {
                        return true;
                    }
                }
                break;

            case 'monthly':
                foreach ($runTimes as $runTime) {
                    if (date('Y-m', $runTime) == date('Y-m', $when)) {
                        return true;
                    }
                }
                break;

            default:
                throw new \RuntimeException('Job does not have a recognized '
                    . 'schedule, unable to determine completedness.'
                );
                break;
        }

        return false;
    }

    /**
     * Checks to see if the meta-data of the job indicates it's running.
     * Optional: checks to see if the reported PID is still valid (only
     * works on *nix systems with a /proc file system).
     *
     * @param   boolean                 Verify PID is running
     * @return  boolean
     */
    public function isStarted($checkPidOf = true)
    {
        if (!$this->getStartedAt() || $this->getCompletedAt()) {
            return false;
        }

        if ($checkPidOf) {
            $status = sprintf('/proc/%d/cmdline', $this->getPidOf());

            // is the PID still active?
            if (!file_exists($status)) {
                return false;
            }

            // verify the PID is for our job and wasn't simply re-used
            return $this->getPidCmd() == file_get_contents($status);
        }

        return true;
    }

    /**
     * Extracts a timestamp from a given argument of DateTime, a string
     * for strtotime() or an integer representing seconds since the epoch.
     *
     * @param   DateTime|integer|string
     * @return  integer
     */
    public function getTimestampFromArgument($when = null)
    {
        if (!$when) {
            return time();
        }

        if ($when instanceof \DateTime) {
            return $when->getTimestamp();
        }

        if (is_string($when)) {
            if (!$when = strtotime($when)) {
                throw new \InvalidArgumentException('$when contains an invalid strtotime() '
                    . 'value: ' . $when
                );
            }

            return $when;
        }

        if (!is_integer($when)) {
            throw new \InvalidArgumentException('$when argument '
                . 'must be an instance of DateTime, an epoch timestamp, or a string to be '
                . 'evalulated by strtotime().'
            );
        }

        return $when;
    }

    /**
     * Sets the schedule/start time for a job. Similar to calling
     * setSchedule/setStartAt directly, expect that additional validation
     * is done which throws exceptions upon misconfiguration.
     *
     * @param   string                              Schedule (daily, weekly, etc.)
     * @param   DateTime|string|integer             When the job starts (date, time or both based
     *                                                  on the value from schedule)
     * @return  $this
     */
    public function schedule($schedule, $startAt = null)
    {
        $timestamp = $this->getTimestampFromArgument($startAt);

        $this->setStartAt($timestamp)
             ->setSchedule($schedule);

        if (!$this->isValid('schedule')) {
            throw new \InvalidArgumentException('$schedule invalid, not in list of allowed '
                . 'values.'
            );
        }

        if ($schedule == 'once' && $timestamp < time()) {
            throw new \RuntimeException('$schedule of once must include a start date/time '
                . 'in the future, ' . date('Y-m-d H:i:s') . ' given.'
            );
        }

        return $this;
    }
}
