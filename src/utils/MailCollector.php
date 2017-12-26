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

use \Eden\Mail\Pop3;

/**
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class MailCollector extends Pop3
{
    /**
     * Returns a list of raw emails given the range
     *
     * @param number $start Pagination start
     * @param number $range Pagination range
     *
     * @return array
     */
    protected function getRawEmails($start = 0, $range = 10)
    {
        Argument::i()
            ->test(1, 'int')
            ->test(2, 'int');

        $total = $this->getEmailTotal();

        if ($total == 0) {
            return [];
        }

        if (!is_array($start)) {
            $range = $range > 0 ? $range : 1;
            $start = $start >= 0 ? $start : 0;
            $max = $total - $start;

            if ($max < 1) {
                $max = $total;
            }

            $min = $max - $range + 1;

            if ($min < 1) {
                $min = 1;
            }

            $set = $min . ':' . $max;

            if ($min == $max) {
                $set = $min;
            }
        }

        $emails = [];
        for ($i = $min; $i <= $max; $i++) {
            $email = $this->call('RETR '.$i, true);
            if (is_array($email)) {
                $email = implode("\n", $email);
            }
            $emails[] = $email;
        }

        return $emails;

    }

    /**
     * Returns a list of parsed emails given the range
     *
     * @param number $start Pagination start
     * @param number $range Pagination range
     *
     * @return array
     */
    public function getParsedEmails($start = 0, $range = 10)
    {
        $emails = $this->getRawEmails($start, $range);
        if (empty($emails)) {
            return $emails;
        }

        $parsedEmails = [];
        foreach ($emails as $email) {
            $parsedEmails[] = $this->getEmailFormat($email);
        }

        return $parsedEmails;
    }

    /**
     * Returns a list of unparsed emails given the range
     *
     * @param number $start Pagination start
     * @param number $range Pagination range
     *
     * @return array
     */
    public function getUnparsedEmails($start = 0, $range = 10)
    {
        return $this->getRawEmails($start, $range);
    }
}
