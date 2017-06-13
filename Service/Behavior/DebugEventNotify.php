<?php
namespace ZJPHP\Service\Behavior;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\Debugger;
use ZJPHP\Service\NotifyCenter;

class DebugEventNotify extends Behavior
{
    private $_notifyList = [];
    private $_smtp = '';

    protected $errorEmailTpl = "<p>DateTime: %s <br> <b>Fatal Error Happened!<b></p>"
        ."<p>[%s] %s <br> Error on line %s in file %s</p><p>Request From: %s<br>Request URI: %s<br>Request Params: <br><pre>%s</pre><br></p>";

    protected $warningEmailTpl = "<p>DateTime: %s <br> <b>Warning!<b></p>"
        ."<p>[%s] %s <br> Warning on line %s in file %s</p><p>Request From: %s<br>Request URI: %s<br>Request Params: <br><pre>%s</pre><br></p>";

    protected $exceptionEmailTpl = "<p>DateTime: %s <br> <b> %s exception: [%s] %s<b></p>"
        ."<p>Exception thrown on line %s in file %s</p><p>Request From: %s<br>Request URI: %s<br>Request Params: <br><pre>%s</pre><br></p>";

    public function events()
    {
        return [
            Debugger::EVENT_FATAL_ERROR_HAPPEN => 'notifyFatalError',
            Debugger::EVENT_WARNING_HAPPEN => 'notifyWarning',
            Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN => 'notifyUncaughtException',
            Debugger::EVENT_RUNTIME_EXCEPTION_HAPPEN => 'notifyRuntimeException'
        ];
    }

    public function setNotifyList($list)
    {
        foreach ($list as $event_name => $notify_emails) {
            foreach ($notify_emails as $email) {
                if (is_string($email)) {
                    $this->_notifyList[$event_name][] = ['email' => $email, 'name' => ''];
                } elseif (is_array($email)) {
                    $this->_notifyList[$event_name][] = ['email' => $email[0], 'name' => $email[1]];
                }
            }
        }
        return $this->_notifyList;
    }

    public function setSmtp($smtp)
    {
        return $this->_smtp = $smtp;
    }

    public function notifyFatalError($event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_FATAL_ERROR_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $error_data = $event->sender;

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->_smtp,
                'to' => $this->_notifyList[Debugger::EVENT_FATAL_ERROR_HAPPEN],
                'subject' => ZJPHP::$app->getAppName() .' ('. ZJPHP::$app->getAppVersion() . ') - !!Fatal Error Happen!!',
                'body' => sprintf(
                    $this->errorEmailTpl,
                    date('c'),
                    $error_data->errno,
                    $error_data->errstr,
                    $error_data->errline,
                    $error_data->errfile,
                    print_r($_SERVER['REMOTE_ADDR'], true),
                    print_r($_SERVER['REQUEST_URI'], true),
                    print_r($_REQUEST, true)
                ),
                'priority' => 1
            ];
            $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
        }
    }

    public function notifyWarning($event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_WARNING_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $warning_data = $event->sender;

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->_smtp,
                'to' => $this->_notifyList[Debugger::EVENT_WARNING_HAPPEN],
                'subject' => ZJPHP::$app->getAppName() .' ('. ZJPHP::$app->getAppVersion() . ') - Warning!',
                'body' => sprintf(
                    $this->warningEmailTpl,
                    date('c'),
                    $warning_data->errno,
                    $warning_data->errstr,
                    $warning_data->errline,
                    $warning_data->errfile,
                    print_r($_SERVER['REMOTE_ADDR'], true),
                    print_r($_SERVER['REQUEST_URI'], true),
                    print_r($_REQUEST, true)
                ),
                'priority' => 3
            ];

            $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
        }
    }

    public function notifyUncaughtException($event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $exception = $event->sender;

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
                    $exception->getFile(),
                    print_r($_SERVER['REMOTE_ADDR'], true),
                    print_r($_SERVER['REQUEST_URI'], true),
                    print_r($_REQUEST, true)
                ),
                'priority' => 1
            ];
            $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
        }
    }

    public function notifyRuntimeException($event)
    {
        if (!empty($this->_notifyList[Debugger::EVENT_RUNTIME_EXCEPTION_HAPPEN])) {
            $notifyCenter = ZJPHP::$app->get('notifyCenter');

            $exception = $event->sender;

            $params = [
                'bindingKey' => 'debug',
                'smtp' => $this->_smtp,
                'to' => $this->_notifyList[Debugger::EVENT_RUNTIME_EXCEPTION_HAPPEN],
                'subject' => ZJPHP::$app->getAppName() .' ('. ZJPHP::$app->getAppVersion() . ') - Runtime Exception!',
                'body' => sprintf(
                    $this->exceptionEmailTpl,
                    date('c'),
                    'Runtime',
                    $exception->getCode(),
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile(),
                    print_r($_SERVER['REMOTE_ADDR'], true),
                    print_r($_SERVER['REQUEST_URI'], true),
                    print_r($_REQUEST, true)
                ),
                'priority' => 1
            ];
            $queue_email_event = $notifyCenter->buildQueueEmailEvent($params, false);
            $notifyCenter->trigger(NotifyCenter::EVENT_QUEUE_EMAIL, $queue_email_event);
        }
    }
}
