<?php
namespace ZJPHP\Base\Behavior;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Application;
use ZJPHP\Service\NotifyCenter;

class CliAppMonitor extends Behavior
{
    const TABLE_NAME = 'app_monitor_log';

    public function events()
    {
        return [
            Application::EVENT_INIT_APP => 'doMonitor',
            Application::EVENT_END_APP => 'doMonitor'
        ];
    }

    public function doMonitor($app_monitor_event)
    {
        global $argv;
        $db = ZJPHP::$app->get('db');
        $trace_id = $app_monitor_event->getTraceId();

        // Save into DB
        $db->table(static::TABLE_NAME)->insert([
            'trace_id' => $trace_id,
            'timestamp' => $app_monitor_event->timestamp,
            'memory_usage' => $app_monitor_event->serverStatus['memory'],
            'peak_memory' => $app_monitor_event->serverStatus['peak_memory'],
            'app_state' => $app_monitor_event->appState,
            'request' => 'cli request:' . json_encode($argv, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'response' => null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
