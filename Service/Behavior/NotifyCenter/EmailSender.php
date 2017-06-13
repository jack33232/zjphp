<?php
namespace ZJPHP\Service\Behavior\NotifyCenter;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Base\Kit\ArrayHelper;
use PHPMailer;

class EmailSender extends Behavior
{
    private $_smtpSettings = [];

    public function events()
    {
        return [
            NotifyCenter::EVENT_SEND_EMAIL => 'blastEmail'
        ];
    }

    public function blastEmail($send_email_event)
    {
        $server_name = $send_email_event->smtp;
        if (!isset($this->_smtpSettings[$server_name])) {
            $send_email_event->result = false;
            $send_email_event->errMsg = 'No such SMTP: ' . $server_name;
            return;
        }

        $setting = $this->_smtpSettings[$server_name];
        // Init mailer
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->SMTPDebug = $setting['SMTPDebug'];
        $mail->Host = $setting['host']; // Specify main and backup SMTP servers
        $mail->SMTPAuth = $setting['SMTPAuth']; // Enable SMTP authentication
        $mail->Username = $setting['username']; // SMTP username
        $mail->Password = $setting['password']; // SMTP password
        $mail->SMTPSecure = $setting['SMTPSecure']; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $setting['port'];
        $mail->Timeout = $setting['timeout'];

        if (!empty($setting['SMTPOptions'])) {
            $mail->SMTPOptions = $setting['SMTPOptions'];
        }

        if (empty($send_email_event->from)) {
            $send_email_event->from = $setting['username'];
        }
        call_user_func_array([$mail, 'setFrom'], $send_email_event->from);
        foreach ($send_email_event->to as $sub_to) {
            call_user_func_array([$mail, 'addAddress'], $sub_to);
        }
        foreach ($send_email_event->replyTo as $sub_reply_to) {
            call_user_func_array([$mail, 'addReplyTo'], $sub_reply_to);
        }
        foreach ($send_email_event->cc as $sub_cc) {
            call_user_func_array([$mail, 'addCC'], $sub_cc);
        }
        foreach ($send_email_event->bcc as $sub_bcc) {
            call_user_func_array([$mail, 'addBCC'], $sub_bcc);
        }

        foreach ($send_email_event->attachment as $attachment) {
            if (!file_exists($attachment)) {
                continue;
            }
            $mail->addAttachment($attachment);
        }

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = "base64";

        $mail->Subject = '=?UTF-8?B?' . base64_encode($send_email_event->subject) . "?=";

        
        if ($send_email_event->isHTML) {
            $mail->isHTML(true);
            if ($send_email_event->purifyHTML) {
                $mail->msgHTML($send_email_event->body);
            } else {
                $mail->Body = $send_email_event->body;
            }
        } else {
            $mail->Body = $send_email_event->body;
        }
        $mail->AltBody = $send_email_event->altBody;
        $mail->Priority = $send_email_event->priority;

        // Blast!
        $result = $mail->send();
        $send_email_event->result = $result;

        if (!$result) {
            $send_email_event->errMsg = $mail->ErrorInfo;
        }

        return;
    }

    public function setSmtpSettings($settings)
    {
        $default_setting = [
            'SMTPDebug' => 0,
            'host' => '',
            'username' => '',
            'password' => '',
            'SMTPAuth' => true,
            'SMTPSecure' => 'tls',
            'port' => 587,
            'timeout' => 5
        ];
        foreach ($settings as $server_name => $setting) {
            if (!isset($this->_smtpSettings[$server_name])) {
                $setting = ArrayHelper::merge($default_setting, $setting);
                $this->_smtpSettings[$server_name] = $setting;
            } else {
                $this->_smtpSettings[$server_name] = ArrayHelper::merge($this->_smtpSettings[$server_name], $setting);
            }
        }
    }
}
