<?php
namespace console\controllers;

use Yii;
use yii\base\Exception;
use common\helpers\Util;
use common\helpers\ArrayHelper;
use common\helpers\CommonHelper;
use common\models\StatisticsLoan;
use common\exceptions\JPushException;
use common\helpers\MessageHelper;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\MessageLog;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\api\RedisQueue;
use common\helpers\MailHelper;
use common\models\UserLoginUploadLog;
use common\models\UserLoginLog;
use common\models\LoanPersonChannelLoanData;
use common\models\LoanPersonChannelRegisterData;
use common\models\DailyData;
use common\models\message\MessageCollectLog;
use common\models\message\MessageStatusLog;
use common\models\message\PushLog;
use common\models\StatisticsDayData;
use common\services\MessageService;
use common\models\loan\OrderStatisticsByDay;
use common\models\loan\OrderStatisticsByRate;

class MessageNoticeController extends BaseController {

    const ACTIVITY_TEXT = '你有5000额度！凡是在2017.6.16——6.30期间参与还款的用户，即有机会获得最高提额5000元的福利，快去参与吧！';


    public function actionSendMessageRepaymentFive(){
        #$this->_doSendMessage(3); //逾期第1天发送短信
        #$this->_doSendMessage(2); //逾期第7天发送短信
    }

    /**
     * 提前2天短信提醒
     */
    public function actionSendMessageRepaymentOne(){
        $this->_doSendMessage(11);
    }

    /**
     * 还款前一天提醒,生息状态
     */
    public function actionSendMessageRepayment(){
        $this->_doSendMessage(0); //提前一天发送提醒短信
        $this->_doSendMessage(5); //还款当天发送提醒短信     8 点
    }

    //当日还款 -11:30
    public function actionSendMessageRepaymentTwo()
    {
        $today = date('Y-m-d');
        if ($today >= '2019-02-04' && $today <= '2019-02-11'){
            $this->_doSendMessage(12); //过年发用户还款提醒
        }
//        $this->_doSendMessage(6); //新用户当日还款短信推送

    }

    //当日还款短信推送 - 15:30
    public function actionSendMessageRepaymentThree()
    {
//        $this->_doSendMessage(5); //还款当天发送提醒短信
//        $this->_doSendMessage(7); //还款当天发送提醒短信
        #$this->_doSendMessage(9); //还款当天发送提醒短信
    }
    //当日还款短信推送 - 18:00
    public function actionSendMessageRepaymentFour()
    {
//        $this->_doSendMessage(5); //还款当天发送提醒短信
//        $this->_doSendMessage(8); //还款当天发送提醒短信
//        $this->_doSendMessage(10); //还款当天发送提醒短信
    }

    /**
     * 还款前一天提醒 语音 12点
     */
    public function actionSendVoiceMessageRepayment(){
        #$this->_doSendVoiceMessage(0); //提前一天发送提醒短信
    }

    /**
     * 提前还款  下午 18：00
     */
    public  function actionSendVoiceMessageRepaymentSeven(){
        #$this->_doSendVoiceMessage(7); //提前一天发送提醒短信
    }

    //当日还款语音推送 - 13点
    public function actionSendVoiceMessageRepaymentTwo()
    {
        #$this->_doSendVoiceMessage(1); //还款当天发送提醒语音
    }


    //当日还款语音 - 19点
    public function actionSendVoiceMessageRepaymentThree(){
        #$this->_doSendVoiceMessage(2);
    }


    //当日还款语音 - 21点
    public function actionSendVoiceMessageRepaymentSix(){
        #$this->_doSendVoiceMessage(5);
    }

    /**
     * 逾期语音  17:00  玄武
     */
    public function actionSendVoiceMessageRepaymentFour(){
        $this->_doSendVoiceMessage(3);
    }

    /**
     * 逾期语音  11:00  SAIYOU
     */
    public function actionSendVoiceMessageRepaymentFive(){
        $this->_doSendVoiceMessage(4);
    }


    /**
     * $type:3逾期1天,4逾期2天
     * @param unknown $type
     */
    public function actionSendMessageRepaymentType($type) {
        $this->_doSendMessage($type);
    }

