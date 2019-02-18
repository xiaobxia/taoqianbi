<?php

namespace backend\controllers;

use common\models\LoanOrderDayQuota;
use common\models\LoanOrderDefaultQuota;
use Yii;
use yii\web\NotFoundHttpException;


/**
 * 放款订单配额
 */
class LoanOrderQuotaController extends BaseController
{

    
    /**
     * @name 默认额度列表
     * @return mixed
     */
    public function actionIndex()
    {
        $list = LoanOrderDefaultQuota::find()->all();
        return $this->render('index', [
            'list' => $list,
        ]);
    }

    /**
     * @name 每日限额列表
     */
    public function actionDayQuotaList() {
        $day_quota_table = LoanOrderDayQuota::tableName();

        $db = LoanOrderDayQuota::getDb();
        $count = $db->createCommand("SELECT COUNT(*) FROM {$day_quota_table}")->queryScalar();
        $pagination = new \yii\data\Pagination([
            'totalCount'=>$count
        ]);

        $sql = "SELECT * FROM {$day_quota_table}  ORDER BY id DESC LIMIT {$pagination->getOffset()},{$pagination->getLimit()}";
        $rows = $db->createCommand($sql)->queryAll();

        return $this->render('day-quota-list', [
            'rows' => $rows,
            'pagination'=>$pagination
        ]);
    }
    
    /**
     * @name 更新每日配额
     */
    public function actionUpdateDayQuota($id, $return_url=null) {
        $model = LoanOrderDayQuota::findOne((int)$id);
        if($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            if($return_url) {
                return $this->redirect($return_url);
            } else {
                return $this->redirect(['day-quota-list']);
            }
        }

        return $this->render('update-day-quota',[
            'model'=>$model,
        ]);
    }
    
     /**
     * @name 添加每日配额
     */
    public function actionAddDayQuota($return_url=null) {
        $model = new LoanOrderDayQuota();
        if($this->request->isPost){
            if($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
                if($return_url) {
                    return $this->redirect($return_url);
                } else {
                    return $this->redirect(['day-quota-list']);
                }
            }
        }else{
            return $this->render('update-day-quota',[
                'model'=>$model,
            ]);
        }


    }


    /**
     * @name 更新默认额度
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if($this->request->isPost){
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return $this->redirect(['index']);
            }
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }


    protected function findModel($id)
    {
        if (($model = LoanOrderDefaultQuota::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
