<?php
namespace mobile\controllers;

use common\api\RedisQueue;
use common\base\LogChannel;
use common\models\AutoDebitLog;
use common\models\DiscoverColleague;
use common\models\WeixinUser;
use common\services\DiscoverColleagueBannerService;
use common\services\fundChannel\JshbService;
use common\services\LoanService;
use common\services\OrderService;
use Yii;
use yii\base\Exception;
use yii\base\UserException;
use yii\filters\AccessControl;
use common\services\UserService;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\CardInfo;
use common\helpers\TimeHelper;
use common\models\UserCreditMoneyLog;
use common\helpers\Util;
use yii\helpers\Url;
use common\models\UserLoanOrderDelay;
use common\models\UserCaptcha;
use common\exceptions\UserExceptionExt;
use mobile\components\ApiUrl;
use common\models\LoanPerson;
use common\services\CardService;
use common\services\FundService;
use common\services\fundChannel\WycreditService;
use common\models\fund\LoanFundSignUser;
use common\models\UserOrderLoanCheckLog;
use common\helpers\StringHelper;
use common\models\fund\FundSignViewLog;
use common\soa\KoudaiSoa;
use common\models\FinancialDebitRecord;
use common\models\RepaymentConfig;
use yii\db\Query;
use common\models\ErrorMessage;

class LoanController extends BaseController {

    public $layout = 'loan';
    protected $userService;

