<?php
namespace credit\controllers;

use Yii;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\data\Pagination;
use yii\base\UserException;

use common\models\CardInfo;
use common\models\CreditJxlQueue;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\helpers\StringHelper;
use common\services\UserService;
use common\helpers\Util;
use common\models\ContentActivity;
use common\models\fund\LoanFund;
use common\models\fund\OrderFundInfo;
use common\models\NoticeSms;

use credit\components\ApiUrl;
use backend\models\ThirdPartyShuntType;
use backend\models\ThirdPartyShunt;

class CreditWebController extends BaseController {

    public $layout = 'credit';

    public function init() {
        parent::init();

        $this->getResponse()->format = Response::FORMAT_HTML;
    }

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,

                'except' => [ // 除了下面的action其他都需要登录
                    'add-quota',
                    'alipay-process',
//                    'credit-authorization',
                    'help-center',
                    'help-description',
//                    'license-agreement',
//                    'loan-agreement',
                    'loan-issued',
                    'loan-issued2',
                    'license-explode',
                    'message-detail',
                    'open-app',
                    'platform-service',
                    'result-message',
                    'repayment-process',
                    'safe-login-text',
                    'safe-login-txt',
                    'use-instruction',
                    'user-click-count',
                    'diversion',
//                    'verification-jxl',
//                    'withholding-service',
                ],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * 信息授权及使用协议
     */
    public function actionSafeLoginText() {
        $this->view->title = '信息授权使用协议';
        return $this->render('safe-login-text', []);
    }

    /**
     * 用户注册协议
     */
    public function actionSafeLoginTxt() {
        $app_name = $this->getAppName();
        $company_name = $this->getCompany('', 0);
        $this->view->title = $app_name.'-纯信用小额借钱极速放贷';
        return $this->render('safe-login-txt', [
            'app_name' => $app_name,
            'company_name' => $company_name,
        ]);
    }

    /**
     * 平台服务协议 LoanAgreement
     */
    public function actionLoanAgreement() {
        $this->view->title = '平台服务协议';
        $currentUser = Yii::$app->user->identity;

        $user_id = $currentUser->getId();       // 获取用户id
        $order_id = intval($this->request->get('id', 0));

        $sql = LoanPerson::find()->where(['id' => $user_id])->select(['id', 'name', 'id_number', 'phone'])->asArray()->one();
        $order_sql = UserLoanOrder::find()->where(['id' => $order_id])->orderBy('id desc')->limit(1)->asArray()->one();
        if ($order_sql && $order_sql['loan_time'] != 0) {
            $time = date('Y 年 m 月 d 日', $order_sql['loan_time']);
            $idd = 'XYBT' . date('mdHis', $order_sql['loan_time']) . substr(md5($order_sql['id']), -3);
            $order_id = $order_sql["id"];
        } else if ($order_sql['status'] == -3) {
            $idd = '**订单生成后可见**';
            $time = '审核未通过';
        } else {
            $time = date('Y 年 m 月 d 日', time());
            $idd = '**订单生成后可见**';
        }

        $data = [
            'name' => $sql['name'],
            'id_number' => $sql['id_number'],
            'time' => $time,
            'id' => $idd,
        ];

        //附表 数据
        if ($order_sql['loan_time'] > 0) {
            $data['loan_time'] = $order_sql['loan_time'];
            $data['money_amount'] = $order_sql['money_amount'] / 100;
            $data['counter_fee'] = $order_sql['counter_fee'] / 100;
            $data['interests'] = OrderFundInfo::find()->select('interest')->where('`order_id`=' . (int) $order_id)->scalar();
            $data['time_end'] = $order_sql['loan_time'] ? date("Y年m月d日", $order_sql['loan_time'] + $order_sql['loan_term'] * 86400) : ''; //截止时间
        } else {
            $data['loan_time'] = -1;
        }

        $order_id = (string) $order_id;
        $downloan_url = ApiUrl::toCredit(["credit-web/loan-issued", "id" => StringHelper::auto_encrypt("$order_id")]);
        //修改甲方名字
        if(!empty($order_id)){
            $find_id = $this->getFindId($order_id);
        }else{
            $find_id = '';
        }
        $authorization = $this->getCompany($find_id,0);
        $app_name = $this->getAppName();
        return $this->render('loan-agreement', [
                    'data' => $data,
                    'order_id' => $order_id,
                    'down_url' => $downloan_url,
                    'authorization' => $authorization,//甲方
                    'app_name' =>$app_name ,//APP名称
        ]);
    }