    public function _doSendMessage($type=0) {
        $today = time();
        $plan_start_time = strtotime(date('Y-m-d',$today))+24*3600;
        $plan_end_time = strtotime(date('Y-m-d',$today))+2*24*3600;
        if($type == 2){
            $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 7])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->limit(1000)->asArray()->all();
        }elseif($type == 1){
            $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 4])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->limit(1000)->asArray()->all();
        }elseif($type == 3){
            $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 1])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->limit(1000)->asArray()->all();
        }elseif($type == 4){
            $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 2])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->limit(1000)->asArray()->all();
        }elseif(in_array($type,[5,6,7,8,9,10])){
            $plan_start_time = strtotime(date('Y-m-d',$today));
            $plan_end_time = strtotime(date('Y-m-d',$today))+24*3600;
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit(1000)->asArray()
                ->all(); //先查出一千条
        }elseif ($type=11){
            $plan_start_time = strtotime(date('Y-m-d',$today))+2*24*3600;
            $plan_end_time = strtotime(date('Y-m-d',$today))+3*24*3600;
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit(300)->asArray()
                ->all(); //先查出三百条
        }elseif ($type=12){
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" late_day > 0")
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit(1000)->asArray()
                ->all(); //先查出一千条
        } else{
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit(1000)->asArray()->all(); //先查出一千条
        }
        $offset = 0;
        $cannotsend = [];

        new LoanPerson();
        while ($user_loan_order_repayment) {
            $user_ids = [];
            $order_ids = [];
            foreach($user_loan_order_repayment as $item){
                $user_ids[$item['user_id']] = $item['user_id'];
                $order_ids[$item['order_id']] = $item['order_id'];
            }

            $orders = UserLoanOrder::find()->where(['id'=>array_keys($order_ids)])->select(['id','user_id','card_id'])->asArray()->all();
            $card_ids = [];
            foreach($orders as $item){
                $card_ids[$item['card_id']] = $item['card_id'];
            }

            $cards = CardInfo::find()->where(['id'=>array_keys($card_ids)])->select(['id','user_id','card_no'])->asArray()->all();
            $card_info=[];
            foreach($cards as $item){
                $card_info[$item['user_id']] = substr($item['card_no'],-4);
            }

            $users = LoanPerson::find()->where(['id'=>array_keys($user_ids)])->select(['id','name','phone','source_id','customer_type'])->asArray()->all();

            $users_info = [];
            foreach($users as $item){
                $users_info[$item['id']] =[
                    'user_id'=>$item['id'],
                    'name'=>$item['name'],
                    'phone'=>$item['phone'],
                    'source_id'=>$item['source_id'],
                    'customer_type'=>$item['customer_type']
                ];
            }

            foreach($user_loan_order_repayment as $item){
                if(isset($users_info[$item['user_id']])){
                    $name =$users_info[$item['user_id']]['name'];
                    $source_id = $users_info[$item['user_id']]['source_id'];
                    if(empty($item['plan_fee_time'])){
                        continue;
                    }
                    $phone = $users_info[$item['user_id']]['phone'];
                    if(empty($phone)){
                        continue;
                    }

                    //渠道名称
                    if(empty($source_id)){
                        $source_name = LoanPerson::$person_source[LoanPerson::PERSON_SOURCE_MOBILE_CREDIT];
                    }else{
                        $source_name = LoanPerson::$person_source[$source_id];
                        //考虑到贷超引流(21是APP主体)
                        if($source_id!=LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){
                            $source_name = LoanPerson::$person_source[LoanPerson::PERSON_SOURCE_MOBILE_CREDIT];
                        }
                    }

                    $money = $item['principal']/100;
                    $late_fee = $item['late_fee']/100;
                    $total =  ($item['principal']+$item['late_fee']+$item['interests']-$item['true_total_money'])/100;

                    if(isset($card_info[$item['user_id']])&&!empty($card_info[$item['user_id']])){
                        $card = $card_info[$item['user_id']];
                    }else{
                        continue;
                    }
                    if($type == 2){
                        $send_message = '尊敬的'.$name.'，您申请的'.$money.'元借款已逾期7天，逾期费'.$late_fee.'元。请于今日将还款总额'.$total.'元充值到绑定银行卡以便平台扣款。请及时还款哦。如已还款请忽略。';
                    }else if($type == 1){
                        $send_message = '尊敬的'.$name.'，您在'.$source_name.'的'.$money.'元借款已逾期4天，逾期费为'.$late_fee.'元，请于今日将还款总额'.$total.'元充值到绑定银行卡以便平台扣款，避免产生更多的滞纳金。';
                    }else if($type == 3){
                        $send_message = '尊敬的'.$name.'，您申请的'.$money.'元借款已逾期1天，逾期费'.$late_fee.'元。请于今日将还款总额'.$total.'元充值到绑定银行卡以便平台扣款。请及时还款哦。如已还款请忽略。';
                    }else if($type == 4){
                        $send_message = '尊敬的'.$name.'，您在'.$source_name.'的'.$money.'元借款已逾期2天，逾期费为'.$late_fee.'元，请于今日将还款总额'.$total.'元充值到绑定银行卡以便平台扣款，避免产生更多的滞纳金。';
                    }else if($type == 5){
                        $send_message = "尊敬的".$name."，您在".$source_name."的款项今日到期，平台将对您尾号".$card."银行卡进行扣款，请确保资金充足，已还、款，请忽略。"; // ok
                    }else if($type == 6) {
                        $send_message = "通知！你获得一次100%提额机会，今天按时还款还有机会获得抵扣券，名额有限先到先得，赶快去还款提额度！拒收T";
                    } else if($type == 7){
                        $send_message = "尊敬的".$name."，您的".$money."元借款今日到期，今日还款最高15元抵扣券等你拿，还有机会获得最高2000元提额！名额有限先到先得，如已还款请忽略。拒收T";
                    }else if($type == 8) {
                        $send_message = "尊敬的".$name."，您的".$money."元借款今日到期，今日还款就会获得最高2000元提额，还有机会获得大额还款抵扣券！名额有限先到先得，如已还款请忽略。拒收T";
                    }else if($type == 9) {
                        $send_message = "尊敬的".$name."，您的".$money."元借款今日到期。请珍惜您的信用，点滴信用，在于积累，为了不影响您的信用，请按时还款，退订T";     //  大汉三通
                    }else if($type == 10) {
                        $send_message = "尊敬的".$name."，您的".$money."，元借款今日到期。平台已对接国家信用，逾期用户将被上传至信联。为了您的信用，请按时还款！退订T";   //ok 天畅 840040
                    }elseif($type == 11){
                        $send_message = "尊敬的".$name."，您在".$source_name."的借款将于".date('m月d日', $item['plan_fee_time'])."到期。您可登录'.$source_name.'官方app提前完成还款！如需续贷或展期请按指示操作，如您己还款无需理会，谢谢！";
                    }elseif($type == 12){
                        $send_message = "尊敬的".$name."，您在".$source_name."的借款将于".date('m月d日', $item['plan_fee_time'])."到期。您可登录'.$source_name.'官方app提前完成还款！如需续贷或展期请按指示操作，如您己还款无需理会，谢谢！";
                    }else{
                        $send_message = "尊敬的".$name."您好！为了不打扰您过年，您的借款已到期！请前往'.$source_name.'App还款！感谢您的配合！祝您猪年大吉！";
                    }
                    try{
                        $order_id = $item['order_id'];
                        $sms_channel_res = '';
                        if(YII_ENV_PROD){
                            if(in_array($type,[1,2,3,4])){
                                if($source_id == 21){
                                    //$sms_channel = "smsService_MengWang_CS";
                                    #$sms_channel ='smsService_SuDun_CS';//速盾  逾期 催收 暂未接通
                                    $sms_channel ='smsService_TianChang_HY';//暂时写天畅，不实际使用

                                    $ret = MessageHelper::sendSMSCS($phone,$send_message,$sms_channel,$source_id,false);
                                }elseif ($source_id == LoanPerson::PERSON_SOURCE_SX_LOAN){
                                    continue;
//                                    $sms_channel = "smsService_MengWang_SXDCS";
//                                    $ret = MessageHelper::sendSMSCS($phone,$send_message.'回复TD退订',$sms_channel,$source_id,false);
                                }elseif ($source_id == LoanPerson::PERSON_SOURCE_WZD_LOAN){
                                    continue;
                                    #$sms_channel = "smsService_MengWang_WZDCS";//不知渠道短信
                                    #$ret = MessageHelper::sendSMSCS($phone,$send_message.'回复TD退订',$sms_channel,$source_id,false);
                                }else{
                                    continue;
                                }
                            }elseif(in_array($type,[5])){
                                //当日提醒，走行业账号
                                $sms_channel = "smsService_TianChang_HY";//亿美短信改为天畅发
                                if(in_array($source_id,LoanPerson::$source_register_list)){
                                    $sms_channel = "smsService_TianChang_HY";  //当日到期 早8点 天畅换亿美
                                }
                                $ret = MessageHelper::sendSMSHY($phone,$send_message,$sms_channel,$source_id);
                            }elseif(in_array($type,[6,7,8])){
                                //当日提醒，走营销账号
                                $sms_channel = "smsService_TianChang_HY";
                                #$sms_channel = "smsService_TianChang";
                                $pre_meg = '【'.APP_NAMES.'】';
                                $send_message = $pre_meg.$send_message;
                                $ret = MessageHelper::sendSMSYX($phone,$send_message,$sms_channel,$source_id);
                            }elseif(in_array($type,[9,10])){
                                //当日提醒，天畅通知 15:30  18:00
                                $sms_channel = "smsService_TianChang_HY";
                                #$sms_channel = "smsService_TianChang_QYTZ";
                                if($type == 9){
                                    $sms_channel = "smsService_TianChang_HY";   //  大汉三通换成亿美
//                                    $sms_channel = "smsService_DaHan_TZ";   //  大汉三通
                                }
                                $pre_meg = '【'.APP_NAMES.'】';
                                if($source_id == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT){
                                    $pre_meg = '【'.APP_NAMES.'】';
                                }
                                //渠道暂未未使用
//                                elseif ($source_id == LoanPerson::PERSON_SOURCE_SX_LOAN){
//                                    $pre_meg .= '[随心贷]';
//                                }elseif ($source_id == LoanPerson::PERSON_SOURCE_WZD_LOAN){
//                                    $pre_meg .= '[温州贷借款]';
//                                }
                                else{
                                    continue;
                                }
                                if($type==10){
                                    $send_message = $pre_meg.$send_message;
                                }

                                $ret = MessageHelper::sendSMSHY($phone,$send_message,$sms_channel,$source_id);
//   暂无 第二渠道
//                                 if(!$ret){  //天畅发送不成功 用梦网
//                                     $sms_channel = "smsService_MengWang_Repayment";
//                                     if($type == 9){ //15:30
//                                         $send_message = "尊敬的".$name."，今天是您的最后还款日。".$source_name."上网络征信，为了维护您良好的信用记录，请按时还款哦！";
//                                     }else{ //18:00
//                                         $send_message = "尊敬的".$name."，您的".$money."元借款今日到期了，在0点前还款，系统不会将您纳入逾期记录中。为了维护您良好的信用记录，请按时还款哦！";
//                                     }
//                                     switch ($source_id){
//                                         case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT:
//                                             $sms_channel = "smsService_MengWang_Repayment";
//                                             break;
//                                         case LoanPerson::PERSON_SOURCE_WZD_LOAN:
//                                             $sms_channel = "smsService_MengWang_WZDRepayment";
//                                             break;
//                                         default:
//                                             continue;
//                                             break;
//                                     }
//                                     $ret = MessageHelper::sendSMSHY($phone,$send_message,$sms_channel,$source_id);
//                                 }
                            }else{
                                $sms_channel = "smsService_TianChang_HY";
                                if(in_array($source_id,LoanPerson::$source_register_list)){
                                    $sms_channel = "smsService_TianChang_HY";   // 提前一天  大汉 早8点换成亿美换成天畅
                                }
                                //渠道暂未使用
//                                elseif ($source_id == LoanPerson::PERSON_SOURCE_SX_LOAN){
//                                    $sms_channel = "smsService_TianChang_SXD";
//                                }elseif ($source_id == LoanPerson::PERSON_SOURCE_WZD_LOAN){
//                                    $sms_channel = "smsService_TianChang_WZD";
//                                }elseif($source_id == LoanPerson::PERSON_SOURCE_HBJB){
//                                    $sms_channel = "smsService_TianChang_HBQB";
//                                }elseif($source_id == LoanPerson::PERSON_SOURCE_JBGJ){
//                                    $sms_channel = "smsService_TianChang_JZGJ";
//                                }elseif($source_id == LoanPerson::PERSON_SOURCE_KDJZ){
//                                    $sms_channel = "smsService_TianChang_KDJZ";
//                                }
                                $ret = MessageHelper::sendSMSHY($phone,$send_message,$sms_channel,$source_id);
                            }
                            echo "{$send_message}\n";
                        }else{
                            echo $send_message;
                            $sms_channel_res = 'smsService_TianChang_HY';
                            $ret = true;
                        }
                        $this->message('sendSms: '.$ret);
//                        if($ret){
//                            $message_log = new MessageLog();
//                            $message_log->text = $send_message;
//                            $message_log->type = MessageLog::TYPE_YGD_LQB_NOTICE;
//                            $message_log->relate_id = $order_id;
//                            $message_log->sms_channel = $sms_channel_res;
//                            $message_log->created_at = time();
//                            $message_log->operator_name = "shell operator";
//                            $message_log->save();
//                        }else{
//                            $cannotsend[] = ['phone'=>$phone,'user_loan_order_id'=>$order_id];
//                        }
                    }catch(\Exception $e){
                        Yii::error("发送还款前一天短信失败，mobile:{$phone} user_loan_order_id:{$order_id}");
                    }
                }
            }
            $offset++;
            $next_wait = $offset*1000;

            if($type == 2){
                $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 7])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->offset($next_wait)->limit(1000)->asArray()->all();
            }else if($type == 1){
                $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 4])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->offset($next_wait)->limit(1000)->asArray()->all();
            }else if($type == 3){
                $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 1])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->offset($next_wait)->limit(1000)->asArray()->all();
            }else if($type == 4){
                $user_loan_order_repayment = UserLoanOrderRepayment::find()->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)->andWhere(['overdue_day' => 2])->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])->offset($next_wait)->limit(1000)->asArray()->all();
            }else if(in_array($type,[5,6,7,8,9,10])){
                $user_loan_order_repayment = UserLoanOrderRepayment::find()
                    ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                    ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                    ->offset($next_wait)
                    ->limit(1000)->asArray()
                    ->all(); //先查出一千条
            }else{
                $user_loan_order_repayment = UserLoanOrderRepayment::find()
                    ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                    ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                    ->offset($next_wait)->limit(1000)->asArray()->all(); //查出下一千条
            }
        }
        if (!empty($cannotsend)) {
            $html = "<table><tr><th>手机号</th><th>借款ID</th></tr>";
            foreach ($cannotsend as $key => $value) {
                $html .= "<tr><td>{$value['phone']}</td><td>{$value['user_loan_order_id']}</td></tr>";
            }
            $html .= "</table>";
            MailHelper::send(NOTICE_MAIL, date('Y-m-d H:i:s')." 提前一天短信发送失败名单", $html);
        }
        else {
            MailHelper::send(NOTICE_MAIL, date('Y-m-d H:i:s')." 提前一天短信发送失败名单", '无');
        }
    }


    public function _doSendVoiceMessage($type=0) {
        $voice_project = array(
            LoanPerson::PERSON_SOURCE_SX_LOAN => array(
                '0' => 'Xyu6L3',//'随心贷提醒您，明天是您的还款日，明天是您的还款日，为了避免逾期导致您信用度下降请提前准备好资金存入绑定银行卡，或今日'
                '1'=>'16dsu1' //'随心贷提醒您，今天是您还款的最后期限，逾期将会对您今后的信用产生不良影响。请尽快还款，现在还款百分百提额！如您已经还款，请忽略该通话'
            ),
            LoanPerson::PERSON_SOURCE_WZD_LOAN => array(
                '0' => 'JsgzM2',//'温州贷借款提醒您，明天是您的还款日，为了避免逾期导致您信用度下降请提前准备好资金存入绑定银行卡，或今日进入APP主动还款！'
                '1'=>'ytbbu1' //'温州贷借款提醒您，今天是您还款的最后期限，逾期将会对您今后的信用产生不良影响。请尽快还款，现在还款百分百提额！如您已经还款，请忽略该通话'
            ),
            LoanPerson::PERSON_SOURCE_MOBILE_CREDIT => array(
                '0' => '20314',//'极速荷包提醒您，明天是您的还款日，为了避免逾期导致您信用度下降请提前准备好资金存入绑定银行卡，或今日进入APP主动还款！'
                '1' => '20315', //'极速荷包提醒您，今天是您还款的最后期限，逾期将会对您今后的信用产生不良影响。请尽快还款，现在还款百分百提额哦！如您已经还款，请忽略该通话'
                '2' => '20316',  //极速荷包提醒您，今天是您还款的最后期限，截止到目前您还没有还清欠款，逾期将会产生滞纳金并影响您今后的征信，请尽快还款，如您已经还款，请忽略该通话
                '3' => '20339',   // 逾期提醒
                '4' => '04ajy1',  //'极速荷包逾期提醒，您在极速荷包有一笔借款已逾期一天，请及时还款，明日将移交至专业催收人员处理，并上传至国家征信，会对您的生活造成极大的影响，请及时处理。'
                '5' => '20344',   ///极速荷包还款提醒，您在极速荷包有一笔借款今日到期，请及时还款，逾期将移交至专业催收人员处理，并上传至国家征信，会对您的生活造成极大的影响，请及时处理。
                '7' => '30008',
            ),
        );
        $today = time();
        $plan_start_time = strtotime(date('Y-m-d',$today))+24*3600;
        $plan_end_time = strtotime(date('Y-m-d',$today))+2*24*3600;
        if($type==1 || $type == 2 || $type == 5 ){  //当天
            $plan_start_time = strtotime(date('Y-m-d',$today));
            $plan_end_time = strtotime(date('Y-m-d',$today))+24*3600;
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit(500)->asArray()
                ->all(); //先查出五百条

        }elseif($type == 3 || $type == 4){   // 逾期一天
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(['overdue_day' => 1])
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit(500)->asArray()->all();

        }elseif($type == 0 || $type == 7){     //提前一天
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit(500)->asArray()->all(); //先查出五百条
        }else{
            Yii::error("无类型");exit;
        }
        $offset = 0;
        while ($user_loan_order_repayment) {
            $user_ids = [];
            $order_ids = [];
            foreach($user_loan_order_repayment as $item){
                $user_ids[$item['user_id']] = $item['user_id'];
                $order_ids[$item['order_id']] = $item['order_id'];
            }

            $orders = UserLoanOrder::find()->where(['id'=>array_keys($order_ids)])->select(['id','user_id','card_id'])->asArray()->all();
            $card_ids = [];
            foreach($orders as $item){
                $card_ids[$item['card_id']] = $item['card_id'];
            }

            $cards = CardInfo::find()->where(['id'=>array_keys($card_ids)])->select(['id','user_id','card_no'])->asArray()->all();
            $card_info=[];
            foreach($cards as $item){
                $card_info[$item['user_id']] = substr($item['card_no'],-4);
            }

            $users = LoanPerson::find()->where(['id'=>array_keys($user_ids)])->select(['id','name','phone','source_id','customer_type'])->asArray()->all();

            $users_info = [];
            foreach($users as $item){
                $users_info[$item['id']] =[
                    'user_id'=>$item['id'],
                    'name'=>$item['name'],
                    'phone'=>$item['phone'],
                    'source_id'=>$item['source_id'],
                    'customer_type'=>$item['customer_type']
                ];
            }

            $phone_info = [];
            foreach($user_loan_order_repayment as $item){
                if(isset($users_info[$item['user_id']])){
//                    $name =$users_info[$item['user_id']]['name'];
                    $source_id = $users_info[$item['user_id']]['source_id'];
                    if(empty($item['plan_fee_time'])){
                        continue;
                    }
                    $phone = $users_info[$item['user_id']]['phone'];
                    if(empty($phone)){
                        continue;
                    }

                    if(empty($source_id)){
                        $source_id = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
                    }

//                    $money = $item['principal']/100;
//                    $late_fee = $item['late_fee']/100;
//                    $total =  ($item['principal']+$item['late_fee']+$item['interests']-$item['true_total_money'])/100;

                    if(isset($card_info[$item['user_id']])&&!empty($card_info[$item['user_id']])){
//                        $card = $card_info[$item['user_id']];
                    }else{
                        continue;
                    }
                    $phone_info[$source_id]['project'] = $voice_project[$source_id][$type];
                    $phone_info[$source_id]['phone_list'][] = $phone;
                }
            }
            try{
                if(YII_ENV_PROD){
                    if(!empty($phone_info)){
                        // $sms_channel = "smsService_SaiYou_YY";
                        $sms_channel = "smsService_XuanWu_YYTZ";
                        if($type == 4){
                            $sms_channel = "smsService_SaiYou_YY";
                        }

                        foreach ( $phone_info as $s_id => $send_list){
                            if(!in_array($s_id, [LoanPerson::PERSON_SOURCE_MOBILE_CREDIT, LoanPerson::PERSON_SOURCE_SX_LOAN, LoanPerson::PERSON_SOURCE_WZD_LOAN])){
                                continue;
                            }
                            $send_message = '';
                            $ret = MessageHelper::sendAll($send_list,$send_message,$sms_channel,$s_id);
                            echo "{$send_message}\n";
                            $this->message('sendVoiceSms: '.$ret);
                        }
                    }
                }else{
                    $ret = true;
                    $this->message('sendVoiceSms: '.$ret);
                }
                unset($phone_info);
            }catch(\Exception $e){
                Yii::error("批量发送语音通知失败");
            }
            $offset++;
            $next_wait = $offset*500;
            if($type==1 || $type == 2 || $type == 5){
                $user_loan_order_repayment = UserLoanOrderRepayment::find()
                    ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                    ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                    ->offset($next_wait)
                    ->limit(500)->asArray()
                    ->all(); //先查出五百条

            }elseif($type == 3 || $type == 4){   // 逾期一天
                $user_loan_order_repayment = UserLoanOrderRepayment::find()
                    ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(['overdue_day' => 1])
                    ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                    ->offset($next_wait)
                    ->limit(500)->asArray()
                    ->all();

            }elseif($type == 0 || $type == 7){     //提前一天
                $user_loan_order_repayment = UserLoanOrderRepayment::find()
                    ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                    ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                    ->offset($next_wait)
                    ->limit(500)->asArray()
                    ->all(); //先查出五百条

            }else{
                Yii::error("无类型");exit;
            }
        }
    }

    /**
     * 发送活动推送文案
     * @return [type] [description]
     */
    public function actionSendActivityMessage($isPush = false, $isMoblle = false)
    {
        $today = time();
        $limit = 1000;
        $day = 11;
        $jpushService = Yii::$container->get('jpushService');
        $pushText = $jpushService::REPAYMENT_ACTIVITY_TEXT;
        $messageText = self::ACTIVITY_TEXT;
        $user_loan_order_repayment = UserLoanOrderRepayment::find()
            ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
            ->andWhere(['>=' , 'overdue_day', $day])
            ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
            ->limit($limit)
            ->asArray()
            ->all();

        $offset = 0;
        $cannotsend = [];
        while ($user_loan_order_repayment) {
            $user_ids = [];
            foreach ($user_loan_order_repayment as $item) {
                $user_ids[$item['user_id']] = $item['user_id'];
            }
            $users = LoanPerson::find()->where(['id' => array_keys($user_ids)])->select(['id','name','phone', 'source_id'])->indexBy('id')->asArray()->all();
            //查出借款人最近一次登录的设备系统
            $loginIds = UserLoginLog::find()->select(['max(id) as id'])->where(['user_id' => array_keys($user_ids)])->groupBy('user_id')->column();
            $last_login = UserLoginLog::find()->select(['id','user_id', 'source'])->where(['id' => $loginIds])->all();

            $last_login_system = [];
            foreach ($last_login as $key => $value) {
                if (is_numeric($value['source'])) {
                    continue;
                }
                $source = unserialize($value['source']);
                $last_login_system[$value['user_id']]['clientType'] = $source['clientType'];
                $last_login_system[$value['user_id']]['appMarket'] = $source['appMarket'];
            }

            $count = count($user_loan_order_repayment);
            $_i = 0;
            foreach ($user_loan_order_repayment as $item) {

                echo 'Processing progress: '.(++$_i).'/'.$count.", user_id: ".$item['user_id']."\r\n";
                if (isset($last_login_system[$item['user_id']])) {
                    $system = $last_login_system[$item['user_id']];
                    $channel = $jpushService::getChannelByMarket($system['clientType'], $system['appMarket']);
                    $platform = $system['clientType'];
                } else {
                    $channel = LoanPerson::APPMARKET_XYBT;
                    $platform = 'all';
                }
                $userId = $item['user_id'];
                $phone = $users[$userId]['phone'];
                $source_id = $users[$userId]['source_id'];
                try {
                    // $userId = '46875';
                    // $platform = 'android';
                    // $channel = 'xybt';
                    // $phone = '15558025017';

                    if ($isPush && YII_ENV_PROD) {
                        echo "{$pushText}\n";
                        $res = $jpushService->push($pushText, $userId, $channel, $platform);

                        $pushLog = new PushLog();
                        $pushLog->user_id = $userId;
                        $pushLog->platform = $platform;
                        $pushLog->app_market = $channel;
                        $pushLog->created_at = time();
                        if (isset($res[0]) && isset($res[0]['http_code']) && $res[0]['http_code'] == 200) {
                            // 推送成功日志
                            $pushLog->status = PushLog::STATUS_SUCCESS;
                        } else {
                            if (isset($res[0])) {
                                $msg = JPushException::$ERROR_MSG[$res[0]];
                            } else {
                                $msg = $res;
                            }

                            $pushLog->status = PushLog::STATUS_FAILED;
                            $pushLog->message = $msg;
                        }
                        $pushLog->save();
                    }

                    if (empty($phone)) {
                        continue;
                    }

                    // if ($isMoblle && YII_ENV_PROD) {
                    //     $order_id = $item['order_id'];
                    //     $sms_channel_res = '';
                    //     if (YII_ENV_PROD) {
                    //         $sms_channel = "smsServiceXQB_XiAo_YX";
                    //         echo "{$messageText}\n";
                    //         $ret = MessageHelper::sendSMS($phone,$messageText,$sms_channel,$source_id,false,$sms_channel_res);
                    //     } else {
                    //         echo $messageText;
                    //         $sms_channel_res = 'smsServiceXQB_XiAo_YX';
                    //         $ret = true;
                    //     }
                    //     $this->message('sendSms: '.$ret);
                    //     if ($ret) {
                    //         $message_log = new MessageLog();
                    //         $message_log->text = $messageText;
                    //         $message_log->type = MessageLog::TYPE_ACTIVITE_NOTICE;
                    //         $message_log->relate_id = $order_id;
                    //         $message_log->sms_channel = $sms_channel_res;
                    //         $message_log->created_at = time();
                    //         $message_log->operator_name = "shell operator";
                    //         $message_log->save();
                    //     } else {
                    //         $cannotsend[] = ['phone'=>$phone,'user_loan_order_id'=>$order_id];
                    //     }
                    // }

                } catch (\Exception $e) {
                    Yii::error("消息发送失败 {$e}");
                }
            }

            $offset++;
            $next_wait = $offset*$limit;

            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(['>=' , 'overdue_day', $day])
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->offset($next_wait)
                ->limit($limit)
                ->asArray()
                ->all();
        }

        if (!empty($cannotsend)) {
            $html = "<table><tr><th>手机号</th><th>借款ID</th></tr>";
            foreach ($cannotsend as $key => $value) {
                $html .= "<tr><td>{$value['phone']}</td><td>{$value['user_loan_order_id']}</td></tr>";
            }
            $html .= "</table>";
            MailHelper::sendMass([NOTICE_MAIL], date('Y-m-d H:i:s')." 活动短信|推送发送失败名单", $html);
        }
    }

    /**
     * 逾期前一天推送 逾期一天、逾期三天、逾期十天
     *
     */
    public function actionPushRepayment()
    {
        $this->_pushRepayment(-1); //逾期前一天推送
        $this->_pushRepayment(0); //还款当天
        $this->_pushRepayment(1); //逾期一天
        $this->_pushRepayment(3); //逾期三天
        $this->_pushRepayment(10); //逾期十天
    }

    private function _pushRepayment($type = 0)
    {
        $today = time();
        $plan_start_time = strtotime(date('Y-m-d',$today))+24*3600;
        $plan_end_time = strtotime(date('Y-m-d',$today))+2*24*3600;
        $limit = 1000;
        $jpushService = Yii::$container->get('jpushService');
        if ($type == 1 || $type == 3 || $type == 10) {
            $repaymentText = $jpushService::REPAYMENT_ONE_TEXT;
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(['overdue_day' => $type])
                ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit($limit)
                ->asArray()
                ->all();
        } else if ($type == -1) {
            $plan_start_time = strtotime(date('Y-m-d',$today));
            $plan_end_time = strtotime(date('Y-m-d',$today))+24*3600;
            $repaymentText = $jpushService::REPAYMENT_TODAY_TEXT;
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                ->select(['id', 'user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit($limit)
                ->asArray()
                ->all();
        } else {
            $repaymentText = $jpushService::REPAYMENT_TEXT;
            $user_loan_order_repayment = UserLoanOrderRepayment::find()
                ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                ->select(['id', 'user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                ->limit($limit)
                ->asArray()
                ->all();
        }


        $offset = 0;
        $cannotsend = [];
        while ($user_loan_order_repayment) {
            $user_ids = [];
            foreach ($user_loan_order_repayment as $item) {
                $user_ids[$item['user_id']] = $item['user_id'];
            }
            // $user_ids = [];
            // $user_ids['129820024'] = '129820024';
            // $users = LoanPerson::find()->where(['id' => array_keys($user_ids)])->select(['id','name','phone', 'source_id'])->asArray()->all();

            // //查出借款人最近一次登录的设备系统
            // $loginIds = UserLoginLog::find()->select(['max(id) as id'])->where(['user_id' => array_keys($user_ids)])->groupBy('user_id')->column();
            // $last_login = UserLoginLog::find()->select(['id','user_id', 'source'])->where(['id' => $loginIds])->all();

            // $last_login_system = [];
            // foreach ($last_login as $key => $value) {
            //     if (is_numeric($value['source'])) {
            //         continue;
            //     }
            //     $source = unserialize($value['source']);
            //     $last_login_system[$value['user_id']]['clientType'] = $source['clientType'];
            //     $last_login_system[$value['user_id']]['appMarket'] = $source['appMarket'];
            // }

            // $users_info = [];
            // foreach($users as $item) {
            //     $users_info[$item['id']] = [
            //         'user_id'=>$item['id'],
            //         'name'=>$item['name'],
            //         'phone'=>$item['phone'],
            //         'source_id'=>$item['source_id']
            //     ];
            // }
            $count = count($user_loan_order_repayment);
            $_i = 0;
            foreach ($user_loan_order_repayment as $item) {

                echo 'Processing progress: '.(++$_i).'/'.$count.", user_id: ".$item['user_id']."\r\n";
                // if (isset($users_info[$item['user_id']])) {
                // $name = $users_info[$item['user_id']]['name'];
                // if (empty($item['plan_fee_time'])) {
                //     continue;
                // }
                // $phone = $users_info[$item['user_id']]['phone'];
                // if (empty($phone)) {
                //     continue;
                // }

                $last_login = UserLoginLog::find()->select(['id','user_id', 'source'])->where(['user_id' => $item['user_id']])->limit(1)->orderBy(['id' => SORT_DESC])->asArray()->one(Yii::$app->get('db_kdkj_rd_new'));

                if ($last_login && !is_numeric($last_login['source'])) {

                    $source = unserialize($last_login['source']);
                    $channel = $jpushService::getChannelByMarket($source['clientType'], $source['appMarket']);
                    $platform = $source['clientType'];
                } else {

                    $channel = LoanPerson::APPMARKET_XYBT;
                    $platform = 'all';
                }
                $userId = $item['user_id'];

                //渠道名称
                // if (empty($item['source_id'])) {
                //     $source_name = LoanPerson::$person_source[LoanPerson::PERSON_SOURCE_MOBILE_CREDIT];
                // } else {
                //     $source_name = LoanPerson::$person_source[$item['source_id']];
                // }

                try {

                    // $userId = '46875';
                    // $platform = 'android';
                    // $channel = 'xybt';
                    $res = $jpushService->push($repaymentText, $userId, $channel, $platform);

                    $pushLog = new PushLog();
                    $pushLog->user_id = $userId;
                    $pushLog->platform = $platform;
                    $pushLog->app_market = $channel;
                    $pushLog->created_at = time();
                    if (isset($res[0]) && isset($res[0]['http_code']) && $res[0]['http_code'] == 200) {
                        // 推送成功日志
                        $pushLog->status = PushLog::STATUS_SUCCESS;
                    } else {
                        if (isset($res[0])) {
                            $msg = JPushException::$ERROR_MSG[$res[0]];
                        } else {
                            $msg = $res;
                        }

                        $pushLog->status = PushLog::STATUS_FAILED;
                        $pushLog->message = $msg;
                    }
                    $pushLog->save();
                } catch (\Exception $e) {
                    Yii::error("推送失败 {$e}");
                }
                // }
            }

            $offset++;
            $next_wait = $offset*$limit;

            if ($type == 1 || $type == 3 || $type == 10) {
                $user_loan_order_repayment = UserLoanOrderRepayment::find()
                    ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(['overdue_day' => $type])
                    ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                    ->offset($next_wait)
                    ->limit($limit)
                    ->asArray()
                    ->all();
            } else {
                $user_loan_order_repayment = UserLoanOrderRepayment::find()
                    ->where('status <>'.UserLoanOrderRepayment::STATUS_REPAY_COMPLETE)
                    ->andWhere(" plan_fee_time>=".$plan_start_time." and plan_fee_time <".$plan_end_time)
                    ->select(['user_id','principal','late_fee','late_day','interests','true_total_money','plan_fee_time','order_id'])
                    ->offset($next_wait)
                    ->limit($limit)
                    ->asArray()
                    ->all();
            }
        }
    }

    /**
     * 推送测试
     * @return [type] [description]
     */
    public function actionPushTest()
    {
        $user_ids = [];
        $user_ids['46908'] = '46908';
        $users = LoanPerson::find()->where(['id' => array_keys($user_ids)])->select(['id','name','phone', 'source_id'])->asArray()->all();
        $loginIds = UserLoginLog::find()->select(['max(id) as id'])->where(['user_id' => array_keys($user_ids)])->groupBy('user_id')->column();
        $last_login = UserLoginLog::find()->select(['id','user_id', 'source'])->where(['id' => $loginIds])->all();

        $last_login_system = [];
        foreach ($last_login as $key => $value) {
            $source = unserialize($value['source']);
            $last_login_system[$value['user_id']]['clientType'] = $source['clientType'];
            $last_login_system[$value['user_id']]['appMarket'] = $source['appMarket'];
        }

        $jpushService = Yii::$container->get('jpushService');
        if (isset($last_login_system['46908'])) {
            $system = $last_login_system['46908'];
            $channel = $jpushService::getChannelByMarket($system['clientType'], $system['appMarket']);
            $platform = $system['clientType'];
        } else {
            $channel = LoanPerson::APPMARKET_XYBT;
            $platform = 'all';
        }

        try {
            $userId = '46908111';
            // $platform = 'android';
            // $channel = 'xybt';
            $res = $jpushService->push($jpushService::REPAYMENT_TEXT, $userId, $channel, $platform);

            $pushLog = new PushLog();
            $pushLog->user_id = $userId;
            $pushLog->platform = $platform;
            $pushLog->app_market = $channel;
            $pushLog->created_at = time();
            if (isset($res[0]) && isset($res[0]['http_code']) && $res[0]['http_code'] == 200) {
                // 推送成功日志
                $pushLog->status = PushLog::STATUS_SUCCESS;
            } else {
                if (isset($res[0])) {
                    $msg = JPushException::$ERROR_MSG[$res[0]];
                } else {
                    $msg = $res;
                }

                $pushLog->status = PushLog::STATUS_FAILED;
                $pushLog->message = $msg;
            }
            $pushLog->save();
        } catch (\Exception $e) {
            if (YII_ENV_PROD) {
                Yii::error("推送失败 {$e}");
            } else {
                print_r($e);
            }
        }
    }


    /**
     * 收集各种短信通道的上行
     * @return [boolean]
     */
    public function actionCollectMessage() {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        $collects = MessageHelper::collectMesage();
        if (empty($collects)) {
            echo '没有数据' . date('Y-m-d H:i:s') . PHP_EOL;
            return self::EXIT_CODE_NORMAL;
        }

        while ($collects) {
            foreach ($collects as $key => $val) {
                $message = new MessageCollectLog();
                $message->send_time = $val['send_time'];
                $message->expid = $val['expid'];
                $message->phone = $val['phone'];
                $message->message = $val['message'];
                $message->type = $val['type'];
                $message->type_channel = $val['type_channel'];
                $message->created_at = time();
                if (!$message->save()) {
                    $message = implode('##', $val);
                    CommonHelper::stderr("保存上行短信异常，message:{$message}");

                    $key =  'Exception_actionCollectMessage';
                    if (!Yii::$app->cache->get($key)) {
                        MessageHelper::sendSMS(NOTICE_MOBILE2, "保存上行短信异常，message:{$message}"); #短信报警-李格
                        \yii::$app->cache->set($key, 1, 300);
                    }
                }
            }

            $collects = MessageHelper::collectMesage();
        }

        echo '拉取成功' . date('Y-m-d H:i:s') . PHP_EOL;
    }

    /**
     * 查看短信发送状态
     * @return [boolean]
     */
    public function actionStatusMessage() {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        $messages = MessageService::statusMessage('smsService_TianChang_HY_YX');
        if (empty($messages)) {
            echo '没有数据' . date('Y-m-d H:i:s') . PHP_EOL;
            return self::EXIT_CODE_NORMAL;
        }

        while ($messages) {
            foreach ($messages as $key => $val) {
                echo $val['sms_id'] . $val['code'] . '  ' . date('Y-m-d H:i:s') . PHP_EOL;
                $message = new MessageStatusLog();
                $message->send_time = $val['send_time'];
                $message->sms_id = $val['sms_id'];
                $message->phone = $val['phone'];
                $message->code = $val['code'];
                $message->type = $val['type'];
                $message->type_channel = $val['type_channel'];
                $message->created_at = time();
                if (!$message->save()) {
                    $message = implode('##', $val);
                    CommonHelper::stderr("保存短信发送状态异常，message:{$message}");

                    $key =  'Exception_actionCollectMessage';
                    if (!Yii::$app->cache->get($key)) {
                        MessageHelper::sendSMS(NOTICE_MOBILE2, "保存短信发送状态异常，message:{$message}"); #短信报警-李格
                        \yii::$app->cache->set($key, 1, 300);
                    }
                }
            }

            $messages = MessageService::statusMessage();
        }

        echo '拉取成功' . date('Y-m-d H:i:s') . PHP_EOL;
    }
}