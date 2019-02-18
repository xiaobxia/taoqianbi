<?php

namespace backend\controllers;

use Yii;
use common\models\Monitor;
use common\models\MonitorSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * MonitorController implements the CRUD actions for Monitor model.
 */
class MonitorController extends Controller
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
     * Lists all Monitor models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new MonitorSearch();
        $params = array_merge(Yii::$app->request->queryParams, [
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,            
                ]
            ],
        ]);
            
        $dataProvider = $searchModel->search($params);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Monitor model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Monitor model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Monitor();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Monitor model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Monitor model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Monitor model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Monitor the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Monitor::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    public function actionCheckConfig($id) {
        $model = Monitor::findOne((int)$id);
        if(!$model) {
            throw new \Exception('找不到模型！');
        }
        $model->checkConfig();
        var_export($model->getErrors());
    }
    
    public function actionCheck($id) {
        $model = Monitor::findOne((int)$id);
        if(!$model) {
            throw new \Exception('找不到模型！');
        }
        $model->runCheck();
    }
    
    public function actionCronCheck() {
        $models = Monitor::find()->where('next_check_time<'.time())->all();
        foreach($models as $model) {
            /* @var $model Monitor */
            $model->runCheck();
        }
    }
}
