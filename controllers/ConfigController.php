<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\controllers;

use humhub\components\access\ControllerAccess;
use humhub\modules\admin\components\Controller;
use humhub\modules\emailLog\Module;
use Yii;

class ConfigController extends Controller
{
    /**
     * Restricted to system administrators, as logs contain personal data
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [[ControllerAccess::RULE_ADMIN_ONLY]];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $model = Module::getInstance()->getConfiguration();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
