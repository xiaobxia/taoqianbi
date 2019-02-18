<?php

namespace backend\controllers;

use Yii;

use common\models\encrypt\RsaEncrypt;
use common\models\encrypt\EncryptKeys;
use backend\models\search\EncryptKeysSearch;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * EncryptKeysController implements the CRUD actions for EncryptKeys model.
 */
class EncryptKeysController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all EncryptKeys models.
     * @return mixed
     */
    public function actionIndex()
    {
        // $dataProvider = new ActiveDataProvider([
        //     'query' => EncryptKeys::find(),
        // ]);

        $searchModel = new EncryptKeysSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single EncryptKeys model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionUpdate()
    {
        $rsaEncrypt = new RsaEncrypt();
        $result = $rsaEncrypt->generateKeys();
        return $result;
    }

    /**
     * Finds the EncryptKeys model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return EncryptKeys the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EncryptKeys::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}