    /**
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
    public function __construct($id, $module, UserService $userService, $config = [])
    {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    /**
     * 重新指定跳转登录页面
     */
    public function init()
    {
        parent::init();
        Yii::$app->user->loginUrl = 'koudaikj://app.launch/login/applogin';
        // other init
        //指定跳app登录页
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // 除了下面的action其他都需要登录
                'except' => ['fund-sign', 'fund-sign-status', 'application-success','repayment-success','hc-test'], //测试添加不需要登录 //todo 上线修改
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    private function _getOrderInfos($id, $card_id = 0) {
        $user_id = \yii::$app->user->isGuest ? '' : \yii::$app->user->identity->id;
        if (empty($user_id)) {
            return false;
        }

        return UserLoanOrder::getOrderRepaymentCard($id, $user_id, $card_id);
    }

    private function boolSelectBank($phone, $card) {
        return true;
    }

    /**
     * 获取部分还款金额列表
     * @TODO获取部分还款金额列表
     * @name获取部分还款金额列表 [CreditLoanGetConfirmLoan]
     * @method post
     * @param integer $id 借款订单id
     * @author  caochi
     */
    private function getPartRepayMoney(Array $repayment) {
        $data = [];
        if( empty($repayment) ) {
            return $data;
        }
        $repayemnt_config = RepaymentConfig::findOne(array("status" =>  RepaymentConfig::STATUS_VALID));
        if($repayemnt_config->percent && $repayemnt_config->max) {

            $total_money = $repayment['principal'] + $repayment['late_fee'] ; //本金+逾期费用
            $part_money = min($total_money * $repayemnt_config->percent/100, $repayemnt_config->max);

            $part_money = floor($part_money/10000)*10000;

            if( $repayment['remain_money_amount'] <=  $part_money ) { //小于最低还款额禁止部分还款
                return $data;
            }
            if(abs($repayment['remain_money_amount'] - $part_money) < 10000 ) { //差额小于 100 禁止部分还款
                return $data;
            }

            $k = 1;
            for($i = $part_money; $i < $repayment['remain_money_amount'] ; $i = $i + 10000) {
                if($i > 0) {
                    $data[$k] = $i;
                    $k++;
                }
            }
            $data = array_reverse($data); //降序排列
            array_unshift( $data , $repayment['remain_money_amount'] ); //加入

        }

        return $data;

    }

    /**
     * 借款详情
     * @return string
     */
    public function actionLoanDetail() {
        $page_action = $this->request->get('page_action');
        if($page_action == 'get_more') {
            $this->view->title = '借款详情-更多';
        } else {
            $this->view->title = '借款详情';
        }

        $id = $this->request->get('id');
        $from = $this->request->get('from');        // 用于判断来源
        $infos = $this->_getOrderInfos($id);        // 用户订单详情
        $order = $infos['order'];
        $repayment = $infos['repayment'];           // 用户放款详情
        $strPacket = "";
        $list = [];
        $coupon_status = 0;
        $coupon_time = "";
        $coupon_list = [];

        $user_id = $order["user_id"];
        $user_materia = [];

        $show_collection = 0;
        $source = $this->getSource();

        $wxUser = NULL;
        $weixin_show = NULL;

        //部分还款金额list
        $part_repay_money = [];
        if(!empty($repayment) && $source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT) {
            $part_repay_money = $this->getPartRepayMoney($repayment);
        }

        //是否正在还款处理中
        $is_repaymenting=0;
        //前端判断状态
        $type_number = 0;
        $head_show = [];
        if ($repayment) {//已打款
            // 处理催收投诉建议的按钮 //todo 催收投诉
            if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {//已还款
                $list[] = [
                    'title' => '已还款'.'<span>'.date('Y-m-d',$repayment['true_repayment_time']).'</span>',
                    'body' => '恭喜还款成功，又积攒了一份信用',
                    'class' => 'do',
                    'type'  => UserLoanOrder::STATUS_REPAY_COMPLETE,
                ];
                $head_show = [
                    'new_title' => '还款成功',
                    'new_desc' => '还款成功，征信人生，点滴积累！',
                ];
                $type_number = 3;
            } else if ($repayment['is_overdue'] && $repayment['overdue_day']) {//逾期
                $overdue_text = '';
                $list[] = [
                    'title' => '已经逾期' . $repayment['overdue_day'] . '天',
                    'body' => '征信人生，点滴积累'.' <span>逾期费用' . sprintf("%0.2f", $repayment['late_fee'] / 100) . "元" . $overdue_text . "</span>",
                    'class' => 'red',
                    'type'  => UserLoanOrder::STATUS_OVERDUE
                ];
                $autoDebitLog = AutoDebitLog::find()->where(['user_id'=>$user_id,'order_id'=>$order['id']])->orderBy(['id'=>SORT_DESC])->one();
                if ($autoDebitLog && in_array($autoDebitLog['status'],[AutoDebitLog::STATUS_WAIT,AutoDebitLog::STATUS_DEFAULT])) {
                    $is_repaymenting=1;
                    $head_show = [
                        'new_title' => '还款处理中',
                        'new_desc' => '请及时关注还款动态',
                    ];
                }else{
                    $head_show = [
                        'new_title' => '逾期'. $repayment['overdue_day']."天",
                        'new_desc' => '逾期会影响您的信用，快去还款吧！',
                    ];
                }
                $type_number = 4;
            } else {
                $overdue_txt = "";
                $diffDay = TimeHelper::DiffDays(date('Y-m-d H:i:s', $repayment['plan_fee_time']), date('Y-m-d H:i:s'));
                $list[] = [
                    'title' => $diffDay > 0 ? $diffDay . '天后还款 ' . $overdue_txt . '' : '待还款',
                    'body' => '请于' . date('Y-m-d', $repayment['plan_fee_time']) . '日前将还款金额存入银行卡中',
                    'class' => 'green',
                    'type'  => UserLoanOrder::STATUS_LOAN_COMPLING
                ];
                $diffHour = TimeHelper::DiffHours(date('Y-m-d H:i:s', $repayment['plan_fee_time']), date('Y-m-d H:i:s'));
                $diffSencond = TimeHelper::DiffSenconds(date('Y-m-d H:i:s', $repayment['plan_fee_time']), date('Y-m-d H:i:s'));
                $date_txt = '';
                if( $diffDay ){
                    $date_txt .= $diffDay."天";
                }
                $date_txt .= $diffHour."小时";
                $date_txt .= $diffSencond."分";

                $autoDebitLog = AutoDebitLog::find()->where(['user_id'=>$user_id,'order_id'=>$order['id']])->orderBy(['id'=>SORT_DESC])->one();
                if ($autoDebitLog && in_array($autoDebitLog['status'],[AutoDebitLog::STATUS_WAIT,AutoDebitLog::STATUS_DEFAULT])) {
                    $is_repaymenting=1;
                    $head_show = [
                        'new_title' => '还款处理中',
                        'new_desc' => '请及时关注还款动态',
                    ];
                }else{
                    $head_show = [
                        'new_title' => '待还款',
                        'new_desc' => '打款成功，距离还款还有'. $date_txt,
                    ];
                }

                $type_number = 5;
            }
            $list[] = [
                'title' => '打款成功 ' .'<span>'. date('Y-m-d H:i', $order['loan_time']).'</span>',
                'body' => '成功打款至您的预留账号，请准时还款',
                'class' => '',
                'type'  => UserLoanOrder::STATUS_LOAN_COMPLING
            ];

            $list[] = [
                'title' => '审核通过 ' . '<span>'. ($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : '').'</span>',
                'body' => '风控审核通过，请留意账号余额',
                'class' => '',
            ];
        }
        else {
            //未打款
            if ($order['status'] >= UserLoanOrder::STATUS_PAY && !in_array($order['status'], UserLoanOrder::$checkStatus)) { //打款中
                if ($order['status'] == UserLoanOrder::STATUS_FUND_CONTRACT) {
                    $fund_service = Yii::$container->get('fundService');
                    /* @var $fund_service FundService */
                    $order_model = UserLoanOrder::instantiate($order);
                    UserLoanOrder::populateRecord($order_model, $order);
                    /* @var $order_model UserLoanOrder */
                    if (!($url = $fund_service->getSignUrl($order_model))) {
                        throw new \Exception("获取订单 {$order_model->id} 签约URL失败");
                    }
                    $list[] = [
                        'title' => sprintf("银行卡待确认 <a href='%s' title='确认银行卡'>确认银行卡</a>", $url),
                        'body' => '请在1小时内确认银行卡信息，超过系统将自动确认',
                        'class' => 'do',
                    ];
                } else {
                    $list[] = [
                        'title' => '打款中 ' . '<span>'.($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : '').'</span>',
                        'body' => '已进入打款状态，请您耐心等待',
                        'class' => 'do',
                        'type'  => UserLoanOrder::STATUS_PAY
                    ];
                }

                $list[] = [
                    'title' => '审核通过 ' . '<span>'.($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : '').'</span>',
//                    'body' => '恭喜通过风控审核',
                    'body' => '风控审核通过，请留意账号余额',
                    'class' => '',
                ];
            }
            else if ($order['status'] < UserLoanOrder::STATUS_CHECK) { //审核未通过

                // 判断该用户是否有未领取的红包
                $item = [];
                if (count($item) > 0) {
                    $coupon_status = 4;
                } else {
                    $coupon_status = 5;
                }

                $list[] = [
                    'title' => '审核未通过' . '<span>'.($order['updated_at'] ? date('Y-m-d H:i', $order['updated_at']) : '').'</span>',
                    'body'  =>  '您的信用评分不足，该次评分未通过。',
                    'class' => 'die',
                    'btn_url' => true,
                    'type'  =>  UserLoanOrder::STATUS_CANCEL//驳回
                ];
                $type_number = 6;
            } else {//审核中
                $list[] = [
                    'title' => '审核中',
                    'body'  => '已进入风控审核状态，请您耐心等待',
                    'class' => 'do',
                    'type'  => UserLoanOrder::STATUS_CHECK,//审核中
                ];
            }
        }
        $list[] = [
            'title' => '申请提交成功 ' . '<span>'.date('Y-m-d H:i', $order['order_time']).'</span>',
            'body'  => '借款申请已提交，等待风控审核',
            'class' => '',
        ];
        $remainDelayTimes =  0;
        $link_url = YII_ENV_PROD ? ApiUrl::toCredit("credit-web/rebate-slow") : ApiUrl::toRouteCredit(['credit-web/rebate-slow']);
        $slow_url = YII_ENV_PROD ? ApiUrl::toCredit("credit-web/event-details-page") : ApiUrl::toRouteCredit(['credit-web/event-details-page']);
        $book_url = YII_ENV_PROD ? ApiUrl::toCredit("credit-info/leaving-message") : ApiUrl::toRouteCredit(['credit-info/leaving-message'], true);

        $flag_show = 0;
        if (($from == 'h5' || $this->isFromXjk() || $this->isFromKxjie()) &&
            !$repayment) {
            $flag_show = 1;
        }
        $url_str = '';
        $url_one = ApiUrl::toCredit(ApiUrl::toRouteCredit(['credit-web/platform-service', "id" => $order["id"]], true)).$url_str;
        $url_two = ApiUrl::toCredit(ApiUrl::toRouteCredit(['credit-web/loan-agreement', "id" => $order["id"]], true)).$url_str;
        $url_three = ApiUrl::toCredit(ApiUrl::toRouteCredit(['credit-web/license-agreement', "id" => $order["id"]], true)).$url_str;

        //处理对应的状态
        $list = array_reverse($list);
        $count = count($list);
        if ($count == 2 && $list[1]['type'] != UserLoanOrder::STATUS_CANCEL) { //判断用户是否通过审核
            $type_number = 1;
            $list[] = [
                'title' => '打款成功',
                'body' => '成功打款至您的预留账号，请准时还款',
                'class' => '',
                'type' => '',
            ];
            $list[] = [
                'title' => '还款成功',
                'body' => '征信人生，点滴积累',
                'class' => '',
                'type' => '',
            ];
        }
        if ($count == 3) { //打款中
            $type_number = 2;
            $list[] = [
                'title' => '还款成功',
                'body' => '征信人生，点滴积累',
                'class' => '',
                'type' => '',
            ];
        }

        $view = 'loan_detail';

        //查询是不是因为钱放完被拒绝  放款最大
        $oreder_remark = UserOrderLoanCheckLog::find()
            ->select('remark')
            ->where(['order_id'=>$order['id']])
            ->orderBy('id DESC')
            ->limit(1)->one();
        $is_show = 1;
        if(isset($oreder_remark->remark) && $oreder_remark->remark != '') {
            if (mb_strpos($oreder_remark->remark, '放款最大') !== false) {
                $is_show = 0;
            }
        }
        $xjbt = 0;

        //判断是否显示详情页
        $show_loan_detail = 0;
        $urL_res = '';
        $can_loan = 0;//是否显示跳转的按钮

        $hide = false;

        if($hide === false && LoanPerson::getUserLoanType($user_id) == false){
            $can_loan = 1;
        }

        //获取附加的详情数据
        $userLoanInfo  = OrderService::actionLoanInfoDetail($order,$repayment);
        if((string)$userLoanInfo['order_loan_time']=='1970-01-01'){
            $userLoanInfo['order_loan_time']='--';
        }
        if((string)$userLoanInfo['order_repayment_time']=='1970-01-01'){
            $userLoanInfo['order_repayment_time']='--';
        }
        if((string)$userLoanInfo['order_true_repayment_time']=='1970-01-01'){
            $userLoanInfo['order_true_repayment_time']='--';
        }
        //如果没有还款，则显示借款时应还金额
        if(intval($userLoanInfo['order_pay_all_money'])==0){
            $userLoanInfo['order_pay_all_money']=($order['money_amount']+$order['loan_interests'])/100;
        }
        return $this->render($view, [
            'id' => $id,
            'order' => $order,
            'repayment' => $repayment,
            'head_show' => $head_show,
            'page_action' => $page_action,
            'free_money' =>  0,
            'list' => $list,
            'show_type' =>$type_number,
            'remainDelayTimes' => $remainDelayTimes,
            'daly_tip' => '',
            'red_packet' => $strPacket,
            'link_url' => $link_url,
            'book_url' => $book_url,
            'url_one' => $url_one,
            'slow_url' => $slow_url,
            'url_two' => $url_two,
            'url_three' => $url_three,
            'is_xjbt' => $xjbt,
            'show_emergency' => 0, //去除江湖救急 btn
            'emergency_url' => '',
            'useFy' => '',
            'user_materia' => $user_materia,
            'flag_show' => $flag_show,
            'show_collection' => $show_collection,
            'is_show' => $is_show,
            'coupon_list' => $coupon_list,
            'coupon_count' => count($coupon_list) ,
            'source' => $this->getSource(),
            'wx_user' => $wxUser,
            'weixin_show' => $weixin_show,
            'user_coupon' => [ //慢就赔，拒就赔
                "status" => $coupon_status,
                "time" => $coupon_time,
                "hour" => isset($timeArr[0]) ? $timeArr[0] : 0,
                "min" => isset($timeArr[1]) ? $timeArr[1] : 0,
                "sec" => isset($timeArr[2]) ? $timeArr[2] : 0,
            ],
            "part_repay_money" => $part_repay_money,
            'find_list'=>[],
            'show_loan_detail'=>$show_loan_detail,
            'loan_jump_url'=>$urL_res,//被拒绝导流跳转
            'can_loan' => $can_loan,//是否显示借款链接
            'hide'=>$hide,//过审核使用
            'user_info_more'=>$userLoanInfo,
            'is_repaymenting'=>$is_repaymenting
        ]);
    }


    public function actionFirstNotice()
    {
        if(yii::$app->request->isAjax)
        {
            $order_id = $this->request->post('id');
            $redistKey = 'first_notcie_' . $order_id;
            $redistKeyVal = time() . ':' . $order_id;
            \Yii::$app->redis->set($redistKey, $redistKeyVal);
        }

    }

    /**
     * 选择还款方式
     * [clark]手动还款第一步
     */
    public function actionLoanRepaymentType() {
        new LoanPerson();
        $this->view->title = '请选择还款方式';
        $id = intval($this->request->get('id'));
        $part_money = intval($this->request->get('part_money')); //部分还款金额
        $loan_repaymenttype = intval($this->request->get('loan_repaymenttype'));//展期还款
        $type = intval($this->request->get('type'));
        $coupon_id = intval($this->request->get('coupon_id'));
        $infos = $this->_getOrderInfos($id);
        $order = $infos['order'];
        if (!$infos['repayment']) {
            Yii::error("actionLoanRepaymentType 订单还未打款! infos:".json_encode($infos));
            throw new UserException("订单还未打款");
        }
        $repayment = $infos['repayment'];
        $free_money = isset($infos['repayment']['coupon_money']) ?$infos['repayment']['coupon_money']: 0;
        $user_id = $order['user_id'];
        $alertMgs = '';
        $isPayed = false;
        if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            $isPayed = true;
            $alertMgs = '订单已还款,请勿重复还款!';
        }
        if($loan_repaymenttype==1){
            //展期费计算
            $zhanqi_money=$order['counter_fee'];
            if($zhanqi_money<$order['money_amount']*ZHANQI_LOAN_LV){
                $zhanqi_money=$order['money_amount']*ZHANQI_LOAN_LV;
            }
            $part_money=$repayment['interests']+$repayment['late_fee']+intval($zhanqi_money);
            $part_money = intval($part_money)/100;
        }else{
            $loan_repaymenttype=0;
        }
        $UserCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id' => $order['user_id'],'order_id' => $order['id']])->orderBy('id desc')->one();
        $FinancialDebitRecord = FinancialDebitRecord::find()->where(['user_id' => $order['user_id'],'loan_record_id' => $order['id']])->andWhere(['>=', 'created_at', \strtotime(date('Y-m-d'))])->orderBy(['id'=>SORT_DESC])->limit(1)->one();
        if ($UserCreditMoneyLog && ($UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_ING || $UserCreditMoneyLog->status == UserCreditMoneyLog::STATUS_NORMAL)) {
            $isPayed = true;
            $alertMgs = '订单正在处理中,请勿重复提交还款';
        }
        if ($FinancialDebitRecord && $FinancialDebitRecord->status == FinancialDebitRecord::STATUS_RECALL) {
            $isPayed = true;
            $alertMgs = '该订单处于代扣中,请稍后再试!';
        }

        $selectBank = true;
        $userService = Yii::$container->get('userService');
        $myCards = $userService->getCardInfo($user_id);
        $source_id = $this->getSource();
        $view = 'choose_repayment_type';
        if(!in_array($source_id,LoanPerson::$source_register_list)){
            $view = 'choose_repayment_type_other';
        }
        if(Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){
            $view = 'choose_repayment_type_pro';
        }

        $db = new Query();
        $where_hc=' bentime <='.time().' and endtime>= '.time();
        $zhifubao=$db->select('status,bentime,endtime')->from('tb_zhifustatus')->where($where_hc)->one();

        // 当日汇潮是否有过还款失败
        $s_t = strtotime(date('Y-m-d',time()));
        $e_t = $s_t + 86400;

        $where =' status = -2 and debit_type = 7 and user_id = '.$user_id.' and created_at>='.$s_t.' and created_at < '.$e_t ;
        $hc = AutoDebitLog::find()->select(['id'])->where($where)->one();

        if($hc){
            $zhifubao['status'] = 1;
            $zhifubao['bentime']= $s_t;
            $zhifubao['endtime']= $e_t;
        }

        return $this->render($view, [
            'id' => $id,
            'type' => $type,
            'order' => $order,
            'repayment' => $repayment,
            'selectBank' => $selectBank, //TODO try delete me
            'myCards' => $myCards, //TODO try delete me
            'free_money' => $free_money,
            'alertMgs' => $alertMgs,
            'isPayed' => $isPayed,
            'part_money' => $part_money,
            'useFy' => false,
            'zhifubao'=>$zhifubao,
            'loan_repaymenttype'=>$loan_repaymenttype
        ]);
    }

