<?php
/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

use humhub\components\View;
use humhub\modules\emailLog\models\Configuration;
use humhub\modules\emailLog\Module;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;

/**
 * @var $this View
 * @var $model Configuration
 */

$module = Module::getInstance();
?>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Button::light(Yii::t('EmailLogModule.config', 'Email logs'))
            ->link(['/email-log/admin/index'])
            ->icon('list')
            ->style('margin-left: 6px;')
            ->right()
            ->sm() ?>

        <strong><?= $module->getName() ?></strong>

        <div class="text-body-secondary">
            <?= $module->getDescription() ?>
        </div>
    </div>

    <div class="panel-body">
        <div class="alert alert-info cuzy-free-module-info" role="alert">
            This module was created and is maintained by
            <a href="https://www.cuzy.app/"
               target="_blank">CUZY.APP (view other modules)</a>.
            <br>
            It's free, but it's the result of a lot of design and maintenance work over time.
            <br>
            If it's useful to you, please consider
            <a href="https://www.cuzy.app/checkout/donate/"
               target="_blank">making a donation</a>
            or
            <a href="https://github.com/cuzy-app/email-log"
               target="_blank">participating in the code</a>.
            Thanks!
        </div>

        <?php $form = ActiveForm::begin(['acknowledge' => true]); ?>
        <?= $form->field($model, 'purgePeriod')->input('number', ['min' => 0]) ?>
        <?= $form->field($model, 'logErrors')->checkbox() ?>
        <?= $form->field($model, 'notifyAdmins')->checkbox() ?>
        <?= $form->field($model, 'showInAdminMenu')->checkbox() ?>
        <?= $form->field($model, 'contentStorage')->dropDownList(Configuration::getContentStorageOptions()) ?>
        <?= $form->field($model, 'redactTokens')->checkbox() ?>
        <?= $form->field($model, 'redactedUrlParameters')->textInput() ?>
        <?= Button::save()->submit() ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
