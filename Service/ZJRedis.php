<?php
namespace ZJPHP\Service;

use ZJPHP\Base\Component;
use ZJPHP\Base\Application;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\Exception\DatabaseErrorException;
use ZJPHP\Base\Exception\InvalidConfigException;
use Redis;
use RedisException;

class ZJRedis extends Component
{
    private $_redisClients = [];
    private $_connectionSettings = [];

    public function init()
    {
        parent::init();
        if (!extension_loaded('Redis')) {
            throw new InvalidConfigException('PHP Has No Redis Extension', 503);
        }
    }

    public function setConnections($connections)
    {
        $default = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => 2,
            'database' => 0,
            'auth_passwd' => ''
        ];
        foreach ($connections as $connection => $setting) {
            if (count($connections) === 1) {
                $connection = 'default';
            }
            $this->_connectionSettings[$connection] = ArrayHelper::merge($default, $setting);
        }
    }

    public function connect($connection = 'default')
    {
        if (!isset($this->_connectionSettings[$connection])) {
            throw new InvalidConfigException('Redis Config Error', 500);
        }

        if ($this->isRedisAlive($connection)) {
            return $this->_redisClients[$connection];
        }

        try {
            $redis_client = new Redis();
            extract($this->_connectionSettings[$connection]);
            $redis_client->connect($host, (int) $port, (int) $timeout);

            if (!empty($auth_passwd)) {
                $redis_client->auth($auth_passwd);
            }

            if ($database) {
                $redis_client->select((int) $database);
            }

            return $this->_redisClients[$connection] = $redis_client;
        } catch (RedisException $e) {
            throw new DatabaseErrorException('Redis Database Failed to Connect', 503, $e);
        }
    }

    public function isRedisAlive($connection)
    {
        if (!isset($this->_redisClients[$connection])) {
            return false;
        }

        $redis_client = $this->_redisClients[$connection];
        try {
            return $redis_client->ping() === '+PONG';
        } catch (Exception $e) {
            return false;
        }
    }

    public function __destruct()
    {
        foreach ($this->_redisClients as $redis_client) {
            $redis_client->close();
        }
    }
}
