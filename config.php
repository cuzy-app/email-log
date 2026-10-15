<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\commands\CronController;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\emailLog\Events;
use yii\mail\BaseMailer;

return [
    'id' => 'email-log',
    'class' => humhub\modules\emailLog\Module::class,
    'namespace' => 'humhub\modules\emailLog',
    'events' => [
        [
            'class' => BaseMailer::class,
            'event' => BaseMailer::EVENT_BEFORE_SEND,
            'callback' => [Events::class, 'onMailerBeforeSend'],
        ],
        [
            'class' => BaseMailer::class,
            'event' => BaseMailer::EVENT_AFTER_SEND,
            'callback' => [Events::class, 'onMailerAfterSend'],
        ],
        [
            'class' => AdminMenu::class,
            'event' => AdminMenu::EVENT_INIT,
            'callback' => [Events::class, 'onAdminMenuInit'],
        ],
        [
            'class' => CronController::class,
            'event' => CronController::EVENT_ON_DAILY_RUN,
            'callback' => [Events::class, 'onCronDailyRun'],
        ],
    ],
];
