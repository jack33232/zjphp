<?php
namespace ZJPHP\Base\Behavior;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Application;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Base\CascadingEvent;

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

    public function doMonitor(CascadingEvent $app_monitor_event)
    {
        // Begin cascading
        if ($app_monitor_event->name === Application::EVENT_INIT_APP) {
            $app_monitor_event->beginCascading();
        }
        // Handle event
        $this->saveToDb($app_monitor_event);

        // End cascading
        if ($app_monitor_event->name === Application::EVENT_END_APP) {
            $app_monitor_event->endCascading();
        }
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
