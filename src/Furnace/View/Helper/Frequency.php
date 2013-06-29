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

/**
 * Furnace job frequency view helper
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Frequency extends AbstractHelper
{
    /**
     * Invokes the view helper
     *
     * @param   Furnace\Entity\Job
     * @return  string
     */
    public function __invoke(JobEntity $job)
    {
        switch ($job->getSchedule()) {
            case 'daily':
                return $this->getView()->translate('Daily');
                break;

            case 'weekly':
                return sprintf($this->getView()->translate('Weekly on %s'),
                    date('l', $job->getStartAt()->getTimestamp())
                );
                break;

            case 'monthly':
                return sprintf($this->getView()->translate('The %s of every month'),
                    date('jS', $job->getStartAt()->getTimestamp())
                );
                break;

            case 'once':
                return $this->getView()->translate('As soon as possible');
                break;

            default:
                return $this->getView()->translate('Unknown');
                break;
        }
    }
}