    /**
     * 渠道还款接口
     */
    public function actionChannelRepayment($id)
    {
        $id = (int)$id;
        $order = UserLoanOrder::findOne($id);
        $user = Yii::$app->user->getIdentity();

        $order_info = UserLoanOrder::getOrderRepaymentCard($id, $user->id);
        if (!$order || $order->user_id != $user->id) {
            throw new \Exception('找不到对应的订单');
        }

        $card_info = $order->cardInfo;
    }

    /**
     * 支付宝还款方式
     */
    public function actionLoanRepaymentAliapy()
    {
        $this->view->title = '支付宝还款方式';
        $id = $this->request->get('id');
        $user = Yii::$app->user->identity;

//        $user = LoanPerson::find()->where(['id'=>$id])->select(['name','phone','username'])->limit(1)->one();
        $data = [
            'name' => $user->name,
            'phone' => isset($user->phone) ? $user->phone : $user->username,
        ];
        $infos = $this->_getOrderInfos($id);
        if (!$infos['repayment']) {
            throw new UserException("订单还未打款");
        }
        $view = 'repayment_alipay';
        if($this->isFromHBJB()){
            $view = 'repayment_alipay_hbqb';
        }
        $company_name = $this->getCompany('',1);
        return $this->render($view, [
            'data' => $data,
            'id' => $id,
            'order' => $infos['order'],
            'company_name'=>$company_name
        ]);
    }

    /**
     * 微信还款方式
     * @return string
     * @throws UserException
     */
    public function actionLoanRepaymentWeixin(){
        $this->view->title = '微信还款方式';
        $id = $this->request->get('id');
        $user = Yii::$app->user->identity;

        $data = [
            'name' => $user->name,
            'phone' => isset($user->phone) ? $user->phone : $user->username,
        ];
        $infos = $this->_getOrderInfos($id);
        if (!$infos['repayment']) {
            throw new UserException("订单还未打款");
        }
        $view = 'repayment_weixin';
//        if($this->isFromHBJB()){
//            $view = 'repayment_alipay_hbqb';
//        }
        $company_name = $this->getCompany('',1);
        return $this->render($view, [
            'data' => $data,
            'id' => $id,
            'order' => $infos['order'],
            'company_name'=>$company_name
        ]);
    }

    /**
     * 支付宝APP还款第二步
     */
    public function actionAliPayApply()
    {
        $this->response->format = 'json';
        $id = $this->request->post('id');
        $money = $this->request->post('operateMoney');
        $money = intval(bcmul($money,100));
        $infos = $this->_getOrderInfos($id);
        if (!$infos['repayment']) throw new UserException("订单还未打款");
        $datas = $this->getPartRepayMoney($infos['repayment']);
        if (!in_array($money,$datas)) {
            $money = null;
        }
        $loanService = Yii::$container->get('loanService');
        $res = $loanService->applyAliyPay($infos['order'], $infos['repayment'],$money);
        $status = $res['code'];
        if ($status == UserCreditMoneyLog::STATUS_ING) {
            $callBackAliyPayJs = isset($res['response']) ? $res['response'] :'';
            return array('code' => 1,'id'=>$id, 'msg' => '正在处理中', 'response' => $callBackAliyPayJs) ;
        } elseif ($status == UserCreditMoneyLog::STATUS_APPLY) {
            return ['code' => 0, 'id' => $id, 'msg' => $res['message']];
        } elseif ($status == UserCreditMoneyLog::STATUS_FAILED) {
            return ['code' => 0, 'id' => $id, 'msg' => '还款失败,请10分钟后,选择其他还款方式.'];
        }
        return ['code' => 0, 'id' => $id, 'msg' => '请勿频繁操作!'];
    }

    /**
     *
     * 汇潮支付宝APP还款第二步
     */

    public function actionHcAliPayApply()
    {
        $this->response->format = 'json';
        $id = $this->request->post('id');
        $money = $this->request->post('operateMoney');
        $money = intval(bcmul($money,100));
        $loanrepaymenttype = intval($this->request->post('loanrepaymenttype'));//借款展期
        $infos = $this->_getOrderInfos($id);
        if (!$infos['repayment']) throw new UserException("订单还未打款");
        $datas = $this->getPartRepayMoney($infos['repayment']);
        if (!in_array($money,$datas)) {
            if($loanrepaymenttype==1){
                //借款展期
                //如果是借款展期，应还金额是利息+砍头息
                $order = $infos['order'];
                $repayment=$infos['repayment'];
                if($order['is_extend_loan']==UserLoanOrder::IS_NOT_EXTEND_LOAN && $repayment['overdue_day']<=7){
                    $myorder=UserLoanOrder::findOne(['id'=>$order['id']]);
                    $myorder->is_extend_loan = UserLoanOrder::IS_EXTEND_LOAN;
                    $myorder->save();
                }
                //展期费计算
                $zhanqi_money=$order['counter_fee'];
                if($zhanqi_money<$order['money_amount']*ZHANQI_LOAN_LV){
                    $zhanqi_money=$order['money_amount']*ZHANQI_LOAN_LV;
                }
                $money=intval($repayment['interests']+$repayment['late_fee']+intval($zhanqi_money));
            }else{
                $money = null;
            }
        }
        if($loanrepaymenttype!=1){
            //如果不是展期借款
            $order = $infos['order'];
            if($order['is_extend_loan']==UserLoanOrder::IS_EXTEND_LOAN){
                $myorder=UserLoanOrder::findOne(['id'=>$order['id']]);
                $myorder->is_extend_loan = UserLoanOrder::IS_NOT_EXTEND_LOAN;
                $myorder->save();
            }
        }
        $loanService = Yii::$container->get('loanService');
        $res = $loanService->applyHcAliyPay($infos['order'], $infos['repayment'],$money);
        $status = $res['code'];
        if ($status == UserCreditMoneyLog::STATUS_ING) {
            $hcAliyPayUrl = isset($res['aliPayURL']) ? $res['aliPayURL'] :'';
            return array('code' => 1,'id'=>$id, 'msg' => '正在处理中', 'hcAliyPayUrl' => $hcAliyPayUrl) ;
        } elseif ($status == UserCreditMoneyLog::STATUS_APPLY) {
            return ['code' => 0, 'id' => $id, 'msg' => $res['message']];
        } elseif ($status == UserCreditMoneyLog::STATUS_FAILED) {
            return ['code' => 0, 'id' => $id, 'msg' => '还款失败,请10分钟后,选择其他还款方式.'];
        }
        return ['code' => 0, 'id' => $id, 'msg' => '请勿频繁操作!'];
    }

