<?php
namespace ZJPHP\Service\Behavior\NotifyCenter;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Base\Event;
use ZJPHP\Service\NotifyCenter;
use PC;

class BlastPHPConsole extends Behavior
{
    public function events()
    {
        return [
            NotifyCenter::EVENT_BLAST_PHP_CONSOLE => 'pcDebug'
        ];
    }

    public function pcDebug(Event $event)
    {
        PC::debug($event->payload->get('variable'), $event->payload->get('tag'));
        $event->handled = true;
    }
}
