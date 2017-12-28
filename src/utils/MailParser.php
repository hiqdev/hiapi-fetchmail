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
use \ZBateson\MailMimeParser\Message;
use \EmailReplyParser\EmailReplyParser;

/**
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class MailParser
{
    public function __construct()
    {
        $this->mailParser = new MailMimeParser();
    }

    public function parseMail(string $rawEmailString) : array
    {
        return $this->parseMessage($this->mailParser->parse($rawEmailString));
    }

    public function parseMessage(Message $message) : array
    {
        return [
            'from_email' => $this->getFromEmail($message),
            'from_name' => $this->getFromName($message),
            'subject' => $this->getSubject($message),
            'message' => $this->getMessage($message),
            'attachments' => $this->getAttachments($message),
        ];
    }

    public function getFromEmail(Message $message) : string
    {
        return $message->getHeaderValue('from');
    }

    public function getFromName(Message $message) : string
    {
        return $message->getHeader('from')->getPersonName();
    }

    public function getSubject(Message $message) : string
    {
        return $message->getHeaderValue('subject');
    }

    public function getContentType(Message $message) : string
    {
        return $message->getHeader('Content-Type');
    }

    public function getCharset(Message $message) : string
    {
         return strtoupper($message->getHeaderParameter('Content-Type', 'charset', 'utf-8'));
    }

    public function getMessage(Message $message) : string
    {
        $encoding = $this->getCharset($message);
        $rawText = $message->getTextContent() ? : $message->getHtmlContent();
        $text = $encoding === 'UTF-8' ? $rawText : mb_convert_encoding($rawText, 'UTF-8', $encoding);
        return EmailReplyParser::parseReply($text);

    }

    public function getAttachments(Message $message) : array
    {
        if ($message->getAttachmentCount() === 0) {
            return [];
        }

        $messageAttachments = [];
        foreach ($message->getAllAttachmentParts() as $attachment) {

        }

        return $messageAttachments;
    }
}
