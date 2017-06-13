<?php
namespace ZJPHP\Service\Behavior\CastingMold;

use ZJPHP\Base\Behavior;
use ZJPHP\Base\ZJPHP;

class CastTranslation extends Behavior
{
    public function castTranslation(&$dataSet, $field, $lang = null, $domain = null)
    {
        if (null === $lang) {
            $lang = ZJPHP::$app->getLang();
        }

        $dataSet[$field] = ZJPHP::$app->get('translation')->trans($dataSet[$field], [], $domain, $lang);
    }
}
