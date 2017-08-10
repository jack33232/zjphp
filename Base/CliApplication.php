<?php
namespace ZJPHP\Base;

use ZJPHP\Base\Exception\InvalidCallException;
use ZJPHP\Service\Debugger;
use ZJPHP\Base\CascadingEvent;
use ZJPHP\Base\Event;
use ReflectionClass;
use Exception;
use Throwable;

abstract class CliApplication extends Application
{
    protected $appName = 'ZJPHP_CLI_APP';

    protected $bootstrap = [
        'debugger',
        'translation'
    ];

    protected $dependency = [
        'normal' => [],
        'singleton' => []
    ];

    public function coreComponents()
    {
        return [
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
                'as fileMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastFile',
                'as translationMold' => 'ZJPHP\\Service\\Behavior\\CastingMold\\CastTranslation'
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

    public function run ()
    {
        $this->state = self::STATE_START;
        $this->genesis = microtime(true);
        // Go to real logic
        try {
            $args = func_get_args();
            call_user_func_array([$this, 'handleRequest'], $args);
        } catch (Exception $err) {
            $payload = [
                'error' => $err
            ];
            $event = new Event($payload);
            $debugger = $this->get('debugger');
            $debugger->trigger(Debugger::EVENT_RUNTIME_ERROR_HAPPEN, $event);
        } catch (Throwable $err) {
            $payload = [
                'error' => $err
            ];
            $event = new Event($payload);
            $debugger = $this->get('debugger');
            $debugger->trigger(Debugger::EVENT_RUNTIME_ERROR_HAPPEN, $event);
        }
        // Finish
        $this->state = self::STATE_END;
        if (RUNTIME_ENV !== 'production') {
            $app_monitor_event = $this->buildAppMonitorEvent();
            $this->trigger(self::EVENT_END_APP, $app_monitor_event);
        }
    }
}
