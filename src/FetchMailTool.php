<?php
/**
 * hiAPI FetchMail Tool
 *
 * @link      https://github.com/hiqdev/hiapi-fetchmail
 * @package   hiAPI FetchMail Tool
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\fetchmail;

use \Ddeboer\Imap\Server;
use \Html2Text\Html2Text;
use \EmailReplyParser\EmailReplyParser;

/**
 * hiAPI FetchMail Tool.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class FetchMailTool extends \hiapi\components\AbstractTool
{
    const MAILBOX = 'INBOX';

    /* @var object [[\Ddeboer\Imap\Server]] */
    protected $connection;

    /* @var array */
    protected $messagesToDelete;

    public function __construct($base, $data = [])
    {
        parent::__construct($base, $data);

        foreach (['url','login', 'password'] as $argument) {
            if (empty($this->data[$argument])) {
                throw new Exception(ucfirst($argument) . ' could not be empty');
            }
        }

        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                $config = $data['data'];
            }

            if (is_string($data['data'])) {
                $config = json_decode($data['data'], true, 512);
            }
        }

        $server = new Server(
            $this->data['url'],
            $config['port'] ?? 993,
            $config['flags'] ?? '/imap/ssl/validate-cert',
            $config['parameters'] ?? [],
            $config['options'] ?? 0,
            $config['retries'] ?? 1
        );

        if (empty($server)) {
            throw new \Exception('no connection');
        }

        $connection = $server->authenticate($this->data['login'], $this->data['password']);
        $this->connection = $connection;

    }

    public function __destruct()
    {
        $this->clear();
        $this->disconnect();
    }

    public function mailsFetch($params = [])
    {
        $mailbox = $this->connection->getMailbox(self::MAILBOX);
        $messages = $mailbox->getMessages();
        if (empty($messages)) {
            return [];
        }
        $emails = [];
        foreach ($messages as $message) {
            $emails[$message->getNumber()] = [
                'number' => $message->getNumber(),
                'message_id' => $message->getId(),
                'from_email' => $message->getFrom()->getAddress(),
                'from_name' => $message->getFrom()->getName(),
                'subject' => $message->getSubject(),
                'message' => EmailReplyParser::parseReply($message->getBodyText() ? : Html2Text::convert($message->getBodyHtml())),
                'in_reply_to' => trim($message->getHeaders()->get('in_reply_to') ?: '', '<>'),
            ];

            if ($message->getAttachments()) {
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->isEmbeddedMessage()) {
                        $embedded = $attachment->getEmbeddedMessage();
                        $emails[$message->getNumber()]['message'] = EmailReplyParser::parseReply($message->getBodyText() ? : Html2Text::convert($message->getBodyHtml()));
                        continue;
                    }

                    if (($file = @tempnam(sys_get_temp_dir(), 'thread_attach')) === false) {
                        continue;
                    }

                    file_put_contents($file, $attachment->getDecodedContent());

                    $emails[$message->getNumber()]['attachments'][] = [
                        'filename' => $attachment->getFilename(),
                        'filepath' => $file,
                    ];
                }
            }

            $this->messagesToDelete[] = $message->getNumber();
        }

        return $emails;
    }

    public function disconnect()
    {
        if ($this->connection !== null) {
            $this->connection->expunge();
            $this->connection = null;
        }
    }

    public function clear()
    {
        if ($this->connection !== null) {
            if (!empty($this->messagesToDelete)) {
                $mailbox = $this->connection->getMailbox(self::MAILBOX);
                foreach ($this->messagesToDelete as $id) {
                    $mailbox->getMessage($id)->delete();
                }
            }

            $this->disconnect();
        }
    }
}
