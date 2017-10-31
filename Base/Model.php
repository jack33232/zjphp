<?php
namespace ZJPHP\Base;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\Kit\StringHelper;
use ZJPHP\Base\Exception\UnknownPropertyException;
use ZJPHP\Base\Exception\InvalidParamException;
use ZJPHP\Base\Exception\InvalidCallException;
use ReflectionClass;
use IteratorAggregate;
use ArrayAccess;
use stdClass;

class Model extends Component implements IteratorAggregate, ArrayAccess
{
    protected $activeRecord;
    public static $ormTable;
    public static $ormPK;

    public function __construct(stdClass $obj, $config = [])
    {
        $this->activeRecord = $obj;
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
        if (!property_exists($this->activeRecord, static::$ormPK)) {
            throw new InvalidParamException('A model need primary key value.');
        }
    }

    public function __get($key)
    {
        $result = 'not found';
        $exception = null;
        if (property_exists($this->activeRecord, $key)) {
                $result = $this->activeRecord->$key;
        } else {
            try {
                $result = parent::__get($key);
            } catch (UnknownPropertyException $e) {
                $exception = $e;
                $this->passiveRetrieveActiveRecord();
            }
        }
        if ($result !== 'not found') {
            return $result;
        } elseif ($result === 'not found' && property_exists($this->activeRecord, $key)) {
            $result = $this->activeRecord->$key;
            return $result;
        } else {
            throw $exception;
        }
    }

    public function __set($key, $value)
    {
        if (property_exists($this->activeRecord, $key)) {
            $this->activeRecord->$key = $value;
        } else {
            parent::__set($key, $value);
        }
    }

    public function __isset($key)
    {
        if (property_exists($this->activeRecord, $key)) {
            return  true;
        } else {
            return parent::__isset($key);
        }
    }

    public function __unset($key)
    {
        if (property_exists($this->activeRecord, $key)) {
            unset($this->activeRecord->$key);
        } else {
            return parent::__unset($key);
        }
    }

    public function updateActiveRecord($data)
    {
        foreach ($data as $key => $value) {
            $this->activeRecord->$key = $value;
        }
    }

    protected function passiveRetrieveActiveRecord()
    {
        if (isset(static::$ormTable) && isset(static::$ormPK) && property_exists($this->activeRecord, static::$ormPK)) {
            $db = ZJPHP::$app->get('db');
            $this->activeRecord = $db->table(static::$ormTable)->where(static::$ormPK, $this->activeRecord->{static::$ormPK})->first();
        }
    }

    public function getIterator()
    {
        return $this->loopGenerator();
    }

    public function &loopGenerator()
    {
        foreach ($this->activeRecord as $key => &$value) {
            yield $key => $value;
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            throw new InvalidCallException('Model Class cannot assign null offset property.');
        } else {
            $this->__set($offset, $value);
        }
    }

    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }
}
