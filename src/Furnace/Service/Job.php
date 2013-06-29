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

namespace Furnace\Service;

use ContainMapper\Service\AbstractService;
use ContainMapper\Mapper;
use ContainMapper\Cursor;
use Furnace\Entity\Job as JobEntity;

/**
 * Service for managing and tracking jobs through their entities.
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Job extends AbstractService
{
    /**
     * @var ContainMapper\Mapper
     */
    protected $mapper;

    /**
     * Constructor
     *
     * @param   ContainMapper\Mapper
     * @return  void
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Finds a single job by its name/primary key value.
     *
     * @param   string                          Name
     * @return  Furnace\Entity\Job
     */
    public function findByName($name)
    {
        return $this->prepare($this->mapper)->findOne(array(
            'name' => $name,
        ));
    }

    /**
     * Finds many jobs that match a given schedule (monthly, daily, etc.).
     *
     * @param   string                          Schedule
     * @return  Furnace\Entity\Job[] (via ContainMapper\Cursor)
     */
    public function findBySchedule($schedule)
    {
        $this->prepare($this->mapper)->find(array(
            'schedule' => $schedule,
        ));
    }

    /**
     * Finds incomplete jobs that match a given schedule (monthly, daily, etc.).
     *
     * @param   string                          Schedule
     * @param   DateTime|string|integer
     * @return  Furnace\Entity\Job[] (via ContainMapper\Cursor)
     */
    public function findIncompleteBySchedule($schedule, $when = time())
    {
        $cursor  = $this->findBySchedule($schedule);
        $results = array();

        foreach ($cursor as $job) {
            if (!$job->isCompleted($when)) {
                $results[] = $job->export();
            }
        }

        return new Cursor($this->mapper, $results);
    }

    /**
     * Adds or saves an existing job entity.
     *
     * @param   Furnace\Entity\Job
     * @return  $this
     */
    public function save(JobEntity $job)
    {
        $this->prepare($this->mapper)->persist($job);
        return $this;
    }
}
