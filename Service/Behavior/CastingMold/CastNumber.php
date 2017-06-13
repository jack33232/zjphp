<?php
namespace ZJPHP\Service\Behavior\CastingMold;

use ZJPHP\Base\Behavior;

class CastNumber extends Behavior
{
    public function castInt(&$dataSet, $field)
    {
        if (is_numeric($dataSet[$field])) {
            return $dataSet[$field] = intval($dataSet[$field]);
        }
    }

    public function castFloat(&$dataSet, $field)
    {
        if (is_numeric($dataSet[$field])) {
            return $dataSet[$field] = floatval($dataSet[$field]);
        }
    }

    public function castDecimal(&$dataSet, $field)
    {
        if (is_numeric($dataSet[$field])) {
            return $dataSet[$field] = round($dataSet[$field], 2);
        }
    }

    public function castCustomizeNumber(&$dataSet, $field, $decimals = 0, $decPoint = ".", $thousandSep = ",")
    {
        if (is_numeric($dataSet[$field])) {
            return $dataSet[$field] = number_format($dataSet[$field], $decimals, $decPoint, $thousandSep);
        }
    }
}
