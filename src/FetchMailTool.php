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

use \hiapi\fetchmail\utils\MailParser;
use \Ddeboer\Imap\Server;

/**
 * hiAPI FetchMail Tool.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class FetchMailTool extends \hiapi\components\AbstractTool
{
    protected $connection;

    public function __construct($base, $data = [])
    {
        parent::__construct($base, $data);

        foreach (['url','login', 'password'] as $argument) {
            if (empty($this->data[$argument])) {
                throw new Exception(ucfirst($argument) . ' could not be empty');
            }
        }

        $server = new Server(
            $this->data['url']
        );

        if (emty($server)) {
            throw new \Exception('no connection');
        }

        $connection = $server->authenticate($this->data['login'], $this->data['password']);
        $this->connection = $connection;

    }

    public function __destruct()
    {
        if ($this->pop3 !== null) {
            $this->pop3->disconnect();
            unset($this->pop3);
        }
    }

    public function mailsFetch($params = [])
    {
        $mailbox = $this->connection->getMailbox('INBOX');
        $messages = $mailbox->getMessages();
        if (empty($messages)) {
            return [];
        }
        foreach ($messages as $message) {
            $emails[$message->getNumber()] = [
                'number' => $message->getNumber(),
                'message_id' => $message->getId(),
                'from_email' => $message->getFrom(),
                'subject' => $message->getSubject(),
                'message' => $message->getBodyText() ? : $message->getBodyHtml(),
            ];

            if ($message->getAttachments()) {
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->isEmbeddedMessage()) {
                        $embedded = $attachment->getEmbeddedMessage();
                        $emails[$message->getNumber()]['message' => $message->getBodyText() ? : $message->getBodyHtml();
                        continue;
                    }

                    file_put_contents(temp_name());
                }
            }

            $mailbox->getMessage($message->getNumber())->delete();
        }

        return $parsedEmails;
    }

    protected function mailsGetAll()
    {
        $total = $this->pop3->getEmailTotal();
        if ($total === 0) {
            return [];
        }

        return $this->pop3->getEmails(0, $total);
    }
}
