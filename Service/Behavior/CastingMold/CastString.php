<?php
namespace ZJPHP\Service\Behavior\CastingMold;

use ZJPHP\Base\Behavior;
use ZJPHP\Base\Kit\StringHelper;
use ZJPHP\Base\ZJPHP;

class CastString extends Behavior
{
    public function castString(&$dataSet, $field)
    {
        if (is_string($dataSet[$field])) {
            return $dataSet[$field] = sprintf("%s", $dataSet[$field]);
        }
    }

    public function castCustomizeString(&$dataSet, $field, $format, $other_fields = [])
    {
        if (is_string($dataSet[$field])) {
            $parameters = [$format, $dataSet[$field]];
            foreach ($other_fields as $key) {
                if (isset($dataSet[$key]) && is_string($dataSet[$key])) {
                    $parameters[] = $dataSet[$field];
                }
            }
            return $dataSet[$field] = call_user_func_array('sprintf', $parameters);
        }
    }

    public function castTrimString(&$dataSet, $field)
    {
        if (is_string($dataSet[$field])) {
            return $dataSet[$field] = trim($dataSet[$field]);
        }
    }

    public function castJsonDecode(&$dataSet, $field, $assoc = true, $depth = 512, $options = 0)
    {
        if (is_string($dataSet[$field])) {
            return $dataSet[$field] = json_decode($dataSet[$field], $assoc, $depth, $options);
        }
    }

    public function castJsonEncode(
        &$dataSet,
        $field,
        $option = JSON_NUMERIC_CHECK,
        $depth = 512
    ) {
        return $dataSet[$field] = json_encode($dataSet[$field], $option, $depth);
    }

    public function castHtmlDecode(&$dataSet, $field, $flags = (ENT_COMPAT | ENT_HTML401))
    {
        if (is_string($dataSet[$field])) {
            return $dataSet[$field] = htmlspecialchars_decode($dataSet[$field], $flags);
        }
    }

    public function castUrlDecode(&$dataSet, $field)
    {
        if (is_string($dataSet[$field])) {
            return $dataSet[$field] = rawurldecode($dataSet[$field]);
        }
    }

    public function castStringFromEnum(&$dataSet, $field, array $enum)
    {
        if (key_exists($dataSet[$field], $enum)) {
            $dataSet[$field] = $enum[$dataSet[$field]];
        }
    }
}
