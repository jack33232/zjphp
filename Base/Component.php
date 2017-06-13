<?php
namespace ZJPHP\Base;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Exception\InvalidCallException;
use ZJPHP\Base\Exception\UnknownMethodException;
use ZJPHP\Base\Exception\UnknownPropertyException;

class Component extends Object
{
    private $_events = [];

    private $_behaviors;

    public function __get($name)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name, true)) {
                    return $behavior->$name;
                }
            }
        }

        if (method_exists($this, $setter)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    public function __set($name, $value)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        } elseif (strncmp($name, 'on ', 3) === 0) {
            $this->on(trim(substr($name, 3)), $value);
            return;
        } elseif (strncmp($name, 'as ', 3) === 0) {
            $this->attachBehavior(trim(substr($name, 3)), $value);
            return;
        } else {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name, true)) {
                    $behavior->$name = $value;

                    return;
                }
            }
        }

        if (method_exists($this, $getter)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    public function __isset($name)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name)) {
                    return $this->$name !== null;
                }
            }
        }
        return false;
    }

    public function __unset($name)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (method_exists($this, $setter)) {
            $this->$setter(null);
            return;
        } else {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name)) {
                    $behavior->$name = null;
                    return;
                }
            }
        }
        throw new InvalidCallException('Unsetting an unknown or read-only property: ' . get_class($this) . '::' . $name);
    }

    public function __call($name, $params)
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behaviorObject) {
            if ($behaviorObject->hasMethod($name)) {
                return call_user_func_array([$behaviorObject, $name], $params);
            }
        }
        throw new UnknownMethodException('Calling unknown method: ' . get_class($this) . "::$name()");
    }

    public function __clone()
    {
        $this->_events = [];
        $this->_behaviors = [];
    }

    public function hasProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        return $this->canGetProperty($name, $checkVars, $checkBehaviors) || $this->canSetProperty($name, false, $checkBehaviors);
    }

    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (method_exists($this, $getter) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canGetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        list($getter, $setter) = static::getAndSet($name);
        if (method_exists($this, $setter) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function hasMethod($name, $checkBehaviors = true)
    {
        if (method_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->hasMethod($name)) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getBehavior($name)
    {
        $this->ensureBehaviors();
        return isset($this->_behaviors[$name]) ? $this->_behaviors[$name] : null;
    }

    public function getBehaviors()
    {
        $this->ensureBehaviors();
        return $this->_behaviors;
    }

    public function attachBehavior($name, $behavior)
    {
        $this->ensureBehaviors();
        return $this->attachBehaviorInternal($name, $behavior);
    }

    public function attachBehaviors($behaviors)
    {
        $this->ensureBehaviors();
        foreach ($behaviors as $name => $behavior) {
            $this->attachBehaviorInternal($name, $behavior);
        }
    }

    public function behaviors()
    {
        return [];
    }

    public function ensureBehaviors()
    {
        if ($this->_behaviors === null) {
            $this->_behaviors = [];
            foreach ($this->behaviors() as $name => $behavior) {
                $this->attachBehaviorInternal($name, $behavior);
            }
        }
    }

    public function detachBehavior($name)
    {
        $this->ensureBehaviors();
        if (isset($this->_behaviors[$name])) {
            $behavior = $this->_behaviors[$name];
            unset($this->_behaviors[$name]);
            $behavior->detach();
            return $behavior;
        } else {
            return null;
        }
    }

    public function detachBehaviors()
    {
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $name => $behavior) {
            $this->detachBehavior($name);
        }
    }

    private function attachBehaviorInternal($name, $behavior)
    {
        if (!$behavior instanceof Behavior) {
            $behavior = ZJPHP::createObject($behavior);
        }
        if (is_int($name)) {
            $behavior->attach($this);
            $this->_behaviors[] = $behavior;
        } else {
            if (isset($this->_behaviors[$name])) {
                $this->detachBehavior($name);
            }
            $behavior->attach($this);
            $this->_behaviors[$name] = $behavior;
        }
        return $behavior;
    }

    public function hasEventHandler($name)
    {
        $this->ensureBehaviors();
        return !empty($this->_events[$name]) || Event::hasHandlers($this, $name);
    }

    public function on($name, $handler, $data = null, $append = true, $is_transaction_event = false)
    {
        $this->ensureBehaviors();
        if ($is_transaction_event) {
            Event::on($this, $name, $handler, $data, $append, true);
            return;
        }

        if ($append || empty($this->_events[$name])) {
            $this->_events[$name][] = [$handler, $data];
        } else {
            array_unshift($this->_events, [$handler, $data]);
        }
    }

    public function off($name, $handler = null, $is_transaction_event = false)
    {
        $this->ensureBehaviors();
        if ($is_transaction_event) {
            return Event::off($this, $name, $handler, true);
        }

        if (empty($this->_events[$name])) {
            return false;
        }

        if ($handler === null) {
            unset($this->_events[$name]);
            return true;
        } else {
            $removed = false;
            foreach ($this->_events[$name] as $i => $boundHandler) {
                if ($handler === $boundHandler[0]) {
                    unset($this->_events[$name][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                $this->_events[$name] = array_values($this->_events[$name]);
            }
            return $removed;
        }
    }

    public function trigger($name, Event $event = null)
    {
        $this->ensureBehaviors();
        if (!empty($this->_events[$name])) {
            if ($event === null) {
                $event = new Event();
            }
            if ($event->sender === null) {
                $event->sender = $this;
            }
            $event->name = $name;
            $event->handled = false;
            foreach ($this->_events[$name] as $boundHandler) {
                $event->data = $boundHandler[1];
                call_user_func($boundHandler[0], $event);
                if ($event->handled) {
                    return;
                }
            }
        }
        Event::trigger($this, $name, $event);
    }
}
