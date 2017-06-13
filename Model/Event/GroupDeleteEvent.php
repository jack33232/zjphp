<?php
namespace ZJPHP\Model\Event;

use ZJPHP\Base\Event;

class GroupDeleteEvent extends Event
{
    public $group;
    public $groupInfo;
    public $groupOwnerShips;
}
