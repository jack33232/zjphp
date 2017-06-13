<?php
namespace ZJPHP\Base\Behavior;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Application;
use ZJPHP\Service\NotifyCenter;

class WebAppMonitor extends Behavior
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
        $notifyCenter = ZJPHP::$app->get('notifyCenter');
        $db = ZJPHP::$app->get('db');
        $trace_id = $app_monitor_event->getTraceId();

        $data = [
            $app_monitor_event->timestamp,
            $app_monitor_event->appState,
            number_format($app_monitor_event->serverStatus['memory'] / 1024, 3). 'KB',
            number_format($app_monitor_event->serverStatus['peak_memory'] / 1024, 3). 'KB'
        ];
        

        $blastPHPConsoleEvent = $notifyCenter->buildBlastPHPConsoleEvent($data, $trace_id);
        $notifyCenter->trigger(NotifyCenter::EVENT_BLAST_PHP_CONSOLE, $blastPHPConsoleEvent);

        unset($data);
        // Save into DB
        $db->table(static::TABLE_NAME)->insert([
            'trace_id' => $trace_id,
            'timestamp' => $app_monitor_event->timestamp,
            'memory_usage' => $app_monitor_event->serverStatus['memory'],
            'peak_memory' => $app_monitor_event->serverStatus['peak_memory'],
            'app_state' => $app_monitor_event->appState,
            'request' => json_encode($app_monitor_event->request),
            'response' => json_encode($app_monitor_event->response),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
