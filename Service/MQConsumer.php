<?php
namespace ZJPHP\Service;

use Workerman\Worker;
use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\Event;
use ZJPHP\Service\Debugger;
use ZJPHP\Facade\ZJRedis;
use ZJPHP\Facade\Database;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Exception\InvalidCallException;
use ZJPHP\Base\Exception\InvalidParamException;
use ZJPHP\Exception\MQException;
use Bunny\Channel;
use Bunny\Async\Client;
use Bunny\Message;
use Exception;
use Throwable;

class MQConsumer extends Component
{
    protected $rabbitmqOpts = [];
    protected $qos = 1;
    protected $msgPoolTtl = 2592000; // 30 days

    private $_client = null;

    public function start(Worker $worker, $queue, $callback)
    {
        $event_loop = $worker->getEventLoop();
        $this->_client = new Client($event_loop, $this->rabbitmqOpts);

        $qos = $this->qos;
        $self = $this;

        $this->_client->connect()->then(function (Client $client) {
            return $client->channel();
        })->then(function (Channel $channel) use ($qos) {
            return $channel->qos(0, $qos)->then(function () use ($channel) {
                return $channel;
            });
        })->then(function (Channel $channel) use ($queue) {
            return $channel->queueDeclare($queue, false, true, false, false)->then(function () use ($channel) {
                return $channel;
            });
        })->then(function (Channel $channel) use ($callback, $queue) {
            echo ' [*] Waiting for messages from queue - ' . $queue, "\n";

            $channel->consume($callback, $queue);
        })->otherwise(function (Exception $e) {
            $err = new MQException($e->getMessage(), $e->getCode(), $e);
            throw $err;
        })->otherwise(function (Throwable $e) {
            $err = new MQException($e->getMessage(), $e->getCode(), $e);
            throw $err;
        })->done();
    }

    public function logMessage(Message $message)
    {
        $envelope = json_decode($message->content, true);
        $message_content = $envelope['content'];
        $message_signature = $envelope['signature'];

        $data = [
            'binding_key' => $message->routingKey,
            'message_content' => $message_content,
            'signature' => $message_signature,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return Database::table('recieved_message_queue')->insertGetId($data);
    }

    public function updateMessageLog($message_log_id, $ack_or_nack)
    {
        Database::table('recieved_message_queue')->where('id', $message_log_id)->update([
            'has_acked' => $ack_or_nack,
            'acked_at' => date('Y-m-d H:i:s')
        ]);
    }

    // Not for MQ but processor's transaction
    public function internalAck($signature)
    {
        $redis_client = ZJRedis::connect();
        $key = "MQConsumerPool:app-" . ZJPHP::$app->getAppName();
        $existed = $redis_client->exists($key);
        $result = $redis_client->sAdd($key, $signature);
        if (!$existed) {
            $redis_client->expire($key, $this->msgPoolTtl);
        }
        return $result;
    }

    // Local Internal
    public function isMsgDuplicate($signature)
    {
        $redis_client = ZJRedis::connect();
        $key = "MQConsumerPool:app-" . ZJPHP::$app->getAppName();

        return $redis_client->sIsMember($key, $signature);
    }

    public function stop()
    {
        if (!is_null($this->_client)) {
            $this->_client->disconnect();
        }
    }

    public function setRabbitMQ($setting)
    {
        $this->rabbitmqOpts = $setting;
    }

    public function setQos($qos)
    {
        $this->qos = $qos;
    }

    public function getQos()
    {
        return $this->qos;
    }

    public function setMsgPoolTtl($ttl)
    {
        if (!is_numeric($ttl) && $ttl < 3600) {
            throw new InvalidConfigException('Message Pool Ttl invalid.');
        }

        $this->msgPoolTtl = $ttl;
    }
}
