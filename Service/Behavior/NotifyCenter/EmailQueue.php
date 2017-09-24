<?php
namespace ZJPHP\Service\Behavior\NotifyCenter;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Facade\MQSender;
use ZJPHP\Facade\Viewer;

class EmailQueue extends Behavior
{
    protected $connection = 'default';
    protected $handlerMap = [
        'default' => 'mq',
        'debug' => 'mq'
    ];
    protected $notifyCenterExchange = null; // For MQ to use

    public function events()
    {
        return [
            NotifyCenter::EVENT_QUEUE_EMAIL => 'queueEmail'
        ];
    }

    public function queueEmail($queue_email_event)
    {
        $binding_key = $queue_email_event->payload->get('bindingKey');
        $handler = $this->handlerMap[$binding_key] ?? 'mq';
        switch ($handler) {
            case 'mq':
                $this->mqQueueEmail($queue_email_event);
                break;
            
            case 'db':
                $this->dbQueueEmail($queue_email_event);
                break;
        }
    }

    protected function mqQueueEmail($queue_email_event)
    {
        $send_email_event = $queue_email_event->payload->get('sendEmailEvent');
        $body = $queue_email_event->payload->get('body');
        $body_tpl = $queue_email_event->payload->get('bodyTpl');
        $render_data = $queue_email_event->payload->get('renderData');
        if (empty($body) && !empty($body_tpl)) {
            $body = Viewer::render($body_tpl, $render_data);
        }

        $send_email_event->payload->set('body', $body);
        $scheduled_at = $queue_email_event->payload->get('scheduledAt');
        $routing_key = $queue_email_event->payload->get('bindingKey') . '.email';

        $message = [
            'action' => 'send', // action name
            'args' => [
                0 => serialize($send_email_event)
            ]
        ];

        MQSender::scheduledBlast($this->notifyCenterExchange, $routing_key, $message, $scheduled_at);
        $queue_email_event->handled = true;
    }

    protected function dbQueueEmail($queue_email_event)
    {
        $db = ZJPHP::$app->get('db');
        $db->table('notify_email_queue', $this->connection)->insert([
            'binding_key' => $queue_email_event->payload->get('bindingKey'),
            'send_email_event' => serialize($queue_email_event->payload->get('sendEmailEvent')),
            'body' => $queue_email_event->payload->get('body'),
            'body_tpl' => $queue_email_event->payload->get('bodyTpl'),
            'render_data' => !empty($queue_email_event->payload->get('renderData')) ? serialize($queue_email_event->payload->get('renderData')) : null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'scheduled_at' => $queue_email_event->payload->get('scheduledAt')
        ]);

        $queue_email_event->handled = true;
    }

    public function setDB($connection)
    {
        $this->connection = $connection;
    }

    public function setHandlerMap($map)
    {
        $this->handlerMap = array_merge($this->handlerMap, $map);
    }

    public function setNotifyCenterExchange($exchange)
    {
        $this->notifyCenterExchange = $exchange;
    }
}
