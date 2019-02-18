<?php
namespace backend\controllers;

use common\exceptions\UserExceptionExt;
use common\helpers\CommonHelper;
use common\models\mongo\statistics\SensitiveDictUserLogMongo;
use Yii;
use common\helpers\Url;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\Response;
use yii\data\Pagination;
use common\models\SensitiveDict;
use common\helpers\StringHelper;
use yii\db\Query;

/**
 * 敏感词过滤
 */
class SensitiveDictController extends BaseController {

    /**
     * @name 删除敏感词 /actionDelete
     **/
    public function actionDelete() {
        $this->response->format = Response::FORMAT_JSON;
        try {
            $id = intval($this->request->post('id'));
            $sensitiveDict = SensitiveDict::findOne($id);
            if (is_null($sensitiveDict)) {
                return [
                    'code' => -1,
                    'message' => '敏感词不存在'
                ];
            }

            $sensitiveDict->delete();
            return [
                'code' => 0,
                'message' => '删除成功'
            ];
        } catch (Exception $e) {
            return [
                'code' => -1,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function getFilter()
    {
        $condition = '1 = 1 and id > 0 ';
        $search = $this->request->get();
        if (isset($search['name']) && empty($search['name'])) {
            $condition .= " AND name = '" . $search['name'] . "'";
        }
        if (isset($search['id']) && $search['id'] == '0') {
            $condition .= " AND id = '" . $search['id'] . "'";
        }

        return $condition;
    }

    /**
     * @name 敏感词列表
     * @return string
     */
    public function actionShowList() {
        // $condition = $this->getFilter();
        $search = $this->request->get();
        $query = SensitiveDict::find()->orderBy('id DESC');
        if (isset($search['id'])) {
            $query->andFilterWhere(['id' => $search['id']]);
        }
        if (isset($search['name'])) {
            $query->andFilterWhere(['name' => $search['name']]);
        }
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]); //, Yii::$app->get('db_kdkj_rd')
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(); //Yii::$app->get('db_kdkj_rd')

        return $this->render('show-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * @name 添加敏感词
     * @return string
     */
    public function actionAdd() {
        if ($this->getRequest()->isPost) {

            $name = trim($this->getRequest()->post('name'));

            if (SensitiveDict::findOne(['name' => $name])) {
                return $this->redirectMessage('敏感词已存在', self::MSG_ERROR);
            }

            $sensitiveDict = new SensitiveDict();
            $sensitiveDict->name = $name;
            try {
                if ($sensitiveDict->save()) {
                    return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('sensitive-dict/show-list'));
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }

        return $this->render('add');
    }

    /**
     * @name 被拒用户明细列表
     * @return string
     */
    public function actionCensorList()
    {
        $message = $this->getRequest()->get('message','');
        $log_time = $this->getRequest()->get('log_time','');
        $query = SensitiveDictUserLogMongo::find()->where(['level'=>1]);
        if($message)
        {
            $query->andFilterWhere(['like','message',$message]);
        }
        if($log_time)
        {
            $start_time = strtotime($log_time);
            $end_time = $start_time + 86400;
            $query->andFilterWhere(['between','log_time',$start_time,$end_time]);
        }
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 20;
        $user_log = $query->offset($pages->offset)->limit($pages->limit)->orderBy('_id DESC')->all();
        return $this->render('censor-list',[
            'user_log' => $user_log,
            'pages' => $pages,
        ]);
    }
}
