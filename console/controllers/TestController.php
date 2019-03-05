<?php

namespace console\controllers;

use common\models\CreditJsqb;
use common\models\CreditYx;
use common\services\WLService;
use common\services\Yxservice;
use Yii;
use common\api\RedisQueue;
use common\helpers\CurlHelper;
use common\helpers\MailHelper;
use common\helpers\MessageHelper;
use common\models\Channel;
use common\models\CreditBqs;
use common\models\CreditBr;
use common\models\CreditJxl;
use common\models\CreditJxlQueue;
use common\models\CreditMg;
use common\models\LoanPerson;
use common\models\mongo\risk\RuleReportMongo;
use common\models\risk\Rule;
use common\models\UserContact;
use common\models\UserCreditData;
use common\models\UserLoanOrderRepayment;
use common\models\UserOrderLoanCheckLog;
use common\models\UserVerification;
use common\models\WeixinUser;
use common\services\CreditCheckService;
use common\services\fundChannel\JshbService;
use common\services\JxlService;
use common\services\RiskControlCheckService;
use common\services\RiskControlService;
use console\soa\UserLoanOrder;
use Exception;
use yii\base\ErrorException;
use common\helpers\CommonHelper;
use common\helpers\Util;
use common\models\UserCreditMoneyLog;
use common\models\UserRegisterInfo;
use common\models\mongo\risk\OrderReportMongo;
use yii\helpers\ArrayHelper;

class TestController extends BaseController {

    public function actionSendMess(){
        $appPath = __DIR__ . DIRECTORY_SEPARATOR;
        //读取文件内容
        $str = file($appPath.'test.txt');//将整个文件内容读入到一个字符串中
//        $str = file_get_contents($appPath.'test.txt');//将整个文件内容读入到一个字符串中
        var_dump($str);exit;
//        $str_encoding = mb_convert_encoding($str, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');//转换字符集（编码）
        $arr = explode("\r\n", $str);
        foreach ($arr as &$row){
            $row = trim($row);
        }
        unset($row);
        //得到后的数组
        var_dump($arr);

//        $sms_channel = 'smsService_TianChang_HY';
//        $source_id = 21;
//        $source_pre = '极速花花';
//        $source_now = '淘钱币';
//        $name = '李格';
//        $phone = '17682449388';
//        $send_message = $name.'，您好【'.$source_pre.'】将迁移到【'.$source_now.'】平台，作为老用户！首次还款将有50元现金红包！https://fir.im/4wfa';
//        $ret = MessageHelper::sendSMSHY($phone,$send_message,$sms_channel,$source_id);
    }

    public function actionSys(){
        $channel = 10008;
        $data = UserOrderLoanCheckLog::find()->from(UserOrderLoanCheckLog::tableName().' as a ')
            ->leftJoin(UserRegisterInfo::tableName().' as b','a.user_id = b.user_id')
            ->where(['b.source' => $channel ,'a.after_status' => -3, 'tree'=> 'TreeArcher','b.date'=> '2018-12-18'])//'b.date'=> '2018-12-18',
            ->asArray()
            ->all();

        // $order_ids = ArrayHelper::getColumn($data, 'id');
        // $order_ids = [];
        // foreach ($data as $item) {
        //     $order_ids[] = intval($item['id']);
        // }
        foreach ($data as &$v){
            $order_reports = OrderReportMongo::find()
                ->select(['order_id', 'reject_roots'])
                ->where(['order_id' => intval($v['user_id'])])//intval($v['order_id'])$order_ids
                ->asArray()
                ->one();
            if (isset($order_reports) && !empty($order_reports['reject_roots'])){
//                var_dump($order_reports['reject_roots']);
                $arr[$order_reports['reject_roots']][] = $v['order_id'];
//                $v['reject_roots'] = $order_reports['reject_roots'];
            }
        }
        foreach ($arr as $key => $value){
            $count = isset($value) ?count($value):0;
            echo $key."\t\t\t\t".$count."\r\n";
            // $res[]['reject_roots'] = $key;
            // $res[]['count'] = count($value);
        }
    }

