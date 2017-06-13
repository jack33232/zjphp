<?php
namespace ZJPHP\Service;

use ZJPHP\Base\Component;
use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Application;
use ZJPHP\Base\Exceptions\InvalidConfigException;
use ZJPHP\Base\Kit\ArrayHelper;
use Klein\Klein;

class Router extends Component
{
    const EVENT_APP_ERROR_HAPPEN = 'appErrorHappen';
    const EVENT_APP_HTTP_ERROR_HAPPEN = 'appHttpErrorHappen';

    private $_router;
    private $_response;
    private $_routeMap;
    private $_filters;
    private $_namespace;
    private $_matchedPairs;
    private $_matched;
    private $_methodsMatched;

    public function init()
    {
        parent::init();

        $this->_router = new Klein();
        $this->processRouteMap();

        $this->_router->onError(function ($router, $errMsg, $errType, $err) {
            $event = ZJPHP::createObject([
                'class' => 'RuntimeErrorEvent',
                'sender' => $router,
                'err' => $err
            ]);
            $this->trigger(static::EVENT_APP_ERROR_HAPPEN, $event);
        });
        $this->_router->onHttpError(function ($code, $router) {
            $event = ZJPHP::createObject([
                'class' => 'RuntimeHttpErrorEvent',
                'sender' => $router,
                'code' => $code
            ]);
            $this->trigger(static::EVENT_APP_HTTP_ERROR_HAPPEN, $event);
        });

        $this->routeMatch(null, $this->_response);
    }

    public function ip()
    {
        $request = $this->_router->request();
        return $request->ip();
    }

    public function uri()
    {
        $request = $this->_router->request();
        return $request->uri();
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function getRequest()
    {
        return $this->_router->request();
    }

    public function setRouteMap(array $routeMap)
    {
        $this->_routeMap = $routeMap;
    }

    public function setFilters(array $filters)
    {
        $this->_filters = $filters;
    }

    public function setNamespace($namespace)
    {
        $this->_namespace = $namespace;
    }

    public function setResponse($definition)
    {
        return $this->_response = ZJPHP::createObject($definition);
    }

    protected function routeMatch()
    {
        $match_result = $this->_router->routeMatch();
        $this->_matchedPairs = $match_result['matched_pairs'];
        $this->_matched = $match_result['matched'];
        $this->_methodsMatched = $match_result['methods_matched'];
    }

    protected function processRouteMap()
    {
        $processedRouteMap = [];

        if (isset($this->_routeMap['namespaces'])) {
            $standard_mask = [
                'dependency' => [],
                'passArgs' => [],
                'filters' => $this->_filters
            ];
            foreach ($this->_routeMap['namespaces'] as $namespace => $namespace_setting) {
                $namespace_setting = ArrayHelper::merge($standard_mask, $namespace_setting);

                foreach ($namespace_setting['rules'] as $rule_setting) {
                    $rule_setting = ArrayHelper::merge($standard_mask, $rule_setting);
                    // Method
                    $method = $rule_setting['method'];
                    // Path
                    $path = $this->_namespace . $namespace . $rule_setting['path'];
                    // Controller
                    $controller = (isset($rule_setting['controller']))
                        ? $rule_setting['controller']
                        : $namespace_setting['controller'];
                    // Action
                    $action = $rule_setting['action'];
                    // Filters
                    $filters = array_filter(ArrayHelper::merge($namespace_setting['filters'], $rule_setting['filters']));
                    // Dependency
                    $dependency = array_filter(ArrayHelper::merge($namespace_setting['dependency'], $rule_setting['dependency']));
                    // Event Callbacks
                    $event_callbacks = (isset($rule_setting['callbacks'])) ? $rule_setting['callbacks'] : [];
                    // Pass arguments
                    $pass_args = array_filter(ArrayHelper::merge($namespace_setting['passArgs'], $rule_setting['passArgs']));
                    // Is last
                    $is_last = (isset($rule_setting['isLast'])) ? $rule_setting['isLast'] : false;

                    $callback = $this->createRouteCallback($controller, $action, $filters, $dependency, $event_callbacks, $pass_args, $is_last);

                    $this->_router->respond($method, $path, $callback);
                }
            }
        }

        if (isset($this->_routeMap['singleRules'])) {
            $standard_mask = [
                'dependency' => [],
                'passArgs' => [],
                'filters' => $this->_filters
            ];

            foreach ($this->_routeMap['singleRules'] as $rule_setting) {
                $rule_setting = ArrayHelper::merge($standard_mask, $rule_setting);
                // Method
                $method = $rule_setting['method'];
                // Path
                $path = $this->_namespace . $rule_setting['path'];
                // Controller
                $controller = $rule_setting['controller'];
                // Action
                $action = $rule_setting['action'];
                // Filters
                $filters = array_filter($rule_setting['filters']);
                // Dependency
                $dependency = $rule_setting['dependency'];
                // Event Callbacks
                $event_callbacks = (isset($rule_setting['callbacks'])) ? $rule_setting['callbacks'] : [];
                // Pass arguments
                $pass_args = $rule_setting['passArgs'];
                // Is last
                $is_last = (isset($rule_setting['isLast'])) ? $rule_setting['isLast'] : false;

                $callback = $this->createRouteCallback($controller, $action, $filters, $dependency, $event_callbacks, $pass_args, $is_last);

                $this->_router->respond($method, $path, $callback);
            }
        }
    }

    protected function createRouteCallback($controller, $action, $filters, $dependency, $event_callbacks, $pass_args, $is_last)
    {
        $callback = function ($request, $response, $service, $app, $router) use ($controller, $action, $filters, $dependency, $event_callbacks, $pass_args, $is_last) {
            // Pass args by app
            foreach ($pass_args as $key => $value) {
                $app->$key = $value;
            }

            foreach ($filters as $filter) {
                $filter_obj = ZJPHP::createObject($filter);
                $filter_obj->filter($request, $response, $service, $app, $router);
                unset($filter_obj);
                if ($response->isSent()) {
                    return false;
                }
            }

            if (is_string($controller)) {
                $controller = [
                    'class' => $controller,
                    'dependency' => $dependency,
                    'callbacks' => $event_callbacks
                ];
            } elseif (is_array($controller)) {
                $controller['dependency'] = isset($controller['dependency']) ? ArrayHelper::merge($dependency, $controller['dependency']) : $dependency;
                $controller['callbacks'] = isset($controller['callbacks']) ? ArrayHelper::merge($event_callbacks, $controller['callbacks']) : $event_callbacks;
            }
            $controller_obj = ZJPHP::createObject($controller);
            $controller_obj->$action($request, $response, $service, $app, $router);

            if ($is_last) {
                $router->skipRemaining();
            }
        };

        return $callback;
    }

    public function dispatch()
    {
        $this->_router->lazyDispatch($this->_matchedPairs, $this->_matched, $this->_methodsMatched, $this->_response);
    }
}
