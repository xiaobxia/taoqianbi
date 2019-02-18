<?php

namespace backend\controllers;

use backend\models\AdminUser;
use common\models\UserLoanOrder;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use common\helpers\Url;
use yii\web\Response;
use common\models\SolrUpdateLog;
use common\models\SolrInsertLog;
use common\services\statistics\OrderDetailService;

/*
 * getFilter() where条件组合
 * actionUpdateLogList() 更新日志列表e
 * actionInsertLogList() 插入日志列表
 */
class SolrLogController extends  BaseController{
    /**
     * 条件语句组合
     * @return string
     */
	public function getFilter(){
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (!isset($search['num_id'])) {
                if (!isset($search['date'])) {
                    if (isset($search['flag']) && !empty($search['flag'] && $search['flag'] != 0)) {
                        $condition .= " AND flag = " . intval($search['flag']);
                    }
                    if (isset($search['begin_at']) && !empty($search['begin_at'])) {
                        $condition .= " AND begin_at >= " . strtotime($search['begin_at']);
                    }
                    if (isset($search['finish_at']) && !empty($search['finish_at'])) {
                        $condition .= " AND begin_at <= " . strtotime($search['finish_at']);
                    }
                } else {
                    if (isset($search['date']) && !empty($search['date'] && $search['date'] != 0)) {
                        $condition .= " AND update_date = " . intval($search['date']);
                    }
                    if (isset($search['flag']) && !empty($search['flag'] && $search['flag'] != 0)) {
                        $condition .= " AND flag = " . intval($search['flag']);
                    }
                }
            } else {
                if (isset($search['num_id']) && !empty($search['num_id'] && $search['num_id'] != 0)) {
                    $condition .= " AND id = " . intval($search['num_id']);
                }
            }
        }
        return $condition;
    }

    /**
     * @return string
     * @nameSolr日志-更新日志/actionUpdateLogList
     */
	public function actionUpdateLogList(){
        $condition = self::getFilter();
        $list = new SolrUpdateLog();
        if(!(preg_match('/id/', $condition))){
            if(!(preg_match('/update_date/', $condition))){
                $list = SolrUpdateLog::find()->from(SolrUpdateLog::tableName())->orderBy('`id` desc')->groupBy('update_date')->where($condition);
                $countQuery = clone $list;
                $pages = new Pagination(['totalCount' => $countQuery->count()]);
                $pages->pageSize = 15;
                $list = $list->offset($pages->offset)->limit($pages->pageSize)->select(['sum(success) as success','max(total) as total','update_date','sum(fail) as fail','count(flag) as flag','min(begin_at) as begin_at','max(finish_at) as finish_at'])->all();
                return $this->render('solr-list', array(
                    'data_list' => $list,
                    'pages' => $pages,
                    'type' => 1,
                ));
            }else{
                $list = SolrUpdateLog::find()->from(SolrUpdateLog::tableName())->orderBy('`num` desc')->where($condition);
                $countQuery = clone $list;
                $pages = new Pagination(['totalCount' => $countQuery->count()]);
                $pages->pageSize = 15;
                $list = $list->select(['id','num','update_date','success','fail_id','fail','flag','begin_at','finish_at'])->all();
                return $this->render('solr-list', array(
                    'data_list' => $list,
                    'pages' => $pages,
                    'type' => 1,
                ));
            }
        }else{
            $list = SolrUpdateLog::find()->from(SolrUpdateLog::tableName())->orderBy('`num` desc')->where($condition);
            $list = $list->select(['id','fail_id'])->all();
            $list_fail_id = explode(',,',$list[0]->fail_id);
            $pages = new Pagination(['totalCount' => count($list_fail_id)]);
            $pages->pageSize = 15;
            for($i=$pages->offset;$i<(($pages->offset)+($pages->pageSize));$i++){
                if(isset($list_fail_id[$i])){$arr[] = $list_fail_id[$i];};
            }
            return $this->render('solr-list', array(
                'data_list' => $arr,
                'pages' => $pages,
                'type' => 1,
            ));
        }
    }

    /**
     * @return string
     * @nameSolr日志-插入日志/actionInsertLogList
     */
	public function actionInsertLogList(){
		$condition = self::getFilter();
        $list = new SolrInsertLog();
        if(!(preg_match('/id/', $condition))){
            if(!(preg_match('/update_date/', $condition))){//一般显示
                $list = SolrInsertLog::find()->from(SolrInsertLog::tableName())->orderBy('`id` desc')->groupBy('update_date')->where($condition);
                $countQuery = clone $list;
                $pages = new Pagination(['totalCount' => $countQuery->count()]);
                $pages->pageSize = 15;
                $list = $list->offset($pages->offset)->limit($pages->pageSize)->select(['sum(success) as success','sum(total) as total','update_date','sum(fail) as fail','max(flag) as flag','min(begin_at) as begin_at','max(finish_at) as finish_at'])->all();
                return $this->render('solr-list', array(
                    'data_list' => $list,
                    'pages' => $pages,
                    'type' => 2,
                ));
            }else{//显示某一天的每一个处理进程的结果 只有10条 不用分页
                $list = SolrInsertLog::find()->from(SolrInsertLog::tableName())->orderBy('`num` desc')->where($condition);
                $countQuery = clone $list;
                $pages = new Pagination(['totalCount' => $countQuery->count()]);
                $pages->pageSize = 15;
                $list = $list->select(['id','num','update_date','success','fail_id','fail','flag','begin_at','finish_at'])->all();
                return $this->render('solr-list', array(
                    'data_list' => $list,
                    'pages' => $pages,
                    'type' => 2,
                ));
            }
        }else{//显示错误记录编号
            $arr = [];
            $list = SolrInsertLog::find()->from(SolrInsertLog::tableName())->orderBy('`num` desc')->where($condition);
            $list = $list->select(['id','fail_id'])->all();
            $list_fail_id = explode(',,',$list[0]->fail_id);
            $pages = new Pagination(['totalCount' => count($list_fail_id)]);
            $pages->pageSize = 15;
            for($i=$pages->offset;$i<(($pages->offset)+($pages->pageSize));$i++){
                if(isset($list_fail_id[$i])){$arr[] = $list_fail_id[$i];};
            }
            return $this->render('solr-list', array(
                'data_list' => $arr,
                'pages' => $pages,
                'type' => 2,
            ));
        }
	}

    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @nameSolr日志-插入日志/actionRewriteInsert
     * 重写
     */
    public function actionRewriteInsert(){
        $search_re = $this->request->get();
        $arr = [];
        $str_1 = '';
        $list = UserLoanOrder::find()->select('id')->where($search_re['order_id'])->one(Yii::$app->get('db_kdkj_rd'));
        if(!empty($list)){//存在订单
            $log = SolrInsertLog::find()->from(SolrInsertLog::tableName())->where(['id'=>$search_re['num_id']])->one(Yii::$app->get('db_kdkj_rd'));
            if(!empty($log)){//错误日志存在
                $str = "/".$search_re['order_id']."\|/";
                if(preg_match($str,$log->fail_id)){
                    $arr_num = 0;$arr_item = 0;
                    $arr = explode(',,',$log->fail_id);
                    foreach($arr as $item){
                        if(preg_match($str,$item)){
                            $str_1 = $item;
                            $arr_item = $arr_num;
                        }
                        $arr_num++;
                    }
                    if(!preg_match('/finish/',$str_1)) {
                        //获取错误信息完成
                        $data_arr = [];
                        $data_arr[] = $search_re['order_id'];
                        $obj = new OrderDetailService();
                        $doc_arr = $obj->createOrderDetail($data_arr);
                        if (!empty($doc_arr)) {//成功从数据库中提取到关系数据
                            $update_result = $obj->tryToUpdate($doc_arr);
                            if (!empty($update_result) && isset($update_result['code'])) {
                                if ($update_result['code'] == 0) {//重写成功
                                    //更新统计数据 修改log->fail_id里面的信息
                                    $result_succ = $log->success + 1;
                                    $result_fau = $log->fail - 1;
                                    $str_1 = preg_replace('/\|/', '|finish|', $str_1);
                                    $arr[$arr_item] = $str_1;
                                    $str_fail_id = implode(',,', $arr);
                                    $data_insert = [
                                        'success' => $result_succ,
                                        'fail' => $result_fau,
                                        'fail_id' => $str_fail_id
                                    ];
                                    $obj->insertSolrLog($data_insert, $log->id);
                                } else {//重写失败
                                    //修改log->fail_id里面的信息(更新错误信息)
                                    $err_id = (int)preg_replace('/-.*/','',preg_replace('/[^=]*=/','',$update_result['message']));
                                    $arr[$arr_item] = $err_id.'|'.$update_result['message'];
                                    $str_fail_id = implode(',,', $arr);
                                    $data_insert = [
                                        'fail_id' => $str_fail_id
                                    ];
                                    $obj->insertSolrLog($data_insert, $log->id);
                                }
                            }
                        }
                    }
                }
            }
        }
        if($arr==[]) {
            $list = SolrInsertLog::find()->from(SolrInsertLog::tableName())->orderBy('`num` desc')->where(['id' => intval($search_re['num_id'])]);
            $list = $list->select(['id', 'fail_id'])->all();
            $list_fail_id = explode(',,', $list[0]->fail_id);
            $pages = new Pagination(['totalCount' => count($list_fail_id)]);
        }else{
            $pages = new Pagination(['totalCount' => count($arr)]);
        }
        $pages->pageSize = 15;
        for ($i = $pages->offset; $i < (($pages->offset) + ($pages->pageSize)); $i++) {
            if (isset($list_fail_id[$i])) {
                $arr[] = $list_fail_id[$i];
            };
        }
        return $this->render('solr-list', array(
            'data_list' => $arr,
            'pages' => $pages,
            'type' => 2,
        ));
    }
}
?>