<?php
namespace ZJPHP\Base;

use ZJPHP\DI\Container;
use ZJPHP\DI\ServiceLocator;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\CascadingEvent;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\BootstrapInterface;

abstract class Application extends ServiceLocator
{
    const EVENT_INIT_APP = 'appInitiation';
    const EVENT_END_APP = 'appEnd';

    const STATE_BEGIN = 'begin';
    const STATE_INIT = 'init';
    const STATE_START = 'start';
    const STATE_END = 'finish';

    protected $state;
    protected $appName = 'ZJPHP_APP';
    protected $appVersion = '';

    protected $genesis;
    protected $dependency = [
        'normal' => [],
        'singleton' => []
    ];
    protected $bootstrap = [
        'debugger',
        'session',
        'translation'
    ];

    private $_appConfigMtime = null;
    private $_maintainSetting = [];

    public function __construct(array $config = [])
    {
        $this->genesis = microtime(true);
        ZJPHP::$container = new Container();
        ZJPHP::$app = $this;
        $this->state = self::STATE_BEGIN;

        if ($config['from_cache'] === false) {
            $this->preInit($config);
        } else {
            unset($config['from_cache']);
        }
        unset($config['config_mtime']);
        
        parent::__construct($config);
    }

    public function init()
    {
        $this->bootstrap();
        // Create app monitor event object & trigger App initialization event

        $this->state = self::STATE_INIT;
        if (RUNTIME_ENV !== 'production') {
            $app_monitor_event = $this->buildAppMonitorEvent();
            $this->trigger(self::EVENT_INIT_APP, $app_monitor_event);
        }
    }

