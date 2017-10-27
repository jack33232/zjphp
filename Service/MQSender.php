<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\Event;
use ZJPHP\Service\Debugger;
use ZJPHP\Facade\Database;
use ZJPHP\Facade\Security;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Exception\InvalidCallException;
use ZJPHP\Base\Exception\InvalidParamException;
use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use PDO;
use Exception;
use Throwable;

class MQSender extends Component
{
    protected $rabbitmqOpts;
    protected $client;
    protected $establishedQueues = [];
    protected $establishedExchanges = [];

    public function init()
    {
        $this->client = new Client($this->rabbitmqOpts);
        $this->client->connect();
    }

    public function blast($queue, $messages)
    {
        if (empty($messages)) {
            throw new InvalidCallException('message is empty...');
        }

        if (ArrayHelper::isAssociative($messages)) {
            $messages = [$messages];
        }

        $bkpMessages = [];
        $envelopes = [];
        $now = date('Y-m-d H:i:s');
        foreach ($messages as $message) {
            $message_content = json_encode($message, JSON_NUMERIC_CHECK);
            $message_signature = $this->signMessage($message_content);
            $bkpMessages[] = [
                'binding_key' => $queue,
                'message_content' => $message_content,
                'signature' => $message_signature,
                'created_at' => $now
            ];
            $envelopes[] = [
                'alg' => 'md5',
                'content' => $message_content,
                'signature' => $message_signature
            ];
        }

        $message_ids = $this->bkpMessage($bkpMessages);

        // Start....
        if (isset($this->establishedQueues[$queue])) {
            $channel = $this->establishedQueues[$queue];
        } else {
            $channel = $this->client->channel();
            $channel->queueDeclare($queue, false, true, false, false);
            $this->establishedQueues[$queue] = $channel;
        }

        foreach ($envelopes as $indx => $envelope) {
            $channel->publish(
                json_encode($envelope),
                [
                    'delivery_mode' => 2
                ],
                '',
                $queue
            );
            Database::table('blasted_message_queue')->where('id', $message_ids[$indx])->update([
                'has_blasted' => 1,
                'blasted_at' => date('Y-m-d H:i:s')
            ]);
        }

        // End....
    }

    public function scheduledBlast($exchange, $routing_key, $messages, $scheduled_at = 'now')
    {
        if (empty($messages)) {
            throw new InvalidCallException('message is empty...');
        }

        if (ArrayHelper::isAssociative($messages)) {
            $messages = [$messages];
        }

        $bkpMessages = [];
        $envelopes = [];
        $now = date('Y-m-d H:i:s');
        foreach ($messages as $message) {
            $message_content = json_encode($message, JSON_NUMERIC_CHECK);
            $message_signature = $this->signMessage($message_content);
            $bkpMessages[] = [
                'binding_key' => $exchange,
                'message_content' => $message_content,
                'signature' => $message_signature,
                'created_at' => $now
            ];
            $envelopes[] = [
                'alg' => 'md5',
                'content' => $message_content,
                'signature' => $message_signature
            ];
        }

        $message_ids = $this->bkpMessage($bkpMessages);

        // Start....
        if (isset($this->establishedExchanges[$exchange])) {
            $channel = $this->establishedExchanges[$exchange];
        } else {
            $channel = $this->client->channel();
            $channel->exchangeDeclare(
                $exchange,
                'x-delayed-message',
                false, // passive
                true, // durable
                false, // auto_delete
                false, // internal
                false, // nowait
                ['x-delayed-type' => 'topic']
            );
            $this->establishedExchanges[$exchange] = $channel;
        }

        foreach ($envelopes as $indx => $envelope) {
            $channel->publish(
                json_encode($envelope),
                [
                    'delivery_mode' => 2,
                    'x-delay' => ceil(strtotime($scheduled_at) - time()) * 1000
                ],
                $exchange,
                $routing_key
            );
            Database::table('blasted_message_queue')->where('id', $message_ids[$indx])->update([
                'has_blasted' => 1,
                'blasted_at' => date('Y-m-d H:i:s')
            ]);
        }

        // End....
    }

    protected function signMessage($message_content)
    {
        return Security::hash($message_content, 'md5');
    }

    protected function bkpMessage($messages)
    {
        $message_id = Database::table('blasted_message_queue')->insertGetId($messages);
        return range($message_id, $message_id + count($messages) - 1);
    }

    public function setRabbitMQ($setting)
    {
        $this->rabbitmqOpts = $setting;
    }

    //reblast not compatible for scheduled msg
    public function reblast(array $message_ids)
    {
        $records = Database::table('blasted_message_queue')->whereIn('id', $message_ids)->get();
        $messages = [];
        foreach ($records as $record) {
            $queue = $record->binding_key;
            $messages[$queue][] = json_decode($record->message_content, true);
        }

        foreach ($messages as $queue => $messages) {
            $this->blast($queue, $messages);
        }

        return true;
    }

    public function __destruct()
    {
        foreach ($this->establishedQueues as $channel) {
            $channel->close();
        }

        foreach ($this->establishedExchanges as $channel) {
            $channel->close();
        }

        $this->client->disconnect();
    }
}