    /**
     * 处理订单合同的导出
     */
    public function actionLoanIssued() {
        $this->view->title = '借款协议';

        $order_id = StringHelper::auto_decrypt($this->request->get('id'));
// 		$order_id = 18;
        if (!StringHelper::verifyNumber($order_id)) {
            throw new UserException('无效的订单号');
        }
        //处理order_id
        $user_order = UserLoanOrder::find()->where(['id' => $order_id])->orderBy('id desc')->limit(1)->one();
        if (!$user_order) {
            throw new UserException('找不到对应的订单');
        }
        if ($user_order && $user_order->loan_time != 0) {
            $time = date('Y 年 m 月 d 日', $user_order->loan_time);
        } else {
            $time = date('Y 年 m 月 d 日', time());
        }
        $user = LoanPerson::find()->where(['id' => $user_order->user_id])->limit(1)->one();
        if ($user_order) {
            $idd = 'XYBT' . date('mdHis', $user_order->loan_time) . substr(md5($user_order->id), -3);
            $money_da = Util::numToMoney($user_order->money_amount / 100);
        } else {
            $idd = "**订单生成后可见**";
            $money_da = "**订单生成后可见**";
        }
        /* @var $user_order UserLoanOrder */
        $loanFundInfo = $user_order->loanFund;
        if (!$loanFundInfo) {
            $loanFundInfo = LoanFund::findOne(LoanFund::ID_KOUDAI);
        }

        $unsure_text = '**订单生成后可见**';
        $data = [
            'name' => $user->name,
            'id_number' => $user->id_number,
            'lender' => $loanFundInfo->company_name, // 出借方
            'phone' => $user->phone,
            'day' => $user_order->loan_term + 1,
            'money' => $user_order->money_amount / 100,
            'money_da' => $money_da,
            'time' => $user_order->loan_time ? date("Y年m月d日", $user_order->loan_time) : $unsure_text,
            'time_end' => $user_order->loan_time ? date("Y年m月d日", $user_order->loan_time + $user_order->loan_term * 86400) : $unsure_text,
            'service_fee' => $user_order['counter_fee'] / 100,
            'service_fee_rate' => ($user_order['counter_fee'] / $user_order->money_amount) * 100,
            'interest_rate' => $loanFundInfo->interest_rate,
            'time_two' => $time,
            'id' => $idd,
            'interest_rate' => $loanFundInfo->interest_rate,
            'lender_id_number' => $loanFundInfo->id_number
        ];

        if ($loanFundInfo->type == LoanFund::TYPE_P2P && $user_order->loan_time) { //p2p 展示四方类型
            //四方协议
            return $this->render('platform-service-2', [
                        'data' => $data,
                        'order_id' => '',
                        'down_url' => '',
            ]);
        }
        else {
            return $this->render('platform-service-2', [
                'data' => $data,
                'order_id' => '',
                'down_url' => '',
            ]);
        }
    }

    /**
     * 客服管理-借款订单列表-借款协议
     */
    public function actionLoanIssued2() {
        $this->view->title = '借款协议';

        $order_id = StringHelper::auto_decrypt($this->request->get('id'));
        if (!StringHelper::verifyNumber($order_id)) {
            throw new UserException('无效的订单号');
        }
        //处理order_id
        /* @var $order \common\models\UserLoanOrder */
        $order = UserLoanOrder::find()->where(['id' => $order_id])->orderBy('id desc')->limit(1)->one();
        if (!$order) {
            throw new UserException('找不到对应的订单');
        }

        $user_id = $order->user_id;
        $user = LoanPerson::findOne($user_id);
        $data = $order->getContractData($user);
        $downloan_url = ApiUrl::toCredit([
            'credit-web/loan-issued',
            'id' => StringHelper::auto_encrypt($order_id),
        ]);

        $fund = $order->loanFund;
        if (!$fund) {
            $fund = LoanFund::findOne(LoanFund::ID_KOUDAI);
        }

        $data['lender'] = $fund->company_name;
        $data['interest_rate'] = $fund->interest_rate;
        $data['lender_id_number'] = $fund->id_number;
        $data['lender_name'] = $fund->name;
        if (!empty($order_id)) {
            $find_id = $this->getFindId($order_id);
        }

        $company_name = $this->getCompany($find_id, 0);

        //四方协议
        return $this->render('platform-service', [
            'order' => $order,
            'data' => $data,
            'order_id' => $order_id,
            'down_url' => $downloan_url,
            'company'=>$company_name
        ]);
    }

