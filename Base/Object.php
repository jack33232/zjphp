<?php
namespace ZJPHP\Base;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Exception\UnknownMethodException;
use ZJPHP\Base\Exception\UnknownPropertyException;
use ZJPHP\Base\Exception\InvalidCallException;

class Object implements Configurable
{
    public static function className()
    {
        return get_called_class();
    }
    
    public function __construct($config = [])
    {
        if (!empty($config)) {
            ZJPHP::configure($this, $config);
        }
        $this->init();
    }

    public function __get($name)
    {
        list($getter, $setter) = static::getAndSet($name);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, $setter)) {
            throw new InvalidCallException("Getting write-only property:" . get_class($this) . "::" . $name);
        } else {
            throw new UnknownPropertyException("Getting unknown property:" . get_class($this) . "::" . $name);
        }
    }

    public function __set($name, $value)
    {
        list($getter, $setter) = static::getAndSet($name);

        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        } elseif (method_exists($this, $getter)) {
            throw new InvalidCallException("Getting read-only property:" . get_class($this) . "::" . $name);
        } else {
            throw new UnknownPropertyException("Getting unknown property:" . get_class($this) . "::" . $name);
        }
    }

    public function __isset($name)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    public function __unset($name)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (!method_exists($this, $setter)) {
            throw new InvalidCallException("Unsetting read-only property:" . get_class($this) . "::" . $name);
        } else {
            $this->$setter(null);
        }
    }

    public function __call($name, $params)
    {
        throw new UnknownMethodException("Calling unknown method:" . get_class($this) . "::$name()");
    }

    public function init()
    {
    }

    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, $checkVars);
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        list($getter, $setter) = static::getAndSet($name);
        return method_exists($this, $getter) || $checkVars && property_exists($this, $name);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        list($getter, $setter) = static::getAndSet($name);
        return method_exists($this, $setter) || $checkVars && property_exists($this, $name);
    }

    protected static function getAndSet($name)
    {
        $getter = 'get' . ucfirst($name);
        $setter = 'set' . ucfirst($name);
        return array($getter, $setter);
    }
}
