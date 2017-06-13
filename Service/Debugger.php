<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\BootstrapInterface;
use ZJPHP\Base\Kit\ArrayHelper;

class Debugger extends Component implements BootstrapInterface
{
    const EVENT_FATAL_ERROR_HAPPEN = 'fatalErrorHappen';
    const EVENT_WARNING_HAPPEN = 'warningHappen';
    const EVENT_NOTICE_HAPPEN = 'noticeHappen';
    const EVENT_UNCAUGHT_EXCEPTION_HAPPEN = 'uncaughtExpcetionHappen';
    const EVENT_RUNTIME_EXCEPTION_HAPPEN = 'runtimeExceptionHappen';

    const EVENT_DEBUGGER_BOOTUP = 'debuggerBootup';

    public function bootstrap()
    {
        // Default Error & Exception Handler
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
        register_shutdown_function([$this, 'checkFatal']);
        // Trigger bootup event
        $this->trigger(static::EVENT_DEBUGGER_BOOTUP);
    }

    public function checkFatal()
    {
        $error = error_get_last();
        if (!is_null($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR])) {
            $this->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $logger = ZJPHP::$app->get('logger');

        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }

        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
                $msg_tpl = "[%s] %s \n Error on line %s in file %s";
                $msg = sprintf($msg_tpl, $errno, $errstr, $errline, $errfile);
                $logger->error($msg);

                // Trigger event
                $event_sender = (object)[
                    'errno' => $errno,
                    'errstr' => $errstr,
                    'errfile' => $errfile,
                    'errline' => $errline
                ];

                $fatal_error_event = ZJPHP::createObject(['class' => 'ZJPHP\\Base\\Event', 'sender' => $event_sender]);
                $this->trigger(self::EVENT_FATAL_ERROR_HAPPEN, $fatal_error_event);
                exit(1);
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $msg_tpl = "[%s] %s \n Warning on line %s in file %s";
                $msg = sprintf($msg_tpl, $errno, $errstr, $errline, $errfile);
                $logger->warning($msg);

                // Trigger event
                $event_sender = (object)[
                    'errno' => $errno,
                    'errstr' => $errstr,
                    'errfile' => $errfile,
                    'errline' => $errline
                ];
                $warning_event = ZJPHP::createObject(['class' => 'ZJPHP\\Base\\Event', 'sender' => $event_sender]);
                $this->trigger(self::EVENT_WARNING_HAPPEN, $warning_event);
                break;
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_NOTICE:
            case E_USER_NOTICE:
                $msg_tpl = "[%s] %s \n Notice on line %s in file %s";
                $msg = sprintf($msg_tpl, $errno, $errstr, $errline, $errfile);
                $logger->notice($msg);

                // Trigger event
                $event_sender = (object)[
                    'errno' => $errno,
                    'errstr' => $errstr,
                    'errfile' => $errfile,
                    'errline' => $errline
                ];
                $notice_event = ZJPHP::createObject(['class' => 'ZJPHP\\Base\\Event', 'sender' => $event_sender]);
                $this->trigger(self::EVENT_NOTICE_HAPPEN, $notice_event);
                break;

            default:
                $msg_tpl = "[%s] %s \n Unknown on line %s in file %s";
                $msg = sprintf($msg_tpl, $errno, $errstr, $errline, $errfile);
                $logger->info($msg);
                break;
        }

        /* Don't execute PHP internal error handler */
        return true;
    }

    public function exceptionHandler($exception)
    {
        $logger = ZJPHP::$app->get('logger');
        $msg = "Uncaught exception: " . $exception->getMessage();

        $exception_detail = [
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        $logger->error($msg, $exception_detail);

        // Trigger event
        $exception_event = ZJPHP::createObject(['class' => 'ZJPHP\\Base\\Event', 'sender' => $exception]);
        $this->trigger(self::EVENT_UNCAUGHT_EXCEPTION_HAPPEN, $exception_event);
        exit(1);
    }

    public function setReportLevel($level)
    {
        error_reporting($level);
    }
}
