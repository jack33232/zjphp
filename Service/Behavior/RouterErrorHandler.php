<?php
namespace ZJPHP\Service\Behavior;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Event;
use ZJPHP\Service\Router;

class RouterErrorHandler extends Behavior
{
    public function events()
    {
        return [
            Router::EVENT_APP_ERROR_HAPPEN => 'doErrorHandler',
            Router::EVENT_APP_HTTP_ERROR_HAPPEN => 'doHTTPErrorHandler'
        ];
    }

    public function doErrorHandler(Event $event)
    {
        $error = $event->err;
        $logger = ZJPHP::$app->get('logger');
        $msg = "Runtime exception: " . $error->getMessage();

        $log_context = [
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine()
        ];
        $logger->notice($msg, $log_context);
    }

    public function doHTTPErrorHandler(Event $event)
    {
        $code = $event->code;
        $router = $event->sender;
        $logger = ZJPHP::$app->get('logger');

        $log_context = [
            'code' => $code,
            'uri' => $router->request()->uri(),
            'ip' => $router->request()->ip(),
            'params' => $router->request()->params()
        ];

        $logger->notice('Http Error Happen.', $log_context);
    }
}
