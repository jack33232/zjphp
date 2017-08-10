<?php
namespace ZJPHP\Service\Behavior\Debugger;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\Debugger;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Base\Event;
use ReflectionClass;

class CliErrorNotify extends Behavior
{
    private $_notifyList = [];
    private $_smtp = '';

    protected $errorEmailTpl = "<p>DateTime: %s <br> <b>Fatal Error Happened!<b></p>"
        ."<p>[%s] %s <br> Error on line %s in file %s</p>";

    protected $warningEmailTpl = "<p>DateTime: %s <br> <b>Warning!<b></p>"
        ."<p>[%s] %s <br> Warning on line %s in file %s</p>";

    protected $exceptionEmailTpl = "<p>DateTime: %s <br> <b> %s exception: [%s] %s<b></p>"
        ."<p>Exception thrown on line %s in file %s</p>";

    protected $rtErrEmailTpl = "<p>DateTime: %s <br> <b> %s Runtime Error: [%s] %s<b></p>"
        ."<p>Error / Exception thrown on line %s in file %s</p>";

    protected $notifiableRtErr = [
        'code' => [],
        'type' => [
            'Error',
            'PDOException',
            'Illuminate\\Database\\QueryException',
            'ZJPHP\\Base\\Exception\\DatabaseErrorException',
            'ZJPHP\\Base\\Exception\\InvalidConfigException'
        ]
    ];

    public function events()
    {
        return [
            Debugger::EVENT_FATAL_ERROR_HAPPEN => 'notifyFatalError',
            Debugger::EVENT_WARNING_HAPPEN => 'notifyWarning',
            Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN => 'notifyUncaughtException',
            Debugger::EVENT_RUNTIME_ERROR_HAPPEN => 'notifyRuntimeError'
        ];
    }

    public function setNotifyList($list)
    {
        foreach ($list as $event_name => $notify_emails) {
            $this->_notifyList[$event_name] = $notify_emails;
        }
        return $this->_notifyList;
    }

    public function setSmtp($smtp)
    {
        return $this->_smtp = $smtp;
    }

    public function notifyFatalError(Event $event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_FATAL_ERROR_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->_smtp,
                'to' => $this->_notifyList[Debugger::EVENT_FATAL_ERROR_HAPPEN],
                'subject' => ZJPHP::$app->getAppName() .' ('. ZJPHP::$app->getAppVersion() . ') - !!Fatal Error Happen!!',
                'body' => sprintf(
                    $this->errorEmailTpl,
                    date('c'),
                    $event->payload->get('errno'),
                    $event->payload->get('errstr'),
                    $event->payload->get('errline'),
                    $event->payload->get('errfile')
                ),
                'priority' => 1
            ];
            $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
        }
    }

    public function notifyWarning(Event $event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_WARNING_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->_smtp,
                'to' => $this->_notifyList[Debugger::EVENT_WARNING_HAPPEN],
                'subject' => ZJPHP::$app->getAppName() .' ('. ZJPHP::$app->getAppVersion() . ') - Warning!',
                'body' => sprintf(
                    $this->warningEmailTpl,
                    date('c'),
                    $event->payload->get('errno'),
                    $event->payload->get('errstr'),
                    $event->payload->get('errline'),
                    $event->payload->get('errfile')
                ),
                'priority' => 3
            ];

            $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
        }
    }

    public function notifyUncaughtException(Event $event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $exception = $event->payload->get('exception');

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->_smtp,
                'to' => $this->_notifyList[Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN],
                'subject' => ZJPHP::$app->getAppName() .' ('. ZJPHP::$app->getAppVersion() . ') - Uncaught Exception!',
                'body' => sprintf(
                    $this->exceptionEmailTpl,
                    date('c'),
                    'Uncaught',
                    $exception->getCode(),
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile()
                ),
                'priority' => 1
            ];
            $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
        }
    }

    public function notifyRuntimeError(Event $event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_RUNTIME_ERROR_HAPPEN])) {
            $error = $event->payload->get('error');
            $error_reflection = new ReflectionClass($error);
            $error_code = $error->getCode();
            $error_type = $error_reflection->getName();

            if (in_array($error_code, $this->notifiableRtErr['code'])
                || in_array($error_type, $this->notifiableRtErr['type'])
            ) {
                $notifyCenter = ZJPHP::$app->get('notifyCenter');

                $params = [
                    'bindingKey' => 'debug',
                    'smtp' => $this->_smtp,
                    'to' => $this->_notifyList[Debugger::EVENT_RUNTIME_ERROR_HAPPEN],
                    'subject' => ZJPHP::$app->getAppName() .' ('. ZJPHP::$app->getAppVersion() . ') - Runtime Exception!',
                    'body' => sprintf(
                        $this->rtErrEmailTpl,
                        date('c'),
                        'Runtime',
                        $error->getCode(),
                        $error->getMessage(),
                        $error->getLine(),
                        $error->getFile()
                    ),
                    'priority' => 1
                ];
                $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
                $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
            }
        }
    }

    public function setNotifiableRtErr($setting)
    {
        $this->notifiableRtErr = [
            'code' => [],
            'type' => []
        ];
        foreach ($setting as $errorCodeOrType) {
            if (is_numeric($errorCodeOrType)) {
                $this->notifiableRtErr['code'][] = intval($errorCodeOrType);
            } elseif (is_string($errorCodeOrType)) {
                $this->notifiableRtErr['type'][] = ltrim($errorCodeOrType, "\\");
            }
        }
    }
}
