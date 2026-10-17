<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\models;

use humhub\components\SettingsManager;
use Yii;
use yii\base\Model;

class Configuration extends Model
{
    public const CONTENT_STORAGE_FULL = 'full';
    public const CONTENT_STORAGE_TEXT = 'text';
    public const CONTENT_STORAGE_NONE = 'none';

    public SettingsManager $settingsManager;

    /**
     * @var int|string Number of days after which logs are deleted (0 = never)
     */
    public int|string $purgePeriod = 30;
    public bool|string $logErrors = true;
    public bool|string $notifyAdmins = true;
    public bool|string $showInAdminMenu = true;
    public string $contentStorage = self::CONTENT_STORAGE_NONE;
    public bool|string $redactTokens = true;
    /**
     * @var string Comma separated list of URL query parameter names whose values are redacted
     */
    public ?string $redactedUrlParameters = 'token, key, secret, password, hash, code, signature, sig, auth, access_token';

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['purgePeriod'], 'integer', 'min' => 0],
            [['logErrors', 'notifyAdmins', 'showInAdminMenu', 'redactTokens'], 'boolean'],
            [['contentStorage'], 'in', 'range' => array_keys(static::getContentStorageOptions())],
            [['redactedUrlParameters'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'purgePeriod' => Yii::t('EmailLogModule.config', 'Delete logs older than (days)'),
            'logErrors' => Yii::t('EmailLogModule.config', 'Add an error in the HumHub log when an email cannot be sent'),
            'notifyAdmins' => Yii::t('EmailLogModule.config', 'Notify system administrators when an email cannot be sent'),
            'showInAdminMenu' => Yii::t('EmailLogModule.config', 'Add an "Email log" entry in the Administration menu'),
            'contentStorage' => Yii::t('EmailLogModule.config', 'Email content to store'),
            'redactTokens' => Yii::t('EmailLogModule.config', 'Redact security tokens in the stored content'),
            'redactedUrlParameters' => Yii::t('EmailLogModule.config', 'URL parameters to redact'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints(): array
    {
        return [
            'purgePeriod' => Yii::t('EmailLogModule.config', '0 to never delete logs.'),
            'logErrors' => Yii::t('EmailLogModule.config', 'HumHub already logs the transport error, but without the recipient and the subject.'),
            'notifyAdmins' => Yii::t('EmailLogModule.config', 'At most one notification per hour.'),
            'contentStorage' => Yii::t('EmailLogModule.config', 'Emails can contain personal data. Storing no content reduces the database size, but the email cannot be previewed or resent.'),
            'redactTokens' => Yii::t('EmailLogModule.config', 'Recommended: emails such as password recovery, registration invitation or email change contain links with secret tokens that would allow anyone having access to the logs to take over an account.'),
            'redactedUrlParameters' => Yii::t('EmailLogModule.config', 'Comma separated list. The values of these URL query parameters are replaced with {redacted}.', ['redacted' => EmailLog::REDACTED]),
        ];
    }

    public static function getContentStorageOptions(): array
    {
        return [
            self::CONTENT_STORAGE_FULL => Yii::t('EmailLogModule.config', 'HTML and plain text'),
            self::CONTENT_STORAGE_TEXT => Yii::t('EmailLogModule.config', 'Plain text only'),
            self::CONTENT_STORAGE_NONE => Yii::t('EmailLogModule.config', 'No content'),
        ];
    }

    public function getRedactedUrlParameterList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string)$this->redactedUrlParameters))));
    }

    public function loadBySettings(): void
    {
        $this->purgePeriod = (int)$this->settingsManager->get('purgePeriod', $this->purgePeriod);
        $this->logErrors = (bool)$this->settingsManager->get('logErrors', $this->logErrors);
        $this->notifyAdmins = (bool)$this->settingsManager->get('notifyAdmins', $this->notifyAdmins);
        $this->showInAdminMenu = (bool)$this->settingsManager->get('showInAdminMenu', $this->showInAdminMenu);
        $this->contentStorage = $this->settingsManager->get('contentStorage', $this->contentStorage);
        $this->redactTokens = (bool)$this->settingsManager->get('redactTokens', $this->redactTokens);
        $this->redactedUrlParameters = $this->settingsManager->get('redactedUrlParameters', $this->redactedUrlParameters);
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $this->settingsManager->set('purgePeriod', (int)$this->purgePeriod);
        $this->settingsManager->set('logErrors', (bool)$this->logErrors);
        $this->settingsManager->set('notifyAdmins', (bool)$this->notifyAdmins);
        $this->settingsManager->set('showInAdminMenu', (bool)$this->showInAdminMenu);
        $this->settingsManager->set('contentStorage', $this->contentStorage);
        $this->settingsManager->set('redactTokens', (bool)$this->redactTokens);
        $this->settingsManager->set('redactedUrlParameters', $this->redactedUrlParameters);

        return true;
    }
}
