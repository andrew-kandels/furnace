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
 * Contain definition class describing a Furnace execution history event.
 *
 * @category    akandels
 * @package     furnace
 * @copyright   Copyright (c) 2013 Andrew P. Kandels (http://andrewkandels.com)
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class History extends AbstractDefinition
{
    /**
     * Configure the entity properties.
     *
     * @return  void
     */
    public function setUp()
    {
        $this->registerTarget(AbstractDefinition::ENTITY, __DIR__ . '/..')
             ->registerTarget(AbstractDefinition::FILTER, __DIR__ . '/../Filter');

        $this->setProperty('startedAt', 'dateTime');
        $this->setProperty('completedAt', 'dateTime');
        $this->setProperty('failedAt', 'dateTime');
        $this->setProperty('message', 'string');
    }
}