    /**
     * 借款协议
     */
    public function actionPlatformService($id = null) {
        $this->view->title = '借款协议';
        $user = Yii::$app->user->identity;
        /* @var $user \common\models\LoanPerson */
        if (!Yii::$app->request->get('channel_info')) {
            if (empty($user)) {
                throw new ForbiddenHttpException(Yii::t('yii', 'Login Required'));
            }

            $user_id = $user->getId();
            if ($id && $user_id) {
                $order = UserLoanOrder::find()
                    ->where(sprintf("user_id=%d AND id=%d", $user_id, $id))
                    ->orderBy("id desc")
                    ->limit(1)->one(); // 出借方
                if (!$order) {
                    throw new UserException('找不到对应的订单');
                }

                /* @var $order \common\models\UserLoanOrder */
                $data = $order->getContractData($user);
                $downloan_url = ApiUrl::toCredit([
                    "credit-web/loan-issued",
                    "id" => StringHelper::auto_encrypt("$id")
                ]);

                $fund = $order->loanFund;
                if (!$fund) {
                    $fund = LoanFund::findOne(LoanFund::ID_KOUDAI);
                }
                $data['lender'] = $fund->company_name;
                if (!empty($id)) {
                    $find_id = $this->getFindId($id);
                }
                if (Util::getMarket() == LoanPerson::APPMARKET_XH) {
                    $data['lender'] = $this->getCompany($find_id, 0);
                }
                $data['interest_rate'] = $fund->interest_rate;
                $data['lender_id_number'] = $fund->id_number;
                $data['lender_name'] = $fund->name;

                $company_name = $this->getCompany($find_id,0);
                //四方协议
                $service_view = 'platform-service';
                //两方协议
                $useragent = $this->getUserAgent();
                $list = LoanPerson::userServerList();
                $list_res = $list[$useragent];
                if (isset($list_res) && in_array($user->phone, $list_res)) {
                    $service_view = 'platform-service-5';
                    $data['lender_name'] = $company_name;
                }

                //三方协议
                if (Util::getMarket() == LoanPerson::APPMARKET_XH && !in_array($user->phone, $list_res)) {
                    $service_view = 'platform-service-4';
                }

                return $this->render($service_view, [
                    'order' => $order,
                    'data' => $data,
                    'order_id' => $id,
                    'down_url' => $downloan_url,
                    'company'=>$company_name
                ]);
            }
            else if ($user_id) {
                $unsure_text = '**订单生成后可见**';
                $day = intval($this->request->get('day',14));
                $money = intval($this->request->get('money', 1000));
                $money = $money ?? 1000;
                $type = intval($this->request->get('type'));
                $type = $type == 2 ? 2 : 1;

                $time_two = date('Y 年 m 月 d 日', time());
                $time = time();
                $fee = Util::calcLoanInfo($day, $money, $type); // 服务费
                $money_da = Util::numToMoney($money);
                $data = [
                    'name' => $user->name,
                    'id_number' => $user->id_number,
                    'lender' => $unsure_text, // 出借方
                    'phone' => $user->phone,
                    'day' => $day + 1,
                    'money' => $money,
                    'money_da' => $money_da,
                    'time' => $unsure_text,
                    'time_end' => $unsure_text,
                    'service_fee' => $fee['counter_fee'],
                    'service_fee_rate' => ($fee['counter_fee'] / $money) * 100,
                    'time_two' => $time_two,
                    'lender_id_number' => false,
                    'id' => $unsure_text,
                ];

                $id = isset($id) ? $id : "";
                $downloan_url = ApiUrl::toCredit(["credit-web/loan-issued", "id" => StringHelper::auto_encrypt($id)]);

                return $this->render('platform-service-2', [
                    'order' => null,
                    'data' => $data,
                    'order_id' => $id,
                    'down_url' => $downloan_url,
                ]);
            }
        }else{
            $unsure_text = '**订单生成后可见**';
            $day = intval($this->request->get('day', \yii::$app->params['counter_fee_rate']));
            $money = intval($this->request->get('money', 1000));
            $type = intval($this->request->get('type'));
            $time_two = date('Y 年 m 月 d 日', time());
            $time = time();
            $fee = Util::calcMultiLoanInfo($day, $money); // 服务费
            $money_da = Util::numToMoney($money);
            $data = [
                'name' => '**订单生成后可见**',
                'id_number' => '********',
                'lender' => $unsure_text, // 出借方
                'phone' => '**订单生成后可见**',
                'day' => $day + 1,
                'money' => $money,
                'money_da' => $money_da,
                'time' => $unsure_text,
                'time_end' => $unsure_text,
                'service_fee' => $fee['counter_fee'],
                'service_fee_rate' => ($fee['counter_fee'] / $money) * 100,
                'time_two' => $time_two,
                'lender_id_number' => false,
                'id' => $unsure_text,
            ];

            $id = isset($id) ? $id : "";
            $downloan_url = ApiUrl::toCredit(["credit-web/loan-issued", "id" => StringHelper::auto_encrypt($id)]);

            return $this->render('platform-service-2', [
                'order' => null,
                'data' => $data,
                'order_id' => $id,
                'down_url' => $downloan_url,
            ]);
        }
    }

