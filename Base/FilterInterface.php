<?php
namespace ZJPHP\Base;

interface FilterInterface
{
    public function filter($request, $response, $service, $app, $router);
}
