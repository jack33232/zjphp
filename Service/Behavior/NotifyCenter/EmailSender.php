<?php
namespace ZJPHP\Service\Behavior\NotifyCenter;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Behavior;
use ZJPHP\Service\NotifyCenter;
use ZJPHP\Base\Kit\ArrayHelper;
use PHPMailer;

class EmailSender extends Behavior
{
    protected $smtpSettings = [];

    protected $smtpCache = [];

    public function events()
    {
        return [
            NotifyCenter::EVENT_SEND_EMAIL => 'blastEmail'
        ];
    }

    public function blastEmail($send_email_event)
    {
        $server_name = $send_email_event->payload->get('smtp');
        if (!isset($this->smtpSettings[$server_name])) {
            $send_email_event->payload->set('result', false);
            $send_email_event->payload->set('errMsg', 'No such SMTP: ' . $server_name);
            return;
        }

        $setting = $this->smtpSettings[$server_name];
        // Init mailer or get it from cache
        $mail = $this->getMailer($server_name, $setting);

        $this->processEmailAddr($mail, $setting);

        foreach ($send_email_event->payload->get('attachment') as $attachment) {
            if (!file_exists($attachment)) {
                continue;
            }
            $mail->addAttachment($attachment);
        }

        $mail->Subject = '=?UTF-8?B?' . base64_encode($send_email_event->payload->get('subject')) . "?=";

        
        if ($send_email_event->payload->get('isHTML', true)) {
            $mail->isHTML(true);
            if ($send_email_event->payload->get('purifyHTML', false)) {
                $mail->msgHTML($send_email_event->payload->get('body'));
            } else {
                $mail->Body = $send_email_event->payload->get('body');
            }
        } else {
            $mail->Body = $send_email_event->payload->get('body');
        }
        $mail->AltBody = $send_email_event->payload->get('altBody');
        $mail->Priority = $send_email_event->payload->get('priority');

        // Blast!
        $result = $mail->send();
        $send_email_event->payload->set('result', $result);

        if (!$result) {
            $send_email_event->payload->set('errMsg', $mail->ErrorInfo);
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
            if (!isset($this->smtpSettings[$server_name])) {
                $setting = ArrayHelper::merge($default_setting, $setting);
                $this->smtpSettings[$server_name] = $setting;
            } else {
                $this->smtpSettings[$server_name] = ArrayHelper::merge($this->smtpSettings[$server_name], $setting);
            }
        }
    }

    protected function getMailer($server_name, $setting)
    {
        if (isset($this->smtpCache[$server_name])) {
            $mail = $this->smtpCache[$server_name];
        } else {
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

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = "base64";
        }

        return $mail;
    }

    protected function processEmailAddr($mail, $setting)
    {
        if (!$send_email_event->payload->exists('from')) {
            $send_email_event->payload->set('from', $setting['username']);
            $mail->setFrom($setting['username']);
        } else {
            $from = $send_email_event->payload->get('from');
            $mail->setFrom($from['email'], $from['name']);
        }

        if ($send_email_event->payload->exists('to')) {
            $tos = $send_email_event->payload->get('to');
            foreach ($tos as $to) {
                $mail->addAddress($to['email'], $to['name']);
            }
        }

        if ($send_email_event->payload->exists('replyTo')) {
            $reply_tos = $send_email_event->payload->get('replyTo');
            foreach ($reply_tos as $reply_to) {
                $mail->addReplyTo($reply_to['email'], $reply_to['name']);
            }
        }
        
        if ($send_email_event->payload->exists('cc')) {
            $ccs = $send_email_event->payload->get('cc');
            foreach ($ccs as $cc) {
                $mail->addCC($cc['email'], $cc['name']);
            }
        }

        if ($send_email_event->payload->exists('bcc')) {
            $bccs = $send_email_event->payload->get('bcc');
            foreach ($bccs as $bcc) {
                $mail->addBCC($bcc['email'], $bcc['name']);
            }
        }
    }
}
