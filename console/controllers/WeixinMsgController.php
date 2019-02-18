<?php
/**
 * Created by PhpStorm.
 * User: marisa
 * Date: 2017/6/30
 * Time: 2:08
 */
namespace console\controllers;

use Yii;
use common\api\RedisQueue;
use common\helpers\CommonHelper;
use common\helpers\Util;
use common\models\CardInfo;
use common\helpers\MessageHelper;
use common\models\mongo\wechat\CusomterTemplateMongo;
use common\models\UserCouponInfo;
use common\models\UserCreditMoneyLog;
use common\models\UserCreditTotal;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\WeixinUser;
use common\models\LoanPerson;
use common\services\UserService;
use common\services\WeixinService;

class WeixinMsgController extends BaseController{

    //推送用户的账户信息和优惠券消息  cRu9pdO5D8qL493EpMqwXAYS9ZETch_i0WyOr7ExSs0 //有就发送
    //待还款金额：{{keyword1.DATA}}
    //还款日期：{{keyword2.DATA}}
    //账户额度：{{keyword3.DATA}}
    //剩余可用额度：{{keyword4.DATA}}
    //可用券：{{keyword5.DATA}}
    public function actionLoanAccountMsg()
    {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }
        $where = "o.is_push = 0 AND o.user_id = w.uid AND o.is_use = 0";
        $weixin_user_info = UserCouponInfo::find()
            ->from(UserCouponInfo::tableName() . 'as o')
            ->leftJoin(WeixinUser::tableName() . 'as w', 'w.uid =o.user_id ')
            ->where($where)
            ->select(
                'w.openid,w.uid,w.nickname'
            )->groupBy('o.user_id')->limit(1000)->asArray()->all();
        if (!empty($weixin_user_info)) {
            $weixinser = Yii::$app->weixinService;
            foreach ($weixin_user_info as $k => $v) {
                //查询有几张券
               // $count = UserCouponInfo::find()->where(['user_id' => $v['uid']])->count();
                $count = 0;
                //查询用户最后一条借款记录
                $info = UserLoanOrderRepayment::find()->where(['user_id' => $v['uid']])
                    ->select('plan_fee_time,true_total_money,status')
                    ->orderBy('id desc')->limit(1)->one();
                if (empty($info)) {
                    $data['keyword1'] = '无';
                    $data['keyword2'] = '无';
                } elseif ($info->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                    $data['keyword1'] = '无';
                    $data['keyword2'] = '无';
                } else {
                    $data['keyword1'] = date('Y.m.d', $info->plan_fee_time);
                    $data['keyword2'] = sprintf("%0.2f", $info->true_total_money / 100);
                }
                //用户额度
                $money = UserCreditTotal::find()
                    ->where(['user_id' => $v['uid']])
                    ->select('amount,used_amount,locked_amount')->one();
                $amount = $money->amount / 100;
                $user_amount = $money->used_amount / 100;
                $locked_amount = $money->locked_amount / 100;
                $no_user_amount = $amount - $locked_amount - $user_amount;
                $data['keyword3'] = $amount . '.00';
                $data['keyword4'] = $no_user_amount . '.00';
                $data['keyword5'] = $count;
                $msg = $weixinser->endMsg($v['uid']);
                $data['remark'] = $msg['msg'];
                $data['url'] = $msg['url'];
                $data['openid'] = $v['openid'];
                $res = $weixinser->TemplateOne($data);
                if ($res == true) {
                    UserCouponInfo::updateAll(['is_push' => 1], 'user_id =' . $v['uid']);
                    echo "微信用户".$v['uid']."详情推送成功\r\n";
                } else {
                    //记录错误日志
                    Yii::error(\sprintf('%s微信用户详情推送失败：%s,%s', $v['uid'],json_encode($res)));
                    echo "微信用户".$v['uid']."详情推送失败\r\n";
                }
            }
        } else {
            echo '暂无数据';
        }
    }

    //用户13天的还款提示
    public function actionLoanTip(){
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }
        $where = "o.user_id = w.uid and w.uid != 0 and o.status not in(4,5,-3)";//5扣款中 -3已逾期
        $weixin_user_loan_tip = UserLoanOrderRepayment::find()
            ->from(UserLoanOrderRepayment::tableName(). 'as o')
            ->leftJoin(WeixinUser::tableName() . 'as w', 'w.uid =o.user_id ')
            ->leftJoin(LoanPerson::tableName() . 'as l', 'l.id =o.user_id ')
            ->where($where)
            ->select(
                'w.openid,w.uid,w.nickname,o.plan_fee_time,o.total_money,l.name'
            );
        $countQuery = clone $weixin_user_loan_tip;
        $totle = $countQuery->count();
        $limit = 100;//测试
        $limit_num = 100;
        $page = (int)ceil($totle/$limit);
        $time_14 = strtotime(date("Y-m-d"));//当天的
        $weixinser = Yii::$app->weixinService;
        for ($i=0;$i<$page;$i++){
            $limit_res = $i*$limit_num;
            $info = $weixin_user_loan_tip->offset($limit_res)->limit($limit)->asArray()->all(Yii::$app->get('db_kdkj_rd_new'));
            foreach ($info as $k=>$v){
                //判断是13天还是14天的
                $plan_repayment_time = strtotime(date('Y-m-d',$v['plan_fee_time']));
                $plan_day_res3 = $plan_repayment_time-86400;
                if($plan_day_res3 == $time_14){
                    //查询用户额度
                    $user_total = UserCreditTotal::find()->where(['user_id'=>$v['uid']])
                        ->select('amount')->one();
                    $money = $user_total->amount;
                    $data['keyword1'] = date('Y-m-d',$time_14);
                    $data['keyword2'] = date('Y-m-d',$v['plan_fee_time']);
                    $data['keyword3'] = sprintf("%0.2f", $money / 100);
                    $data['keyword4'] = sprintf("%0.2f", $v['total_money'] / 100);
                    $msg = $weixinser->endMsg($v['uid']);
                    $data['remark'] = $msg['msg'];
                    $data['url'] = $msg['url'];
                    $data['openid'] = $v['openid'];
                    $data['name'] = $v['name'];
                    //$mongo = new CusomterTemplateMongo();
                    //$mongo->name = "微信用户13天推送".date('Y-m-d',time());
                    //$mongo->data = json_encode($data, JSON_UNESCAPED_UNICODE);
                    //$mongo->created_time = time();
                    //$mongo->save();
                    $res = $weixinser->TemplateLoanTips($data);
                    if ($res != true) {
                        //记录错误日志
                        echo $res." 微信用户".$v['uid']."13天推送失败\r\n";
                        Yii::error(\sprintf('微信用户13天还款推送失败：%s,%s', $v['uid'],$res));
                    }else{
                        echo "微信用户".$v['uid']."13天推送成功\r\n";
                    }
                }
            }
        }
    }

    //14天的还款提示
    public function actionLoanTips(){
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }
        $where = "o.user_id = w.uid and w.uid != 0 and o.status not in(4,5,-3)";//5扣款中 -3已逾期
        $weixin_user_loan_tip = UserLoanOrderRepayment::find()
            ->from(UserLoanOrderRepayment::tableName(). 'as o')
            ->leftJoin(WeixinUser::tableName() . 'as w', 'w.uid =o.user_id ')
            ->leftJoin(LoanPerson::tableName() . 'as l', 'l.id =o.user_id ')
            ->where($where)
            ->select(
                'w.openid,w.uid,w.nickname,o.plan_fee_time,o.total_money,l.name'
            );
        $countQuery = clone $weixin_user_loan_tip;
        $totle = $countQuery->count();
        //$limit = 500;//分页执行怕数据量太大
        $limit = 100;//测试
        $limit_num = 100;
        $page = (int)ceil($totle/$limit);
        $time_14 = strtotime(date("Y-m-d"));//当天的
        $weixinser = Yii::$app->weixinService;
        for ($i=0;$i<$page;$i++){
            $limit_res = $i*$limit_num;
            $info = $weixin_user_loan_tip->offset($limit_res)->limit($limit)->asArray()->all(Yii::$app->get('db_kdkj_rd_new'));
            foreach ($info as $k=>$v){
                //14天的
                $plan_repayment_time = strtotime(date('Y-m-d',$v['plan_fee_time']));
                if ($plan_repayment_time == $time_14) {
                    $msg = $weixinser->endMsg($v['uid']);
                    $data['keyword1'] = sprintf("%0.2f", $v['total_money'] / 100);
                    $data['keyword2'] = date('Y-m-d', $v['plan_fee_time']);
                    $data['remark'] = $msg['msg'];
                    $data['url'] = $msg['url'];
                    $data['openid'] = $v['openid'];
                    $data['name'] = $v['name'];
                    //$mongo = new CusomterTemplateMongo();
                    //$mongo->name = "微信用户14天推送".date('Y-m-d',time());
                    //$mongo->data = json_encode($data, JSON_UNESCAPED_UNICODE);
                    //$mongo->created_time = time();
                    //$mongo->save();
                    $res = $weixinser->TemplateLoanTip($data);
                    if ($res != true) {
                        //记录错误日志
                        echo $res ." 微信用户" . $v['uid'] . "14天推送失败\r\n";
                        Yii::error(\sprintf('微信用户14天还款推送失败：%s,%s', $v['uid'], $res));
                    } else {
                        echo "微信用户" . $v['uid'] . "14天推送成功\r\n";
                    }
                }
            }
        }
    }

    //用户还款提醒
    public function actionPayStatus(){
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }
        $datas = RedisQueue::pop([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO]);
        while (!empty($datas)){
            $data_res = json_decode($datas,true);
            $weixin_user = WeixinUser::find()->where(['uid' => $data_res['user_id']])->asArray()->one(Yii::$app->get('db_kdkj_rd_new'));
            $weixinServer = Yii::$app->weixinService;
            if (!empty($data_res) && $data_res['code'] == 1002 && !empty($weixin_user)) {
                $data['keyword1'] = sprintf("%0.2f", $data_res['loan_money']/100);
                $data['keyword2'] = '银行卡还款';
                $data['keyword3'] = $data_res['error']['error_info'];
                $msg = $weixinServer->endMsg($data_res['user_id']);
                $data['url'] = $msg['url'];
                $data['remark'] = $msg['msg'];
                $data['openid'] = $weixin_user['openid'];
                //$mongo = new CusomterTemplateMongo();
                //$mongo->name = "微信用户还款失败推送".date('Y-m-d',time());
                //$mongo->data = json_encode($data, JSON_UNESCAPED_UNICODE);
                //$mongo->created_time = time();
                //$mongo->save();
                $res = $weixinServer->TemplatePayETip($data);
                if($res != true){
                    Yii::error(\sprintf('微信用户还款失败推送失败：%s,%s', $data_res['user_id'],$res));
                }
                echo '微信用户'.$data_res['user_id'].'还款失败推送成功';
            } elseif (!empty($data_res) && $data_res['code'] == 1001 && !empty($weixin_user)) {
                $data['keyword1'] = sprintf("%0.2f", $data_res['loan_money']/100);
                $data['keyword2'] = COMPANY_NAME;
                $data['keyword3'] = '主动还款';
                $msg = $weixinServer->endMsg($data_res['user_id']);
                $data['url'] = $msg['url'];
                $data['remark'] = $msg['msg'];
                $data['openid'] = $weixin_user['openid'];
                //$mongo = new CusomterTemplateMongo();
                //$mongo->name = "微信用户还款成功推送".date('Y-m-d',time());
                //$mongo->data = json_encode($data, JSON_UNESCAPED_UNICODE);
                //$mongo->created_time = time();
                //$mongo->save();
                $res = $weixinServer->TemplatePaySTip($data);
                if($res != true){
                    echo $res.' 微信用户'.$data_res['user_id']."还款成功推送失败\r\n";
                    Yii::error(\sprintf('微信用户还款成功推送失败：%s,%s', $data_res['user_id'],$res));
                }
                echo '微信用户'.$data_res['user_id']."还款成功推送成功\r\n";
            }
            $datas = RedisQueue::pop([RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO]);
        }

    }

    /**
     * @借款成功
     * @return in
     * @throws \Exception
     */
    public function actionLoanSuccess(){
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }
        $datas = RedisQueue::pop([RedisQueue::LIST_WEIXIN_USER_LOAN_INFO]);
        while (!empty($datas)){
            $data_res = json_decode($datas,true);
            $weixin_user = WeixinUser::find()->where(['uid' => $data_res['user_id']])->asArray()->one();
            $weixinServer = Yii::$app->weixinService;
            if (!empty($data_res) && $data_res['code'] == 1001 && !empty($weixin_user)) {
                $loan_order = UserLoanOrderRepayment::find()->where(['order_id'=>$data_res['order_id']])->asArray()->one();
                $loan_order_res = UserLoanOrderRepayment::find()->where(['user_id'=>$loan_order['user_id']])->orderBy('id desc')->limit(1)->asArray()->one();
                //放款金额
                $data['keyword1'] = sprintf("%0.2f", $loan_order_res['principal']/100);
                //银行卡
                $userService = new UserService;
                $band_data=$userService->getCardInfo($data_res['user_id']);
                $data['keyword2']='尾号为'.$band_data[0]['card_no_end'];
                //借款期限
                $data['keyword3'] = date('d',$loan_order_res['plan_fee_time']-$loan_order_res['loan_time']).'天';
                //还款日
                $data['keyword4'] = date('Y-m-d',$loan_order_res['plan_fee_time'])??'';

                $msg = $weixinServer->endMsg($data_res['user_id']);
                $data['url'] = $msg['url'];
                $data['remark'] = $msg['msg'];
                $data['openid'] = $weixin_user['openid'];
                //$mongo = new CusomterTemplateMongo();
                //$mongo->name = "微信用户打款成功推送".date('Y-m-d',time());
                //$mongo->data = json_encode($data, JSON_UNESCAPED_UNICODE);
                //$mongo->created_time = time();
                //$mongo->save();
                $res = $weixinServer->TemplateLoanSTip($data);
                if($res != true){
                    echo $res.' 微信用户'.$data_res['user_id']."打款成功推送成功\r\n";
                    Yii::error(\sprintf('微信用户打款成功推送失败：%s,%s', $data_res['user_id'],$res));
                }
                echo '微信用户'.$data_res['user_id']."打款成功推送成功\r\n";
            }
            $datas = RedisQueue::pop([RedisQueue::LIST_WEIXIN_USER_LOAN_INFO]);
        }
    }



    public function actionFix($num_res){//数据分几次跑
        $weixin_server = Yii::$app->weixinService;
        $access_token = $weixin_server->get_access_token_redis();
        //获取access_token =
//        $weixin_user = WeixinUser::find()->where(['status'=>1]);
//        $weixin_count = $weixin_user->count();
        $limit = 1000;
        $num = 0;
        $connection = Yii::$app->db;
        $id = 0;
        $sql = "select * from tb_weixin_user where status = 1 and id mod 5 = $num_res and id > $id limit $limit ";
        echo $sql.PHP_EOL;
        $weixin_res =  $connection->createCommand($sql)->queryAll();
        while($weixin_res){
            foreach ($weixin_res as $k=>$v){
                $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$v['openid']}&lang=zh_CN";
                $json_res = WeixinService::postData($url,'');
                $data_res = json_decode($json_res,true);
                if($data_res['errcode'] == '42001'){//如果token失效 重新获取
                    //获取锁
                    $t_lock = WeixinService::getTokenLock();
                    if($t_lock){
                        sleep(5);//停5秒
                        WeixinService::tokenLock();//添加锁
                        echo "更新token暂停5秒";
                    }else{
                        $weixin_server->get_access_token_redis();
                        echo "跳过\r\n";
                        continue;
                    }
                }
                if($data_res['subscribe'] == 0){
                    $user = WeixinUser::find()->where(['uid'=>$v['uid']])->one();
                    $user->status = 0;
                    $user->unsubscribe_time = time();
                    if($user->save(false)){
                        echo "用户".$v['uid']."取消关注\r\n";
                        echo "取消关注的用户一共有".$num++."人\n";
                    }else{
                        var_dump($user->getErrors());
                        echo "用户".$v['uid']."取消关注保存失败\r\n";
                    }
                }else{
                    echo "用户".$v['uid']."没有取消关注跳过\r\n";
                }
                $id = $v['id'];
            }
            $sql = "select * from tb_weixin_user where status = 1 and id mod 5 = $num_res and id > $id limit $limit ";
            echo $sql.PHP_EOL;
            $weixin_res =  $connection->createCommand($sql)->queryAll();

        }
    }

    /**
     * 微信用户问卷统计
     */
    public function actionSendUserAsk($num_res){
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }
        Util::cliLimitChange(1024);
        $limit = 5000;
        $connection = Yii::$app->db_kdkj_rd_new;
        $id = 0;
        $sql = "select * from tb_weixin_user where id mod 10 = $num_res and id > $id limit $limit ";
        $weixin_res =  $connection->createCommand($sql)->queryAll();
        while($weixin_res){
            foreach ($weixin_res as $k=>$v){
                $id = $v['id'];
                $weixinServer = Yii::$app->weixinService;
                $data['keyword1'] = '关于用户日常消费';
                $data['keyword2'] = '2017年11月8日';
                $data['keyword3'] = '2017年11月10日';
                $data['openid'] = $v['openid'];
                $res = $weixinServer->TemplateAsk($data);
                if($res != true){
                    echo $res.' 微信用户'.$v['user_id']."问卷成功推送成功\r\n";
                    Yii::error(\sprintf('微信用户问卷推送失败：%s,%s', $data['user_id'],$res));
                }
                echo '微信用户'.$data['user_id']."问卷推送成功\r\n";
            }
            echo '数据一共'.count($weixin_res).'条';
            $sql = "select * from tb_weixin_user where id mod 10 = $num_res and id > $id limit $limit ";
            $weixin_res =  $connection->createCommand($sql)->queryAll();
        }
    }

    /**
     * 微信用户问卷统计
     */
    public function actionSendAskUser($filename){
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }
        Util::cliLimitChange(1024);
        //读取用户的手机列表s
        $file_name_res = '/tmp/'.$filename.'.txt';
        $arr_arr = fopen($file_name_res,'r');
        if ($arr_arr) {
            while (($buffer = fgets($arr_arr, 1024)) !== false) {
                $phone = trim($buffer);
                $weixin_res = WeixinUser::find()->where(['phone'=>$phone])->asArray()->one();
                $weixinServer = Yii::$app->weixinService;
                $data['keyword1'] = '通过审核';
                $data['keyword2'] = date('Y年m月d日 h:i:s',time());
                $data['openid'] = $weixin_res['openid'];
                $res = $weixinServer->TemplateAskUser($data);
                echo $res.' 微信用户'.$weixin_res['uid']."导流推送\r\n";
            }
            if (!feof($arr_arr)) {
                echo "Error: unexpected fgets() fail\n";
            }
            fclose($arr_arr);
            echo "数据保存完成";
        }
    }

    /**
     * @name 微信用户打标签
     */
    public function actionTagWeixinUser($filename,$tag_id){
        Util::cliLimitChange(1024);
        $file_name_res = '/tmp/'.$filename.'.txt';
        $arr_arr = fopen($file_name_res,'r');
        if ($arr_arr) {
            while (($buffer = fgets($arr_arr, 1024)) !== false) {
                $phone = trim($buffer);
                $weixin_res = WeixinUser::find()->where(['phone'=>$phone])->one();
                $weixinService = Yii::$app->weixinService;
                $access_toekn = $weixinService->get_access_token();
                $opend_id = $weixin_res->openid;
                $api_url = "https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token={$access_toekn}";
                $data['openid_list'] = [$opend_id];
                $data['tagid'] = $tag_id;
                $res = WeixinService::postData($api_url,json_encode($data));
                $res_josn = json_decode($res,true);
                if($res_josn['errcode'] != 0){
                    echo $res_josn['errcode']."\r\n";
                }else{
                    echo "{$phone}.保存成功\r\n";
                }
            }
            if (!feof($arr_arr)) {
                echo "Error: unexpected fgets() fail\n";
            }
            fclose($arr_arr);
            echo "数据保存完成";
        }
    }
}