    public function actionMess(){
        $phone = '17682449388';
        $sms_channel = 'smsService_TianChang_HY';
//        $sms_channel = 'smsService_YiMei';
//        $sms_channel = 'smsServiceXQB_XiAo';
        $source_id = 21;
        $send_message = "您的验证码为:821728 (有效期30分钟)";
        $ret = MessageHelper::sendSMSHY($phone,$send_message,$sms_channel,$source_id);
        var_dump($ret);
    }

    /**
     * 测试同盾服务
     * 皮晴晴 370404199006301915 13333333333 6224900598341823
     * 智润凌 110404190001011928 13800008888 6228481198303800000
     * 张三 230010190301044345 13911112222 6228481198283400000
     * 李四 330283190112120612 18011111111 6217007110004820000
     * 王五 451110190501015626 18600000000 6228480868103320000
     * 赵六 520001190812121210 13100000000 6212261409003370000
     * 好给力 520001190812121210 13100000000
     *
     * @param $account_name
     * @param $id_number
     * @param $account_mobile
     */
    public function actionTd($account_name, $id_number, $account_mobile, $card_number='') {
        $td_svc = Yii::$app->tdService;
        if (empty($card_number)) {
            CommonHelper::stdout( 'bizPreLoan' . PHP_EOL );

            $person = new LoanPerson();
            $person->id = \time();
            $person->name = $account_name;
            $person->id_number = $id_number;
            $person->phone = $account_mobile;
            $res = $td_svc->bizPreLoan($person);
        }
        else {
            CommonHelper::stdout( 'bizAuth' . PHP_EOL );
            $res = $td_svc->bizAuth($account_name, $id_number, $account_mobile, $card_number);
        }

        CommonHelper::stdout( sprintf("result: %s\n", var_export($res, true)) );
    }

    public function actionRedis($id){
        RedisQueue::push([RedisQueue::LIST_CHECK_ORDER, $id]);
    }


    public function actionVerName(){
        $ret = JshbService::realnameAuth('张冬冬', '341226199308160110');

        var_dump($ret);

    }

    public function actionJxl(){
        $orgAccount = 'dichangjr';
        $url = "https://www.juxinli.com/orgApi/rest/v3/applications/{$orgAccount}";
//         $url = "https://credit-test.wealida.com/telecom/collect/open-id";
        $loanPerson = LoanPerson::findOne(1);



        $phone = $loanPerson['phone'];//
        $id_number = strtoupper($loanPerson['id_number']);//
        $name = $loanPerson['name'];
        $home_tel = "";
        $contacts = UserContact::findOne(['user_id' => $loanPerson->id]);
        $contacts_arr = [];
        if (!empty($contacts)) {
            $mobile_list = explode(":", $contacts->mobile);
            $contacts_arr[] = [
                'contact_tel' => current($mobile_list),
                'contact_name' => $contacts->name,
                'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation] ?? "0"  // 默认配偶
            ];
            if (!empty($contacts->mobile_spare) && !empty($contacts->name_spare)) {
                $spare_mobile_list = explode(":", $contacts->mobile_spare);
                $contacts_arr[] = [
                    'contact_tel' => current($spare_mobile_list),
                    'contact_name' => $contacts->name_spare,
                    'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation_spare] ?? "6" // 默认其他
                ];
            }
        }

        $options['home_tel'] = $home_tel;
        $options['contacts'] = $contacts_arr;

        $id_card = str_replace('x', 'X', $id_number);
        $post_data = [
            'selected_website'=>[],
            'skip_mobile'=>false,
            'basic_info'=>[
                'name' => $name,
                'id_card_num' => $id_card,
                'cell_phone_num' => $phone,
                'home_tel'=>$options['home_tel']
            ],
            'contacts'=>$options['contacts'],
            'uid'=>''
        ];

        $result = CurlHelper::curlHttp($url, 'JXL', json_encode($post_data), 300);
        if(!empty($result)){
            if($result['code'] == 65557 && $result['success']){

                $website = $result['data']['datasource']['website'];
                $open_id = $result['data']['token'];
                $service_code = '588154';

                $this->submitServicePasswordNew($open_id,$website,$phone,$service_code);


            }
        }
    }

