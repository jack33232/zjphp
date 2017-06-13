<?php
namespace ZJPHP\Service\Behavior\CastingMold;

use ZJPHP\Base\Behavior;
use DateTime;
use DateInterval;

class CastDate extends Behavior
{
    public function castDatetime(&$dataSet, $field)
    {
        if (strtotime($dataSet[$field]) !== false) {
            $dateObj = new DateTime($dataSet[$field]);
            return $dataSet[$field] = $dateObj->format('c');
        }
    }

    public function castCustomizeDatetime(&$dataSet, $field, $format = 'c')
    {
        if (strtotime($dataSet[$field]) !== false) {
            $dateObj = new DateTime($dataSet[$field]);
            return $dataSet[$field] = $dateObj->format($format);
        }
    }

    public function castAge(&$dataSet, $field)
    {
        if (strtotime($dataSet[$field]) !== false && strtotime($dataSet[$field]) < time()) {
            $today = new DateTime('NOW');
            $dateObj = new DateTime($dataSet[$field]);
            $dateDiff = $today->diff($dateObj);

            return $dataSet[$field] = $dateDiff->format('%y');
        }
    }
}
