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
use \Eden\Mail\Pop3;

/**
 * hiAPI FetchMail Tool.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class FetchMailTool extends \hiapi\components\AbstractTool
{
    protected $pop3;
    protected $parser;

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
        if ($this->pop3 !== null) {
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

        if ($this->data['internal_parser']) {
            return $emails;
        }

        foreach ($emails as $id => $email) {
            $parsedEmails[] = $this->parser->parseMail($email['raw']);
            $this->pop3->remove($id + 1);
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
