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
    private $_routeRules;
    private $_filters;
    private $_namespace;
    private $_matchedPairs;
    private $_matched;
    private $_methodsMatched;

    public function init()
    {
        parent::init();

        $this->_router = new Klein();

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

    public function setRouteMap(array $route_map)
    {
        $this->_routeMap = [];
        if (!isset($route_map['file']) || !file_exists($route_map['file'])) {
            throw new InvalidConfigException('Route map file is not defined or not exist.');
        }

        $this->_routeMap['file'] = $route_map['file'];
        $this->_routeMap['name'] = !empty($route_map['name']) ? $route_map['name'] : 'default';
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

    public function getRouteRules()
    {
        return $this->_routeRules;
    }

    protected function routeMatch()
    {
        $this->processRouteMap();
        $match_result = $this->_router->routeMatch(null, $this->_response);
        $this->_matchedPairs = $match_result['matched_pairs'];
        $this->_matched = $match_result['matched'];
        $this->_methodsMatched = $match_result['methods_matched'];
    }

    protected function processRouteMap()
    {
        $route_map_file = $this->_routeMap['file'];
        $route_map_name = $this->_routeMap['name'];
        $mtime = filemtime($route_map_file);
        $apcu_enabled = function_exists('apcu_fetch');
        
        // Try to use cached
        if ($apcu_enabled) {
            $cache_exist = false;
            $cache_mtime = apcu_fetch('int:' . RUNTIME_ENV . '_router_' . $route_map_name . '_mtime', $cache_exist);
            if ($cache_exist !== false && $cache_mtime === $mtime) {
                $this->_routeRules = apcu_fetch('array:' . RUNTIME_ENV . '_router_rules_' . $route_map_name);
                $routes = apcu_fetch('object:' . RUNTIME_ENV . '_router_routes_' . $route_map_name);
                $this->_router->resetRoutes($routes);
                return;
            }
        }

        $raw_route_rules = require($route_map_file);
        $this->_routeRules = [];
        // Reset router route collection
        $this->_router->resetRoutes();

        if (isset($raw_route_rules['namespaces'])) {
            $standard_mask = [
                'dependency' => [],
                'passArgs' => [],
                'filters' => $this->_filters
            ];
            foreach ($raw_route_rules['namespaces'] as $namespace => $namespace_setting) {
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

                    $route = $this->_router->respond($method, $path, [get_class($this), 'fakeCallback']);
                    $route_name = spl_object_hash($route);
                    $route->setName($route_name);
                    $this->_routeRules[$route_name] = [
                        'controller' => $controller,
                        'action' => $action,
                        'filters' => $filters,
                        'dependency' => $dependency,
                        'event_callbacks' => $event_callbacks,
                        'pass_args' => $pass_args,
                        'is_last' => $is_last
                    ];
                }
            }
        }

        if (isset($raw_route_rules['singleRules'])) {
            $standard_mask = [
                'dependency' => [],
                'passArgs' => [],
                'filters' => $this->_filters
            ];

            foreach ($raw_route_rules['singleRules'] as $rule_setting) {
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

                $route = $this->_router->respond($method, $path, [get_class($this), 'fakeCallback']);
                $route_name = spl_object_hash($route);
                $route->setName($route_name);
                $this->_routeRules[$route_name] = [
                    'controller' => $controller,
                    'action' => $action,
                    'filters' => $filters,
                    'dependency' => $dependency,
                    'event_callbacks' => $event_callbacks,
                    'pass_args' => $pass_args,
                    'is_last' => $is_last
                ];
            }
        }

        if ($apcu_enabled) {
            $cache_rules_result = apcu_store('array:' . RUNTIME_ENV . '_router_rules_' . $route_map_name, $this->_routeRules);
            $cache_rules_resultII = apcu_store('object:' . RUNTIME_ENV . '_router_routes_' . $route_map_name, $this->_router->routes());

            if ($cache_rules_result && $cache_rules_resultII) {
                apcu_store('int:' . RUNTIME_ENV . '_router_' . $route_map_name . '_mtime', $mtime);
            }
        }
    }

    public static function fakeCallback($request, $response, $service, $app, $router, $matched, $methods_matched, $route)
    {
        // Extract varaibles from route rule
        $route_name = $route->getName();
        $router_service = ZJPHP::$app->get('router');
        $route_rules = $router_service->getRouteRules();
        extract($route_rules[$route_name]);
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
    }

    public function dispatch()
    {
        $this->routeMatch();
        $this->_router->lazyDispatch($this->_matchedPairs, $this->_matched, $this->_methodsMatched, $this->_response);
    }
}
