<?php
namespace ZJPHP\Service\Behavior\Debugger;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\Debugger;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Base\Event;
use ZJPHP\Service\Behavior\Debugger\CliErrorNotify;
use ReflectionClass;

class CliDirectErrorNotify extends CliErrorNotify
{
    public function notifyFatalError(Event $event)
    {
        if (!empty($this->notifyList[Debugger::EVENT_FATAL_ERROR_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $params = [
                'smtp' => $this->smtp,
                'to' => $this->notifyList[Debugger::EVENT_FATAL_ERROR_HAPPEN],
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
            $send_email_event = $notifyCenter->buildSendEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_SEND_EMAIL, $send_email_event);
        }
    }

    public function notifyWarning(Event $event)
    {
        if (!empty($this->notifyList[Debugger::EVENT_WARNING_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->smtp,
                'to' => $this->notifyList[Debugger::EVENT_WARNING_HAPPEN],
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

            $send_email_event = $notifyCenter->buildSendEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_SEND_EMAIL, $send_email_event);
        }
    }

    public function notifyUncaughtException(Event $event)
    {
        if (!empty($this->notifyList[Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $exception = $event->payload->get('exception');

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->smtp,
                'to' => $this->notifyList[Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN],
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
            $send_email_event = $notifyCenter->buildSendEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_SEND_EMAIL, $send_email_event);
        }
    }

    public function notifyRuntimeError(Event $event)
    {
        if (!empty($this->notifyList[Debugger::EVENT_RUNTIME_ERROR_HAPPEN])) {
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
                    'smtp' => $this->smtp,
                    'to' => $this->notifyList[Debugger::EVENT_RUNTIME_ERROR_HAPPEN],
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
                $send_email_event = $notifyCenter->buildSendEmailEvent($params, false);
                $notifyCenter->trigger(NotifyCenter::EVENT_SEND_EMAIL, $send_email_event);
            }
        }
    }
}
