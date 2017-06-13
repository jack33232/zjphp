<?php
namespace ZJPHP\DI;

use Closure;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Component;
use ZJPHP\Base\ZJPHP;

class ServiceLocator extends Component
{
    private $_definitions = [];
    private $_components = [];

    public function __get($name)
    {
        if ($this->has($name)) {
            return $this->get($name);
        } else {
            return parent::__get($name);
        }
    }

    public function __isset($name)
    {
        if ($this->has($name, true)) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    public function set($id, $definition)
    {
        if ($definition === null) {
            unset($this->_definitions[$id], $this->_components[$id]);
            return;
        }

        unset($this->_components[$id]);

        if (is_object($definition) || is_callable($definition, true)) {
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new InvalidConfigException("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new InvalidConfigException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

    public function clear($id)
    {
        unset($this->_definitions[$id], $this->_components[$id]);
    }

    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            } else {
                return $this->_components[$id] = ZJPHP::createObject($definition);
            }
        } elseif ($throwException) {
            throw new InvalidConfigException('Unknown component ID: ' .  $id);
        } else {
            return null;
        }
    }

    public function getComponents($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_components;
    }

    public function setComponents($components)
    {
        foreach ($components as $id => $definition) {
            $this->set($id, $definition);
        }
    }
}