    /**
     * 打开APP地址
     */
    public function actionOpenApp() {
        return $this->render('open-app', []);
    }

    /**
     * 授权扣款委托书
     */
    public function actionLicenseAgreement() {
        $unsure_text = '**订单生成后可见**';
        $this->view->title = '授权扣款委托书';
        $currentUser = Yii::$app->user->identity;
        $user_id = $currentUser->getId();       // 获取用户id
        $sql = LoanPerson::find()->from(LoanPerson::tableName() . ' as a')->leftJoin(CardInfo::tableName() . ' as b', 'a.id=b.user_id')
                        ->where(['a.id' => $user_id])->select(['a.id', 'a.name', 'a.id_number', 'a.phone', 'b.bank_name', 'b.card_no'])->asArray()->one();
        $order_id = intval($this->request->get('id', 0));
        $order_sql = UserLoanOrder::find()->where(['id' => $order_id])->orderBy('id desc')->limit(1)->asArray()->one();
        if ($order_sql && $order_sql['loan_time'] != 0) {
            $time = date('Y 年 m 月 d 日', $order_sql['loan_time']);
        } else if ($order_sql['status'] == -3) {
            $time = '审核未通过';
        } else {
            $time = date('Y 年 m 月 d 日', time());
        }

        if ($order_id > 0 && $order_sql && $order_sql['card_id']) {
            $cardInfo = CardInfo::find()->where(['id' => $order_sql['card_id']])->asArray()->one();

            $bankname = $cardInfo['bank_name'];
            $card_no = $cardInfo['card_no'];
        } else {
            $bankname = $sql['bank_name'];
            $card_no = $sql['card_no'];
        }
        $authorization = $this->getCompany('',0);
        $data = [
            'name' => $sql['name'],
            'authorization' => $authorization,
            'id_number' => $sql['id_number'],
            'phone' => $sql['phone'],
            'bank_name' => $bankname,
            'card_no' => $card_no,
            'time' => $time,
        ];


        return $this->render('license-agreement', [
            'data' => $data,
        ]);
    }

