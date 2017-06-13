<?php
namespace ZJPHP\Service\Behavior\NotifyCenter;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Service\NotifyCenter;

class EmailDBQueue extends Behavior
{
    const TABLE_NAME = 'notify_email_queue';

    public function events()
    {
        return [
            NotifyCenter::EVENT_QUEUE_EMAIL => 'queueEmail'
        ];
    }

    public function queueEmail($queue_email_event)
    {
        $db = ZJPHP::$app->get('db');
        $db->table(static::TABLE_NAME)->insert([
            'binding_key' => $queue_email_event->bindingKey,
            'send_email_event' => serialize($queue_email_event->sendEmailEvent),
            'body' => $queue_email_event->body,
            'body_tpl' => $queue_email_event->bodyTpl,
            'render_data' => !empty($queue_email_event->renderData) ? serialize($queue_email_event->renderData) : null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'scheduled_at' => $queue_email_event->scheduledAt
        ]);

        $queue_email_event->handled = true;
    }
}
