<?php
namespace ZJPHP\Service;

use Closure;
use ZJPHP\Base\Component;
use ZJPHP\Base\Application;
use ZJPHP\Base\Kit\ArrayHelper;
use Illuminate\Database\Capsule\Manager as Capsule;
use ZJPHP\Base\TransactionInterface;
//use Illuminate\Events\Dispatcher;
//use Illuminate\Container\Container;
use ZJPHP\Base\Exception\InvalidConfigException;
use ZJPHP\Base\Event;
use PDO;

class Database extends Component implements TransactionInterface
{
    private $_capsule;

    public function setConnections($connections)
    {
        $capsule = new Capsule;
        foreach ($connections as $name => $setting) {
            if (count($connections) === 1) {
                $name = 'default';
            }
            $capsule->addConnection($setting, $name);
        }

        //$capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        //$capsule->bootEloquent();
        $this->_capsule = $capsule;
    }

    public function getDBManager()
    {
        return $this->_capsule->getDatabaseManager();
    }

    public function getPdo($connection = 'default')
    {
        return $this->_capsule->getConnection($connection)->getPdo();
    }

    public function getLastInsertId($connection = 'default')
    {
        return $this->_capsule->getConnection($connection)->getPdo()->lastInsertId();
    }

    public function connect($fetech_mode = PDO::FETCH_OBJ, $fetch_argument = null, $connection = 'default', array $fetch_constructor_argument = [])
    {
        $connection_instance = Capsule::connection($connection);
        $connection_instance->setFetchMode($fetech_mode, $fetch_argument, $fetch_constructor_argument);
        return $connection_instance;
    }

    public function table($table, $connection = 'default')
    {
        return $this->connect(PDO::FETCH_OBJ, null, $connection)->table($table);
    }

    public function select($query, $bindings = [], $connection = 'default')
    {
        return $this->connect(PDO::FETCH_OBJ, null, $connectio)->select($query, $bindings);
    }

    public function cursor($query, $bindings = [], $connection = 'default')
    {
        return $this->connect(PDO::FETCH_OBJ, null, $connectio)->cursor($query, $bindings);
    }

    public function insert($query, $bindings = [], $connection = 'default')
    {
        return $this->connect(PDO::FETCH_OBJ, null, $connectio)->insert($query, $bindings);
    }

    public function update($query, $bindings = [], $connection = 'default')
    {
        return $this->connect(PDO::FETCH_OBJ, null, $connectio)->update($query, $bindings);
    }

    public function delete($query, $bindings = [], $connection = 'default')
    {
        return $this->connect(PDO::FETCH_OBJ, null, $connectio)->delete($query, $bindings);
    }

    public function statement($query, $bindings = [], $connection = 'default')
    {
        return $this->connect(PDO::FETCH_OBJ, null, $connectio)->statement($query, $bindings);
    }

    public function raw($value, $connection = 'default')
    {
        $connection_instance = Capsule::connection($connection);
        return $connection_instance->raw($value);
    }

    public function transaction($callback, $args = [])
    {
        return $this->connect()->transaction(function () use ($callback, $args) {
            call_user_func_array($callback, $args);
        });
    }

    public function beginTransaction()
    {
        $result = $this->connect()->beginTransaction();
        Event::beginTransaction();
        return $result;
    }

    public function rollBack()
    {
        $result = $this->connect()->rollBack();
        Event::transactionRollback();
        return $result;
    }

    public function commit()
    {
        $result = $this->connect()->commit();
        Event::transactionCommit();
        return $result;
    }

    public function enableQueryLog()
    {
        return $this->connect()->enableQueryLog();
    }

    public function getQueryLog()
    {
        return Capsule::getQueryLog();
    }

    public function disconnect($connection = 'default')
    {
        $database_manager = $this->getDBManager();
        $connections_to_kill = [];

        if ($connection === 'all connections') {
            $connections_to_kill = $database_manager->getConnections();
        } else {
            $connections_to_kill[$connection] = $database_manager->connection($connection);
        }
        
        foreach ($connections_to_kill as $name => $connection) {
            $connection->disconnect();
        }
    }
}
