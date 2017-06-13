<?php
namespace ZJPHP\Base;

use League\CLImate\CLImate;
use ZJPHP\Base\Exception\InvalidCallException;
use ZJPHP\Service\Debugger;
use ReflectionClass;
use Exception;
use Throwable;

class CliApplication extends Application
{
    protected $errorsForNotice = [
        'code' => [],
        'type' => [
            'Error',
            'PDOException',
            'Illuminate\\Database\\QueryException',
            'ZJPHP\\Base\\Exception\\DatabaseErrorException',
            'ZJPHP\\Base\\Exception\\InvalidConfigException'
        ]
    ];

    protected $bootstrap = [
        'debugger',
        'translation'
    ];

    protected $dependency = [
        'normal' => [
            'AppMonitorEvent' => [
                'class' => 'ZJPHP\\Base\\Event\\CliAppMonitor'
            ],
            'NotifySendEmailEvent' => [
                'class' => 'ZJPHP\\Service\\Event\\SendEmail'
            ],
            'NotifyQueueEmailEvent' => [
                'class' => 'ZJPHP\\Service\\Event\\QueueEmail'
            ]
        ]
    ];


    public function coreComponents()
    {
        return [
            'cache' => [
                'class' => 'ZJPHP\\Service\\Cache',
                'defaultDriver' => 'files',
                'fallbackDriver' => 'files'
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
                'reportLevel' => E_ERROR | E_WARNING
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

    public function handleRequest()
    {
        $climate = new CLImate();
        // Controller & Action
        $climate->arguments->add([
            'controller' => [
                'prefix' => 'c',
                'longPrefix' => 'controller',
                'description' => 'Controller represents the business logic domain.',
                'required' => true,
                'castTo' => 'string'
            ],
            'action' => [
                'prefix' => 'a',
                'longPrefix' => 'action',
                'description' => 'Action represents a specific business logic action.',
                'defaultValue' => 'run',
                'castTo' => 'string'
            ]
        ]);

        try {
            $climate->arguments->parse();
            $controller_str = $climate->arguments->get('controller');
            $controller = ZJPHP::createObject($controller_str);
            $action = $climate->arguments->get('action');

            if (method_exists($controller, $action)) {
                $controller->$action($climate);
            } else {
                throw new InvalidCallException('No such action:' . $action . ' in controller:' . $controller_str, 400);
            }
        } catch (Exception $e) {
            $this->error($climate, $e);
        } catch (Throwable $e) {
            $this->error($climate, $e);
        }
    }

    protected function error($climate, $e)
    {
        // Check notify or not
        $error_reflection = new ReflectionClass($e);
        $error_code = $e->getCode();
        $error_type = ltrim($error_reflection->getName(), '\\');

        if ($error_type === 'Exception') {
            $climate->description($this->getAppName());
            $climate->usage();
            $climate->border();
            $climate->br()->out($e->getMessage());
        }

        if (in_array($error_code, $this->errorsForNotice['code'])
            || in_array($error_type, $this->errorsForNotice['type'])
        ) {
            $debugger = $this->get('debugger');
            // Trigger event
            $exception_event = ZJPHP::createObject(['class' => 'ZJPHP\\Base\\Event', 'sender' => $e]);
            $debugger->trigger(Debugger::EVENT_RUNTIME_EXCEPTION_HAPPEN, $exception_event);
        } else {
            throw $e;
        }
    }

    public function setErrorsForNotice($setting)
    {
        $this->errorsForNotice = [
            'code' => [],
            'type' => []
        ];
        foreach ($setting as $errorCodeOrType) {
            if (is_numeric($errorCodeOrType)) {
                $this->errorsForNotice['code'][] = intval($errorCodeOrType);
            } elseif (is_string($errorCodeOrType)) {
                $this->errorsForNotice['type'][] = ltrim($errorCodeOrType, "\\");
            }
        }
    }

    public function getErrorsForNotice($code_or_type = 'both')
    {
        switch ($code_or_type) {
            case 'code':
                return $this->errorsForNotice['code'];
                break;
            case 'type':
                return $this->errorsForNotice['type'];
                break;
            default:
                return $this->errorsForNotice;
                break;
        }
    }
}
