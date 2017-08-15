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

        register_shutdown_function([$this, 'onShutDown']);
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
        call_user_func([$connection_instance, 'setFetchMode'], $fetech_mode, $fetch_argument, $fetch_constructor_argument);
        return $connection_instance;
    }

    public function table($table)
    {
        return $this->connect()->table($table);
    }

    public function select($query, $bindings = [])
    {
        return $this->connect()->select($query, $bindings);
    }

    public function cursor($query, $bindings = [])
    {
        return $this->connect()->cursor($query, $bindings);
    }

    public function insert($query, $bindings = [])
    {
        return $this->connect()->insert($query, $bindings);
    }

    public function update($query, $bindings = [])
    {
        return $this->connect()->update($query, $bindings);
    }

    public function delete($query, $bindings = [])
    {
        return $this->connect()->delete($query, $bindings);
    }

    public function statement($query, $bindings = [])
    {
        return $this->connect()->statement($query, $bindings);
    }

    public function raw($value)
    {
        return $this->connect()->raw($value);
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

    public function onShutDown()
    {
        $database_manager = $this->getDBManager();
        $all_connections = $database_manager->getConnections();
        foreach ($all_connections as $name => $connection) {
            $connection->disconnect();
        }
    }
}
