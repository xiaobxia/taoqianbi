<?php
/**
 * Created by PhpStorm.
 * User: zhangyuliang
 * Date: 17/6/1
 * Time: 上午10:16
 */

namespace backend\controllers;
use common\models\CardInfo;
use common\models\LoanPerson;
use Yii;
use common\models\DebitErrorLog;
use yii\base\Exception;
use yii\data\Pagination;
use common\models\BankConfig;


class DebitErrorController extends BaseController
{
    /**
     * @name 扣款回调失败列表
     * @return string
     */
    public function actionErrorList()
    {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) $condition .= " AND d.id = " . intval($search['id']);
            if (isset($search['card_no']) && !empty($search['card_no'])) $condition .= ' AND c.card_no = ' . "'".trim($search['card_no'])."'";
            if (isset($search['user_id']) && !empty($search['user_id'])) $condition .= " AND d.user_id = " . intval($search['user_id']);
            if (isset($search['phone']) && !empty($search['phone'])) $condition .= " AND p.phone = " . trim($search['phone']);
            if (isset($search['type']) && !empty($search['type'])) $condition .= " AND d.type = " . trim($search['type']);
            if (isset($search['created_at_begin']) && !empty($search['created_at_begin'])) $condition .= " AND d.created_at >= " . strtotime(trim($search['created_at_begin']));
            if (isset($search['created_at_end']) && !empty($search['created_at_end'])) $condition .= " AND d.created_at <= " . strtotime(trim($search['created_at_end']));
        }
        $query = DebitErrorLog::find()->from(DebitErrorLog::tableName().'as d')->where($condition)
            ->select(['d.*','p.name','p.phone','c.bank_name','c.card_no'])
            ->leftJoin(LoanPerson::tableName().'as p','d.user_id = p.id')
            ->leftJoin(CardInfo::tableName().'as c','d.card_id = c.id')
            ->orderBy(['d.id' => SORT_DESC]);
        if($this->request->get('submitcsv') == 'exportcsv'){
            return $this->_exportErrorList($query);
        }
        $countQuery = clone $query;
        $totalCount = $countQuery->count('*',Yii::$app->get('db_kdkj_rd'));
        $pages = new Pagination(['totalCount' => $totalCount]);
        $pages->pageSize = 20;
        $debitLogLists = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('debit-error-list',['pages'=>$pages,'debitLogList'=>$debitLogLists]);
    }

    public function actionErrorView($id)
    {
        $debitErrorLog = DebitErrorLog::find()->from(DebitErrorLog::tableName().'as d')->where(['d.id'=>$id])
            ->select(['d.*','p.name','p.phone','c.bank_name','card_no'])
            ->leftJoin(LoanPerson::tableName().'as p','d.user_id = p.id')
            ->leftJoin(CardInfo::tableName().'as c','d.card_id = c.id')
            ->limit(1)->asArray()->one();
        $user_id = Yii::$app->user->identity->getId() ;
        return $this->render('error-view',['debitErrorLog'=>$debitErrorLog]);
    }

    public function actionSetRemark()
    {
        try {
            if (!$this->getRequest()->isPost) throw new Exception('提交方式错误!');
            $adminId = Yii::$app->user->identity ? Yii::$app->user->identity->getId() : 0;
            if (!$adminId) throw new Exception('登录失效!请重新登录');
            $formData = $this->request->post();
            if (!isset($formData['debitErrorId'])) throw new Exception('参数错误!');
            if (!isset($formData['remark']) || strlen($formData['remark']) == 0) throw new Exception('备注不能为空!');
            $debitErrorLog = DebitErrorLog::findOne(['id'=>$formData['debitErrorId']]);
            if (!$debitErrorLog) throw new Exception('未找到相关数据!');
            $debitErrorLog->remark = $formData['remark'];
            $debitErrorLog->status = DebitErrorLog::STATUS_1;
            $debitErrorLog->admin_id = $adminId;
            $debitErrorLog->updated_at = time();
            if (!$debitErrorLog->save()) throw new Exception('保存失败!');
            return $this->redirect(['debit-error/error-list']);
        } catch (Exception $ex){
            return $this->redirectMessage($ex->getMessage(), self::MSG_ERROR);
        }
    }

    /**
     * @name 扣款回调失败列表统计导出_exportDebitRecordStatistics
     */
    public function _exportErrorList($query){
        $this->_setcsvHeader('扣款回调失败报表.csv');
        $datas = $query->asArray()->all(Yii::$app->get('db_kdkj_rd'));
        $items = [];
        foreach($datas as $value){
            $items[] = [
                'ID' => $value['id'],
                '用户ID' => $value['user_id'],
                '所属行' => $value['bank_name'],
                '卡号' => $value['card_no'],
                '手机号' => $value['phone'],
                '渠道' => isset(BankConfig::$platform[$value['platform']]) ? BankConfig::$platform[$value['platform']] : '',
                '错误信息'=> $value['error_msg'],
                '类型' => DebitErrorLog::$ERROR_TYPE[$value['type']],
                '备注' =>$value['remark'],
                '状态' =>DebitErrorLog::$ERROR_STATUS[$value['status']],
                '管理员ID' =>$value['admin_id'],
                '创建时间' =>date('Y-m-d H:i:s',$value['created_at']),
                '修改时间' =>date('Y-m-d H:i:s',$value['updated_at']),
            ];
        }
        echo $this->_array2csv($items);
        exit;
    }

}