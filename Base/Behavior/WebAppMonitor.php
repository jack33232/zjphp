<?php
namespace ZJPHP\Base\Behavior;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Application;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Base\CascadingEvent;

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

    public function doMonitor(CascadingEvent $app_monitor_event)
    {
        // Begin cascading
        if ($app_monitor_event->name === Application::EVENT_INIT_APP) {
            $app_monitor_event->beginCascading();
        }
        // Handle event
        $this->saveToDb($app_monitor_event);
        if (RUNTIME_ENV === 'dev') {
            $this->blastConsoleEvent($app_monitor_event);
        }
        // End cascading
        if ($app_monitor_event->name === Application::EVENT_END_APP) {
            $app_monitor_event->endCascading();
        }
    }

    protected function blastConsoleEvent($app_monitor_event)
    {
        // Notify Center Service
        $notify_center = ZJPHP::$app->get('notifyCenter');
        // Payload data
        $trace_id = $app_monitor_event->getTraceId();
        $wall_time = $app_monitor_event->payload->get('wall_time');
        $app_state = $app_monitor_event->payload->get('app_state');
        $memory_usage = $app_monitor_event->payload->get('memory_usage');
        $peak_memory = $app_monitor_event->payload->get('peak_memory');

        $data = [
            $app_state,
            ($wall_time > 1000) ? sprintf('%.3fs', $wall_time / 1000) : sprintf('%.1fms', $wall_time),
            ($memory_usage > 1024) ? sprintf('%.3fMB', $memory_usage / 1024) : sprintf('%.3fKB', $memory_usage),
            ($peak_memory > 1024) ? sprintf('%.3fMB', $peak_memory / 1024) : sprintf('%.3fKB', $peak_memory)
        ];

        // Get Console Event
        $php_console_event = $notify_center->buildBlastPHPConsoleEvent($data, $trace_id);
        $notify_center->trigger(NotifyCenter::EVENT_BLAST_PHP_CONSOLE, $php_console_event);
    }

    protected function saveToDb($app_monitor_event)
    {
        // DB Service
        $db = ZJPHP::$app->get('db');
        // Payload data
        $trace_id = $app_monitor_event->getTraceId();
        $wall_time = $app_monitor_event->payload->get('wall_time');
        $app_state = $app_monitor_event->payload->get('app_state');
        $memory_usage = $app_monitor_event->payload->get('memory_usage');
        $peak_memory = $app_monitor_event->payload->get('peak_memory');
        // Save
        $db->table(static::TABLE_NAME)->insert([
            'trace_id' => $app_monitor_event->getTraceId(),
            'wall_time' => $app_monitor_event->payload->get('wall_time'),
            'memory_usage' => $app_monitor_event->payload->get('memory_usage'),
            'peak_memory' => $app_monitor_event->payload->get('peak_memory'),
            'app_state' => $app_monitor_event->payload->get('app_state'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