    public function actionHcTest(){
        $this->response->format = 'json';
        $params = [
            'merchantOutOrderNo' => \common\helpers\StringHelper::generateUniqid(),
            'merid' => 'yft2017082500005',
            'noncestr' => 'hc'.\common\helpers\StringHelper::generateUniqid(),
            'orderMoney' => '1.00',
            'orderTime' => date("YmdHis"),
            'notifyUrl' => 'http://jh.yizhibank.com/api/callback',
        ];
        $aliPayURL = 'http://jh.yizhibank.com/api/createOrder?';
        foreach ($params as $k => $v) { $aliPayURL .= $k.'='.$v.'&';}
        $sign = \common\models\Order::genHcSign($params,'gNociwieX1aCSkhvVemcXkaF9KVmkXm8');
        $aliPayURL .= 'sign='.$sign;
//        $aliPayURL = 'http://jh.yizhibank.com/trade?alipay://platformapi/startApp?appId=10000011&url='.urlencode($aliPayURL);
        return ['code' => 1,'id'=>15, 'msg' => '正在处理中', 'hcAliyPayUrl' => $aliPayURL];
    }

    /**
     * 支付宝还款结果查询
     * [zhangyuliang]支付宝APP还款第三步
     */
    public function actionAlipayResult()
    {
        $this->view->title = '还款结果';
        $id = $this->request->get('id');
        return $this->render('loan_state', [
            'msg' => '还款处理中',
            'is_success' => true,
            'id' => $id,
        ]);
    }

    public function actionGetCallBackRes()
    {
        $this->response->format = 'json';
        $id = $this->request->get('id');
        $user_id = Yii::$app->user->identity->id;
        try {
            $autoDebitLog = AutoDebitLog::find()->where(['user_id'=>$user_id,'order_id'=>$id])->orderBy(['id'=>SORT_DESC])->one();
            if ($autoDebitLog['status'] == AutoDebitLog::STATUS_FAILED) {
                return ['code'=>1,'status'=>UserCreditMoneyLog::STATUS_FAILED];
            } elseif($autoDebitLog['status'] == AutoDebitLog::STATUS_SUCCESS) {
                return ['code'=>1,'status'=>UserCreditMoneyLog::STATUS_SUCCESS,'mgs'=>'success'];
            } else {
                return ['code'=>1,'status'=>UserCreditMoneyLog::STATUS_ING];
            }
        } catch(Exception $ex) {
            return ['code'=>1,'status'=>UserCreditMoneyLog::STATUS_FAILED,'msg'=>$ex->getMessage()];
        }
    }

    /**
     * 快捷支付还款方式
     */
    public function actionLoanRepaymentQuick()
    {
        $this->view->title = '银行卡转账';
        $id = $this->request->get('id');
        $infos = $this->_getOrderInfos($id);
        $id = $this->request->get('id');
        if (!$infos['repayment']) {
            throw new UserException("订单还未打款");
        }
        return $this->render('repayment_quick', [
            'id' => $id,
            'order' => $infos['order'],
        ]);
    }

    /**
     * 支付确认验证码
     */
    public function actionConfirmCode($result_url = null)
    {
        $this->view->title = '支付短信验证码校验';
        $id = $this->request->get('id');             //$id   UserLoanOrder->id
        $card_id = $this->request->get('card_id', '0');  //$card_id  CardInfo->id
        $result_url = $this->request->get('result_url', '');

        $infos = $this->_getOrderInfos($id, $card_id);
        if (!$infos['repayment']) {
            throw new UserException("订单还未打款");
        }
        if (!$infos['card_info']) {
            throw new UserException("银行卡不存在");
        }
        $user_id = Yii::$app->user->identity->id;
        /*$loanService = Yii::$container->get('loanService');
        $params = ['user_ip' => Util::getUserIP(), 'type' => 10, 'remark' => 'card_id:' . $infos['card_info']['id']];*/
        //UserCreditMoneyLog::clearDebitStatus($user_id, $id);
        //$loanService->applyDebit($infos['order'], $infos['repayment'], $infos['card_info'], Yii::$app->user->identity, $params);
        //if ($status = UserCreditMoneyLog::getDebitStatus($user_id, $id) == UserCreditMoneyLog::STATUS_SUCCESS) {
        $UserService = Yii::$container->get('userService');
        if($UserService->generateAndSendCaptcha($infos['card_info']['phone'], 'payCharge')){
            return $this->render('confirm_code', [
                'money' => $infos['repayment']['remain_money_amount'],
                'bank_info' => $infos['order']['bank_info'],
                'phone' => $infos['card_info']['phone'],
                'loan_order_id'=> $id,
                'card_id'=>$card_id,
                'result_url' => $result_url ? $result_url : Url::toRoute(['loan/pay-result', 'id' => $id])
            ]);
        } else {
            $msg = '对不起，发送支付请求失败，请稍后重试！';
            if ($error = UserCreditMoneyLog::getDebitErrorMsg($user_id, $id)) {
                $msg .= '<br/>失败原因:<span style="color:#ff1237">' . $error . '</span>';
            }
            if ($result_url) {
                return $this->render('/wuba/error', [
                    'msg' => $msg,
                    'result_url' => $result_url,
                ]);
            }
            return $this->render('pay_apply', [
                'msg' => $msg,
                'id' => $id,
            ]);
        }
    }

    /**
     * 支付确认验证码
     */
    public function actionConfirmCharge()
    {
        $this->response->format = 'json';
        $code = trim($this->request->get('code'));
        $phone = trim($this->request->get('phone'));
        $card_id = trim($this->request->get('card_id'));
        $loan_order_id = intval($this->request->get('oid'));
        $data = ['code' => -1, 'message' => '验证码错误'];
        if (!$code) {
            return $data;
        }
        /*if (!YII_ENV_PROD) {
            $code = '0000';
        }*/

        //$ret = $loanService->confirmCharge($code, $log['order_uuid']);
        $type = 'payCharge';
        if (!(\common\helpers\ToolsUtil::checkMobile($phone))) {
            $data['message'] = '手机号码格式错误';
            return $data;
        }
        $userService = Yii::$container->get('userService');
        if($userService->validatePhoneCaptcha($phone,$code,$type)){

            $infos = $this->_getOrderInfos($loan_order_id, $card_id);
            if (!$infos['repayment']) {
                throw new UserException("订单还未打款");
            }
            if (!$infos['card_info']) {
                throw new UserException("银行卡不存在");
            }
            $user_id = Yii::$app->user->identity->id;
            $loanService = Yii::$container->get('loanService');
            $params = ['user_ip' => Util::getUserIP(), 'remark' => 'card_id:' . $infos['card_info']['id']];
            UserCreditMoneyLog::clearDebitStatus($user_id, $loan_order_id);
            $ret = $loanService->applyDebit($infos['order'], $infos['repayment'], $infos['card_info'], Yii::$app->user->identity, $params);
            if($ret){
                UserCreditMoneyLog::setDebitStatus($user_id, $loan_order_id, UserCreditMoneyLog::STATUS_SUCCESS);
                $data['message'] = '支付确认成功';
            }else{
                UserCreditMoneyLog::setDebitStatus($user_id, $loan_order_id, UserCreditMoneyLog::STATUS_FAILED);
                $data['message'] = '支付确认失败';
            }
        }else{
            return $data;
        }
        $data['code'] = 0;
        return $data;
    }

    /**
     * 申请还款
     * [clark]手动还款第二步
     */
    public function actionPayApply()
    {
        $this->view->title = '申请还款';
        $this->autoCheckPayPwdSign();
        $id = $this->request->get('id');
        $card_id = $this->request->get('card_id', '0');
        $infos = $this->_getOrderInfos($id, $card_id);
        if (!$infos['repayment']) {
            throw new UserException("订单还未打款");
        }
        if (!$infos['card_info']) {
            throw new UserException("银行卡信息错误");
        }

        $user_id = Yii::$app->user->identity->id;
        $loanService = Yii::$container->get('loanService');
        $loanService->applyDebit($infos['order'], $infos['repayment'], $infos['card_info']);
        $status = UserCreditMoneyLog::getDebitStatus($user_id, $id);
        if ($status == UserCreditMoneyLog::STATUS_ING) {
            return $this->render('pay_apply', [
                'id' => $id,
                'msg' => '',
            ]);
        } else {
            return $this->actionPayResult(); //[clark]手动还款第三步
        }
    }