    /**
     * 授权扣款委托书导出
     */
    public function actionLicenseExplode() {
        $unsure_text = '**订单生成后可见**';
        $this->view->title = '授权扣款委托书';

        $order_id = StringHelper::auto_decrypt($this->request->get('id'));

        if (!StringHelper::verifyNumber($order_id)) {
            throw new UserException('无效的订单号');
        }
        //处理order_id
        $user_order = UserLoanOrder::find()->where(['id' => $order_id])->orderBy('id desc')->limit(1)->one();
        if (!$user_order) {
            throw new UserException('找不到对应的订单');
        }

        $order_sql = UserLoanOrder::find()->where(['id' => $order_id])->orderBy('id desc')->limit(1)->asArray()->one();

        $user_id = $order_sql['user_id'];       // 获取用户id
        $sql = LoanPerson::find()->from(LoanPerson::tableName() . ' as a')->leftJoin(CardInfo::tableName() . ' as b', 'a.id=b.user_id')
                        ->where(['a.id' => $user_id])->select(['a.id', 'a.name', 'a.id_number', 'a.phone', 'a.source_id', 'b.bank_name', 'b.card_no'])->asArray()->one();

        if ($order_sql && $order_sql['loan_time'] != 0) {
            $time = date('Y 年 m 月 d 日', $order_sql['loan_time']);
        } else if ($order_sql['status'] == -3) {
            $time = '审核未通过';
        } else {
            $time = date('Y 年 m 月 d 日', time());
        }

        if ($order_id > 0 && $order_sql && $order_sql['card_id']) {
            $cardInfo = CardInfo::find()->where(['id' => $order_sql['card_id']])->asArray()->one();

            $bankname = $cardInfo['bank_name'];
            $card_no = $cardInfo['card_no'];
        } else {
            $bankname = $sql['bank_name'];
            $card_no = $sql['card_no'];
        }

        $source = $this->getSource();
        switch ($source){
            case LoanPerson::PERSON_SOURCE_HBJB:
                $authorization = '淮北汇邦小额贷款股份有限公司';
                break;
            case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT:
                $authorization = COMPANY_NAME;
                break;
            case LoanPerson::PERSON_SOURCE_WZD_LOAN:
                $authorization = COMPANY_NAME;
                break;

            default:
                $authorization = COMPANY_NAME;
                break;
        }
        $data = [
            'name' => $sql['name'],
            'authorization' => $authorization,
            'id_number' => $sql['id_number'],
            'phone' => $sql['phone'],
            'bank_name' => $bankname,
            'card_no' => $card_no,
            'time' => $time,
        ];

        return $this->render('license-agreement', [
            'data' => $data,
        ]);
    }

    /**
     * 代扣服务协议
     */
    public function actionWithholdingService() {
        $currentUser = Yii::$app->user->identity;
        if (empty($currentUser)) {
            throw new ForbiddenHttpException();
        }

        $this->view->title = '代扣服务协议';
        $user_id = $currentUser->getId();       // 获取用户id
        $sql = LoanPerson::find()->from(LoanPerson::tableName() . ' as a')
            ->leftJoin(CardInfo::tableName() . ' as b', 'a.id=b.user_id')
            ->where(['a.id' => $user_id])
            ->select(['a.id', 'a.name', 'a.id_number', 'a.phone', 'b.card_no', 'b.bank_name'])
            ->asArray()->one();
        $myService = new UserService();
        $card_info = $myService->getMainCardInfo($user_id);
        $data = [
            'name' => $sql['name'],
            'id_number' => $sql['id_number'],
            'phone' => $sql['phone'],
            'bank_id' => substr($card_info->card_no, 0, 4) . " **** **** " . substr($card_info->card_no, -4),
            'bank_name' => $sql['bank_name'],
        ];
        return $this->render('withholding-service', [
            'data' => $data,
        ]);
    }

    /**
     * 征信授权协议
     */
    public function actionCreditAuthorization() {
        $currentUser = Yii::$app->user->identity;
        if (empty($currentUser)) {
            throw new ForbiddenHttpException();
        }

        $this->view->title = '征信授权协议';
        $user_id = $currentUser->getId();       // 获取用户id
        $sql = LoanPerson::find()->where(['id' => $user_id])
            ->select(['id', 'name', 'id_number', 'phone'])
            ->asArray()->limit(1)->one();
        $data = [
            'name' => $sql['name'],
            'id_number' => $sql['id_number'],
            'phone' => $sql['phone'],
        ];
        return $this->render('credit-authorization', [
            'data' => $data,
        ]);
    }

    /**
     * 提额攻略
     */
    public function actionAddQuota() {
        $this->view->title = '提额攻略';
        return $this->render('add-quota', [
        ]);
    }

    public function actionOperator() {
        $this->view->title = '运营商信息授权协议';
        $company = $this->getCompany('',0);
        $app_name = $this->getAppName();
        return $this->render('operator', [
            'company'=>$company,
            'app_name'=>$app_name,
        ]);
    }

    /**
     * 新的帮助中心H5页面3
     */
    public function actionUseInstruction() {
        $this->view->title = '优惠券说明';
        return $this->render('use-instruction', [
        ]);
    }

