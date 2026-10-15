<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog;

use humhub\modules\emailLog\models\Configuration;
use humhub\modules\emailLog\notifications\EmailSendingFailed;
use Yii;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 *
 * @property-read string $configUrl
 * @property-read Configuration $configuration
 * @property-read string[] $notifications
 */
class Module extends \humhub\components\Module
{
    /**
     * @var string defines the icon
     */
    public $icon = 'envelope';

    private ?Configuration $_configuration = null;

    /**
     * @throws NotFoundHttpException
     */
    public static function getInstance(): static
    {
        /** @var ?static $module */
        $module = Yii::$app->getModule('email-log');
        if (!$module?->isEnabled) {
            throw new NotFoundHttpException('Email Log module not enabled');
        }
        return $module;
    }

    public function getConfiguration(): Configuration
    {
        if ($this->_configuration === null) {
            $this->_configuration = new Configuration(['settingsManager' => $this->settings]);
            $this->_configuration->loadBySettings();
        }
        return $this->_configuration;
    }

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/email-log/config']);
    }

    /**
     * @inerhitdoc
     */
    public function getName()
    {
        return Yii::t('EmailLogModule.base', 'Email Log');
    }

    /**
     * @inerhitdoc
     */
    public function getDescription()
    {
        return Yii::t('EmailLogModule.base', 'Logs all emails sent by HumHub (recipient, subject, content, status and error), with a searchable administration list, failure alerts and auto-purge.');
    }

    /**
     * @inheridoc
     */
    public function getNotifications()
    {
        if (Yii::$app->user->isAdmin()) {
            return [
                EmailSendingFailed::class,
            ];
        }
        return [];
    }
}
