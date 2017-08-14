<?php
namespace ZJPHP\Service;

use ZJPHP\Base\Component;
use ZJPHP\Base\Kit\StringHelper;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\Exception\UnknownMethodException;
use ReflectionMethod;

class CastingMold extends Component
{
    private static $_reflectionCache = [];
    private $_apcuEnabled;

    const REFLECTION_CACHE_KEY = 'array:service_casting_mold_reflection';

    public function init()
    {
        $this->_apcuEnabled = function_exists('apcu_fetch');

        if ($this->_apcuEnabled) {
            $cache = apcu_fetch(self::REFLECTION_CACHE_KEY);
            if ($cache !== false) {
                self::$_reflectionCache = $cache;
            }
        }
    }

    public function formatData($fieldRules, $data, $isNested = true)
    {
        if ($isNested) {
            foreach ($data as $indx => $childData) {
                $data[$indx] = $this->formatData($fieldRules, $childData, false);
            }
        } else {
            foreach ($fieldRules as $field => $rules) {
                foreach ($rules as $ruleIndex => $rule) {
                    if (!key_exists($field, $data)) {
                        continue;
                    }
                    if ($ruleIndex === 'sub_rule') {
                        if (!empty($data[$field])) {
                            $data[$field] = $this->formatData($rule, $data[$field]);
                        }
                        continue;
                    }

                    if ($ruleIndex === 'rule') {
                        if (!empty($data[$field])) {
                            $data[$field] = $this->formatData($rule, $data[$field], false);
                        }
                        continue;
                    }

                    if (is_numeric($ruleIndex)) {
                        $castHandler = 'cast' . StringHelper::studly($rule);
                        $reflection = $this->achieveReflection($castHandler);

                        $this->callCastHandler($reflection, $castHandler, $data, $field);
                    } else {
                        $castHandler = 'cast' . StringHelper::studly($ruleIndex);

                        $reflection = $this->achieveReflection($castHandler);
                        $this->callCastHandler($reflection, $castHandler, $data, $field, $rule);
                    }
                }
            }
        }

        return $data;
    }

    public function callCastHandler($reflection, $handler, &$data, $field, $params = [])
    {
        $handlerParams = [];
        if (is_array($data[$field]) && !ArrayHelper::isAssociative($data[$field])) {
            foreach ($data[$field] as $tempField => &$childData) {
                $this->callCastHandler($reflection, $handler, $data[$field], $tempField, $params);
            }

            return;
        }

        $paramIndex = 0; // Only for non-standard parameters
        foreach ($reflection['handlerParams'] as $name => $value) {
            switch ($name) {
                case 'dataSet':
                    $handlerParams[] = &$data;
                    break;
                case 'field':
                    $handlerParams[] = $field;
                    break;
                default:
                    if (ArrayHelper::isAssociative($params) && isset($params[$name])) {
                        $assocIndex = array_search($name, array_keys($reflection['handlerParams']));
                        $handlerParams[$assocIndex] = $params[$name];
                    } elseif (isset($params[$paramIndex])) {
                        $handlerParams[] = $params[$paramIndex];
                    } else {
                        $handlerParams[] = $value;
                    }
                    $paramIndex++;
                    break;
            }
        }

        call_user_func_array([$this, $handler], $handlerParams);
    }

    public function achieveReflection($method)
    {
        if (key_exists($method, self::$_reflectionCache)) {
            return self::$_reflectionCache[$method];
        }

        $reflectionObj = null;
        $handlerParams = [];
        if (method_exists($this, $method)) {
            $reflectionObj = new ReflectionMethod($this, $method);
        } else {
            foreach ($this->getBehaviors() as $behaviorClass) {
                if (method_exists($behaviorClass, $method)) {
                    $reflectionObj = new ReflectionMethod($behaviorClass, $method);
                    break;
                }
            }
        }

        if ($reflectionObj !== null) {
            $handlerParamObjs = $reflectionObj->getParameters();
            foreach ($handlerParamObjs as $handlerParamObj) {
                $parameterName = $handlerParamObj->getName();
                if ($handlerParamObj->isOptional()) {
                    $handlerParams[$parameterName] = $handlerParamObj->getDefaultValue();
                } else {
                    $handlerParams[$parameterName] = 'N/A';
                }
            }
        } else {
            throw new UnknownMethodException("CastHelper method $method is not available.");
        }

        self::$_reflectionCache[$method]['reflectionObj'] = $reflectionObj;
        self::$_reflectionCache[$method]['handlerParams'] = $handlerParams;

        if ($this->_apcuEnabled) {
            apcu_store(self::REFLECTION_CACHE_KEY, self::$_reflectionCache);
        }

        return self::$_reflectionCache[$method];
    }
}
