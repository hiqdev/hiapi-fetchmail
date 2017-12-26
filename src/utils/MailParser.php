<?php
/**
 * hiAPI Fetchmail plugin
 *
 * @link      https://github.com/hiqdev/hiapi-fetchmail
 * @package   hiapi-fetchmail
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\fetchmail\utils;

use \ZBateson\MailMimeParser\MailMimeParser;
use \EmailReplyParser\Parser\EmailReplyParser;

/**
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class MailParser
{
    public function __construct()
    {
        $this->mailParser = new MailMimeParser();
    }

    public function parseMail(string $rawEmailString) : MailMimeParser
    {
        return $this->mailParser->parse($rawEmailString);
    }

    public function parseMessage(MailMimeParser $message) : array
    {
        return [
            'from_email' => $this->getFromEmail($message),
            'from_name' => $this->getFromName($message),
            'subject' => $this->getSubject($message),
            'message' => $this->getMessage($message),
            'attachments' => $this->getAttachments($message),
        ];
    }

    public function getFromEmail(MailMimeParser $message) : string
    {
        return $message->getHeaderValue('from');
    }

    public function getFromName(MailMimeParser $message) : string
    {
        return $message->getHeader('from')->getPersonName();
    }

    public function getSubject(MailMimeParser $message) : string
    {
        return $message->getHeaderValue('subject');
    }

    public function getContentType(MailMimeParser $message) : string
    {
        return $message->getHeader('Content-Type');
    }

    public function getCharset(MailMimeParser $message) : string
    {
         return strtoupper($message->getHeaderParameter('Content-Type', 'charset', 'utf-8'));
    }

    public function getMessage(MailMimeParser $message) : string
    {
        $encoding = $this->getCharset($message);
        $rawText = $message->getTextPart() ? : $message->getHtmlPart();
        $text = $encoding === 'UTF-8' ? $rawText : mb_convert_encoding($rawText, 'UTF-8', $encoding);
        return EmailReplyParser::parseReply($text);

    }

    public function getAttachments(MailMimeParser $message) : array
    {
        return [];
    }

}
