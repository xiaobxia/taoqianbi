<?php

namespace backend\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use common\models\fund\FundAccount;

class FundAccountController extends \yii\web\Controller
{
    /**
     * @name 资方账号主体列表 
     * @return type
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => FundAccount::find(),
        ]);
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
        
    }
    
    /**
     * @name 创建 资方账号主体 
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new FundAccount();
        $model->created_at = time();
        $model->updated_at = time();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
           
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * @name 更新账号主体
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
    
    /**
     * Finds the LoanFund model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return LoanFund the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FundAccount::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    

}
