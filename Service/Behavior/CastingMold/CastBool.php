<?php
namespace ZJPHP\Service\Behavior\CastingMold;

use ZJPHP\Base\Behavior;

class CastBool extends Behavior
{
    public function castBoolean(&$dataSet, $field)
    {
        return $dataSet[$field] = boolval($dataSet[$field]);
    }
}
