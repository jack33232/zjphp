<?php
namespace ZJPHP\Service;

use ZJPHP\Base\ZJPHP;
use ZJPHP\Base\Component;
use ZJPHP\Base\Kit\ArrayHelper;
use ZJPHP\Base\Event;

class NotifyCenter extends Component
{
    const EVENT_SEND_EMAIL = 'notifySendEmail';
    const EVENT_QUEUE_EMAIL = 'notifyQueueEmail';
    const EVENT_BLAST_PHP_CONSOLE = 'notifyBlastPHPConsole';
    
    public function buildBlastPHPConsoleEvent($variable, $tag = 'debug')
    {
        $payload = [
            'variable' => $variable,
            'tag' => $tag
        ];

        return new Event($payload);
    }

    public function buildSendEmailEvent(array $params, $throw_exception = true)
    {
        $params_mask = [
            'to', 'from', 'cc', 'bcc', 'replyTo',
            'smtp', 'attachment', 'isHTML', 'purifyHTML',
            'subject', 'body', 'altBody', 'priority'
        ];
        $default_payload = [
            'altBody' => 'Please use email client which has the ability to view HTML.',
            'attachment' => [],
            'purifyHTML' => false,
            'isHTML' => true,
            'subject' => 'Email Send From ZJPHP',
            'body' => '',
            'priority' => null, // null (default), 1 = High, 3 = Normal, 5 = low.
            'result' => null,
            'errMsg' => null
        ];

        $payload = ArrayHelper::mask($params_mask, $params, $default_payload);

        $this->uniformEmailAddr($payload);
        if (!$this->validateEmailSetting($payload, $throw_exception)) {
            return false;
        }

        return new Event($payload);
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

        $payload = [
            'bindingKey' => (isset($params['bindingKey'])) ? $params['bindingKey'] : 'default',
            'sendEmailEvent' => $send_email_event,
            'body' => $email_body,
            'bodyTpl' => (isset($params['bodyTpl'])) ? $params['bodyTpl'] : null,
            'renderData' => (isset($params['renderData'])) ? $params['renderData'] : null,
            'scheduledAt' => (isset($params['scheduledAt']) && $temp = strtotime($params['scheduledAt'])) ? date('Y-m-d H:i:s', $temp) : date('Y-m-d H:i:s')
        ];
        return new Event($payload);
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
            'altBody' => 'string',
            'from' => 'sometimes|required|array',
            'from.email' => 'email|max:65',
            'from.name' => 'string',
            'to' => 'required|array',
            'to.*.email' => 'email|max:65',
            'to.*.name' => 'string',
            'cc' => 'sometimes|required|array',
            'cc.*.email' => 'email|max:65',
            'cc.*.name' => 'string',
            'bcc' => 'sometimes|required|array',
            'bcc.*.email' => 'email|max:65',
            'bcc.*.name' => 'string',
            'replyTo' => 'sometimes|required|array',
            'replyTo.*.email' => 'email|max:65',
            'replyTo.*.name' => 'string'
        ];

        $validator = $validation->make($params, $validationRule);

        if ($validator->fails()) {
            if ($throw_exception) {
                $msg = implode("\n", $validator->errors()->all());
                throw new \InvalidArgumentException($msg, 400);
            }
            return false;
        }

        return true;
    }

    protected function uniformEmailAddr(&$payload)
    {
        $email_addr_fields = [
            'from',
            'to',
            'cc',
            'bcc',
            'replyTo'
        ];
        foreach ($payload as $key => $item) {
            if (in_array($key, $email_addr_fields)) {
                $single = false;
                if ($key === 'from') {
                    $single = true;
                }
                $result = [];
                if (is_string($item)) {
                    $result[] = ['email' => $item, 'name' => ''];
                } elseif (is_array($item)) {
                    $processor = function ($sub_item) {
                        if (is_string($sub_item)) {
                            return ['email' => $sub_item, 'name' => ''];
                        } elseif (is_array($sub_item) && count($sub_item) === 2) {
                            $temp = array_values($sub_item);
                            return ['email' => $sub_item[0], 'name' => $sub_item[1]];
                        }
                    };
                    $result = array_map($processor, $item);
                }
                $payload[$key] = ($single) ? reset($result) : $result;
            }
        }

        return $payload;
    }
}
