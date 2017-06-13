<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use GuzzleHttp\Client;

class HttpClient extends Component
{
    public function instance(array $config = [])
    {
        return new Client($config);
    }
}
