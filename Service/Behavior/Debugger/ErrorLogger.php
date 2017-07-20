<?php
namespace ZJPHP\Service\Behavior\Debugger;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\Debugger;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Base\Event;

class ErrorLogger extends Behavior
{
    public function events()
    {
        return [
            Debugger::EVENT_FATAL_ERROR_HAPPEN => 'logFatalError',
            Debugger::EVENT_WARNING_HAPPEN => 'logWarning',
            Debugger::EVENT_NOTICE_HAPPEN => 'logNotice',
            Debugger::EVENT_UNCAUGHT_EXCEPTION_HAPPEN => 'logUncaughtException',
            Debugger::EVENT_RUNTIME_ERROR_HAPPEN => 'logRuntimeError',
            Debugger::EVENT_RUNTIME_HTTP_ERROR_HAPPEN => 'logRuntimeHttpError'
        ];
    }

    public function logFatalError(Event $event)
    {
        $logger = ZJPHP::$app->get('logger');

        $msg_tpl = "[%s] %s \n Error on line %s in file %s";
        $msg = sprintf(
            $msg_tpl,
            $event->payload->get('errno'),
            $event->payload->get('errstr'),
            $event->payload->get('errline'),
            $event->payload->get('errfile')
        );
        $logger->notice($msg);
    }

    public function logWarning(Event $event)
    {
        $logger = ZJPHP::$app->get('logger');

        $msg_tpl = "[%s] %s \n Warning on line %s in file %s";
        $msg = sprintf(
            $msg_tpl,
            $event->payload->get('errno'),
            $event->payload->get('errstr'),
            $event->payload->get('errline'),
            $event->payload->get('errfile')
        );
        $logger->notice($msg);
    }

    public function logNotice(Event $event)
    {
        $logger = ZJPHP::$app->get('logger');

        $msg_tpl = "[%s] %s \n Notice on line %s in file %s";
        $msg = sprintf(
            $msg_tpl,
            $event->payload->get('errno'),
            $event->payload->get('errstr'),
            $event->payload->get('errline'),
            $event->payload->get('errfile')
        );
        $logger->notice($msg);
    }

    public function logOtherError(Event $event)
    {
        $logger = ZJPHP::$app->get('logger');

        $msg_tpl = "[%s] %s \n Unknown on line %s in file %s";
        $msg = sprintf(
            $msg_tpl,
            $event->payload->get('errno'),
            $event->payload->get('errstr'),
            $event->payload->get('errline'),
            $event->payload->get('errfile')
        );
        $logger->info($msg);
    }

    public function logUncaughtException(Event $event)
    {
        $logger = ZJPHP::$app->get('logger');
        $exception = $event->payload->get('exception');
        $msg = "Uncaught exception: " . $exception->getMessage();

        $log_context = [
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        $logger->error($msg, $log_context);
    }

    public function logRuntimeError(Event $event)
    {
        $logger = ZJPHP::$app->get('logger');

        $error = $event->payload->get('error');
        $msg = "Runtime exception: " . $error->getMessage();

        $log_context = [
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine()
        ];
        $logger->notice($msg, $log_context);
    }

    public function logRuntimeHttpError(Event $event)
    {
        $router_service = ZJPHP::$app->get('router');
        $logger = ZJPHP::$app->get('logger');

        $code = $event->payload->get('code');
        $log_context = [
            'code' => $code,
            'uri' => $router_service->uri(),
            'ip' => $router_service->ip()
        ];

        $logger->notice('Http Error Happen.', $log_context);
    }
}
