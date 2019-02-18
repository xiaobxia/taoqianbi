<?php
namespace backend\controllers;

use common\api\RedisQueue;
use Yii;
use yii\db\Query;
use yii\data\Pagination;

use common\models\AccumulationFund;
use common\models\LoanPerson;
use yii\helpers\VarDumper;

class HouseFundController extends BaseController{

    protected function getFilter() {
        $condition = '1 = 1 and a.id>0 ';
        $search = $this->request->get();
        if (isset($search['id']) && !empty($search['id'])) {
            $condition .= " AND a.id = " . intval($search['id']);
        }
        if (isset($search['user_id']) && !empty($search['user_id'])) {
            $condition .= " AND a.user_id = " . intval($search['user_id']);
        }
        if (isset($search['token']) && !empty($search['token'])) {
            $condition .= " AND a.token = '".$search['token']."'" ;
        }
        if (isset($search['channel']) && !empty($search['channel'])) {
            $condition .= " AND a.channel like '%" . $search['channel']."%'";
        }
        if (isset($search['status']) && !empty($search['status'])) {
            $condition .= " AND a.status = '" . $search['status']."'";
        }
        if (isset($search['status']) && $search['status'] == '0') {
            $condition .= " AND a.status = '" . $search['status']."'";
        }

        if (isset($search['add_start']) && !empty($search['add_start'])) {
            $condition .= " AND a.updated_at >= " . strtotime($search['add_start']);
        }
        if (isset($search['add_end']) && !empty($search['add_end'])) {
            $condition .= " AND a.updated_at < " . strtotime($search['add_end']);
        }
        if(isset($search['phone'])&&!empty($search['phone'])){
            $condition .= " and p.phone = {$search['phone']}";
        }
        if(isset($search['id_card'])&&!empty($search['id_card'])){
            $condition .= " and p.id_number = '{$search['id_card']}'";
        }

        return $condition;
    }

    /**
     * @name 用户管理-用户公积金列表/actionHouseFundList
     */
    public function actionHouseFundList() {
        $condition = $this->getFilter();
        $query = AccumulationFund::find()
            ->from(AccumulationFund::tableName().' as a')
//            ->innerJoin(LoanPerson::tableName().' as p','a.user_id = p.id')
            ->where($condition)->orderBy('a.updated_at DESC');

//        $countQuery = clone $query;
        $count = 9999999;
//        $count = \yii::$app->db_kdkj_rd->cache(function() use ($countQuery) {
//            return $countQuery->count('*', \yii::$app->db_kdkj_rd);
//        }, 3600);
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('house-fund-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * @name 用户管理-用户公积金详情/actionHouseFundView
     */
    public function actionHouseFundView($id) {
        $list = AccumulationFund::findOne($id);
        $data = !empty($list['data']) ? json_decode($list['data'], true) : [];
        if (!empty($data)) {
            $rating1 = [];
            if (isset($data['details'])) {
                foreach ($data['details'] as $key => $detail) {
                    $rating1[$key] = $detail['trading_date'];
                }
                array_multisort($rating1, SORT_DESC, $data['details']);
            }
        }
        $loan_person = LoanPerson::findOne([ 'id' => $list['user_id'] ]);
        return $this->render('house-fund-view', array(
            'loan_person' => $loan_person,
            'list' => $list,
            'param' => $data,
            'data' => isset($data['details']) ? $data['details'] : [],
        ));
    }

    /**
     * @name 用户管理-重新获取公积金信息/actionHouseFundNew
     */
    public function actionHouseFundNew($id) {
        $model = AccumulationFund::findOne(['id' => $id]);
        if (empty($model)) {
            return $this->redirectMessage('找不到该记录', self::MSG_ERROR);
        }

        $model->status = AccumulationFund::STATUS_INIT;
        RedisQueue::push([RedisQueue::LIST_HOUSEFUND_TOKEN, $id]);
        if ($model->save()) {
            return $this->redirectMessage('重新获取中', self::MSG_SUCCESS);
        }
        else {
            return $this->redirectMessage('操作失败', self::MSG_ERROR);
        }
    }

}
