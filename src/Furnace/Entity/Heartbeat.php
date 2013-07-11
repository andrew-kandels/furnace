<?php
namespace Furnace\Entity;

use Contain\Entity\AbstractEntity;
use Contain\Entity\Property\Property;

/**
 * Heartbeat Entity (auto-generated by the Contain module)
 *
 * This instance should not be edited directly. Edit the definition file instead
 * and recompile.
 */
class Heartbeat extends AbstractEntity
{

    protected $inputFilter = 'Furnace\Entity\Filter\Heartbeat';
    protected $messages = array();

    /**
     * Initializes the properties of this entity.
     *
     * @return  $this
     */
    public function init()
    {
        $this->define('name', 'string', array (
  'primary' => true,
));
        $this->define('at', 'dateTime', array (
  'dateFormat' => 'Y-m-d H:i:s',
));
        $this->define('pidOf', 'integer');
        $this->define('hostname', 'string');
        $this->define('user', 'string');
            }


    /**
     * Accessor getter for the name property
     *
     * @return  See: Contain\Entity\Property\Type\StringType::getValue()
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Accessor setter for the name property
     *
     * @param   See: Contain\Entity\Property\Type\StringType::parse()
     * @return  $this
     */
    public function setName($value)
    {
        return $this->set('name', $value);
    }

    /**
     * Accessor existence checker for the name property
     *
     * @return  boolean
     */
    public function hasName()
    {
        $property = $this->property('name');
        return !($property->isUnset() || $property->isEmpty());
    }

    /**
     * Accessor getter for the at property
     *
     * @return  See: Contain\Entity\Property\Type\DateTimeType::getValue()
     */
    public function getAt()
    {
        return $this->get('at');
    }

    /**
     * Accessor setter for the at property
     *
     * @param   See: Contain\Entity\Property\Type\DateTimeType::parse()
     * @return  $this
     */
    public function setAt($value)
    {
        return $this->set('at', $value);
    }

    /**
     * Accessor existence checker for the at property
     *
     * @return  boolean
     */
    public function hasAt()
    {
        $property = $this->property('at');
        return !($property->isUnset() || $property->isEmpty());
    }

    /**
     * Accessor getter for the pidOf property
     *
     * @return  See: Contain\Entity\Property\Type\IntegerType::getValue()
     */
    public function getPidOf()
    {
        return $this->get('pidOf');
    }

    /**
     * Accessor setter for the pidOf property
     *
     * @param   See: Contain\Entity\Property\Type\IntegerType::parse()
     * @return  $this
     */
    public function setPidOf($value)
    {
        return $this->set('pidOf', $value);
    }

    /**
     * Accessor existence checker for the pidOf property
     *
     * @return  boolean
     */
    public function hasPidOf()
    {
        $property = $this->property('pidOf');
        return !($property->isUnset() || $property->isEmpty());
    }

    /**
     * Accessor getter for the hostname property
     *
     * @return  See: Contain\Entity\Property\Type\StringType::getValue()
     */
    public function getHostname()
    {
        return $this->get('hostname');
    }

    /**
     * Accessor setter for the hostname property
     *
     * @param   See: Contain\Entity\Property\Type\StringType::parse()
     * @return  $this
     */
    public function setHostname($value)
    {
        return $this->set('hostname', $value);
    }

    /**
     * Accessor existence checker for the hostname property
     *
     * @return  boolean
     */
    public function hasHostname()
    {
        $property = $this->property('hostname');
        return !($property->isUnset() || $property->isEmpty());
    }

    /**
     * Accessor getter for the user property
     *
     * @return  See: Contain\Entity\Property\Type\StringType::getValue()
     */
    public function getUser()
    {
        return $this->get('user');
    }

    /**
     * Accessor setter for the user property
     *
     * @param   See: Contain\Entity\Property\Type\StringType::parse()
     * @return  $this
     */
    public function setUser($value)
    {
        return $this->set('user', $value);
    }

    /**
     * Accessor existence checker for the user property
     *
     * @return  boolean
     */
    public function hasUser()
    {
        $property = $this->property('user');
        return !($property->isUnset() || $property->isEmpty());
    }

}