    /**
     * 新的帮助中心H5页面
     */
    public function actionHelpCenter() {
        $type = $this->request->get('type');
        $this->view->title = '帮助中心';
        // 登录态控制
        $title = false;
        if ($this->isFromXjk() || $this->isFromHBJB() || $this->isFromWZD()) {
            $title = true;
        }
        $view = 'help-center';
        if($this->isFromHBJB()){
            $view = 'help-center-hbqb';
        }
        if ($this->isFromSXD()){
            $view = 'help-center-sxd';
        }

        //现金白条
        if($this->getVersion() == LoanPerson::APPMARKET_XJBT){
            $view = 'help-center-xjbt';
        }
        //现金白条
        if(Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){
            $view = 'help-center-pro';
        }

        if($this->isFromWeichat()){
            $source = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
        }else{
            $source = $this->getSource();
        }
        $qq = '';//默认值
        $call = '';
        if($source == LoanPerson::PERSON_SOURCE_WZD_LOAN){//温州贷的值
            $qq = '2127394414';
            $call = '021-80311201';
        }
        $clientType = \yii::$app->request->getClient()->clientType;
        return $this->render($view, [
                    'title' => $title,
                    'type' => $clientType,
                    'source'=>$source,
                    'qq'=>$qq,
                    'call'=>$call,
        ]);
    }

    /**
     * 新的帮助中心H5页面2
     */
    public function actionHelpDescription() {
//        $source = $this->getSource();
        $app_name = $this->getAppName();
//        $app_name = LoanPerson::$person_source[$source];
        $this->view->title = '帮助中心';
        return $this->render('help-description', [
            'app_name'=>$app_name,
        ]);
    }

    /**
     * 支付宝支付流程
     */
    public function actionAlipayProcess() {
        $this->view->title = '支付宝转账';
        return $this->render('alipay-process', [
        ]);
    }

    /**
     * 支付方式
     */
    public function actionRepaymentProcess() {
        $this->view->title = '还款方式';
        return $this->render('repayment-process', [
        ]);
    }

    /**
     * 聚信立认证h5
     * @name 聚信立认证h5
     * @param string $source_id 来源ID
     * @method get
     */
    public function actionVerificationJxl($source_id = null) {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            throw new Exception('登录态失效');
        }

        $user_id = $curUser->id;
        if ($this->isFromApp()) {
            $verify = Yii::$container->get('userService')->getVerifyInfo($user_id);
            if (!$verify || !$verify['real_contact_status']) {
                return $this->render('message', ['msg' => '请先完成紧急联系人认证哦!']);
            }
        }

        $url = '';
        if (!empty($this->request->get('url'))) {
            $url = $this->request->get('url');
        }

        $queue = CreditJxlQueue::find()->where(['user_id' => $user_id])->limit(1)->one();
        $status = $queue ? $queue->current_status : CreditJxlQueue::STATUS_INPUT_PHONE_PWD;

        $source_id = $this->getSource();
        $appmarket = Util::getMarket();
        $img = 'yzwc.png'; //完成认证的图片
        $check_img = 'safe-icon-yes.png';
        switch($source_id) {
            case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT :
                $check_img = 'safe-icon-yes.png';
                switch($appmarket) {
                    case LoanPerson::APPMARKET_XJBT:
                        $img = 'xybt_xybt_fuli_yzwc.png';
                    break;
                    case LoanPerson::APPMARKET_XJBT_PRO:
                        $img = 'chengong-pro.png';
                        $check_img = 'xuanzpro.png';
                    break;
                }
            break;

            case LoanPerson::PERSON_SOURCE_HBJB:
                $img = 'chenggong-hbqb.png';
                $check_img = 'xuanze.png';
                break;

            case LoanPerson::PERSON_SOURCE_WZD_LOAN:
                $img = 'chenggong-wzd.png';
                $check_img = 'wxuanze.png';
                break;

            case LoanPerson::PERSON_SOURCE_SX_LOAN:
                $img = 'chenggong-sxd.png';
                $check_img = 'sxuanze.png';
                break;
        }

        $view = 'verification-jxl';
        if ($source_id == LoanPerson::PERSON_SOURCE_HBJB){
            $view = 'verification-jxl-hbqb';
        }

        $jump = false;
        if ($appmarket == LoanPerson::APPMARKET_XJBT_PRO) {
            $jump = true; //跳过等待，走下一步
        };

