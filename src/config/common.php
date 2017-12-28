<?php
/**
 * hiAPI Mail Fetch
 *
 * @link      https://github.com/hiqdev/hiapi-fetchmail
 * @package   hiapi-fetchmail
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

return [
    'container' => [
        'definitions' => [
            'fetchmail-tool' => [
                'class' => \hiapi\fetchmail\FetchMailTool::class,
            ],
        ],
    ],
];
