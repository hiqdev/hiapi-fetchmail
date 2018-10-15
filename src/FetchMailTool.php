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

        $server = new Server($this->data['url'], 143, '/novalidate-cert');

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
        foreach ($messages as $message) {
            $emails[$message->getNumber()] = [
                'number' => $message->getNumber(),
                'message_id' => $message->getId(),
                'from_email' => $message->getFrom()->getAddress(),
                'from_name' => $message->getFrom()->getName(),
                'subject' => $message->getSubject(),
                'message' => $message->getBodyText() ? : Html2Text::convert($message->getBodyHtml()),
            ];

            if ($message->getAttachments()) {
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->isEmbeddedMessage()) {
                        $embedded = $attachment->getEmbeddedMessage();
                        $emails[$message->getNumber()]['message'] = $message->getBodyText() ? : Html2Text::convert($message->getBodyHtml());
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