    public function submitServicePasswordNew($open_id,$website,$phone, $service_password){
        $url = "https://www.juxinli.com/orgApi/rest/v2/messages/collect/req";
//         $url = "https://credit-test.wealida.com/telecom/collect/service-password";
        $post_data = [
            'account' => $phone,
            'token' => $open_id,
            'password' => $service_password,
            'website' => $website,
            'captcha' => '',
            'type' => '',
        ];
        $result = CurlHelper::curlHttp($url, 'JXL', json_encode($post_data), 300);
        var_dump($result);exit();
        if(!empty($result)){
            if($result['success'] && isset($result['data']) && isset($result['data']['process_code']) && $result['data']['process_code'] !=0){
                return [
                    'code' => 0,
                    'process_code' => $result['data']['process_code'],
                    'message' => "服务密码提交成功",
                ];
            }
            return [
                'code' => -1,
                'message' =>$result['data']['content'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '运营商服务密码提交失败，请求发送失败',
            ];
        }
    }

    public function actionTest($node = 166) {
        $loanPerson = LoanPerson::findOne([
            'id' => 6,
            'status' => LoanPerson::PERSON_STATUS_PASS,
        ]);
//        var_dump($loanPerson);exit();

        $ccs = new CreditCheckService;

        //百融-特殊名单
        $br_dat = $ccs->getBrData($loanPerson, false);
        var_dump($br_dat);
        \call_user_func(
            [CommonHelper::class, ($br_dat ? 'stdout' : 'stderr')],
            "br_dat: {$loanPerson->id} " . ($br_dat ? 'success' :  'failed') . PHP_EOL
        );

        //百融-多次申请核查v2
        $br_apply = $ccs->getBrApplyData($loanPerson, false);
        \call_user_func(
            [CommonHelper::class, ($br_apply ? 'info' : 'error')],
            "br_apply: {$loanPerson->id} " . ($br_apply ? 'success' :  'failed') . PHP_EOL
        );
        exit();

        //白骑士-决策信息
        $bqs_dat = $ccs->getBqsData($loanPerson, false);
        \call_user_func(
            [CommonHelper::class, ($bqs_dat ? 'stdout' : 'stderr')],
            "bqs_dat: {$loanPerson->id} " . ($bqs_dat ? 'success' :  'failed') . PHP_EOL
        );

        //face++检测
        $face_data = $ccs->getFacePlus($loanPerson, 2, 13);
        \call_user_func(
            [CommonHelper::class, ($face_data ? 'stdout' : 'stderr')],
            "face_dat: {$loanPerson->id} " . ($face_data ? 'success' :  'failed') . PHP_EOL
        );



        try {
            //获取聚信立报告
            $jxl_ret = $ccs->getJxlBaseReport($loanPerson);
            \call_user_func(
                [CommonHelper::class, ($jxl_ret ? 'stdout' : 'stderr')],
                "jxl_ret: {$loanPerson->id} " . ($jxl_ret ? 'success' :  'failed') . PHP_EOL
            );
        }
        catch (\Exception $e) {
            throw new \Exception("{$loanPerson->id} JxlBaseReport获取异常: {$e}", 3000);
        }


        //同盾
        $td_data = $ccs->getTdData($loanPerson, false);
//        var_dump($td_data);exit();
        \call_user_func(
            [CommonHelper::class, ($td_data ? 'stdout' : 'stderr')],
            "td_ret: {$loanPerson->id} " . ($td_data ? 'success' :  'failed') . PHP_EOL
        );




//        MailHelper::send('dongdongzhang@jisuhebao.com', 'test');
    }
    /*
     * 微信PUSH 新口子
     */
    protected function sendXybt($svc, $wx_user, $url,$content,$date_str) {
        return $svc->sendTpl('SCge42DvUuGcRS-H3m-HLt77STqXcPzbnVNyagKcYho', [
            'openid' => $wx_user['openid'],
            'url'=> $url,
            'first' => $content,
            'keyword1' => '1000-20000元',
            'keyword2' => '7-30天',
            'keyword3' => '审核通过',
            'remark' => '点击详情，立即借款',
        ], 4);
    }


    /**
     * [0112]微信消息
     * console /usr/local/php716/bin/php ./yii huang-wen-pai/wx-msg 'wx0119.txt' 'http://qb.wzdai.com/newh5/web/page/guide-page?tag=0119' '亲，您的风控审核已通过，现在可以开始借款啦！新口子亲测可下款，最高可贷数万元。' '2018年1月19日'
     */
    public function actionWxMsg($url= 'http://www.baidu.com', $content= '亲，您的风控审核已通过，现在可以开始借款啦！新口子亲测可下款，最高可贷数万元。', $date_str = '2018年1月19日') {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

//        $maps = [
//            'sendXybt' => '/tmp/'.$file_name,
////            'sendXybt' => '../data/xybt/wx0116.txt'
//        ];

        Util::cliLimitChange(1024);

        $wx_service = Yii::$app->weixinService;
        $func ='sendXybt';

            CommonHelper::stdout(sprintf('%s start.', $func) . PHP_EOL );
//            $contents = \file($file);
            $contents = [NOTICE_MOBILE];
            foreach($contents as $phone) {
                $phone = trim($phone);
                if (! MessageHelper::getType($phone)) {
                    CommonHelper::stdout(sprintf('%s - %s not_phone.', $func, $phone) . PHP_EOL );
                }
                else { //get wx_user, send wx push .
                    $wx_user = WeixinUser::find()
                        ->where(['phone' => $phone])
                        ->asArray()->one();
                    if (empty($wx_user)) {
                        CommonHelper::stdout(sprintf('%s - %s none_user.', $func, $phone) . PHP_EOL );
                    }
                    else {
                        $ret = $this->$func($wx_service, $wx_user, $url, $content, $date_str);
                        CommonHelper::stdout(sprintf('%s - %s - %s.', $func, $phone, strval($ret)) . PHP_EOL );
                    }
                }
            }


        return self::EXIT_CODE_NORMAL;
    }

    public function actionTestInfo(){
        @$time_now =date('h:i:s',time());
        if(($time_now>='09:00:00'&&$time_now<='09:10:00')||($time_now>='14:00:00'&&$time_now<='14:05:00')||($time_now>='20:00:00'&&$time_now<='20:20:00')||($time_now>='22:30:00'&&$time_now<='22:40:00')){
            return  false;
        }
    }

    public function __unset($name)
    {
        return parent::__unset($name); // TODO: Change the autogenerated stub
    }

    public function actionTestInfos($type){
        $service = new RiskControlCheckService();
        if($type == 1){
            $res = UserLoanOrder::find()->select(['user_id'])->groupBy('user_id')->all();
        }else{
            $res = UserLoanOrderRepayment::find()->select(['user_id'])->groupBy('user_id')->all();
        }
        $count = 0;
        $count1 = 0;
        $count2 = 0;
        foreach ($res as $k=>$v){
            $bqs = CreditBqs::find()->where(['person_id'=>$v['user_id']])->asArray()->one();
            $data_res = json_decode($bqs['data'],true);
            $data['bqs']['finalDecision'] = $data_res['finalDecision'];
           // $ress = $service->checkBqsDecisionInfo($data);
            /*if($ress['value'] == 'Review'){
                $count ++;
                $user_id = $v['user_id'];
            }elseif ($ress['value'] == 'Accept'){
                $count1++;//通过的
                $user_id2 = $v['user_id'];
            }else{
                $name = $ress['value'];
                $count2++;
                $user_id3 = $v['user_id'];
            }*/
        }
        //echo $user_id."Review:".$count.'|'.$user_id2." Accept:".$count1.'|'.$user_id3." other:".$count2.':'.$name."\r\n";
    }

    /**
     * @name 百融数据
     */
    public function actionTestBrInfo($type,$count_type){
        $service = new RiskControlCheckService();
        if($type == 1){
            $res = UserLoanOrder::find()->select(['user_id'])->groupBy('user_id')->all();
        }else{
            $res = UserLoanOrderRepayment::find()->select(['user_id'])->groupBy('user_id')->all();
        }
        $count2 = 0;
        foreach ($res as $k=>$v){
            $bqs = CreditBr::find()->where(['person_id'=>$v['user_id']])->asArray()->all();
            $data['br'] = $bqs;
            $ress = $service->checkBrSpecialList($data);;
            ;
            $num = intval($count_type);
            if($ress['value'] > $num){
                $count2 ++;
            }
        }
        echo $count_type."：".$count2."\r\n";
    }

    /**
     * 修复JXL数据拉去失败的用户并重新推入队列
     */
    public function actionFixJxlUser(){
        $all = UserOrderLoanCheckLog::find()
            ->where(['remark'=>'采集不到聚信立数据 拒绝'])
            ->andWhere(['between','created_at','1524121200','1524135600'])
            ->asArray()->all();
        foreach ($all as $k=>$v){
            $transaction = Yii::$app->db->beginTransaction();
            //修改订单状态  status
            $order = UserLoanOrder::find()->where(['id'=>$v['order_id']])->one();
            $order->status  = 0;
            $order->auto_risk_check_status  = 0;
            //修改运营商状态
            $creditjxl = CreditJxlQueue::find()->where(['user_id'=>$v['user_id']])->one();
            $creditjxl->current_status = 6;
            //修改认证状态
            $user_verifi = UserVerification::find()->where(['user_id'=>$v['user_id']])->one();
            $user_verifi->real_jxl_status = 1;
            if($order->save() && $creditjxl->save() && $user_verifi->save()){
                //PUSH 队列
                RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX,$v['order_id']]);
                $transaction->commit();
                echo "用户".$v['user_id'].'|'.$v['order_id']."修复成功\r\n";
            }else{
                $transaction->rollBack();
            }
        }
    }
    /**
     * 蜜罐分黑中介分
     */
    public function actionGetMg(){
        $all = UserLoanOrderRepayment::find()->where(['<','created_at',1523116800])->all();
        foreach ($all as $k=>$v){
            $res = CreditMg::find()->where(['person_id'=>$v['user_id']])->one();
            $data1 = json_decode($res['data'],true);
            $key = isset($data1['user_gray']['phone_gray_score'])?$data1['user_gray']['phone_gray_score']:10000;
            if($v['overdue_day'] == 0){
                $val = isset($data[$key][1])?$data[$key][1]:0;
                $data[$key][1] = $val + 1;

            }elseif($v['overdue_day'] >=0 && $v['overdue_day'] <=3){
                $val2 = isset($data[$key][2])?$data[$key][2]:0;
                $data[$key][2] = $val2 + 1;
            }else{
                $val3 = isset($data[$key][3])?$data[$key][3]:0;
                $data[$key][3] = $val3 + 1;
            }
        }
        var_dump(json_encode($data));die;
    }

    /**
     * 风控脚本测试
     */
    public function actionTestRule(){
        $rule_id = 390;
        $user_all = UserLoanOrder::find()->from(UserLoanOrder::tableName().' as o')
            ->leftJoin(UserOrderLoanCheckLog::tableName().' as r','o.id = r.order_id')
        ->where(['between','o.created_at','1526227200','1526486400'])->where(['r.remark'=>'现金白卡黑名单拒接'])->groupBy('o.user_id')->limit(2)->all();
        foreach ($user_all as $k=>$v){
            $order = UserLoanOrder::find()->where(['id'=>$v['id']])->one();
            $loan_person = LoanPerson::find()->where(['id'=>$v['user_id']])->one();
            if(empty($loan_person)){
                return json_encode(['result'=>'用户不存在']);
            }
            $result = "运行特征失败";
            try{

                $riskControlService = new RiskControlService();
                $ret = $riskControlService->runSpecificRule([$rule_id],$loan_person,$order,1,1);
                foreach ($ret as $key => $value) {
                    //$result = "risk:".$value['risk']."\ndetail:".$value['detail']."\nvalue:".var_export($value['value'],true).".";
                    $result = $value['value'];
                    break;
                }
            }catch(Exception $e1){
                $result = "---error1---\nmessage:".$e1->getMessage()."\n".$e1->getTraceAsString();
            }catch(ErrorException $e2){
                var_dump($e2->getMessage());
                var_dump($e2->getFile());
                var_dump($e2->getLine());
                $result = "error: \n". $e2->getMessage() . "; \n" . $e2->getFile() . "; \n" . $e2->getLine() . "; \n" .$e2->getTraceAsString();
            }
            var_dump($result);die;
            if(isset($result['result'])) {
                var_dump($result);die;
                $key = isset($result['result']['txt'])?$result['result']['txt']:'';
                if (isset($result['result']['txt']) && $result['result'] != 1 ) {
                    $val2 = isset($data[$key])?$data[$key]:0;
                    $data[$key] = $val2 + 1;
                    var_dump($v['user_id'],$data[$key],$val2,$key);
                }
            }
        }
    }



    public function actionTestRules($rule_id){
        Util::cliLimitChange(1024);
        $user_all = UserLoanOrderRepayment::find()->where(['is_overdue'=>0])->andWhere(['>','user_id',39])->andwhere(['<','plan_fee_time','1524412799'])->all();
        $service = new RiskControlCheckService();
        switch ($rule_id){
            case '738':
                $sername = 'CheckYxLatelyLoan';
                break;
            case '737':
                $sername = 'CheckYxOverdueM2';
                break;
            case '736':
                $sername = 'CheckYxOverdueM1';
                break;
            case '735':
                $sername = 'CheckYxFenxian';
                break;
            case '734':
                $sername = 'CheckYxOverdueM6';
                break;
            case '733':
                $sername = 'CheckYxOverdueM3';
                break;
            case '732':
                $sername = 'CheckYxOverdueM3';
                break;
            case '731':
                $sername = 'CheckYxDangerInfo';
                break;
            case '730':
                $sername = 'CheckZcFen';
        }
        foreach ($user_all as $k => $v) {
            $data1 = CreditYx::findLatestOne(['user_id' =>  $v['user_id']]);
            $data_res['yx'] = json_decode($data1->data,true);
            $service_res = $service->$sername($data_res);
            $key = isset($service_res['value']) ? intval($service_res['value']) : 0;
            if($key == 0){
                $key = 1000;
            }
            if($rule_id == 730) {
                if ($key < 450) {
                    $key = 440;
                }
                if ($key > 560) {
                    $key = 560;
                }
                if ($key > 450 && $key <= 460) {
                    $key = 450;
                }
                if ($key > 460 && $key <= 470) {
                    $key = 460;
                }
                if ($key > 470 && $key <= 480) {
                    $key = 470;
                }
                if ($key > 480 && $key <= 490) {
                    $key = 480;
                }
                if ($key > 490 && $key <= 500) {
                    $key = 490;
                }
                if ($key > 500 && $key <= 510) {
                    $key = 500;
                }

                if ($key > 510 && $key <= 520) {
                    $key = 510;
                }
                if ($key > 520 && $key <= 530) {
                    $key = 520;
                }
                if ($key > 530 && $key <= 540) {
                    $key = 530;
                }
                if ($key > 540 && $key <= 550) {
                    $key = 540;
                }
                if ($key > 550 && $key <= 560) {
                    $key = 550;
                }
            }
            $val2 = isset($data[$key]) ? $data[$key] : 0;
            $data[$key] = $val2 + 1;
        }
        ksort($data);
        var_dump($data);
    }

    /*
     * 补充放款用户宜信的数据
     */
    public function actionGetYxInfo(){
            $loan_person = LoanPerson::find()->where(['id'=>1128242])->one();
            $res = Yxservice::getData($loan_person);
            if($res){
                echo "用户1128242数据保存完成\r\n";
            }else{
                echo "用户1128242数据保存失败\r\n";
            }

    }
    /**
     * 查看M1数据返回-1
     */
    public function actionTestError(){
        $res = UserLoanOrderRepayment::find()->where(['is_overdue'=>1])->andWhere(['>','user_id',39])->all();
        foreach ($res as $k=>$v){
            $loanperson = LoanPerson::find()->where(['id'=>$v['user_id']])->one();
            $data = CreditYx::find()->where(['user_id'=>$loanperson->id])->one();
            $data_arr = json_decode($data->data,true);
            if(isset($data_arr['params']['data']['loanRecords'])){
                $loan_info = $data_arr['params']['data']['loanRecords'];
                foreach ($loan_info as $k1=>$v1){
                    if($v1['overdueStatus'] == 'M1'){
                        echo $v['user_id']."\r\n";
                    }
                }
            }
        }
    }
    /**
     * 测试现金白开黑名单
     */
    public function actionTestBkList(){
        $service = new WLService();
        $id = 116283;
        $person = LoanPerson::find()->where(['id'=>$id])->one();
        $res = $service::getIsBlack($person);
        var_dump($res);die;
    }

    public  function actionAbcd(){

        $offset = 0;
        $limit  = 1000;

        $res = UserLoanOrderRepayment::find()->orderBy('id')->limit($limit)->all();

        while($res){

            foreach($res as $v){

                $log_list = UserCreditMoneyLog::find()->where(['order_id'=>$v->order_id,'status'=>UserCreditMoneyLog::STATUS_SUCCESS])->orderBy('id')->asArray()->all();

                if($log_list){

                    $money = $v->true_total_money ;  //实际还款金额

                    $yh_benji = 0;  //  次订单已还本金
                    $yh_lixi  = 0;
                    $yh_znj   = 0;

                    foreach($log_list as $kk=>$log){

                        $current_log = UserCreditMoneyLog::findOne(['id'=>$log['id']]);

                        $current_money  = $current_log->operator_money ;  // 当次还款金额

                        $ksy_money = $current_money;

                        $current_log->operator_interests = 0;
                        $current_log->operator_principal = 0;
                        $current_log->operator_late_fee  = 0;
                        $current_log->operator_overflow  = 0;

                        if($yh_lixi < $v->interests){
                            $current_log->operator_interests = $ksy_money >= ($v->interests - $yh_lixi) ? ($v->interests - $yh_lixi) : $ksy_money;
                            $ksy_money = $ksy_money - $current_log->operator_interests;
                            $yh_lixi  = $yh_lixi + $current_log->operator_interests;
                        }

                        if($yh_benji < $v->principal  && $ksy_money > 0){

                            $current_log->operator_principal = $ksy_money >= ($v->principal - $yh_benji) ? ($v->principal - $yh_benji) :$ksy_money;
                            $ksy_money = $ksy_money - $current_log->operator_principal;
                            $yh_benji = $yh_benji + $current_log->operator_principal;
                        }

                        if($yh_znj < $v->late_fee  && $ksy_money > 0 ){

                            $current_log->operator_late_fee = $ksy_money >= ($v->late_fee - $yh_znj) ? ($v->late_fee - $yh_znj) : $ksy_money;
                            $ksy_money = $ksy_money - $current_log->operator_late_fee;
                            $yh_znj = $yh_znj + $current_log->operator_late_fee;
                        }

                        if($ksy_money > 0){
                            $current_log->operator_overflow = $ksy_money;
                        }

                        if($current_log->save()){
                            echo '订单  :'.$current_log->order_id,' + 日志ID：'.$current_log->id.'  成功'."\r\n";
                        }else{
                            echo 'ERROR -------------- 订单  :'.$current_log->order_id,' + 日志ID：'.$current_log->id." \r\n";
                        }
                    }
                }
            }

            $offset++;
            $next_wait = $offset*1000;

            $res = UserLoanOrderRepayment::find()->orderBy('id')->offset($next_wait)->limit(1000)->all();
        }

    }
}
