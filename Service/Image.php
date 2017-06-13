<?php
namespace ZJPHP\Service;

use ZJPHP\Base\Component;
use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Exceptions\InvalidConfigException;
use ZJPHP\Base\Exceptions\InvalidCallException;
use Intervention\Image\ImageManager;
use Intervention\Image\Image as ImageObj;

class Image extends Component
{
    protected $imageManagerConfig = [
        'driver' => 'gd' // Can be changed to imagick if it is installed
    ];

    protected $imageManagerObj;

    public function init()
    {
        $this->instantiate();
    }

    protected function instantiate()
    {
        $this->imageManagerObj = new ImageManager(['driver' => $this->imageManagerConfig['driver']]);
    }

    public function setImageManager($config)
    {
        $this->imageManagerConfig = array_merge($this->imageManagerConfig, $config);
    }

    public function getImageManager()
    {
        return $this->imageManagerObj;
    }

    public function getImage($source)
    {
        return $this->imageManagerObj->make($source);
    }

    public function createCanvas()
    {
        $args = func_get_args();
        return call_user_func_array([$this->imageManagerObj, 'canvas'], $args);
    }

    public function save(ImageObj $imageObj, $basename, $dir = UPLOAD_DIR, $path = '')
    {
        $saveDir = $dir;
        if ($path) {
            $path = ltrim($path, '/'); //Mac or Linux
            $path = ltrim($path, '\\'); // Windows
            $saveDir .= DIRECTORY_SEPARATOR . $path;
            if (!file_exists($saveDir)) {
                try {
                    mkdir($saveDir, 0755, true);
                } catch (Exception $e) {
                    throw new InvalidCallException('Error when create "' . $saveDir . '" dir with error msg: ' . $e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        $fullFilePath = $saveDir . DIRECTORY_SEPARATOR . $basename;
        $imageObj->save($fullFilePath);
    }

    public function achieveImageContent($path, $dir = UPLOAD_DIR, $format = 'data-url', $thumbnail = false, $quality = 75)
    {
        $path = ltrim($path, '/'); //Mac or Linux
        $path = ltrim($path, '\\'); // Windows
        $fullFilePath = $dir . DIRECTORY_SEPARATOR . $path;
        if (!file_exists($fullFilePath)) {
            throw new InvalidCallException('Request "' . $fullFilePath . '" file not exist.');
        }

        $imageObj = $this->getImage($fullFilePath);

        if ($thumbnail && is_numeric($thumbnail) && $thumbnail > 0) {
            $imageObj->fit($thumbnail);
        }

        return (string) $imageObj->encode($format, $quality);
    }
}
