<?php
namespace ZJPHP\DI;

use ReflectionClass;
use ZJPHP\Base\Component;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Exception\InvalidCallException;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\ZJPHP;
use ZJPHP\DI\Instance;
use Exception;

class Container extends Component
{
    private $_singletons = [];
    private $_definitions = [];
    private $_params = [];
    private $_reflections = [];
    private $_dependencies = [];

    public function set($class, $definition = [], $params = [])
    {
        $this->_definitions[$class] = $this->normallizeDefinition(
            $class,
            $definition
        );

        $this->_params[$class] = $params;

        unset($this->_singletons[$class]);
        return $this;
    }

    public function setSingleton($class, $definition = [], $params = [])
    {
        $this->_definitions[$class] = $this->normallizeDefinition(
            $class,
            $definition
        );

        $this->_params[$class] = $params;

        $this->_singletons[$class] = null;
        return $this;
    }

    public function get($class, $params = [], $config = [])
    {
        $result_object = null;

        if (isset($this->_singletons[$class])) {
            return $this->_singletons[$class];
        } elseif (!isset($this->_definitions[$class])) {
            return $this->build($class, $params, $config);
        }

        $definition = $this->_definitions[$class];

        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($class, $params));
            $result_object = call_user_func($definition, $this, $params, $config);
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            if ($concrete === $class) {
                $result_object = $this->build($class, $params, $config);
            } else {
                $result_object = $this->get($concrete, $params, $config);
            }
        } elseif (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new InvalidConfigException('Wrong definition of the class: '.$class.' with its type:'.gettype($definition));
        }

        if (array_key_exists($class, $this->_singletons)) {
            $this->_singletons[$class] = $result_object;
        }

        return $result_object;
    }

    public function has($class)
    {
        return isset($this->_definitions[$class]);
    }

    public function hasSingleton($class, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_singletons[$class]) : array_key_exists($class, $this->_singletons);
    }

    public function clear($class)
    {
        unset($this->_definitions[$class], $this->_singletons[$class]);
    }

    protected function normallizeDefinition($class, $definition)
    {
        if (empty($definition)) {
            return ['class' => $class];
        } elseif (is_string($definition)) {
            return ['class' => $definition];
        } elseif (is_callable($definition) || is_object($definition)) {
            return $definition;
        } elseif (is_array($definition)) {
            if (!isset($definition['class'])) {
                if (strpos($class, '\\') !== false) {
                    $definition['class'] = $class;
                } else {
                    throw new InvalidConfigException('Need "class"');
                }
            }
            return $definition;
        } else {
            throw new InvalidConfigException('WTFxck Definition!');
        }
    }

    protected function getDependencies($class)
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $c = $param->getClass();
                    $dependencies[] = Instance::of($c === null ? null : $c->getName());
                }
            }
        }

        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    public function getDefinitions($class = null)
    {
        return (is_null($class) || !$this->has($class)) ? $this->_definitions : $this->_definitions[$class];
    }

    protected function resolveDependencies($dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    $dependencies[$index] = $this->get($dependency->id);
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()
                        ->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new Exception('Class '.$class.' require parameter '.$name
                        .' when instantiation.');
                }
            }
        }
        return $dependencies;
    }

    protected function build($class, $params, $config)
    {
        list($reflection, $dependencies) = $this->getDependencies($class);

        if ($reflection->isInterface()) {
            throw new InvalidCallException('Cannot build a interface.');
        }

        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        if (!empty($dependencies) && $reflection->implementsInterface('ZJPHP\\Base\\Configurable')) {
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        } else {
            $object = $reflection->newInstanceArgs([]);
            foreach ($config as $key => $value) {
                $object->$key = $value;
            }
            return $object;
        }
    }

    protected function mergeParams($class, $params)
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        } else {
            $ps = $this->_params[$class];
            foreach ($params as $index => $value) {
                $ps[$index] = $value;
            }
            return $ps;
        }
    }

    public function invoke(callable $callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
        } else {
            return call_user_func_array($callback, $params);
        }
    }


    public function resolveCallableDependencies(callable $callback, $params = [])
    {
        if (is_array($callback)) {
            $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        } else {
            $reflection = new \ReflectionFunction($callback);
        }

        $args = [];

        $associative = ArrayHelper::isAssociative($params);

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if (($class = $param->getClass()) !== null) {
                $className = $class->getName();
                if ($associative && isset($params[$name]) && $params[$name] instanceof $className) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                } elseif (!$associative && isset($params[0]) && $params[0] instanceof $className) {
                    $args[] = array_shift($params);
                } elseif (isset(ZJPHP::$app) && ZJPHP::$app->has($name) && ($obj = ZJPHP::$app->get($name)) instanceof $className) {
                    $args[] = $obj;
                } else {
                    $args[] = $this->get($className);
                }
            } elseif ($associative && isset($params[$name])) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif (!$associative && count($params)) {
                $args[] = array_shift($params);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $funcName = $reflection->getName();
                throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
            }
        }

        foreach ($params as $value) {
            $args[] = $value;
        }
        return $args;
    }
}