    /**
     * 新申请还款
     * [wangcheng]手动还款第二步
     */
    public function actionPayApplyNew()
    {
        $this->response->format = 'json';
        try{
            $this->autoCheckPayPwdSign();
            $id = $this->request->post('id');
            $money = $this->request->post('money');
            $loanrepaymenttype = intval($this->request->post('loanrepaymenttype'));//借款展期
            $user_id = Yii::$app->user->identity->id;
            $card_id = $this->request->post('card_id', '0');
            $infos = $this->_getOrderInfos($id, $card_id);
            $money = StringHelper::safeConvertCentToInt($money);
            if (!$infos['repayment']) { return [ 'code' => -1, 'msg' => '订单异常，请联系客服']; }
            if (!$infos['card_info']) { return [ 'code' => -1,'msg' => '银行卡信息错误'];}
            if ($infos['repayment']['user_id'] != $user_id) { return [ 'code' => -1, 'msg' => '提交信息错误']; }
            $datas = $this->getPartRepayMoney($infos['repayment']);
            if (in_array($money,$datas) && $money <= $infos['repayment']['remain_money_amount']) {
                $extra['money'] = $money;
            } else {
                $extra = array();
                if($loanrepaymenttype==1){
                    //如果是借款展期，应还金额是利息+砍头息
                    $order = $infos['order'];
                    $repayment=$infos['repayment'];
                    if($order['is_extend_loan']==UserLoanOrder::IS_NOT_EXTEND_LOAN && $repayment['overdue_day']<=7){
                        $myorder=UserLoanOrder::findOne(['id'=>$order['id']]);
                        $myorder->is_extend_loan = UserLoanOrder::IS_EXTEND_LOAN;
                        $myorder->save();
                    }
                    //展期费计算
                    $zhanqi_money=$order['counter_fee'];
                    if($zhanqi_money<$order['money_amount']*ZHANQI_LOAN_LV){
                        $zhanqi_money=$order['money_amount']*ZHANQI_LOAN_LV;
                    }
                    $extra['money'] = intval($repayment['interests']+$repayment['late_fee']+intval($zhanqi_money));
                }
            }

            //如果不是展期借款
            if($loanrepaymenttype==0){
                $order = $infos['order'];
                if($order['is_extend_loan']==UserLoanOrder::IS_EXTEND_LOAN){
                    $myorder=UserLoanOrder::findOne(['id'=>$order['id']]);
                    $myorder->is_extend_loan = UserLoanOrder::IS_NOT_EXTEND_LOAN;
                    $myorder->save();
                }
            }

            $loanService = new LoanService();
            //设置还款还款方式为用户主动还款
            $extra['debit_type'] = AutoDebitLog::DEBIT_TYPE_ACTIVE;
            $ret = $loanService->applyDebitNew($infos['order'], $infos['repayment'], $infos['card_info'],$extra);
            return $ret;
        } catch (\Exception $e) {
            return [ 'code' => -1, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 新获取扣款状态借口
     */
    public function actionGetPayStatusNew()
    {
        $this->response->format = 'json';
        $user_id = Yii::$app->user->identity->id;
        $key = "user_money_log_status_for_{$user_id}";
        $ret = RedisQueue::get(['key'=>$key]);
        if(!$ret){
            return [
                'code' => -2,
                'msg' => '代扣进行中,请稍后查看详情'
            ];
        }
        $ret = json_decode($ret,1);
        if($ret['code'] == 0){
            return [
                'code' => 0,
                'msg' => '恭喜您，本次还款成功'
            ];
        }else{
            $msg = '支付失败，请更换银行卡再试或支付宝还款';

            if(isset($ret['err_code']) && isset(UserCreditMoneyLog::$error_code[$ret['err_code']])){
                $msg = UserCreditMoneyLog::$error_code[$ret['err_code']];
            }
            return [
                'code' => -1,
                'msg' => $msg
            ];
        }
    }
    /**
     * 查询还款状态
     */
    public function actionPayStatus()
    {
        $this->response->format = 'json';
        $user_id = Yii::$app->user->identity->id;
        $id = $this->request->get('id');
        $status = UserCreditMoneyLog::getDebitStatus($user_id, $id);
        $msg = '';
        if ($status == UserCreditMoneyLog::STATUS_FAILED && $error = UserCreditMoneyLog::getDebitErrorMsg($user_id, $id)) {
            $msg = '<br/>失败原因:<span style="color:red">' . $error . '</span>';
        }
        return ['status' => $status, 'ing' => UserCreditMoneyLog::STATUS_ING, 'max_times' => 60, 'msg' => $msg];
    }

    /**
     * 还款结果
     * [clark]手动还款第三步
     */
    public function actionPayResult()
    {
        $this->view->title = '还款结果';
        $id = $this->request->get('id');
        $user_id = Yii::$app->user->identity->id;
        $status = UserCreditMoneyLog::getDebitStatus($user_id, $id);
        $msg = '';
        $flag = false;
        //if ($status == UserCreditMoneyLog::STATUS_SUCCESS || UserCreditMoneyLog::STATUS_APPLY) {
        if (in_array($status,array(UserCreditMoneyLog::STATUS_SUCCESS,UserCreditMoneyLog::STATUS_APPLY))) {
            $msg = '还款申请已提交，请稍后查看';
            $flag = true;
        } else if ($status == UserCreditMoneyLog::STATUS_FAILED) {
            $msg = '遗憾还款失败，请尝试其他还款方式吧!';
            if ($error = UserCreditMoneyLog::getDebitErrorMsg($user_id, $id)) {
                //UserCreditMoneyLog::setDebitStatus($user_id, $id,UserCreditMoneyLog::STATUS_FAILED);
                $msg = $error;
            } else {
                $userCreditMoneyLog = UserCreditMoneyLog::find()->where(['user_id'=>$user_id,'order_id'=>$id,'status'=>UserCreditMoneyLog::STATUS_FAILED])->orderBy('id desc')->asArray()->limit(1)->one();
                if ($userCreditMoneyLog['remark']) {
                    $msg = '遗憾还款失败，请尝试其他还款方式吧!';
                }
                UserCreditMoneyLog::setDebitErrorMsg($user_id,$id,$msg);
            }

        } else if ($status == -4) {
            $msg = '银行卡不支持代扣';
        } else if ($status == -3) {
            $msg = '很抱歉，您的银行卡今日支付次数已达上限，请明日换卡或更换还款方式！';
        } else {
            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $id])->select('status')->asArray()->limit(1)->one();
            if ($repayment && $repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $msg = '恭喜还款成功，点滴信用，弥足珍贵';
                $flag = true;
            } else {
                $error = '还款正在进行中，请勿重复还款';
                $msg = '<br/>失败原因:<span style="color:red">' . $error . '</span>';
            }
        }
        return $this->render('pay_apply', [
            'msg' => $msg,
            'is_success' => $flag,
            'id' => $id,
        ]);
    }

    /**
     * 银行卡列表
     */
    public function actionCardList($source = null, $source_id = null)
    {
        return $this->actionCardDetail($source, $source_id);
    }

    public function actionCardDetail($source = null, $source_id = null)
    {
        $this->view->title = '已绑银行卡';
        $user_id = Yii::$app->user->identity->id;
        $cards = Yii::$container->get('userService')->getCardInfo($user_id, 1);
        if ($cards) {
            return $this->render('card_detail', [
                'source' => $source,
                'source_id' => $source_id,
                'card_info' => $cards[0],
                'can_rebind' => CardInfo::checkCanRebind($user_id),
            ]);
        } else {
            return $this->actionBindCard();
        }
    }

    /**
     * 绑定银行卡
     * @param string $source 来源 null表示无 xjk-shandai表示极速钱包-闪贷
     * @param string $source_id 来源ID
     */
    public function actionBindCard($source = null, $source_id = null)
    {
        if ($source === 'xjk-shandai') {
            $this->view->title = '绑定银行卡';
        } else if ($source === 'add-card') {
            $this->view->title = '还款添加银行卡';
        } else {
            $this->view->title = '重新绑定银行卡';
        }
        $user_id = Yii::$app->user->identity->id;
        $name = Yii::$app->user->identity->name;
        $source_type = $this->getSource();
        return $this->render('card_bind', [
            'name' => $name,
            'card_list' => CardInfo::getCardConfigList(),
            'source' => $source,
            'source_id' => $source_id,
            'source_type' => $source_type,
        ]);
    }

    /**
     * 绑卡提交数据
     */
    public function actionDoBindCard() {
        $this->response->format = 'json';

        /* @var $cur_user LoanPerson */
        /* @var $cardService \common\services\CardService */
        /* @var $user_service \common\services\UserService */
        $cardService = Yii::$container->get('cardService');
        $user_service = Yii::$container->get('userService');
        $cur_user = Yii::$app->user->identity;

        $ret = ['code' => -1, 'message' => '绑卡失败']; #default resp

        $params = $this->request->post();
//        $source = $this->getSource();
        $source=LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        $id_number = $cur_user->id_number;
        $name = $cur_user->name;
        $phone = $params['phone'];
        $card_no = $params['card_no'];
        $bank_id = intval($params['bank_id']);
        if (empty($bank_id)) {
            $ret['message'] = '请选择银行名称';
            return $ret;
        }
        if (empty($card_no)) {
            $ret['message'] = '请填写银行卡号';
            return $ret;
        }

        //绑卡次数限制
        $key = \sprintf('bind_card_%s_%s', $cur_user->id, date('Ymd'));
        $key_count = RedisQueue::get(['key'=>$key]);
        if ($key_count > 3 && $phone!='18973133550') {
            $ret = ['code' => -1, 'message' => '已超过今日绑卡最大次数'];
            return $ret;
        } else {
            RedisQueue::set(['expire' => 86400, 'key' => $key, 'value'=> ++ $key_count]);
        }
//        $card_info = KoudaiSoa::instance('BankCard')->cardBin(trim($params['card_no']));
        $card_info = JshbService::cardBin(trim($params['card_no']),$bank_id);
        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            $ret['message'] = '请检查银行卡号是否正确';
            return $ret;
        }

        if (isset($card_info['data']['card_type']) && $card_info['data']['card_type'] != 1) {
            $ret['message'] = '卡片类型错误';
            \yii::warning( sprintf('uid_bind_loan_card %s, %s, %s',
                $cur_user->id, $params['card_no'], json_encode($card_info)
            ), LogChannel::USER_CARD );
            return $ret;
        }

        //检查当前银行卡以及预留手机号是否绑卡成功
        $BankCardInfo=CardInfo::findOne(['card_no'=>$card_no,'phone'=>$phone,'status'=>1]);
        if($BankCardInfo){
            $ret['message'] = '对不起，该银行卡已被绑定过';
            return $ret;
        }

        //判断绑卡鉴权验证码谁来发发送
        $user_id=$cur_user->id;
        $key="band_card_channel_{$user_id}";
        $channel=RedisQueue::get(['key'=>$key]);
        $channel_id='';
        if(!empty($channel)){
            $channel=json_decode($channel,true);
            $channel_id=$channel[0];
        }
        if(empty($channel_id) || $channel_id==''){
            return UserExceptionExt::throwCodeAndMsgExt('请先获取手机短信验证码');
        }

        $code = trim($this->request->post('code'));
        //验证银行卡
//        $card_info = KoudaiSoa::instance('BankCard')->cardVerify($card_no, $phone, $id_number, $name, ['client_ip' => Util::getUserIP()]);
//        $card_info = JshbService::cardVerify($card_no, $phone, $id_number, $name ,$bank_id);
        $card_info = JshbService::cardQuickVerify($card_no, $phone, $id_number, $name ,$bank_id ,$channel_id ,$code);
        Yii::error(json_encode($card_info));
        if (false == $card_info || (!isset($card_info['code']))) {
            \yii::warning( sprintf('%s bindcard_service_failed_833 %s, %s',
                $cur_user->id, json_encode($params), json_encode($card_info)
            ), LogChannel::USER_CARD );
            $ret['message'] = '银行卡检查服务异常，请稍后重试。';
            $ret['data'] = $card_info;
            return $ret;
        }
        else if (0 != $card_info['code']) {
            \yii::warning( sprintf('%s bindcard_service_failed_839 %s, %s',
                $cur_user->id, json_encode($params), json_encode($card_info)
            ), LogChannel::USER_CARD );
            $ret['message'] = (isset($card_info['message']) ? $card_info['message'] : '请检查填写信息是否正确');
            $ret['data'] = $card_info;
            return $ret;
        }

        try {
            if (CardInfo::checkCanRebind($cur_user->id)) {
                if ($main_card = $user_service->getMainCardInfo($cur_user->id)) {
                    $params['rebind'] = 1;
                }
                //已经进行过银行卡四要素鉴权，后面不需要在进行鉴权
                $params['skipSoaValidate'] = 1;
                $params['skipValidateCaptcha'] = 1;
                $params['channel_id']=$channel_id;

                //如果是之前自己绑过的卡 进行切换
                if (($card_record = CardService::getOldCardRecord(trim($params['card_no']), $cur_user->id,$source))
                    && $main_card && $card_record->id != $main_card->id) {
                    $ret = $cardService->switchCard($cur_user, $main_card, $card_record, $params, isset($params['phone']) ? $params['phone'] : null, $source);
                }
                else {
                    $params['source_id'] = $source;
                    $ret = $cardService->bindCard($cur_user, $params);
                }
                //删除绑卡redis中缓存
                $key="band_card_channel_{$user_id}";
                RedisQueue::del(['key'=>$key]);
            }
            else {
                $ret['message'] = '对不起，您有未完成的订单，暂时不能重新绑卡！';
            }
        }
        catch (\Exception $e) {
            \yii::warning( sprintf('%s bindcard_exception_861 %s, %s', $cur_user->id, json_encode($params), $e), LogChannel::USER_CARD );
            $ret['message'] = $e->getMessage();
        }

        if ($card_info) {
            $ret['data'] = $card_info;
        }

        return $ret;
    }

