<?php
namespace ZJPHP\Service\Behavior\CastingMold;

use ZJPHP\Base\Behavior;
use ZJPHP\Base\Kit\ArrayHelper;

class CastEmpty extends Behavior
{
    public function castHidden(&$dataSet, $field)
    {
        ArrayHelper::smoothUnset($dataSet, $field);
    }

    public function castUnsetOnEmpty(&$dataSet, $field)
    {
        if (empty($dataSet[$field])) {
            ArrayHelper::smoothUnset($dataSet, $field);
        }
    }

    public function castUnsetChildOnEmptyField(&$dataSet, $field, $emptyField)
    {
        if (empty($dataSet[$field][$emptyField])) {
            ArrayHelper::smoothUnset($dataSet, $field);
        }
    }
}
