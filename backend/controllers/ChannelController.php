<?php

namespace backend\controllers;

use common\models\Channel;
use common\models\ChannelStatistic;
use common\models\LoanPerson;
use common\services\LinkService;
use console\soa\UserLoanOrder;
use Yii;
use yii\data\Pagination;
use common\helpers\Url;
use common\api\RedisQueue;
use common\models\UserRegisterInfo;
use common\models\UserVerification;
use common\models\ChannelLoanCount;

class ChannelController extends BaseController
{
    /**
     * @return string
     * @name 渠道统计-渠道推广详情/actionChannelStatisticDetail
     */
    public function actionChannelStatisticDetail(){
        new LoanPerson();
        $role = $this->request->get('role',"");
        $user_role = Yii::$app->user->identity->role;
        $condition = "1 = 1";
        $array = [];
        if (empty($role) && !empty($user_role)){
            $arr = explode(",",$user_role);
            $role_list = [];
            foreach ($arr as $val){
                if (array_key_exists($val, LoanPerson::$user_agent_source)){
                    $role_list[] = $val;
                    $role = $role_list[0];
                }
            }
            unset($arr);

            foreach ($role_list as $value){
                $array[] = array($value, Url::toRoute(['channel/channel-statistic-detail', 'role' => $value]), 1);
            }
            unset($role_list);
        }
        if(!empty($role)){
            $condition .= " and parent_id = ".LoanPerson::$user_agent_source[$role];
        }

        if ($this->getRequest()->getIsGet()){
            $search = $this->request->get();
            if (isset($search['begintime']) && !empty($search['begintime'])){
                $condition .= " and a.time >=".strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])){
                $condition .= " and a.time <=".strtotime($search['endtime']);
            }
        }

        $query = ChannelStatistic::find()->from(ChannelStatistic::tableName().'as a')
            ->leftJoin(Channel::tableName().'as b', 'a.parent_id = b.source_id')
            ->where($condition)
            ->andWhere(['b.status' => Channel::STATUS_YES])
            ->select('a.*,b.name,b.loan_show')
            ->orderBy(['a.id' => SORT_DESC]);

        $countQuery = clone $query;
