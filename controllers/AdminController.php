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
use humhub\modules\emailLog\models\EmailLog;
use humhub\modules\emailLog\models\EmailLogSearch;
use Yii;
use yii\web\NotFoundHttpException;

class AdminController extends Controller
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
        $searchModel = new EmailLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->get());

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'statusCounts' => $searchModel->getStatusCounts(),
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->renderAjax('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionResend($id)
    {
        $this->forcePostRequest();
        $model = $this->findModel($id);

        if (!$model->hasContent()) {
            $this->view->error(Yii::t('EmailLogModule.base', 'This email cannot be resent as its content has not been stored.'));
            return $this->redirect(['index']);
        }

        $mail = Yii::$app->mailer->compose();
        if ($model->html_body) {
            $mail->setHtmlBody($model->html_body);
        }
        if ($model->text_body) {
            $mail->setTextBody($model->text_body);
        }
        $mail->setTo($model->email);
        $mail->setSubject((string)$model->subject);

        if ($mail->send()) {
            $this->view->success(Yii::t('EmailLogModule.base', 'The email has been resent.'));
        } else {
            $this->view->error(Yii::t('EmailLogModule.base', 'The email could not be resent. See the log for details.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function findModel($id): EmailLog
    {
        $model = EmailLog::findOne(['id' => (int)$id]);
        if ($model === null) {
            throw new NotFoundHttpException();
        }
        return $model;
    }
}
