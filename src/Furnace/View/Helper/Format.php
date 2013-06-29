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

/**
 * Furnace job formatting view helper
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Format extends AbstractHelper
{
    /**
     * Invokes the view helper
     *
     * @param   integer         Seconds
     * @param   integer         Base time (optional)
     * @return  string
     */
    public function __invoke($t, $measure = null)
    {
        if (!$measure) {
            $measure = time();
        }

        $elapsed = intval($measure - $t);
        $parts = array();

        if ($elapsed >= 86400) {
            $x = floor($hours = $elapsed / 86400);
            $parts[] = sprintf($this->getView()->translate('%d day%s'),
                number_format($x, 0), $x != 1 ? 's' : ''
            );
            $elapsed -= ($hours * 86400);
        }

        if ($elapsed >= 3600) {
            $x = floor($hours = $elapsed / 3600);
            $parts[] = sprintf($this->getView()->translate('%d hour%s'),
                number_format($x, 0), $x != 1 ? 's' : ''
            );
            $elapsed -= ($hours * 3600);
        }

        if ($elapsed >= 60) {
            $x = floor($minutes = $elapsed / 60);
            $parts[] = sprintf($this->getView()->translate('%d minute%s'),
                number_format($x, 0), $x != 1 ? 's' : ''
            );
            $elapsed -= ($minutes * 60);
        }

        $elapsed = (int) $elapsed;

        if ($elapsed > 0) {
            $parts[] = sprintf($this->getView()->translate('%s second%s'),
                number_format($elapsed, 0), $elapsed != 1 ? 's' : ''
            );
        }

        if (count($parts) > 1) {
            $parts[count($parts) - 1] = $this->getView()->translate('and') . ' ' . $parts[count($parts) - 1];
        }

        if (!$parts) {
            $parts = array($this->getView()->translate('better than a second'));
        }

        return implode(', ', $parts);
    }
}
