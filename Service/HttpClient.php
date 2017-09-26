<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class HttpClient extends Component
{
    protected $globalConfig = [
        'connection_timeout' => 0,
        'timeout' => 0,
        'verify' => true
    ];

    public function instance(array $config = [])
    {
        $config = $config + $this->globalConfig;
        return new Client($config);
    }

    public function getCookieJar()
    {
        return new CookieJar();
    }

    public function setGlobalConfig(array $config)
    {
        $this->globalConfig = $config + $this->globalConfig;
    }
}
