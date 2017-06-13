<?php
namespace ZJPHP\Base\Event;

use ZJPHP\Base\Event;
use ZJPHP\Base\ZJPHP;

class WebAppMonitor extends Event
{
    public static $traceId;
    public $timestamp;
    public $appState;
    public $serverStatus;
    public $request;
    public $response;

    public function init()
    {
        parent::init();

        if (empty(static::$traceId)) {
            $security = ZJPHP::$app->get('security');
            static::$traceId = $security->generateRandomString();
        }
        $router = ZJPHP::$app->get('router');
        $request = $router->getRequest();
        $response = $router->getResponse();
        $this->request = ['params' => $request->params(), 'uri' => $request->uri()];
        $this->response = ['headers' => $response->headers(), 'body' => $response->body()];
        $this->setTimestamp();
        $this->setServerStatus();
        $this->appState = ZJPHP::$app->getState();
    }

    public function getTraceId()
    {
        return static::$traceId;
    }

    public function setTimestamp()
    {
        list($microseconds, $unix_timestamp) = explode(' ', microtime());
        $this->timestamp = $unix_timestamp + $microseconds;
    }

    public function setServerStatus()
    {
        return $this->serverStatus = [
            'memory' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
            'unit' => 'byte'
        ];
    }
}
