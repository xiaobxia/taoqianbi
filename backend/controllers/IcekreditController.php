<?php
namespace backend\controllers;
use common\models\IcekreditAlipay;
use common\models\IcekreditAlipayData;
use common\models\IcekreditSuggestion;
use Yii;
use common\models\LoanPerson;
use yii\data\Pagination;

class IcekreditController extends BaseController{

    /**
     * @name 征信管理-支付宝信息/actionAlipayView
     */
    public function actionAlipayView() {
        $id = intval($this->request->get('id'));
        $loan_person = LoanPerson::find()->where(['id'=>$id])->asArray()->one();
        $record = IcekreditAlipay::findOne(['user_id' => $id, 'status' => IcekreditAlipay::STATUS_SUCCESS]);

        if (!empty($record)) {
            $data = IcekreditAlipayData::find()->where(['rid'=>$record->id])->orderBy('created_at DESC')->asArray()->one();
            $data = isset($data['data']) ? json_decode($data['data'], true) : null;
        } else {
            $data = null;
        }

        return $this->render('alipay-view', array(
            'person' => $loan_person,
            'report' => $data,
        ));
    }

    /**
     * @name 征信管理-冰鉴报告/actionReportView
     */
    public function actionReportView() {
        $id = intval($this->request->get('id'));
        $loan_person = LoanPerson::find()->where(['id'=>$id])->asArray()->one();
        $report = IcekreditSuggestion::find()->where(['user_id'=>$id])->asArray()->one();
        return $this->render('report-view', array(
            'person' => $loan_person,
            'report' => $report,
        ));
    }

    /**
     * @name 征信管理-支付宝认证列表/actionAlipayList
     */
    public function actionAlipayList() {
        $condition = $this->getFilter();
        $query = IcekreditAlipay::find()->from(IcekreditAlipay::tableName().' as a')->leftJoin(LoanPerson::tableName().' as p','a.user_id = p.id')->where($condition);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('alipay-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    protected function getFilter() {
        $condition = '1 = 1 and a.id>0 ';
        $search = $this->request->get();
        if (isset($search['user_id']) && !empty($search['user_id'])) {
            $condition .= " AND a.user_id = " . intval($search['user_id']);
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
            $condition .= " AND a.created_at >= " . strtotime($search['add_start']);
        }
        if (isset($search['add_end']) && !empty($search['add_end'])) {
            $condition .= " AND a.created_at < " . strtotime($search['add_end']);
        }
        if(isset($search['phone'])&&!empty($search['phone'])){
            $condition .= " and p.phone = {$search['phone']}";
        }
        if(isset($search['id_card'])&&!empty($search['id_card'])){
            $condition .= " and p.id_number = '{$search['id_card']}'";
        }

        return $condition;
    }
}
