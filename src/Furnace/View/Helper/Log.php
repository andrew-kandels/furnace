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
 * Furnace job status view helper
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Log extends AbstractHelper
{
    /**
     * @var integer
     */
    protected $maxBytes;

    /**
     * Constructor
     *
     * @param   integer
     * @return  void
     */
    public function __construct($maxBytes)
    {
        $this->maxBytes = $maxBytes;
    }

    /**
     * Invokes the view helper
     *
     * @param   Furnace\Entity\Job
     * @param   string                          Path to log file
     * @return  string
     */
    public function __invoke(JobEntity $job, $log)
    {
        if (!file_exists($log)) {
            return '';
        }

        if (($sz = filesize($log)) > $this->maxBytes) {
            $fp = fopen($log, 'rt');
            fseek($fp, $sz - $this->maxBytes);
            fgets($fp, 1024); // read to next line cleanly (fix broken line)
            $content = fread($fp, $this->maxBytes); // tail
            fclose($fp);
        } else {
            $content = file_get_contents($log);            
        }

        return $this->view->render('furnace/partials/log', array(
            'log' => $log,
            'job' => $job,
            'completed' => !$job->isStarted(),
            'content' => $content,
            'basename' => basename($log),
        ));
    }
}
