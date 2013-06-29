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

namespace Furnace\Factory\Form;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Furnace\Entity\Form\Job as JobForm;
use Furnace\Entity\Filter\Job as JobFilter;
use Zend\Form\Factory as FormFactory;

/**
 * Form Factory
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class Job implements FactoryInterface
{
    /**
     * Creates the job form
     *
     * @param   Zend\ServiceManager\ServiceLocatorInterface
     * @return  Service|null
     */
    public function createService(ServiceLocatorInterface $sm)
    {
        $form = new JobForm();

        $form->setInputFilter(new JobFilter());

        $whiteList = array(
            'name',
            'priority',
            'schedule',
            'description',
            'startAt',
        );

        foreach ($form as $element) {
            if (!in_array($element->getName(), $whiteList)) {
                $form->getInputFilter()->remove($element->getName());
                $form->remove($element->getName());
            }
        }

        $factory = new FormFactory();
        $form->setAttribute('class', 'form-horizontal');

        $form->add($factory->createElement(array(
            'name' => 'submit-btn',
            'type' => 'submit',
            'attributes' => array(
                'value'                 => 'Create',
                'class'                 => 'btn btn-primary',
            ),
        )));

        return $form;
    }
}
