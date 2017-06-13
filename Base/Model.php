<?php
namespace ZJPHP\Base;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\Kit\StringHelper;
use ZJPHP\Base\Exception\UnknownPropertyException;
use ReflectionClass;

class Model extends Component
{
    protected $activeRecord;
    protected $ormTable;
    protected $ormPK;

    public function __construct($obj, $config = [])
    {
        $this->activeRecord = $obj;
        parent::__construct($config);
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

    public function updateActiveRecord($data)
    {
        foreach ($data as $key => $value) {
            $this->activeRecord->$key = $value;
        }
    }

    protected function passiveRetrieveActiveRecord()
    {
        if (isset($this->ormTable) && isset($this->ormPK) && property_exists($this->activeRecord, $this->ormPK)) {
            $db = ZJPHP::$app->get('db');
            $this->activeRecord = $db->table($this->ormTable)->where($this->ormPK, $this->activeRecord->{$this->ormPK})->first();
        }
    }
}