    public function preInit(&$config)
    {
        if (!isset($config['appName'])) {
            throw new InvalidConfigException('App Name is required.');
        }
        if (!isset($config['timeZone'])) {
            $config['timeZone'] = (ini_get('date.timezone')) ? : 'Asia/Hong_Kong';
        }

        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id] = ArrayHelper::merge($component, $config['components'][$id]);
            }
        }

        $apcu_enabled = function_exists('apcu_fetch');
        if ($apcu_enabled) {
            $cache_key = $config['cache_key'];
            unset($config['cache_key']);
            unset($config['from_cache']);
            apcu_store($cache_key, $config);
        }
    }

    public function coreComponents()
    {
        return [
            'session' => [
                 'class' => 'ZJPHP\\Service\\Session'
            ],
            'router' => [
                 'class' => 'ZJPHP\\Service\\Router',
                 'routeMap' => [],
                 'filters' => [],
                 'response' => 'ZJPHP\\Service\\Router\\Response',
                 'namespace' => ''
            ],
            'cache' => [
                'class' => 'ZJPHP\\Service\\Cache',
                'defaultDriver' => 'files',
                'fallbackDriver' => 'files',
                'disable' => false
            ],
            'viewer' => [
                'class' => 'ZJPHP\\Service\\Viewer',
                'tplDir' => [
                    'app' => SCRIPT_DIR . '/resource/tpl'
                ],
                'twigEnviroment' => [
                    'debug' => false,
                    'charset' => 'utf-8',
                    'cache' => false,
                    'autoescape' => true
                ]
            ],
            'db' => [
                 'class' => 'ZJPHP\\Service\\Database'
            ],
            'redis' => [
                 'class' => 'ZJPHP\\Service\\ZJRedis'
            ],
            'security' => [
                'class' => 'ZJPHP\\Service\\Security'
            ],
            'logger' => [
                'class' => 'ZJPHP\\Service\\Logger'
            ],
            'httpClient' => [
                'class' => 'ZJPHP\\Service\\HttpClient'
            ],
            'notifyCenter' => [
                'class' => 'ZJPHP\\Service\\NotifyCenter'
            ],
            'debugger' => [
                'class' => 'ZJPHP\\Service\\Debugger',
                'reportLevel' => E_ERROR | E_WARNING,
                'as ErrorLogger' => 'ZJPHP\\Service\\Behavior\\Debugger\\ErrorLogger'
            ],
            'validation' => [
                'class' => 'ZJPHP\\Service\\Validation'
            ],
            'cast' => [
                'class' => 'ZJPHP\\Service\\CastingMold',
                'as stringMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastString',
                'as numberMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastNumber',
                'as dateMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastDate',
                'as emptyMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastEmpty',
                'as boolMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastBool',
                'as fileMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastFile',
                'as translationMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastTranslation'
            ],
            'image' => [
                'class' => 'ZJPHP\\Service\\Image'
            ],
            'translation' => [
                'class' => 'ZJPHP\\Service\\Translation',
                'installedLocale' => 'zh-TW, zh-CN, en-US',
                'loader' => [
                    'file',
                    'ZJPHP\\Service\\Translation\\Loader\\PhpFileLoader'
                ],
                'defaultLocale' => 'en-US',
                'cacheDir' => RUNTIME_DIR . '/mp_api_v1/cache',
                'translator' => 'ZJPHP\\Service\\Translation\\ZJTranslator',
                'resources' => [
                    [
                        'file' => 'validation.php',
                        'domain' => 'validation'
                    ],
                    [
                        'file' => 'messages.php'
                    ]
                ]
            ]
        ];
    }

    public function run()
    {
        $this->state = self::STATE_START;
        // Go to real logic
        $this->handleRequest();
        // Finish
        $this->state = self::STATE_END;
        if (RUNTIME_ENV !== 'production') {
            $app_monitor_event = $this->buildAppMonitorEvent();
            $this->trigger(self::EVENT_END_APP, $app_monitor_event);
        }
    }

    abstract public function handleRequest();

    public function getDefaultRenderData()
    {
        return [];
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        return $this->state = $state;
    }

    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    public function getTimeZone()
    {
        return date_default_timezone_get();
    }

    public function setMaintainSetting($setting)
    {
        // File based maintain setting
        if (isset($setting['file']) && file_exists($setting['file'])) {
            $all_settings = json_decode(file_get_contents($setting['file']), true);
            $setting_id = (!empty($setting['app_name']))
                ? $setting['app_name']
                : (!empty($this->appName)
                    ? $this->appName
                    : null);
            if ($setting_id) {
                $this->_maintainSetting = $all_settings[$setting_id];
            }
        }

        // TBD Redis based maintain setting
    }

    public function getMaintainSetting()
    {
        return $this->_maintainSetting;
    }

    public function setEncoding($value)
    {
        mb_internal_encoding($value);
        mb_http_output($value);
    }

    public function getEncoding()
    {
        return mb_internal_encoding();
    }

    public function getLang()
    {
        return $this->get('translation')->getLocale();
    }

    public function getAppName()
    {
        return $this->appName;
    }

    public function setAppName($name)
    {
        return $this->appName = $name;
    }

    public function getAppVersion()
    {
        return $this->appVersion;
    }

    public function setAppVersion($version)
    {
        return $this->appVersion = $version;
    }

    public function setDependency($setting)
    {
        $this->dependency = ArrayHelper::merge($this->dependency, $setting);
        if (isset($this->dependency['normal'])) {
            foreach ($this->dependency['normal'] as $alias => $definition) {
                ZJPHP::$container->set($alias, $definition);
            }
        }
        if (isset($this->dependency['singleton'])) {
            foreach ($this->dependency['singleton'] as $alias => $definition) {
                ZJPHP::$container->setSingleton($alias, $definition);
            }
        }
    }

    public function getGenesis()
    {
        return $this->genesis;
    }

    public function setConfigMtime($mtime)
    {
        return $this->_appConfigMtime = $mtime;
    }

    public function getConfigMtime()
    {
        return $this->_appConfigMtime;
    }

    public function buildAppMonitorEvent()
    {
        $app_monitor_event = new CascadingEvent($this->appName, [
            'wall_time' => (microtime(true) - $this->genesis) * 1000,
            'memory_usage' => memory_get_usage() / 1024,
            'peak_memory' => memory_get_peak_usage() / 1024,
            'app_state' => $this->state
        ]);

        return $app_monitor_event;
    }

    protected function bootstrap()
    {
        foreach ($this->bootstrap as $class) {
            $component = null;
            if (is_string($class)) {
                if ($this->has($class)) {
                    $component = $this->get($class);
                } elseif (strpos($class, '\\') === false) {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                }
            }
            if (!isset($component)) {
                $component = ZJPHP::createObject($class);
            }

            if ($component instanceof BootstrapInterface) {
                $component->bootstrap();
            }
        }
    }
}
