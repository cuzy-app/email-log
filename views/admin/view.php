<?php
/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\components\View;
use humhub\helpers\Html;
use humhub\modules\emailLog\models\EmailLog;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\bootstrap\Tabs;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/**
 * @var $this View
 * @var $model EmailLog
 */

$resendButton = '';
if ($model->hasContent()) {
    $resendButton = Button::primary(Yii::t('EmailLogModule.admin', 'Resend'))
        ->action('client.post', ['resend', 'id' => $model->id])
        ->icon('paper-plane')
        ->confirm(
            Yii::t('EmailLogModule.admin', 'Resend this email?'),
            $model->isRedacted()
                ? Yii::t('EmailLogModule.admin', 'This email contains redacted links ({redacted}) that will not work anymore.', ['redacted' => EmailLog::REDACTED])
                : null,
        );
}

$tabs = [];
if ($model->html_body) {
    $tabs[] = [
        'label' => Yii::t('EmailLogModule.admin', 'HTML'),
        // Sandboxed iframe: no script execution and isolated from the HumHub page
        'content' => Html::tag('iframe', '', [
            'srcdoc' => $model->html_body,
            'sandbox' => '',
            'referrerpolicy' => 'no-referrer',
            'style' => 'width: 100%; height: 60vh; border: 1px solid var(--bs-border-color);',
        ]),
    ];
}
if ($model->text_body) {
    $tabs[] = [
        'label' => Yii::t('EmailLogModule.admin', 'Plain text'),
        'content' => Html::tag('pre', Html::encode($model->text_body), ['style' => 'white-space: pre-wrap; max-height: 60vh;']),
    ];
}
?>

<?php Modal::beginDialog([
    'title' => Html::encode($model->subject),
    'size' => Modal::SIZE_LARGE,
    'footer' => ModalButton::cancel(Yii::t('base', 'Close')) . ' ' . $resendButton,
]) ?>

<table class="table table-sm">
    <tr>
        <th><?= $model->getAttributeLabel('created_at') ?></th>
        <td><?= Yii::$app->formatter->asDatetime($model->created_at) ?></td>
    </tr>
    <tr>
        <th><?= $model->getAttributeLabel('email') ?></th>
        <td>
            <?= Html::encode($model->email) ?>
            <?php if ($model->recipient_type !== EmailLog::RECIPIENT_TYPE_TO): ?>
                <?= Badge::light(strtoupper($model->recipient_type)) ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php if ($model->user): ?>
        <tr>
            <th><?= $model->getAttributeLabel('user_id') ?></th>
            <td><?= Html::containerLink($model->user) ?></td>
        </tr>
    <?php endif; ?>
    <tr>
        <th><?= $model->getAttributeLabel('status') ?></th>
        <td><?= Badge::instance($model->getStatusLabel(), EmailLog::getStatusColors()[$model->status] ?? 'light') ?></td>
    </tr>
    <?php if ($model->error): ?>
        <tr>
            <th><?= $model->getAttributeLabel('error') ?></th>
            <td><pre style="white-space: pre-wrap;"><?= Html::encode($model->error) ?></pre></td>
        </tr>
    <?php endif; ?>
</table>

<?php if ($tabs): ?>
    <?= Tabs::widget(['items' => $tabs]) ?>
<?php else: ?>
    <p class="text-body-secondary"><?= Yii::t('EmailLogModule.admin', 'The content of this email has not been stored.') ?></p>
<?php endif; ?>

<?php Modal::endDialog() ?>
