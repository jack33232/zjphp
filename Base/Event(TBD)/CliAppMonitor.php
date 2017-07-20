<?php
namespace ZJPHP\Base\Event;

use ZJPHP\Base\Event;
use ZJPHP\Base\ZJPHP;

class CliAppMonitor extends Event
{
    public static $traceId;
    public $timestamp;
    public $appState;
    public $serverStatus;

    public function init()
    {
        parent::init();

        if (empty(static::$traceId)) {
            $security = ZJPHP::$app->get('security');
            static::$traceId = $security->generateRandomString();
        }
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
