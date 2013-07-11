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

namespace Furnace\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Furnace\Entity\Job as JobEntity;
use ContainMapper\Cursor;
use RuntimeException;
use Furnace\Service\Job as JobService;

/**
 * Furnace job list view helper
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class JobList extends AbstractHelper
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
     * Invokes the view helper
     *
     * @param   Furnace\Entity\Job[]|ContainMapper\Cursor|array
     * @param   boolean                             Include checkboxes for mass actions?
     * @param   string                              Schedule
     * @return  string
     */
    public function __invoke($arr, $checkbox = false, $schedule = 'all')
    {
        if ($arr instanceof JobEntity) {
            $arr = array($arr);
        }

        if ($arr instanceof Cursor) {
            $arr = $arr->toArray();
        }

        if (!is_array($arr)) {
            throw new RuntimeException('$arr must be an instance of Furnace\Entity\Job, '
                . 'ContainMapper\Cursor or an array of jobs.'
            );
        }

        $jobs = array();

        foreach ($arr as $job) {
            if ($dependencies = $job->getDependencies()) {
                $return = array();

                foreach ($dependencies as $dependency) {
                    if ($subJob = $this->service->findByName($dependency)) {
                        $return[] = $subJob;
                    }
                }

                $job->setExtendedProperty('dependencies', $return);
            }

            $jobs[] = $job;
        }

        $schedules = array(
            'all'     => 'All',
            'daily'   => 'Daily',
            'weekly'  => 'Weekly',
            'monthly' => 'Monthly',
            'once'    => 'Once',
        );

        return $this->view->render('furnace/partials/job-list', array(
            'arr' => $jobs,
            'num' => count($jobs),
            'schedule' => $schedule,
            'schedules' => $schedules,
            'checkbox' => $checkbox,
        ));
    }
}
