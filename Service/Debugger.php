<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Event;
use ZJPHP\Base\Component;
use ZJPHP\Base\BootstrapInterface;
use ZJPHP\Base\Kit\ArrayHelper;

class Debugger extends Component implements BootstrapInterface
{
    const EVENT_FATAL_ERROR_HAPPEN = 'fatalErrorHappen';
    const EVENT_WARNING_HAPPEN = 'warningHappen';
    const EVENT_NOTICE_HAPPEN = 'noticeHappen';
    const EVENT_OTHER_ERROR_HAPPEN = 'otherErrorHappen';
    const EVENT_UNCAUGHT_EXCEPTION_HAPPEN = 'uncaughtExpcetionHappen';
    const EVENT_RUNTIME_ERROR_HAPPEN = 'runtimeErrorHappen';
    const EVENT_RUNTIME_HTTP_ERROR_HAPPEN = 'runtimeHttpErrorHappen';

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

    public function errorHandler($errno, $errstr, $errfile = '', $errline = 0, $errcontext = [])
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting, so let it fall
            // through to the standard PHP error handler
            return false;
        }
        $trace = debug_backtrace();

        $payload = [
            'errno' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
            'errtrace' => print_r($trace, true),
            'errcontext' => print_r($errcontext, true)
        ];
        $event = new Event($payload);

        switch ($errno) {
            case E_ERROR:
            case E_PARSE:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_CORE_ERROR:
                // Trigger event
                $this->trigger(self::EVENT_FATAL_ERROR_HAPPEN, $event);
                exit(1);
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $this->trigger(self::EVENT_WARNING_HAPPEN, $event);
                break;
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
            case E_NOTICE:
            case E_USER_NOTICE:
                // Trigger event
                $this->trigger(self::EVENT_NOTICE_HAPPEN, $event);
                break;

            default:
                $this->trigger(self::EVENT_OTHER_ERROR_HAPPEN, $event);
                break;
        }

        /* Don't execute PHP internal error handler */
        return true;
    }

    public function exceptionHandler($exception)
    {
        $payload = ['exception' => $exception];
        // Trigger event
        $exception_event = new Event($payload);
        $this->trigger(self::EVENT_UNCAUGHT_EXCEPTION_HAPPEN, $exception_event);

        $sapi_type = php_sapi_name();
        if (substr($sapi_type, 0, 3) == 'cli') {
            // Send interupt signal if posix ext loaded
            if (extension_loaded('posix')) {
                $pid = posix_getpid();
                $ppid = posix_getppid();
                if ($ppid) {
                    posix_kill($ppid, SIGINT);
                } else {
                    posix_kill($pid, SIGINT);
                }
            }
        } else {
            exit(1);
        }
    }

    public function setReportLevel($level)
    {
        error_reporting($level);
    }
}