    /**
     * 添加银行卡提交数据
     */
    public function actionDoAddCard() {
        $this->response->format = 'json';
        $ret = ['code' => -1, 'message' => '添加银行卡失败'];

        $cardService = Yii::$container->get('cardService');
        $cur_user = Yii::$app->user->identity;
        $params = $this->request->post();
        $params['source_id'] = $this->getSource();
        try {
            if (!YII_ENV_PROD) {
                $params['skipSoaValidate'] = 1;
            }
            $ret = $cardService->bindAssistCard($cur_user, $params);
        }
        catch (\Exception $e) {
            $ret['message'] = $e->getMessage();
        }

        return $ret;
    }

    /**
     *
     * @name    获取验证码 [cardInfoGetCode]
     * @uses    用户绑定银行卡拉取验证码
     * @method  post
     * @param   string $phone 手机号
     * @author  cheyanbing
     */
    public function actionGetCode() {
        $currentUser = Yii::$app->user->identity;
        $user_id = $currentUser->getId();
        $name = $currentUser->name;
        $id_number = $currentUser->id_number;

        $this->response->format = 'json';
        $ret = ['code' => -1, 'message' => '绑卡失败']; #default resp

        try {
            $params = $this->request->post();
            $phone = $params['phone'];
            $card_no = $params['card_no'];
            $bank_id = intval($params['bank_id']);
            if (empty($bank_id)) {
                $ret['message'] = '请选择银行名称';
                return $ret;
            }
            if (empty($card_no)) {
                $ret['message'] = '请填写银行卡号';
                return $ret;
            }

            $service = Yii::$container->get('JshbService');
//            /* @var $service \common\services\UserService */
//            $service = Yii::$container->get('userService');//原先副卡绑定
            if (!Util::verifyPhone($phone)) {
                return UserExceptionExt::throwCodeAndMsgExt('手机号有误');
            }

            $channel_id = CardInfo::HELIPAY;
            $data = [
                // 业务参数
                'name'         => (string)$name,
                'phone'        => (string)$phone,
                'id_card_no'   => (string)$id_number,
                'bank_card_no' => (string)$card_no,
                'bank_id'      => (string)$bank_id,
                'channel_id'   => (string)$channel_id
            ];
            $sms_result=$service->payAuthSmsCode($data);
            if(!$sms_result || $sms_result['code'] == '0'){
                //redis数量加1
                $key="band_card_channel_{$user_id}";
                //过期时间
                $expire=strtotime(date("Ymd")) + 3600*24 - time();
                RedisQueue::set(['expire'=>$expire,'key'=>$key,'value'=>json_encode([$channel_id,1])]);
                $is_send_sms=true;
            }else{
                $message='抱歉，获取短信验证码失败';
                if(isset($sms_result['message'])){
                    $message=$sms_result['message'];
                }
                return [
                    'code' => -1,
                    'message' => $message
                ];
            }

            if ($is_send_sms) {
                $bank_list = CardInfo::getCardConfigList();
                $sms_tips=HELIPAYTIPS;
                return [
                    'code' => 0,
                    'message' => '成功获取验证码',
                    'data' => ['item' => []],
                    'bank_list' => $bank_list,
                    'sms_tips'  => $sms_tips
                ];
            }
            else {
                return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
            }
            //原先副卡绑定
//            $source = $this->getSource();
//            if ($service->generateAndSendCaptcha($phone, UserCaptcha::TYPE_BIND_BANK_CARD, false, $source)) {
//                return [
//                    'code' => 0,
//                    'message' => '成功获取验证码',
//                    'data' => [],
//                ];
//            }
//            else {
//                return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
//            }
        }
        catch (\Exception $e) {
            ErrorMessage::getMessage(0, '手机号：'.$phone.'，用户绑定银行卡拉取验证码异常：'.$e->getMessage(), ErrorMessage::SOURCE_BINDCARDCODE);
//            \yii::warning(
//                sprintf('%s bindcard_getcode_failed_839 %s, %s', Yii::$app->user->identity->id, json_encode($_REQUEST), $e),
//                LogChannel::USER_CARD
//            );
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 借款续期
     */
    public function actionLoanDelay()
    {
//        $this->view->title = '申请续期';
//        $id = $this->request->get('id');
//        $user_id = Yii::$app->user->identity->id;
//        $infos = $this->_getOrderInfos($id);
//        if (!$infos['repayment']) {
//            throw new UserException("订单还未打款");
//        }
//        $repayment = $infos['repayment'];
//        $order = $infos['order'];
//        $fees = [];
//        $total_moneys = [];
////$quota = UserCreditTotal::findOne(['user_id'=>$user_id]);
//        $creditChannelService = \Yii::$app->creditChannelService;
//        $quota = $creditChannelService->getCreditTotalByUserId($user_id);
//        if (!$quota) {
//            throw new UserException("数据非法");
//        }
//        $delay_info = UserLoanOrderDelay::findOne(['order_id' => $id]);
//        if ($delay_info && $delay_info['delay_times'] >= 1) {
//            $order_repayment_day = strtotime(date("Y-m-d 00:00:00", $repayment["plan_repayment_time"]));
//            if (time() < $order_repayment_day) {
//                // throw new UserException("还有未到期的续期");
//                return $this->render('pay_result', [
//                    'msg' => "您还有未到期的续期",
//                ]);
//            }
//        }
//        $service_fee = UserLoanOrderDelay::getServiceFee($repayment['remain_principal'], $delay_info ? $delay_info['delay_times'] : 0, $order['card_type']);
//        // foreach (UserLoanOrderDelay::$delay_days as $idx => $day) {
//        foreach (UserLoanOrderDelay::getDalayDays() as $idx => $day) {
//            $service_fee = UserLoanOrderDelay::getServiceFee($repayment['remain_principal'], $delay_info ? $delay_info['delay_times'] : 0, $order['card_type'], $day);
//            $fee = Util::calcLqbLoanInfo($day, $repayment['remain_principal'], $quota->pocket_apr, $order['card_type']);
//            $total_moneys[$idx] = sprintf("%0.2f", ($service_fee + $fee + $repayment['late_fee']) / 100);
//            $fees[$idx] = sprintf("%0.2f", $fee / 100);
//        }
//        return $this->render('loan_delay', [
//            'repayment' => $repayment,
//            'fees' => $fees,
//            'total_moneys' => $total_moneys,
//            'service_fee' => $service_fee,
//            'type' => $this->request->get('type', ''),
//        ]);
    }

    public function actionDelayHelp()
    {
//        $this->view->title = $this->t('app_name') . '-纯信用小额借钱极速放贷';
//        return $this->render('delay_help', [
//        ]);
    }

    /**
     * 申请续期
     */
    public function actionDelayApply()
    {
//        $this->view->title = '申请续期';
//        $type = $this->request->get('type', '');
//        $id = $this->request->get('id');
//        if ($type != UserCreditMoneyLog::TYPE_PLAY) {
//            $type = UserCreditMoneyLog::TYPE_DEBIT;
//            $this->autoCheckPayPwdSign();
//        } else {
//            UserCreditMoneyLog::clearDebitStatus(Yii::$app->user->identity->id, $id);
//        }
//
//        $infos = $this->_getOrderInfos($id);
//        if (!$infos['repayment']) {
//            throw new UserException("订单还未打款");
//        }
//        if ($infos['repayment']['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
//            throw new UserException("该订单已还款");
//        }
//        if ($infos['repayment']['is_overdue']) {
//            throw new UserException("逾期中的订单不能续期");
//        }
//        if (!$infos['card_info']) {
//            throw new UserException("银行卡信息错误");
//        }
//        $remainTimes = UserLoanOrderDelay::getRemainDelayTimes($id);
//        if ($remainTimes <= 0) {
//            throw new UserException("续期次数已用完");
//        }
//
//
//        $post = $this->request->post();
//        if (!isset($post['day'])) {
//            throw new UserException("数据非法");
//        }
//        $day = intval($post['day']);
//        $days = UserLoanOrderDelay::getDalayDays();
//        if (!isset($days[$day])) {
//            throw new UserException("续期天数无效");
//        }
//        $user_id = Yii::$app->user->identity->id;
////$quota = UserCreditTotal::findOne(['user_id'=>$user_id]);
//        $creditChannelService = \Yii::$app->creditChannelService;
//        $quota = $creditChannelService->getCreditTotalByUserId($user_id);
//
//        if (!$quota) {
//            throw new UserException("数据非法");
//        }
//        $repayment = $infos['repayment'];
//        $order = $infos['order'];
//        $day = $days[$day];
//
//        $fee = Util::calcLqbLoanInfo($day, $repayment['remain_principal'], $quota->pocket_apr, $order['card_type']);
//        $delay_info = UserLoanOrderDelay::findOne(['order_id' => $id]);
//        // 处理在本次续期未到期期限内不能再次申请续期
//        if ($delay_info && $delay_info['delay_times'] >= 1) {
//            $order_repayment_day = strtotime(date("Y-m-d 00:00:00", $repayment["plan_repayment_time"]));
//            if (time() < $order_repayment_day) {
//                return $this->render('pay_result', [
//                    'msg' => "您还有未到期的续期",
//                ]);
//            }
//        }
//        $service_fee = UserLoanOrderDelay::getServiceFee($repayment['remain_principal'], $delay_info ? $delay_info['delay_times'] : 0, $order['card_type'], $day);
//        $total_money = $service_fee + $fee + $repayment['late_fee'];
//
//        $p_late_fee = $post['late_fee'] * 100;
//        $p_service_fee = $post['service_fee'] * 100;
//        $p_counter_fee = $post['counter_fee'] * 100;
//        $p_total_money = $post['total_money'] * 100;
//        $p_principal = $post['principal'] * 100;
//        if ($p_late_fee != $repayment['late_fee'] || $fee != $p_counter_fee || $p_service_fee != $service_fee ||
//            $total_money != $p_total_money || $repayment['remain_principal'] != $p_principal
//        ) {
////throw new UserException("数据校验错误");
//        }
//        $log = new UserLoanOrderDelayLog();
//        $log->user_id = $user_id;
//        $log->order_id = $id;
//        $log->service_fee = $service_fee;
//        $log->counter_fee = $fee;
//        $log->late_fee = $repayment['late_fee'];
//        $log->delay_day = $day;
//        $log->principal = $repayment['remain_principal'];
//        $repayment['remain_money_amount'] = $total_money; //实际扣款
//        if ($log->save()) {
//            $params = ['user_ip' => Util::getUserIP(), 'delay_debit' => 1, 'remark' => $log->id, 'type' => $type];
//            $loanService = Yii::$container->get('loanService');
//            $ret = $loanService->applyDebit($infos['order'], $repayment, $infos['card_info'], Yii::$app->user->identity, $params);
//            if ($ret && $type == UserCreditMoneyLog::TYPE_PLAY) {
//                return $this->render('confirm_code', [
//                    'money' => $total_money,
//                    'bank_info' => $infos['order']['bank_info'],
//                    'phone' => Yii::$app->user->identity->phone,
//                    'id' => $ret->id,
//                    'result_url' => Url::toRoute(['loan/delay-result', 'id' => $id])
//                ]);
//            }
//            $status = UserCreditMoneyLog::getDebitStatus($user_id, $id);
//            if ($status == UserCreditMoneyLog::STATUS_ING) {
//                return $this->render('delay_apply', [
//                    'id' => $id,
//                    'msg' => '',
//                ]);
//            }
//        }
//        return $this->actionDelayResult();
    }

    /**
     * 查询续期状态
     */
    public function actionDelayStatus()
    {
//        $this->response->format = 'json';
//        $user_id = Yii::$app->user->identity->id;
//        $id = $this->request->get('id');
//        $status = UserCreditMoneyLog::getDebitStatus($user_id, $id);
//        $msg = '';
//        if ($status == UserCreditMoneyLog::STATUS_SUCCESS) {
//            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $id])->select('plan_fee_time')->asArray()->limit(1)->one();
//            $repayment_date = date('Y-m-d', $repayment['plan_fee_time']);
//            $msg = '申请续期成功，还款日续期至' . $repayment_date . '。届时请及时还款！';
//        }
//        return ['status' => $status, 'ing' => UserCreditMoneyLog::STATUS_ING, 'max_times' => 60, 'msg' => $msg];
    }

    /**
     * 续期结果
     */
    public function actionDelayResult()
    {
//        $this->view->title = '续期结果';
//        $id = $this->request->get('id');
//        $user_id = Yii::$app->user->identity->id;
//        $status = UserCreditMoneyLog::getDebitStatus($user_id, $id);
//        $msg = '抱歉，您的续期申请失败，请确保支付银行卡金额充足；或者使用支付宝支付续期费用，具体请到我的->设置->帮助中心查看。';
//        $img = $this->staticUrl('credit/img/img-3.png');
//        if ($status == UserCreditMoneyLog::STATUS_SUCCESS) {
//            $repayment = UserLoanOrderRepayment::find()->where(['order_id' => $id])->select('plan_fee_time')->asArray()->limit(1)->one();
//            $repayment_date = date('Y-m-d', $repayment['plan_fee_time']);
//            $msg = '申请续期成功，还款日续期至' . $repayment_date . '。届时请及时还款！';
//            $img = $this->staticUrl('credit/img/img-1.png');
//        } else if ($status == UserCreditMoneyLog::STATUS_ING) {
//            $msg = '处理中，请稍等!';
//        } else if ($error = UserCreditMoneyLog::getDebitErrorMsg($user_id, $id)) {
//            $msg .= '<br/>失败原因:<span style="color:red">' . $error . '</span>';
//        }
//        return $this->render('delay_apply', [
//            'msg' => $msg,
//            'id' => $id,
//            'img' => $img,
//        ]);
    }

    /**
     * 记录资方签约查看历史
     * @param string $key 查看页面的KEY
     * @param integer $parse_order_id 解析的订单ID
     * @param integer $parse_fund_id 解析的资方类型
     * @param type $parse_time
     */
    protected function logFundSignView($key, $parse_order_id, $parse_fund_id, $parse_time)
    {
        $ip = '';
        try {
            $record = new FundSignViewLog();
            $record->ip = \common\helpers\ToolsUtil::getIp();
            $record->url_key = $key;
            $record->parse_order_id = (int)$parse_order_id;
            $record->parse_fund_id = (int)$parse_fund_id;
            $record->parse_time = (int)$parse_time;
            $record->created_at = time();
            $record->save(false);
        } catch (\Exception $ex) {
            Yii::error("添加资方签约打开页面记录日志失败：{$ex->getFile()}第{$ex->getLine()}行错误：{$ex->getMessage()}, IP:{$ip} KEY:{$key} parse_order_id:{$parse_order_id} parse_fund_id:{$parse_fund_id} parse_time{$parse_time} ", 'kdkj.fund.sign');
        }
    }

    /**
     * 资方签约 由用户在短信打开H5的链接 直接打开页面
     */
    public function actionFundSign($key = '')
    {
        $parse_order_id = $parse_fund_id = $parse_time = 0;
        try {
            $fund_service = Yii::$container->get('fundService');
            /* @var $fund_service FundService */
            $params = $fund_service->parseSignParams($key);
            if (!$params) {
                throw new \Exception('解析参数失败');
            }

            if (empty($params['order_id']) || empty($params['fund_id']) || empty($params['time'])) {
                throw new \Exception('参数不正确');
            }

            $order_id = $parse_order_id = (int)$params['order_id'];
            $parse_fund_id = (int)$params['fund_id'];
            $parse_time = (int)$params['time'];
            $order = UserLoanOrder::findOne($order_id);
            if (!$order) {
                throw new \Exception('找不到订单');
            }
            $order_user = $order->loanPerson;

            if (!$order->cardInfo) {
                throw new \Exception('找不到订单绑定的银行卡');
            }
        } catch (\Exception $ex) {
            $this->logFundSignView($key, $parse_order_id, $parse_fund_id, $parse_time);
            return $this->render('/common/error', [
                'msg' => $ex->getMessage() . '<br/><a href="http://t.cn/RidsgbY">打开'.APP_NAMES.'查看</a>'
            ]);
        }

        $this->logFundSignView($key, $parse_order_id, $parse_fund_id, $parse_time);

        if ($order->loanFund && $order->loanFund->requirePreSign() && !($sign_record = LoanFundSignUser::getSignedRecord($order->user_id, $order->fund_id, $order->cardInfo->card_no))) {//有资金方 并要求签约 并且未签约

            //解决 用户在51签约 平台没有记录 问题
            $service = $order->loanFund->getService();
            $ret = $service->preSign($order);

            if ($ret['code'] == 0 && $ret['data']['sign'] === LoanFundSignUser::STATUS_SIGN_ACTIVE) {
                //改变订单的状态
                $order->loanFund->getService()->afterPreSignSuccess($order, $order->user_id, '用户打开签约页面时，签约记录已经存在。确认银行卡成功，修改订单为待放款');
                return $this->redirect(['fund-sign-status', 'key' => $key]);
            } else if ($ret['code'] == 0) {
                $seri_no = $ret['data']['serialNo'];
            } else {
                $record = LoanFundSignUser::findOne([
                    'user_id' => (int)$order->user_id,
                    'fund_id' => (int)$order->fund_id,
                    'card_no' => trim($order->cardInfo->card_no),
                ]);
                $seri_no = null;
                if ($record && ($record_data = $record->getData())) {
                    $seri_no = isset($record_data['serialNo']) ? $record_data['serialNo'] : null;
                }
            }

            return $this->render('fund_sign', [
                'order' => $order,
                'cardInfo' => $order->cardInfo,
                'key' => $key,
                'seri_no' => $seri_no,
                'result_url' => Url::to(['fund-sign-status', 'key' => $key]),
            ]);
        } else {//跳转到流程页面
            if ($order->status == UserLoanOrder::STATUS_FUND_CONTRACT) {
                $order_fund_service = $order->loanFund->getService();
                if (!empty($sign_record) && ($sign_record->status == LoanFundSignUser::STATUS_SIGN)) {
                    $ret = $order_fund_service->preSign($order);
                    if ($ret['code'] !== 0) {
                        return $this->render('/common/error', [
                            'msg' => '修改订单状态失败<br/><a href="' . Url::current() . '">点击重试</a>'
                        ]);
                    }
                }

                $ret = $order_fund_service->afterPreSignSuccess($order, $order->user_id, '用户访问签约页面，已经有签约记录，修改订单为待放款');
                if ($ret['code'] !== 0) {
                    return $this->render('/common/error', [
                        'msg' => $ret['message'] . '<br/><a href="' . Url::current() . '">点击重试</a>'
                    ]);
                }
            }
            return $this->redirect(['fund-sign-status', 'key' => $key]);
        }
    }

    /**
     * 资方合约状态 不需登录 只需要传入key即可查看 为避免风险不使用 LoanDetail方法 不显示详细的资料
     * @param string $key
     */
    public function actionFundSignStatus($key)
    {
        try {

            $fund_service = Yii::$container->get('fundService');
            /* @var $fund_service FundService */
            $params = $fund_service->parseSignParams($key);
            if (!$params) {
                throw new \Exception('解析参数失败');
            }

            if (empty($params['order_id']) || empty($params['fund_id']) || empty($params['time'])) {
                throw new \Exception('参数不正确');
            }

            $order_id = (int)$params['order_id'];
            $time = (int)$params['time'];

            $infos = UserLoanOrder::getOrderRepaymentCard($order_id);
            $order = $infos['order'];
            $repayment = $infos['repayment'];
            if (!$order) {
                throw new \Exception('找不到订单');
            }

            $list = [];

            if ($repayment) {//已打款
                if ($repayment['status'] == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {//已还款
                    $list[] = [
                        'title' => '已还款 ',
                        'body' => '恭喜还款成功，又积攒了一份信用',
                        'class' => 'do',
                    ];
                } else if ($repayment['is_overdue'] && $repayment['overdue_day']) {
                    $list[] = [
                        'title' => '已逾期' . $repayment['overdue_day'] . '天，逾期费用' . sprintf("%0.2f", $repayment['late_fee'] / 100),
                        'body' => '逾期还款将影响个人信用，今后将影响个人社会生活',
                        'class' => 'do',
                    ];
                } else {
                    $diffDay = TimeHelper::DiffDays(date('Y-m-d', $repayment['plan_fee_time']), date('Y-m-d'));
                    $list[] = [
                        'title' => $diffDay > 0 ? $diffDay . '天后还款 ' : '待还款',
                        'body' => '请于' . date('Y-m-d', $repayment['plan_fee_time']) . '日前将还款金额存入银行卡中',
                        'class' => 'do',
                    ];
                }
                $list[] = [
                    'title' => '打款成功 ' . date('Y-m-d H:i', $order['loan_time']),
                    'body' => '打款至' . (isset($order['bank_info']) && $order['bank_info'] ? $order['bank_info'] : '用户绑定的银行卡'),
                    'class' => '',
                ];

                $list[] = [
                    'title' => '审核通过 ' . ($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : ''),
                    'body' => '恭喜通过风控审核',
                    'class' => '',
                ];
            } else {
                //未打款
                if ($order['status'] >= UserLoanOrder::STATUS_PAY && (!in_array($order['status'], UserLoanOrder::$checkStatus) || $order['status'] == UserLoanOrder::STATUS_PENDING_LOAN)) {//打款中
                    if ($order['status'] == UserLoanOrder::STATUS_FUND_CONTRACT) {
                        $fund_service = Yii::$container->get('fundService');
                        /* @var $fund_service FundService */
                        $order_model = UserLoanOrder::instantiate($order);
                        UserLoanOrder::populateRecord($order_model, $order);
                        /* @var $order_model UserLoanOrder */
                        if (!($url = $fund_service->getSignUrl($order_model))) {
                            throw new \Exception("获取订单 {$order_model->id} 签约URL失败");
                        }
                        $list[] = [
                            'title' => sprintf("银行卡待确认 <a href='%s' title='确认银行卡'>确认银行卡</a>", $url),
                            'body' => '请在1小时内确认银行卡信息，超过系统将自动确认',
                            'class' => 'do',
                        ];
                    } else {
                        $list[] = [
                            'title' => '打款中 ' . ($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : ''),
                            'body' => '已进入打款状态，请您耐心等待',
                            'class' => 'do',
                        ];
                    }

                    $list[] = [
                        'title' => '审核通过 ' . ($order['trail_time'] ? date('Y-m-d H:i', $order['trail_time']) : ''),
                        'body' => '恭喜通过风控审核',
                        'class' => '',
                    ];
                } else if ($order['status'] < UserLoanOrder::STATUS_CHECK) {//审核未通过
                    $body = Yii::$container->get('userService')->getCanNotLoanMsgTip($order['user_id']);

                    $list[] = [
                        'title' => '审核未通过',
                        'body' => $body ? $body : '很遗憾，您的信用评分不足，该次借款未能通过。',
                        'class' => 'die',
                    ];
                } else {//审核中
                    $list[] = [
                        'title' => '审核中',
                        'body' => '已进入风控审核状态，请您耐心等待',
                        'class' => 'do',
                    ];
                }
            }
            $list[] = [
                'title' => '申请提交成功 ' . date('Y-m-d H:i', $order['order_time']),
                'body' => '申请借款' . sprintf("%0.2f", $order['money_amount'] / 100) . '元，期限' . $order['loan_term'] . '天，手续费' . sprintf("%0.2f", $order['counter_fee'] / 100) . '元',
                'class' => '',
            ];
        } catch (\Exception $ex) {
            return $this->render('/common/error', [
                'msg' => $ex->getMessage() . '<br/><a href="http://t.cn/RidsgbY">打开'.APP_NAMES.'查看</a>'
            ]);
        }

        return $this->render('contract_order_status', [
            'order' => $order,
            'list' => $list,
            'key' => $key,
        ]);
    }


    /**
     * 成功申请页
     * @return string
     */
    public function actionApplicationSuccess()
    {

        $view = [];
        $user_id = Yii::$app->user->getId();

        $order_id = Yii::$app->request->get('order_id', null);

        if(!is_null($order_id))
            $view['order_id'] = $order_id;
        /*//新客老客的区分
        $user_type = LoanPerson::find()->where(['id'=>$user_id])->select(['customer_type'])->asArray()->one();
        $loan_oreder = UserLoanOrder::find()->where(['id'=>$order_id])->select(['is_first'])->asArray()->one();
        if(($user_type['customer_type'] == LoanPerson::CUSTOMER_TYPE_OLD && $loan_oreder['is_first'] == 0) || $user_type['customer_type'] == LoanPerson::CUSTOMER_TYPE_OLD){
            $view['old']['老客'] = LoanPerson::CUSTOMER_TYPE_OLD;
        }else{
            $view['new']['新客'] = LoanPerson::CUSTOMER_TYPE_NEW;
        }*/
        return $this->render('application_success', $view);
    }


    /**
     * 还款成功页
     * @return string
     */
    public function actionRepaymentSuccess()
    {

        $view = [];
        $user_id = Yii::$app->user->getId();

        if($user_id)
        {
            $view['wx_info'] = null;
        }

        $order_id = Yii::$app->request->get('order_id', null);

        if(!is_null($order_id))
            $view['order_id'] = $order_id;

        return $this->render('repayment_success',$view);
    }



    /**
     * 被拒用户导流页
     * @return string
     */
    public function actionGuidePage()
    {

        $view = [];

        return $this->render('guide_page',$view);
    }
}
