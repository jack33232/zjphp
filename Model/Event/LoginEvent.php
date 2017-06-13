<?php
namespace ZJPHP\Model\Event;

use ZJPHP\Base\Event;

class LoginEvent extends Event
{
    public $errMsg;
    public $user;
}
