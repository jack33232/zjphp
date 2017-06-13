<?php
namespace ZJPHP\DI;

use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\ZJPHP;

class Instance
{
    public $id;

    protected function __construct($id)
    {
        $this->id = $id;
    }

    public static function of($id)
    {
        return new static($id);
    }

    public static function ensure($reference, $type = null, Container $container = null)
    {
        if ($reference instanceof $type) {
            return $reference;
        } elseif (is_array($reference)) {
            $class = isset($reference['class']) ? $reference['class'] : $type;
            if (!$container instanceof Container) {
                $container = ZJPHP::$container;
            }
            unset($reference['class']);
            $container->get($class, [], $reference);
        } elseif (empty($reference)) {
            throw new InvalidConfigException('The required component is not specified.');
        }

        if (is_string($reference)) {
            $reference = new static($reference);
        }

        if ($reference instanceof self) {
            $component = $reference->get($container);
            if ($component instanceof $type || $type === null) {
                return $component;
            } else {
                throw new InvalidConfigException('"' . $reference->id . '" refers to a ' . get_class($component) . " component. $type is expected.");
            }
        }

        $valueType = is_object($reference) ? get_class($reference) : gettype($reference);
        throw new InvalidConfigException("Invalid data type: $valueType. $type is expected.");
    }

    public function get(Container $container = null)
    {
        if ($container) {
            return $container->get($this->id);
        }

        return ZJPHP::$container->get($this->id);
    }
}
