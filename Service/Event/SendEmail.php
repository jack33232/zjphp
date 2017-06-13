<?php
namespace ZJPHP\Service\Event;

use ZJPHP\Base\Event;

class SendEmail extends Event
{
    private $_to = [];
    private $_from = [];
    private $_cc = [];
    private $_bcc = [];
    private $_replyTo = [];

    public $smtp;
    public $attachment;
    public $isHTML;
    public $purifyHTML;
    public $subject;
    public $body;
    public $altBody;
    public $priority;
    public $result;
    public $errMsg;

    public function getTo()
    {
        return $this->_to;
    }

    public function setTo($to)
    {
        if (is_string($to)) {
            $this->_to[] = [$to];
        } else {
            if (is_array(reset($to))) {
                foreach ($to as $sub_to) {
                    $this->_to[] = [$sub_to['email'], $sub_to['name']];
                }
            } else {
                $this->_to[] = [$to['email'], $to['name']];
            }
        }
    }

    public function getFrom()
    {
        return $this->_from;
    }

    public function setFrom($from)
    {
        if (is_string($from)) {
            $this->_from = [$from];
        } else {
            if (is_array(reset($from))) {
                foreach ($from as $sub_from) {
                    $this->_from[] = [$sub_from['email'], $sub_from['name']];
                }
            } else {
                $this->_from[] = [$from['email'], $from['name']];
            }
        }
    }

    public function getCc()
    {
        return $this->_cc;
    }

    public function setCc($cc)
    {
        if (is_string($cc)) {
            $this->_cc[] = [$cc];
        } else {
            if (is_array(reset($cc))) {
                foreach ($cc as $sub_cc) {
                    $this->_cc[] = [$sub_cc['email'], $sub_cc['name']];
                }
            } else {
                $this->_cc[] = [$cc['email'], $cc['name']];
            }
        }
    }

    public function getBcc()
    {
        return $this->_bcc;
    }

    public function setBcc($bcc)
    {
        if (is_string($bcc)) {
            $this->_bcc[] = [$bcc];
        } else {
            if (is_array(reset($bcc))) {
                foreach ($bcc as $sub_bcc) {
                    $this->_bcc[] = [$sub_bcc['email'], $sub_bcc['name']];
                }
            } else {
                $this->_bcc[] = [$bcc['email'], $bcc['name']];
            }
        }
    }

    public function getReplyTo()
    {
        return $this->_replyTo;
    }

    public function setReplyTo($reply_to)
    {
        if (is_string($reply_to)) {
            $this->_replyTo[] = [$reply_to];
        } else {
            if (is_array(reset($reply_to))) {
                foreach ($reply_to as $sub_reply_to) {
                    $this->_replyTo[] = [$sub_reply_to['email'], $sub_reply_to['name']];
                }
            } else {
                $this->_replyTo[] = [$reply_to['email'], $reply_to['name']];
            }
        }
    }
}
