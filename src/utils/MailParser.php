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
use \Html2Text\Html2Text;

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
        $rawText = $message->getTextContent() ? : Html2Text::convert($message->getHtmlContent());
        $text = $encoding === 'UTF-8' ? $rawText : mb_convert_encoding($rawText, 'UTF-8', $encoding);
        return EmailReplyParser::parseReply($text);

    }

    public function getAttachments(Message $message) : array
    {
        $attachments = [];
        if ($message->getAttachmentCount() === 0) {
            return $attachments;
        }


        foreach ($message->getAllAttachmentParts() as $att) {
            if (($file = @tempnam(sys_get_temp_dir(), 'thread_attach')) === false) {
                continue;
            }

            $handle = fopen($file, "w");
            fwrite($handle, stream_get_contents($att->getContentResourceHandle()));
            fclose($handle);

            $attachments[] = [
                'content-type' => $att->getHeaderValue('Content-Type'),
                'filename' => $att->getHeaderParameter('content-type', 'name'),
                'filepath' => $file,
            ];
        }

        return $attachments;
    }
}
