<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog;

use humhub\helpers\ControllerHelper;
use humhub\modules\admin\widgets\AdminMenu;
use humhub\modules\emailLog\components\EmailLogger;
use humhub\modules\emailLog\models\EmailLog;
use humhub\modules\ui\menu\MenuLink;
use Throwable;
use Yii;
use yii\helpers\Url;
use yii\mail\MailEvent;

class Events
{
    public static function onMailerBeforeSend(MailEvent $event): void
    {
        try {
            EmailLogger::beforeSend($event);
        } catch (Throwable $e) {
            Yii::error('Email Log module: ' . $e->getMessage(), 'email-log');
        }
    }

    public static function onMailerAfterSend(MailEvent $event): void
    {
        try {
            EmailLogger::afterSend($event);
        } catch (Throwable $e) {
            Yii::error('Email Log module: ' . $e->getMessage(), 'email-log');
        }
    }

    public static function onAdminMenuInit($event): void
    {
        try {
            // Don't move in 'isVisible' as it doesn't work in all cases and because the "if" costs less
            if (!Yii::$app->user->isAdmin() || !Module::getInstance()->getConfiguration()->showInAdminMenu) {
                return;
            }

            /** @var AdminMenu $menu */
            $menu = $event->sender;
            $menu->addEntry(new MenuLink([
                'label' => Yii::t('EmailLogModule.base', 'Email log'),
                'icon' => Module::getInstance()->icon,
                'sortOrder' => 1500,
                'isActive' => ControllerHelper::isActivePath('email-log', 'admin'),
                'url' => Url::to(['/email-log/admin/index']),
                'isVisible' => true,
            ]));
        } catch (Throwable $e) {
            Yii::error('Email Log module: ' . $e->getMessage(), 'email-log');
        }
    }

    public static function onCronDailyRun($event): void
    {
        try {
            $purgePeriod = Module::getInstance()->getConfiguration()->purgePeriod;
            if ($purgePeriod > 0) {
                EmailLog::deleteAll(['<', 'created_at', date('Y-m-d H:i:s', strtotime('-' . $purgePeriod . ' days'))]);
            }
        } catch (Throwable $e) {
            Yii::error('Email Log module: ' . $e->getMessage(), 'email-log');
        }
    }
}
