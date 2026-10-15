<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\models;

use humhub\components\ActiveRecord;
use humhub\modules\user\models\User;
use Yii;
use yii\behaviors\AttributeTypecastBehavior;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property string $created_at
 * @property int|null $user_id
 * @property string $email
 * @property string $recipient_type
 * @property string|null $subject
 * @property string|null $html_body
 * @property string|null $text_body
 * @property string $status
 * @property string|null $error
 *
 * @property-read User|null $user
 */
class EmailLog extends ActiveRecord
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SUPPRESSED = 'suppressed';
    public const STATUS_CANCELLED = 'cancelled';

    public const RECIPIENT_TYPE_TO = 'to';
    public const RECIPIENT_TYPE_CC = 'cc';
    public const RECIPIENT_TYPE_BCC = 'bcc';

    public const REDACTED = '[REDACTED]';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'email_log';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'typecast' => [
                'class' => AttributeTypecastBehavior::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'status'], 'required'],
            [['user_id'], 'integer'],
            [['email', 'subject'], 'string', 'max' => 255],
            [['html_body', 'text_body', 'error'], 'string'],
            [['status'], 'in', 'range' => array_keys(static::getStatusLabels())],
            [['recipient_type'], 'in', 'range' => [self::RECIPIENT_TYPE_TO, self::RECIPIENT_TYPE_CC, self::RECIPIENT_TYPE_BCC]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => Yii::t('EmailLogModule.base', 'Date'),
            'user_id' => Yii::t('EmailLogModule.base', 'User'),
            'email' => Yii::t('EmailLogModule.base', 'Email address'),
            'recipient_type' => Yii::t('EmailLogModule.base', 'Recipient type'),
            'subject' => Yii::t('EmailLogModule.base', 'Subject'),
            'html_body' => Yii::t('EmailLogModule.base', 'HTML content'),
            'text_body' => Yii::t('EmailLogModule.base', 'Plain text content'),
            'status' => Yii::t('EmailLogModule.base', 'Status'),
            'error' => Yii::t('EmailLogModule.base', 'Error'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($insert && !$this->created_at) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave($insert);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_SUCCESS => Yii::t('EmailLogModule.base', 'Success'),
            self::STATUS_FAILED => Yii::t('EmailLogModule.base', 'Failed'),
            self::STATUS_SUPPRESSED => Yii::t('EmailLogModule.base', 'Suppressed'),
            self::STATUS_CANCELLED => Yii::t('EmailLogModule.base', 'Cancelled'),
        ];
    }

    public static function getStatusColors(): array
    {
        return [
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_SUPPRESSED => 'secondary',
            self::STATUS_CANCELLED => 'warning',
        ];
    }

    public function getStatusLabel(): string
    {
        return static::getStatusLabels()[$this->status] ?? $this->status;
    }

    public function hasContent(): bool
    {
        return (string)$this->html_body !== '' || (string)$this->text_body !== '';
    }

    public function isRedacted(): bool
    {
        return str_contains((string)$this->html_body, self::REDACTED) || str_contains((string)$this->text_body, self::REDACTED);
    }
}
