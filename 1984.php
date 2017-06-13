<?php
require_once(__DIR__. '/vendor/autoload.php');
require_once(__DIR__ . '/Base/ZJPHP.php');

spl_autoload_register(['ZJPHP\\Base\\ZJPHP', 'autoload'], true, false);

if (constant('RUNTIME_ENV') !== 'production') {
    set_exception_handler(function ($e) {
        echo sprintf(
            "<b>Exception happened before app init with message <b>'%s'</b> in %s:%s </b>",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
    });
}
