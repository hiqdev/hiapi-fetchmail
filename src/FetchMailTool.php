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
use \Bladeroot\Mail\Pop3;

/**
 * hiAPI FetchMail Tool.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class FetchMailTool extends \hiapi\components\AbstractTool
{
    protected $pop3;
    protected $parser;

    protected $messagesToDelete = [];

    public function __construct($base, $data = [])
    {
        parent::__construct($base, $data);

        foreach (['url','login', 'password'] as $argument) {
            if (empty($this->data[$argument])) {
                throw new Exception(ucfirst($argument) . ' could not be empty');
            }
        }

        $this->pop3 = new Pop3($this->data['url'],
                $this->data['login'],
                $this->data['password'],
                $this->data['port'],
                $this->data['ssl'],
                $this->data['tls']);
        $this->parser = new MailParser();
    }

    public function __destruct()
    {
        $this->diconnect();
    }

    public function disconnect()
    {
        if ($this->pop3 !== null) {
            if (!empty($this->messagesToDelete)) {
                $this->pop3->remove($this->messagesToDelete);
            }
            $this->pop3->disconnect();
            unset($this->pop3);
        }
    }

    public function mailsFetch($params = [])
    {
        $emails = $this->mailsGetAll();
        if (empty($emails)) {
            return true;
        }

        foreach ($emails as $id => $email) {
            $removes[] = $id;
            $parsedEmails[] = $this->parser->parseMail($email);
        }

        $this->messagesToDelete = $removes;
        return $parsedEmails;
    }

    protected function mailsGetAll()
    {
        $total = $this->pop3->getEmailTotal();
        if ($total === 0) {
            return [];
        }

        $emails = $this->pop3->getEmails(0, $total);
        $this->disconnect();
        return $emails;
    }
}
