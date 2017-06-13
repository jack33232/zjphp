<?php
namespace ZJPHP\Base;

use ZJPHP\Base\Exception\InvalidParamException;

class Event extends Object
{
    public $name;
    public $sender;
    public $handled = false;
    public $data;

    private static $_in_transaction = false;
    private static $_events = [];
    private static $_transaction_events = [];
    private static $_triggered_transaction_events = [];

    public static function on($class, $name, $handler, $data = null, $append = true, $is_transaction_event = false)
    {
        if (is_string($class)) {
            $class = ltrim($class, '\\');
        } elseif (is_object($class)) {
            $class = spl_object_hash($class);
        } else {
            throw new InvalidParamException('Event listener can only be attached to a class or object. "' . gettype($class) . '" is used.', 500);
        }

        $events_stack = &self::$_events;
        if ($is_transaction_event) {
            $events_stack = &self::$_transaction_events;
        }

        if ($append || empty($events_stack[$name][$class])) {
            $events_stack[$name][$class][] = [$handler, $data];
        } else {
            array_unshift($events_stack[$name][$class], [$handler, $data]);
        }
    }

    public static function off($class, $name, $handler = null, $is_transaction_event = false)
    {
        if (is_string($class)) {
            $class = ltrim($class, '\\');
        } elseif (is_object($class)) {
            $class = spl_object_hash($class);
        } else {
            throw new InvalidParamException('Event listener can only be attached to a class or object. "' . gettype($class) . '" is used.', 500);
        }

        $events_stack = &self::$_events;
        if ($is_transaction_event) {
            $events_stack = &self::$_transaction_events;
        }

        if (empty($events_stack[$name][$class])) {
            return false;
        }
        if ($handler === null) {
            unset($events_stack[$name][$class]);
        } else {
            $removed = false;
            foreach ($events_stack[$name][$class] as $i => $boundHandler) {
                if ($boundHandler[0] === $handler) {
                    unset($events_stack[$name][$class][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                $events_stack[$name][$class] = array_values($events_stack[$name][$class]);
            }

            return $removed;
        }
    }

    public static function rollBackTransactionEvents()
    {
        self::$_in_transaction = false;
        self::$_transaction_events[$transaction_name] = [];
        self::$_triggered_transaction_events[$transaction_name] = [];
    }

    public static function hasHandlers($class, $name, $is_transaction_event = false)
    {
        if (is_string($class)) {
            $class = ltrim($class, '\\');
        } elseif (is_object($class)) {
            $class = spl_object_hash($class);
        } else {
            throw new InvalidParamException('Event listener can only be attached to a class or object. "' . gettype($class) . '" is used.', 500);
        }

        $events_stack = &self::$_events;
        if ($is_transaction_event) {
            $events_stack = &self::$_transaction_events;
        }

        if (empty($events_stack[$name][$class])) {
            return false;
        }
        if (is_object($class)) {
            $class = get_class($class);
        } else {
            $class = ltrim('\\', $class);
        }
        do {
            if (!empty($events_stack[$name][$class])) {
                return true;
            }
        } while (($class = get_parent_class($class)) !== false);

        return false;
    }

    public static function trigger($class, $name, Event $event = null)
    {
        if (empty(self::$_events[$name]) && empty(self::$_transaction_events[$name])) {
            return;
        }
        if ($event === null) {
            $event = new static;
        }
        $event->handled = false;
        $event->name = $name;

        if (is_object($class)) {
            if ($event->sender === null) {
                $event->sender = $class;
            }
            $object_hash = spl_object_hash($class);
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }
        $classes = array_merge(
            [$class],
            (isset($object_hash)) ? [$object_hash] : [],
            class_parents($class, true),
            class_implements($class, true)
        );
        foreach ($classes as $class) {
            if (!empty(self::$_events[$name][$class])) {
                foreach (self::$_events[$name][$class] as $handler) {
                    $event->data = $handler[1];
                    call_user_func($handler[0], $event);
                    if ($event->handled) {
                        return;
                    }
                }
            }

            if (!empty(self::$_transaction_events[$name][$class]) && self::$_in_transaction) {
                foreach (self::$_transaction_events[$name][$class] as $handler) {
                    $event->data = $handler[1];
                    self::$_triggered_transaction_events[] = [$handler[0], $event];
                    if ($event->handled) {
                        return;
                    }
                }
            }
        }
    }

    public static function beginTransaction()
    {
        self::$_in_transaction = true;
    }

    public static function transactionCommit()
    {
        self::$_in_transaction = false;
    }

    public static function transactionEventTrigger()
    {
        if (!empty(self::$_triggered_transaction_events)) {
            foreach (self::$_triggered_transaction_events as $handler_set) {
                call_user_func($handler_set[0], $handler_set[1]);
            }
        }
    }
}
