<?php
/**
 * hiAPI Mail Fetch
 *
 * @link      https://github.com/hiqdev/hiapi-fetchmail
 * @package   hiapi-fetchmail
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

$definitions = [
    'fetchmailTool' => [
        '__class' => \hiapi\fetchmail\FetchMailTool::class,
    ],
];

return class_exists('Yii') ? ['container' => ['definitions' => $definitions]] : $definitions;
