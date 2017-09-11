<?php
namespace ZJPHP\Base;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Model;
use ZJPHP\Base\DataCollection;
use ZJPHP\Base\Exception\InvalidParamException;
use ZJPHP\Base\Exception\InvalidConfigException;

class ModelCollection extends DataCollection
{
    protected $modelName;

    public function init()
    {
        parent::init();

        if (empty($this->modelName) || !class_exists($this->modelName) || !property_exists($this->modelName, 'ormPK')) {
            throw new InvalidConfigException('Valid Model Name Required.');
        }
        $ormPK = $this->modelName::$ormPK;
        foreach ($this->attributes as $key => $model_data) {
            if (!array_key_exists($ormPK, $model_data) || $model_data[$ormPK] !== $key) {
                throw new InvalidParamException('Model Data Miss Primary Key Value.');
            }
        }
    }

    public function add($model_data)
    {
        $ormPK = $this->modelName::$ormPK;
        if (!array_key_exists($ormPK, $model_data)) {
            throw new InvalidParamException('Model Data Miss Primary Key Value.');
        }
        $pk = $model_data[$ormPK];
        if (isset($this->attributes[$pk])) {
            throw new InvalidParamException('No duplicate primary key for model coolection.');
        }
        $this->set($pk, $model_data);
    }

    public function get($pk, $default_val = null)
    {
        if (isset($this->attributes[$pk])) {
            $model_data = parent::get($pk);
            return $this->buildModelObject($model_data);
        }
        return $default_val;
    }

    public function update($pk, $data)
    {
        $model_data = parent::get($pk);
        foreach ($data as $key => $val) {
            $model_data[$key] = $val;
        }
        $this->set($pk, $model_data);
    }

    public function getIterator()
    {
        return $this->generator();
    }

    public function pks()
    {
        return $this->keys();
    }

    protected function generator()
    {
        foreach ($this->attributes as $model_data) {
            yield $this->buildModelObject($model_data);
        }
    }

    protected function buildModelObject($model_data)
    {
        $obj = (object) $model_data;
        return ZJPHP::createObject($this->modelName, [$obj]);
    }
}
