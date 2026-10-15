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
use humhub\modules\emailLog\models\EmailLogSearch;
use humhub\modules\emailLog\Module;
use humhub\widgets\bootstrap\Badge;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;
use humhub\widgets\GridView;
use humhub\widgets\modal\ModalButton;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;

/**
 * @var $this View
 * @var $searchModel EmailLogSearch
 * @var $dataProvider ActiveDataProvider
 * @var $statusCounts array
 */

$module = Module::getInstance();
$statusLabels = EmailLog::getStatusLabels();
$statusColors = EmailLog::getStatusColors();
$searchParams = Yii::$app->request->get('EmailLogSearch', []);
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Button::light(Yii::t('EmailLogModule.admin', 'Settings'))
            ->link($module->getConfigUrl())
            ->icon('gears')
            ->style('margin-left: 6px;')
            ->right()
            ->sm() ?>

        <strong><?= Yii::t('EmailLogModule.admin', 'Email logs') ?></strong>
    </div>

    <div class="panel-body">
        <div class="mb-3">
            <?= Html::a(
                Yii::t('EmailLogModule.admin', 'All') . ' ' . Badge::light((string)array_sum($statusCounts)),
                ['index', 'EmailLogSearch' => array_merge($searchParams, ['status' => null])],
                ['class' => 'btn btn-sm ' . ($searchModel->status ? 'btn-light' : 'btn-primary')],
            ) ?>
            <?php foreach ($statusCounts as $status => $count): ?>
                <?= Html::a(
                    Html::encode($statusLabels[$status] ?? $status) . ' ' . Badge::instance((string)$count, $statusColors[$status] ?? 'light'),
                    ['index', 'EmailLogSearch' => array_merge($searchParams, ['status' => $status])],
                    ['class' => 'btn btn-sm ' . ($searchModel->status === $status ? 'btn-primary' : 'btn-light')],
                ) ?>
            <?php endforeach; ?>
        </div>

        <?php $form = ActiveForm::begin(['method' => 'get', 'action' => Url::to(['index'])]); ?>
        <div class="container gx-0 overflow-x-hidden">
            <div class="row gy-2">
                <div class="col-lg-5">
                    <div class="input-group">
                        <?= Html::activeTextInput($searchModel, 'freeText', [
                                'class' => 'form-control',
                                'placeholder' => Yii::t('EmailLogModule.admin', 'Search by email address, subject, user name or ID'),
                            ]) ?>
                        <button class="btn btn-light" type="submit"><i class="fa fa-search"></i></button>
                    </div>
                </div>
                <div class="col-lg-3">
                    <?= Html::activeDropDownList($searchModel, 'status', $statusLabels, [
                            'class' => 'form-control',
                            'prompt' => Yii::t('EmailLogModule.admin', 'All statuses'),
                            'data-action-change' => 'ui.form.submit',
                        ]) ?>
                </div>
                <div class="col-lg-2">
                    <?= Html::activeInput('date', $searchModel, 'dateFrom', [
                            'class' => 'form-control',
                            'title' => $searchModel->getAttributeLabel('dateFrom'),
                            'data-action-change' => 'ui.form.submit',
                        ]) ?>
                </div>
                <div class="col-lg-2">
                    <?= Html::activeInput('date', $searchModel, 'dateTo', [
                            'class' => 'form-control',
                            'title' => $searchModel->getAttributeLabel('dateTo'),
                            'data-action-change' => 'ui.form.submit',
                        ]) ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
        <br>

        <div class="table-responsive">
            <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        [
                            'attribute' => 'created_at',
                            'format' => 'datetime',
                            'contentOptions' => ['class' => 'text-nowrap'],
                        ],
                        [
                            'attribute' => 'email',
                            'format' => 'raw',
                            'value' => static fn(EmailLog $model) => Html::encode($model->email)
                                . ($model->recipient_type !== EmailLog::RECIPIENT_TYPE_TO
                                    ? ' ' . Badge::light(strtoupper($model->recipient_type))
                                    : ''),
                        ],
                        [
                            'attribute' => 'user_id',
                            'format' => 'raw',
                            'value' => static fn(EmailLog $model) => $model->user
                                ? Html::containerLink($model->user)
                                : '',
                        ],
                        [
                            'attribute' => 'subject',
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => static fn(EmailLog $model) => Badge::instance($model->getStatusLabel(), $statusColors[$model->status] ?? 'light')
                                ->tooltip($model->error ?: null),
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view}',
                            'contentOptions' => ['class' => 'text-nowrap'],
                            'buttons' => [
                                'view' => static fn($url, EmailLog $model) => ModalButton::light()
                                    ->load(['view', 'id' => $model->id])
                                    ->icon('eye')
                                    ->options(['aria-label' => Yii::t('EmailLogModule.admin', 'View')])
                                    ->sm(),
                            ],
                        ],
                    ],
                ]) ?>
        </div>
    </div>
</div>