        $this->view->title = '手机运营商认证';
        return $this->render($view, [
            'source' => $source_id,
            'status' => $status,
            'phone' => $curUser->phone,
            'url' => $url,
            'color' => $this->getColor(),
            'img' => $img,
            'check_img' => $check_img,
            'appmarket' => $appmarket,
            'jump' => $jump,
        ]);
    }

    /**
     * 我的更多页面
     * @name 我的更多页面
     * @method post
     */
    public function actionMore() {
        $this->view->title = '更多信息';
        return $this->render('more', [
        ]);
    }

    /**
     * 运营商忘记密码页面
     * @name 运营商忘记密码页面
     * @method get
     */
    public function actionForgetPwd() {
        $this->view->title = '忘记密码';
        $color = $this->getColor();
        return $this->render('forget-password', [
            'color'=>$color,
        ]);
    }

    // 我的消息
    public function actionResultNotice() {
        $this->view->title = '我的消息';

        $curUser = Yii::$app->user->identity;
        $query = NoticeSms::find()->where(['user_id' => $curUser->id])->orderBy([
            'id' => SORT_DESC,
        ]);
        $pages = new Pagination(['totalCount' => $query->count()]);
        $pages->pageSize = 100;
        $data = $query->offset($pages->offset)->limit($pages->limit)->all();

        $source = $this->getSource();
        $source_color = 'xybt';
        if($source == LoanPerson::PERSON_SOURCE_HBJB){
            $source_color = 'hbqb';
        }else if($source == LoanPerson::PERSON_SOURCE_WZD_LOAN){
            $source_color = 'wzdai_loan';
        }
        return $this->render('result-notice', array(
            'data_list' => $data,
            'source' => $source,
            'source_color' => $source_color,
        ));
    }

    // 公告中心
    public function actionResultMessage() {
        $this->view->title = '公告中心';

        $curUser = Yii::$app->user->identity;
        // 状态是已发布 和 已结束的才显示
        $condition = "status in (".ContentActivity::STATUS_SUCCESS.",".ContentActivity::STATUS_TIMEOUT.")";
        if ($curUser && in_array($curUser->phone, [15102105045])) {
            $condition = " 1=1 ";
        }
        $query = ContentActivity::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);

        $pages = new Pagination(['totalCount' => $query->count()]);
        $pages->pageSize = 10;
        $data = $query->offset($pages->offset)->limit($pages->limit)->all();

        $source = $this->getSource();
        return $this->render('result-message', array(
            'data_list' => $data,
            'source' => $source,
        ));
    }

    // 公告明细
    public function actionMessageDetail() {
        $this->view->title = '公告中心';
        $id = intval($this->request->get('id', 0));
        if ($id) {
            $contentArr = ContentActivity::findOne($id);
            if ($contentArr) {
                $contentArr->count = intval($contentArr->count) + 1;
                $contentArr->save();

                return $this->renderPartial('message-detail', [
                    'content' => $contentArr->remark
                ]);
            }
        }
        return $this->render('result-message', [
            'data_list' => [],
        ]);
    }

    /**
     * 支付宝认证页面
     */
    public function actionAlipayCertification() {
        // 判断是否已验证过 + 验证是否成功
        $this->view->title = '支付宝认证';

        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            throw new Exception('登录态失效');
        }
        $user_id = $curUser->getId();
        $loanPerson = LoanPerson::findOne($user_id);
        $phone = $loanPerson->phone;

        return $this->render('alipay-certification', [
            'phone' => $phone
        ]);
    }

    /**
     * 获取资方id
     */
    private function getFindId($id) {
        $find_id = UserLoanOrder::find()->where(['id'=>$id])->select('fund_id')->one();
        if ($find_id && isset($find_id)) {
            return $find_id->fund_id;
        }

        return 0;
    }


    /**
     * 导流页面
     */
    public function actionDiversion(){

       $this->layout = false;

       $type = ThirdPartyShuntType::find()->where(['status'=>1])->asArray()->orderBy('sort desc')->all();

       foreach($type as $k=>$v){
           $list = ThirdPartyShunt::find()->where(['status'=>1,'type_id'=>$v['id']])->asArray()->orderBy('sort desc')->all();

           if($list){
               $type[$k]['list'] = $list;
           }else{
               unset($type[$k]);
           }
       }

       return $this->render('diversion',['list'=>$type]);
    }


}
