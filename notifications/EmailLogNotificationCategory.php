<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\notifications;

use humhub\modules\notification\components\NotificationCategory;
use humhub\modules\notification\targets\WebTarget;
use humhub\modules\user\models\User;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Will be automatically added in the notifications settings form
 */
class EmailLogNotificationCategory extends NotificationCategory
{
    /**
     * @inheritdoc
     */
    public $id = 'email-log';

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('EmailLogModule.notification', 'Email sending errors');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('EmailLogModule.notification', 'Receive notifications when an email cannot be sent.');
    }

    /**
     * @inheritdoc
     */
    public function isVisible(?User $user = null)
    {
        return $user ? $user->isSystemAdmin() : Yii::$app->user->isAdmin();
    }

    /**
     * Prevent disabling web notifications, as emails may not work
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function getFixedSettings()
    {
        $webTarget = Yii::createObject(WebTarget::class);
        return [
            $webTarget->id,
        ];
    }
}
