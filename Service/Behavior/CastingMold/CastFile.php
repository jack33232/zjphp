<?php
namespace ZJPHP\Service\Behavior\CastingMold;

use ZJPHP\Base\Behavior;
use ZJPHP\Base\ZJPHP;

class CastFile extends Behavior
{
    public function castFilePath(&$dataSet, $field, $dir)
    {
        $dataSet[$field] = ltrim($dataSet[$field], '/'); //Mac or Linux
        $dataSet[$field] = ltrim($dataSet[$field], '\\'); // Windows
        $filePath = $dir . DIRECTORY_SEPARATOR . $dataSet[$field];
        if (file_exists($filePath) && !empty($dataSet[$field])) {
            $dataSet[$field] = $filePath;
        } else {
            $dataSet[$field] = null;
        }
    }

    public function castFileUrl(&$dataSet, $field, $base_url = \ROOT_URL, $dir = \UPLOAD_DIR, $default = false)
    {
        $dataSet[$field] = ltrim($dataSet[$field], '/'); //Mac or Linux
        $dataSet[$field] = ltrim($dataSet[$field], '\\'); // Windows

        if (!empty($default)) {
            $default = ltrim($default, '/');
            $default = ltrim($default, '\\');
        }
        $filePath = $dir . DIRECTORY_SEPARATOR . $dataSet[$field];
        if (file_exists($filePath) && !empty($dataSet[$field])) {
            $dataSet[$field] = $base_url . '/' . $dataSet[$field];
        } elseif ($default && file_exists($dir . $default)) {
            $dataSet[$field] = $default;
        } else {
            $dataSet[$field] = null;
        }
    }

    public function castImageDataUrl(&$dataSet, $field, $dir = \UPLOAD_DIR, $thumbnail = false, $quality = 75)
    {
        $imageService = ZJPHP::$app->get('image');
        try {
            $dataSet[$field] = $imageService->achieveImageContent($dataSet[$field], $dir, 'data-url', $thumbnail, $quality);
        } catch (Exception $e) {
            $dataSet[$field] = null;
        }
    }
}
