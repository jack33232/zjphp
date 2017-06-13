<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\Kit\ArrayHelper;

class NotifyCenter extends Component
{
    const EVENT_SEND_EMAIL = 'notifySendEmail';
    const EVENT_QUEUE_EMAIL = 'notifyQueueEmail';
    const EVENT_BLAST_PHP_CONSOLE = 'notifyBlastPHPConsole';
    
    public function buildBlastPHPConsoleEvent($variable, $tag = 'debug')
    {
        $definition = [
            'class' => 'NotifyBlastPHPConsoleEvent',
            'variable' => $variable,
            'tag' => $tag
        ];

        return ZJPHP::createObject($definition);
    }

    public function buildSendEmailEvent(array $params, $throw_exception = true)
    {
        $params_mask = [
            'class', 'to', 'from', 'cc', 'bcc', 'replyTo',
            'smtp', 'attachment', 'isHTML', 'purifyHTML',
            'subject', 'body', 'altBody', 'priority'
        ];
        $default_params = [
            'class' => 'NotifySendEmailEvent',
            'altBody' => 'Please use email client which has the ability to view HTML.',
            'attachment' => [],
            'purifyHTML' => false,
            'isHTML' => true,
            'body' => '',
            'priority' => null // null (default), 1 = High, 3 = Normal, 5 = low.
        ];

        $definition = ArrayHelper::mask($params_mask, $params, $default_params);

        if (!$this->validateEmailSetting($definition, $throw_exception)) {
            return false;
        }

        return ZJPHP::createObject($definition);
    }

    public function buildQueueEmailEvent(array $params, $throw_exception = true)
    {
        $send_email_event = $this->buildSendEmailEvent($params, $throw_exception);

        if (!$send_email_event) {
            return false;
        }

        // Save body to extra place because of possible existence of bodyTpl
        $email_body = $send_email_event->body;
        $send_email_event->body = null;

        $definition = [
            'class' => 'NotifyQueueEmailEvent',
            'bindingKey' => (isset($params['bindingKey'])) ? $params['bindingKey'] : 'default',
            'sendEmailEvent' => $send_email_event,
            'body' => $email_body,
            'bodyTpl' => (isset($params['bodyTpl'])) ? $params['bodyTpl'] : null,
            'renderData' => (isset($params['renderData'])) ? $params['renderData'] : null,
            'scheduledAt' => (isset($params['scheduledAt']) && $temp = strtotime($params['scheduledAt'])) ? date('Y-m-d H:i:s', $temp) : date('Y-m-d H:i:s')
        ];
        return ZJPHP::createObject($definition);
    }

    protected function validateEmailSetting(array $params, $throw_exception = true)
    {
        $validation = ZJPHP::$app->get('validation');

        $validationRule = [
            'smtp' => 'required|string',
            'attachment' => 'array',
            'attachment.*' => 'string',
            'isHTML' => 'required|boolean',
            'priority' => 'in:1,3,5',
            'subject' => 'string',
            'body' => 'string',
            'altBody' => 'string'
        ];

        $validator = $validation->make($params, $validationRule);

        // Conditional validation for email "from"
        $validator->sometimes('from', 'email|max:65', function ($data) {
            return is_string($data->from);
        });
        $validator->sometimes('from.email', 'email|max:65', function ($data) {
            return is_array($data->from);
        });
        $validator->sometimes('from.name', 'present|string', function ($data) {
            return is_array($data->from);
        });
        // Conditional validation for email "to"
        $validator->sometimes('to', 'required|email|max:65', function ($data) {
            return is_string($data->to);
        });
        $validator->sometimes('to.*.email', 'required|email|max:65', function ($data) {
            return is_array($data->to);
        });
        $validator->sometimes('to.*.name', 'present|string', function ($data) {
            return is_array($data->to);
        });
        // Conditional validation for email "cc"
        $validator->sometimes('cc', 'email|max:65', function ($data) {
            return is_string($data->cc);
        });
        $validator->sometimes('cc.*.email', 'email|max:65', function ($data) {
            return is_array($data->cc);
        });
        $validator->sometimes('cc.*.name', 'present|string', function ($data) {
            return is_array($data->cc);
        });
        // Conditional validation for email "bcc"
        $validator->sometimes('bcc', 'email|max:65', function ($data) {
            return is_string($data->bcc);
        });
        $validator->sometimes('bcc.*.email', 'email|max:65', function ($data) {
            return is_array($data->bcc);
        });
        $validator->sometimes('bcc.*.name', 'present|string', function ($data) {
            return is_array($data->bcc);
        });
        // Conditional validation for email "replyTo"
        $validator->sometimes('replyTo', 'email|max:65', function ($data) {
            return is_string($data->replyTo);
        });
        $validator->sometimes('replyTo.*.email', 'email|max:65', function ($data) {
            return is_array($data->replyTo);
        });
        $validator->sometimes('replyTo.*.name', 'present|string', function ($data) {
            return is_array($data->replyTo);
        });

        if ($validator->fails()) {
            if ($throw_exception) {
                $msg = implode("\n", $validator->errors()->all());
                throw new \InvalidArgumentException($msg, 400);
            }
            return false;
        }

        return true;
    }
}
