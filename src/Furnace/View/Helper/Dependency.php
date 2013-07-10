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
use Zend\Form\Element\Select as SelectElement;
use Furnace\Service\Job as JobService;

/**
 * Furnace job dependency management
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Dependency extends AbstractHelper
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
     * @param   Furnace\Entity\Job
     * @return  string
     */
    public function __invoke(JobEntity $job = null)
    {
        $element = new SelectElement('dependency');

        $jobs = array(
            'daily' => array(),
            'weekly' => array(),
            'monthly' => array(),
        );

        $element->setAttributes(array(
            'id' => 'dependency',
            'class' => 'input-xlarge',
        ));

        $rs = $this->service
            ->sort(array('_id' => 1))
            ->findNotNamed($job ? $job->getName() : '');

        foreach ($rs as $el) {
            $jobs[$el->getSchedule()][] = array(
                'label' => sprintf('%s - %s', $el->getName(), $el->getDescription()),
                'value' => $el->getName(),
            );
        }

        $element->setOptions(array(
            'value_options' => array(
                array(
                    'label' => $this->getView()->translate('-- Select a Job --'),
                    'value' => '',
                ),
                array(
                    'label' => 'Daily',
                    'value' => 'Daily',
                    'options' => $jobs['daily'],
                ),
                array(
                    'label' => 'Weekly',
                    'value' => 'Weekly',
                    'options' => $jobs['weekly'],
                ),
                array(
                    'label' => 'Monthly',
                    'value' => 'Monthly',
                    'options' => $jobs['monthly'],
                ),
            ),
        ));

        return $this->view->render('furnace/partials/dependency', array(
            'job' => $job,
            'element' => $element,
            'dependencies' => implode(',', $job ? $job->getDependencies() : array()),
        ));
    }
}