//        $db = Yii::$app->get('db_kdkj_rd');

        $count = $countQuery->count('*');
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = Yii::$app->request->get('per-page', 15);

        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();



        return $this->render('channel-statistic-detail', array(
            'info' => $info,
            'role' => $array,
            'pages' => $pages,
        ));
    }

    /**
     * @name 渠道用户详情
     * @return string
     */
    public function actionRegisterList(){
        $condition = "1 = 1";
        if ($this->getRequest()->getIsGet()){
            $search = $this->request->get();
            if (isset($search['channel_id']) && !empty($search['channel_id'])){
                $condition .= " and b.source_id =".$search['channel_id'];
            }else{
                return $this->redirectMessage('渠道获取失败', self::MSG_ERROR, Url::toRoute('channel/channel-statistic-detail'));
            }
            if (isset($search['date']) && !empty($search['date'])){
                $condition .= " and a.created_at >=".$search['date'];
                $condition .= " and a.created_at < ".strtotime('+1 days',$search['date']);
            }
            if (isset($search['begintime']) && !empty($search['begintime'])){
                $condition .= " and a.created_at >=".strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])){
                $condition .= " and a.created_at <=".strtotime($search['endtime']);
            }
        }else{
            return $this->redirectMessage('渠道获取失败', self::MSG_ERROR, Url::toRoute('channel/channel-statistic-detail'));
        }
        $query = UserRegisterInfo::find()->from(UserRegisterInfo::tableName().'as a')
            ->leftJoin(LoanPerson::tableName().'as b', 'a.user_id = b.id')
            ->where($condition)
            ->select('b.id,b.name,b.phone,a.created_at as register_time')
            ->orderBy(['a.id' => SORT_DESC]);
        $countQuery = clone $query;

        $count = $countQuery->count('*');
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = Yii::$app->request->get('per-page', 15);
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        foreach ($info as &$value){
            if (isset($value['name']) && !empty($value['name'])){
                $user_name = $value['name'];
                $firstStr     = mb_substr($user_name, 0, 1, 'utf-8');
                $value['name'] = isset($firstStr) ? $firstStr.'**' : '--';
            }
            if (isset($value['phone'])){
                $value['phone'] = substr_replace($value['phone'], '****', 3,4);
            }
            $user_verification = $value['id'] ? UserVerification::findOne(['user_id' => $value['id']]) : null;

            if ($user_verification['real_jxl_status'] == 1){
                $user_loan = UserLoanOrder::findOne(['user_id' => $value['id']]);
            }
            if (isset($user_loan) && !empty($user_loan['created_at'])){
                $value['mess'] = '提交中';
                $value['submit_time'] = $user_loan['created_at'];
            }
            if (isset($user_loan) && !empty($user_loan['loan_time'])){
                $value['mess'] = '借款中';
                $value['submit_time'] = $user_loan['loan_time'];
            }

            if ($user_verification ['real_jxl_status'] == 0){
                $value['mess'] = '未进行运营商认证';
            }
            if ($user_verification ['real_bind_bank_card_status'] == 0){
                $value['mess'] = '未进行绑定银行卡';
            }
            if ($user_verification ['real_contact_status'] == 0){
                $value['mess'] = '未填写联系人信息';
            }
            if (empty($user_verification) || $user_verification ['real_verify_status'] == 0){
                $value['mess'] = '未进行身份认证';
            }

        }

        return $this->render('register-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 系统管理-渠道管理-渠道列表/actionChannelList
     */
    public function actionChannelList(){
        $query = Channel::find()->orderBy('id DESC');
//        echo $query->createCommand()->getRawSql();exit;
//        $res = $query->all();
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;

        $list = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('channel-list', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 系统管理-渠道管理-渠道添加/actionChannelAdd
     */
    public function actionChannelAdd(){
        $model = new Channel();
        if(isset($_GET['id'])){
            $id=intval($_GET['id']);
            if($id <= 0){
                return $this->redirectMessage('抱歉，传递参数有误！', self::MSG_ERROR);
            }
            $model=Channel::findOne($id);
            if(!$model){
                return $this->redirectMessage('抱歉，未找到您的渠道数据！', self::MSG_ERROR);
            }
        }else{
            //添加时，默认启用
            $model->status=1;
            $model->loan_show=0;
        }

        // 有提交则装载post值并验证
        if ($model->load(Yii::$app->getRequest()->post())) {
            $post = Yii::$app->getRequest()->post();

            foreach ($post['Channel'] as $key=>$value){
                $model->$key = $value;
            }
            if(!isset($model->loan_show)){
                $model->loan_show = 0;
            }
            $model->source_str = $model->appMarket ?? '';
//            if($model->pv_rate==''||is_null($model->pv_rate) || empty($model->pv_rate)){
//                $model->pv_rate=0;
//            }
            $channel_data=null;
            if(!isset($_GET['id'])){
                //获得source_id
                $data=Channel::find()->orderBy('source_id desc')->one();
                if($data){
                    $model->source_id=intval($data['source_id'])+1;
                    if($model->source_id<12003){
                        $model->source_id=12003;
                    }
                }else{
                    $model->source_id=12003;
                }
                $channel_data=Channel::find()->select('id')->where(['appMarket'=>$model->appMarket])->one();
            }else{
                $channel_data=Channel::find()->select('id')->where(['appMarket'=>$model->appMarket])
                    ->andWhere(['<>','id',trim($_GET['id'])])->one();
            }

            //渠道英文名称不能重复
            if($channel_data){
                return $this->redirectMessage('英文名称'.$model->appMarket.'已经存在', self::MSG_ERROR);
            }

            if(empty($model->source_str)){
                return $this->redirectMessage('英文名称不能为空', self::MSG_ERROR);
            }
            if(empty($model->source_id)){
                return $this->redirectMessage('来源id不能为空', self::MSG_ERROR);
            }
            if(!isset($_GET['id'])){
                $link_service = new LinkService();
                $short_link = $link_service->ShortUrl($model->source_str);
                $model->link = $short_link['data'];
                if ($short_link['code'] == 0){
                    $model->link =$short_link['data']['short_url'] ?? $short_link['long_url'];
                }
                $model->created_at = time();
            }else{
                $channel_data=Channel::findOne(trim($_GET['id']));
                if($channel_data){
                    $link=$channel_data->link;
                    if(strstr($link,'page/jshbreg')){
                        $link_service = new LinkService();
                        $short_link = $link_service->ShortUrl($model->source_str);
                        $model->link = $short_link['data'];
                        if ($short_link['code'] == 0){
                            $model->link =$short_link['data']['short_url'] ?? $short_link['long_url'];
                        }
                    }
                }
            }
            unset($post);
            $model->effective_at = time();
            $model->updated_at = time();

            if ($model->validate() && $model->save()) {
                self::changeChannelRedis();
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('channel-list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }

//        $pv_rate=['1'=>'1折','2'=>'2折','3'=>'3折','4'=>'4折','5'=>'5折','6'=>'6折','7'=>'7折','8'=>'8折','9'=>'9折'];
        return $this->render('channel-add', array(
            'type'=>'add',
            'model'=>$model,
//            'pv_rate'=>$pv_rate
        ));
    }

    /**
     * @return string
     * @name 系统管理-渠道管理-渠道推广汇总/actionChannelStatisticTotal
     */
    public function actionChannelStatisticTotal(){
        $condition = "1 = 1";$loan_all_total=0;$repayment_all_total=0;
        if ($this->getRequest()->getIsGet()){
            $search = $this->request->get();
            if (isset($search['channel']) && $search['channel'] != ''){
                $condition .= " AND source_id = " . intval($search['channel']);
                $end_date=date("Y-m-d",strtotime("-8 day"));
                $end_date=strtotime($end_date);
                $sql="select sum(a.loan_all) as loan_all,sum(a.repayment_all) as repayment_all from tb_channel_statistic a where a.parent_id=".intval($search['channel']);
                $sql.=" and time<=".$end_date;
                $read_db = \Yii::$app->db_kdkj_rd_new;
                $repayment_data = $read_db->createCommand($sql)->queryOne();
                if($repayment_data){
                    $loan_all_total=$repayment_data['loan_all'];
                    $repayment_all_total=$repayment_data['repayment_all'];
                }
            }
            if (isset($search['begintime']) && !empty($search['begintime'])){
                $condition .= " and a.time >=".strtotime($search['begintime']);
            }
            if (isset($search['endtime']) && !empty($search['endtime'])){
                $condition .= " and a.time <=".strtotime($search['endtime']);
            }
        }

        $query = ChannelStatistic::find()->from(ChannelStatistic::tableName().'as a')
            ->leftJoin(Channel::tableName().'as b', 'a.parent_id = b.source_id')
            ->where($condition)
            ->andWhere(['b.status' => Channel::STATUS_YES])
            ->select('a.*,b.name,b.appMarket')
            ->orderBy(['a.id' => SORT_DESC]);

        $countQuery = clone $query;

        $count = $countQuery->count('*');
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = Yii::$app->request->get('per-page', 15);

        $channel=[];
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        $channel_data=Channel::find()->where(['<>','source_str',''])
            ->select('source_id,name')
            ->andWhere(['>', 'source_id', 21])
            ->andWhere(['<>','appMarket',''])
            ->andWhere(['<>','name',''])
            ->all();
        foreach ($channel_data as $value){
            $channel[$value['source_id']]=$value['name'];
        }
        unset($channel_data);

        return $this->render('channel-statistic-total', array(
            'info' => $info,
            'pages' => $pages,
            'channel' => $channel,
            'loan_all_total' => $loan_all_total,
            'repayment_all_total' => $repayment_all_total
        ));
    }

    public function actionChannelStatisticEdit(){
        if ($this->request->isPost){
            $post = $this->request->post();

            if (empty($post['id'])){
                return json_encode(['result' => 0, 'message' => 'id为空']);
            }
            $model = ChannelStatistic::findOne(['id' => $post['id']]);
            if ($model == ''){
                return json_encode(['result' => 0,'message' => '修改数据不存在']);
            }
            if (isset($post['pv']) && ($post['pv'] != '')){
                $model->pv = trim($post['pv']);
            }

            if (isset($post['pv_rate']) && ($post['pv_rate'] != '')){
                $pv_rate = $post['pv_rate'];
                if ($model->pre_pv == 0){
                    $model->pre_pv = $model->pv;
                }
                $discuss = intval($model->pre_pv/10) ?? 0;//商
                $remain = $model->pre_pv%10;//余数
                $kou = ($remain < $pv_rate) ? $remain : $pv_rate;

                $model->pv_rate = $post['pv_rate'];
                $model->withhold_pv = $discuss * $pv_rate + $kou;
                $model->pv = $model->pre_pv - $model->withhold_pv;
            }

            if ($model->save()){
                return json_encode(['result' => 1, 'message' => '修改成功']);
            }
        }
        return json_encode(['result' => 0, 'message' => '您没有输入数据']);
    }

    /**
     * return string
     * @name 系统管理-渠道管理-渠道扣量/actionChannelWithhold
     **/
    public function actionChannelWithhold(int $id){
        $model = Channel::findOne($id);
        if(!$model) {
            return $this->redirectMessage('抱歉，未找到渠道数据', self::MSG_ERROR);
        }

        if($model->is_withhold==1){
            $model->is_withhold=0;
        }else{
            $model->is_withhold=1;
        }

        if ($model->save()) {
            self::changeChannelRedis();
            return $this->redirectMessage('操作成功', self::MSG_SUCCESS, Url::toRoute('channel-list'));
        } else {
            return $this->redirectMessage('操作失败', self::MSG_ERROR);
        }
    }

    /**
     * 修改渠道redis缓存
     **/
    public function changeChannelRedis(){
        $channel_data=Channel::find()->where(['<>','source_str',''])
            ->select('name,appMarket,source_id,is_withhold')
            ->andWhere(['>', 'source_id', 21])
            ->andWhere(['<>','appMarket',''])
            ->andWhere(['<>','name',''])
            ->all();
        $channel_list=[];$channel_statistic_list=[];
        if($channel_data){
            foreach($channel_data as $k=>$v){
                $channel_list[]=['name'=>trim($v['name']),'appMarket'=>trim($v['appMarket']),'source_id'=>trim($v['source_id'])];
                if($v['is_withhold']==1 && !in_array($v['source_id'],$channel_statistic_list)){
                    $channel_statistic_list[]=$v['source_id'];
                }
            }
        }
        unset($channel_data);
        //保存到redis中
        $expire=strtotime(date("Ymd")) + 3600*24*30 - time();
        RedisQueue::set(['expire'=>$expire,'key'=>LoanPerson::CHANNEL_LIST,'value'=>json_encode($channel_list)]);
        RedisQueue::set(['expire'=>$expire,'key'=>LoanPerson::CHANNEL_No_STATISTIC_LIST,'value'=>json_encode($channel_statistic_list)]);
    }

    /**
     * 渠道转化率统计
     */
    public function actionLoanCountList()
    {
        $params = $this->request->get();
        $condition = 'id > 0';
        if(isset($params['start']) && $params['start'] != ''){
            $condition .= ' and date_time >= "'.$params['start'].'"';
        }
        if(isset($params['end']) && $params['end'] != ''){
            $condition .= ' and date_time <= "'.$params['end'].'"';
        }
        if(isset($params['sub_order_type']) && $params['sub_order_type'] > 0){
            $condition .= ' and source_id = '.$params['sub_order_type'];
        }
        $count = ChannelLoanCount::find()->where($condition)->count();
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = \yii::$app->request->get('per-page', 15);
        $data = ChannelLoanCount::find()->where($condition)->offset($pages->offset)
            ->limit($pages->limit)->orderBy(['id' => SORT_DESC])//->createCommand()->sql;
            ->asArray()->all();
//        echo $data;die;
        return $this->render('channel-loan-count-list', ['data' => $data, 'pages' => $pages]);
    }
}