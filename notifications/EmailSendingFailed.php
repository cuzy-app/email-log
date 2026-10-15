<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\notifications;

use humhub\modules\emailLog\models\EmailLog;
use humhub\modules\emailLog\Module;
use humhub\modules\notification\components\BaseNotification;
use humhub\modules\ui\icon\widgets\Icon;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Sent to system administrators when an email cannot be sent
 *
 * @property EmailLog $source
 */
class EmailSendingFailed extends BaseNotification
{
    /**
     * @inheritdoc
     */
    public $moduleId = 'email-log';

    /**
     * @inheritdoc
     */
    public $requireOriginator = false;

    /**
     * @inheritdoc
     */
    public function category()
    {
        return new EmailLogNotificationCategory();
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return Url::to(['/email-log/admin/index', 'EmailLogSearch' => ['status' => EmailLog::STATUS_FAILED]], true);
    }

    /**
     * @inheritdoc
     */
    public function html()
    {
        return
            Html::tag('div', Icon::get(Module::getInstance()->icon)->size(Icon::SIZE_2x), ['class' => 'rounded float-start', 'style' => 'margin-right: 10px'])
            . $this->getWebAndMailSubject(true);
    }

    /**
     * @inheritdoc
     */
    public function getMailSubject()
    {
        return $this->getWebAndMailSubject();
    }

    public function getWebAndMailSubject(bool $html = false): string
    {
        $email = $this->source instanceof EmailLog ? $this->source->email : '';
        $subject = $this->source instanceof EmailLog ? (string)$this->source->subject : '';

        return Yii::t('EmailLogModule.notification', 'The email "{subject}" could not be sent to {email}.', [
            'subject' => $html ? Html::tag('strong', Html::encode($subject)) : $subject,
            'email' => $html ? Html::tag('strong', Html::encode($email)) : $email,
        ]);
    }
}
