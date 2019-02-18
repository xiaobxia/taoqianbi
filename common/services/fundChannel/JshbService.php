<?php

namespace common\services\fundChannel;

use common\helpers\MessageHelper;
use common\helpers\StringHelper;
use common\helpers\ToolsUtil;
use common\models\BindCardInfo;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\services\CardAuthService;
use common\services\FinancialService;
use common\services\RealNameAuthService;
use Yii;
use common\models\UserLoanOrder;
use common\base\ErrCode;
use common\helpers\CurlHelper;
use yii\base\Exception;
use common\models\BankCardCheckWeb;
use common\api\RedisQueue;
use common\services\RealNameYouFenAuthService;
use common\models\ErrorMessage;
use common\services\AlipayCardAuthService;

/**
 * 极速荷包
 */
class JshbService extends BaseService {
    /**
     * 商户号
     * @var string
     */
    protected $merchant_id = '1';

    public $api;

    public $resultCode = [
        'start' => '1000',//进行中
        'success' => '1003',//成功
        'fail' => '1004',//失败
    ];

    /*
     * 该接口用于商户帮助用户在微贷开通存管账户，商户将请求的参数发送到该接口，同时同步获得用户的开户状态。 本接口无银行的存管界面，不需要用户做任何的操作，有非常好的用户体验。
     * 如果同步接口返回的存管状态是开户中，需要调用开户查询接口来查询最终的结果。
     * 注：如果用户已经开过存管账户，会返回用户开户的银行卡号，会与所传的银行卡不一致的情况，请自行处理
     */
    const open_account_api_url = "bind-card";

    /*
     * 该接口四要素鉴权
     */
    const card_verify_api_url = "auth";

    /*
     * 该接口检验银行卡信息。
     */
    const card_bin_api_url = "queryAccount";

    /*
     * 该接口用于商户向微贷推送发标的信息,本接口同步返回结果，但是该接口的成功不带发标的成功，具体的结果需要 等预打款的回调或者调用发标查询接口来查询最终的结果。
     */
    const withdraw_api_url = "withdraw";

    /*
     * 该接口用于查询发标的相关信息，同时查询预打款的状态。
     */
    const query_withdraw_url = "withdraw-query";

    /*
     * 该接口用于单条代扣。
     */
    const withhold_api_url = "withhold";

    /*
     * 该接口用于批量代扣。
     */
    const batch_withhold_api_url = "batch-withhold";


    /*
     * 该接口用于查询代扣状态。
     */
    const query_withhold_url = "withhold-query";

    /*
     * 带接口用户复查 待确认订单
     */
    const withhold_review_query = 'withhold_review_query';

    /*
     * 该接口四要素鉴权短信
     */
    const card_verify_api_sms_url = "auth_msg";
    const card_quick_verify_api_url = "auth_confirm_msg";

    // 私钥文件
    protected $privateKeyFile = '';
    protected $merchant_private_key = '';

    public $lastRequestRet;

    public function init()
    {
        parent::init();
        if (YII_ENV === 'prod') {
            // TODO: switch to prod
//            $this->api = 'http://pay.jisuhebao.com:15422/';
            $this->api = 'http://'.API_PAYURL.'/';
        } else if (YII_ENV === 'test') {
            $this->api = 'http://'.API_PAYURL.'/';
        } else if (YII_ENV === 'dev') {
            $this->api = 'http://'.TEST_API_PAYURL.'/';
        } else {
            throw new \Exception('未定义环境 ' . YII_ENV . ' 的api地址');
        }
    }

    /**
     * 获取渠道名称
     * @return string
     */
    public function getChannelName()
    {
        return 'fund_unspay';
    }

    /**
     * 获取签名
     */
    public function getSign(array $postArray, $privateKey)
    {
        ksort($postArray);
        $sign = json_encode($postArray, JSON_UNESCAPED_UNICODE);
        return hash('sha256', $sign . $privateKey);
    }

    /**
     * 订单还款成功
     * @param UserLoanOrder $order 订单
     * @return []
     */
    public function orderRepaySuccess($order, $is_prepay = null, $repay_amount = null)
    {
        return [
            'code' => 0,
        ];
    }

    /**
     * 订单放款成功反馈
     * @param UserLoanOrder $order 订单
     * @return []
     */
    public function orderPaySuccess($order) {

    }

    /**
     * 推送订单代付
     * @param UserLoanOrder $order 订单模型
     * @param string $operator 操作人
     * @return []
     */
    public function pushOrder($order, $operator='')
    {
        $user = $order->loanPerson;
        $FinancialLoanRecord = $order->financialLoanRecord;
        if (!$user) {
            return [
                'code' => ErrCode::ORDER_USER_NOT_FOUND,
                'message' => "找不到订单{$order->id}对应的用户",
            ];
        }

        $product_name = FinancialService::KD_PROJECT_NAME;

        $money = $order->money_amount - $order->counter_fee;//借款金额 - 服务费
        //测试
        $customParams = [
            'merchant_id' => (string)$this->merchant_id,                           //商户id, 必填, 校验商户是否存在
            'biz_order_no' => (string)$FinancialLoanRecord->order_id,              //业务订单号, 用来去重
            'bank_card_no' => (string)$FinancialLoanRecord->card_no,               //四要素 - 银行卡号
            'name' => (string)$user->name,                                         //四要素 - 姓名
            'id_card_no' => (string)$user->id_number,                              //四要素 - 身份证号
            'amount' => (string)$money,                                            //打款金额, 单位分
//            'fee' => (string)$order->counter_fee,                                  //服务费, 单位分
            'bank_id' => (string)$FinancialLoanRecord->bank_id,                    //系统间配置的 bank_id, 有耦合, 看看是否需要去掉
            'product_name' => (string)$product_name,                               //产品名称, 最大 256; 支付渠道要求支付, 商户没透传, 支付系统会自动添加
        ];


        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //下订单接口
        $url = $this->api . $this::withdraw_api_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        if (!$ret || $ret['code'] != '0') {
            return [
                'code' => $ret['code'],
                'message' => "请求微贷发标接口失败,请求结果为：{$ret['msg']}",
            ];
        } elseif (!$ret || $ret['code'] == '0') {
            if ( isset($ret['data']['status']) && !empty($ret['data']['status'])){
                $resStatus = trim($ret['data']['status']);
                $resCode = $this->resultCode[$resStatus];
                $resErrorMsg = trim($ret['data']['error_msg']);
                return [
                    'code' => $resCode,
                    'message' => "代付{$resStatus} ".$resErrorMsg,
                ];
            }else{
                return [
                    'code' => 0,
                    'message' => "OK",
                ];
            }

        }

        return $ret;
    }

    /**
     * 查询订单状态
     * @param UserLoanOrder $order 订单模型
     * @param string $operator 操作人
     * @return []
     */
    public function queryOrder($order) {
        return $this->_queryLenderInfo($order);
    }


    /**
     * 用户和资方进行预签约
     * @param UserLoanOrder $order 订单模型
     * @return []
     */
    public function preSign($order)
    {
        $user = $order->loanPerson;
        $FinancialLoanRecord = $order->financialLoanRecord;

        if (!$user) {
            return [
                'code' => ErrCode::ORDER_USER_NOT_FOUND,
                'message' => "找不到订单{$order->id}对应的用户",
            ];
        }
        // TODO: 开户只要做一次
        $product_name = FinancialService::KD_PROJECT_NAME;

        $customParams = [
            'merchant_id' => (string)$this->merchant_id,                           //商户id, 必填, 校验商户是否存在
            'name' => (string)$user->name,                                         //四要素 - 用户名
            'bank_card_no' => (string)$FinancialLoanRecord->card_no,               //四要素 - 银行卡号
            'id_card_no' => (string)$user->id_number,                              //四要素 - 身份证号
            'phone' => (string)$user->phone,                                       //四要素 - 手机号
            'bank_id' => (string)$FinancialLoanRecord->bank_id,                    //系统间配置的 bank_id, 有耦合, 看看是否需要去掉
        ];

        //测试

        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //签约接口
        $url = $this->api . $this::open_account_api_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        if (!$ret || $ret['code'] != '0') {
            return [
                'code' => $ret['code'],
                'message' => "请求签约接口失败,请求结果为：{$ret}",
            ];
        } elseif (!$ret || $ret['code'] == '0') {
            return [
                'code' => 0,
                'message' => "OK",
            ];
        }

        return $ret;
    }

    /**
     * 用户和资方进行预签约
     * @param UserLoanOrder $order 订单模型
     * @return []
     */
    public function preSignNew($params)
    {

        if (!$params) {
            return [
                'code' => 1,
                'message' => "没有参数",
            ];
        }
        // TODO: 开户只要做一次
        $product_name = FinancialService::KD_PROJECT_NAME;

        $customParams = [
            'merchant_id' => (string)$this->merchant_id,                           //商户id, 必填, 校验商户是否存在
            'name' => (string)$params['name'],                                         //四要素 - 用户名
            'bank_card_no' => (string)$params['bank_card_no'],               //四要素 - 银行卡号
            'id_card_no' => (string)$params['id_card_no'],                              //四要素 - 身份证号
            'phone' => (string)$params['phone'],                                       //四要素 - 手机号
            'bank_id' => (string)$params['bank_id'],                    //系统间配置的 bank_id, 有耦合, 看看是否需要去掉
        ];

        //测试

        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //签约接口
        $url = $this->api . $this::open_account_api_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        if (!$ret || $ret['code'] != '0') {
            $ret_json=json_encode($ret);
            return [
                'code' => $ret['code'],
                'message' => "请求签约接口失败,请求结果为：{$ret_json}",
            ];
        } elseif (!$ret || $ret['code'] == '0') {
            return $ret;
        }

        return $ret;
    }

    /**
     * 获得第三方支付系统银行短信验证码
     **/
    public function payAuthSmsCode($params){
        if (!$params) {
            return [
                'code' => 1,
                'message' => "没有参数",
            ];
        }
        // TODO: 开户只要做一次
        $product_name = FinancialService::KD_PROJECT_NAME;

        $customParams = [
            'merchant_id' => (string)$this->merchant_id,                           //商户id, 必填, 校验商户是否存在
            'name' => (string)$params['name'],                                         //四要素 - 用户名
            'bank_card_no' => (string)$params['bank_card_no'],               //四要素 - 银行卡号
            'id_card_no' => (string)$params['id_card_no'],                              //四要素 - 身份证号
            'phone' => (string)$params['phone'],                                       //四要素 - 手机号
            'bank_id' => (string)$params['bank_id'],                    //系统间配置的 bank_id, 有耦合, 看看是否需要去掉
            'channel_id' => (string)$params['channel_id'],                //支付渠道id
        ];

        //测试

        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //签约接口
        $url = $this->api . $this::card_verify_api_sms_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        Yii::error($url.'||'.json_encode($customParams),'card-auth');
        if (!$ret || $ret['code'] != '0') {
            $ret_json=json_encode($ret);
            //记录到mongodb中
            Yii::error($ret_json,'card-auth');
            $msg='';
            if(isset($ret['msg'])){
                $msg=$ret['msg'];
            }
            return [
                'code' => $ret['code'],
//                'message' => "请求签约接口失败,请求结果为：{$ret_json}",
                'message' => $msg,
            ];
        } elseif (!$ret || $ret['code'] == '0') {
            return $ret;
        }

        return $ret;
    }

    /**
     * 用户和资方进行预签约
     * @param UserLoanOrder $order 订单模型
     * @param [] $params 参数
     * @return []
     */
    public function confirmSign($order, $params)
    {
    }

    /**
     * 向资方查询更新订单推送状态
     * @param UserLoanOrder $order 订单模型
     * @param [] $params 参数
     * @return []
     */
    public function updateOrderPushStatus($order, $params)
    {

    }

    /**
     * 查询代付订单信息
     * @param UserLoanOrder $order
     * @return [] 成功格式：
     * {
     *   "code": 0,
     *   "msg": "ok",
     *   "data": {
     *      "biz_order_no": "201711141021101510626070", // 业务订单号
     *      "status": "success" // 支付状态, start-进行中, success - 成功, fail - 失败
     *      "error_msg": "xxx" // 支付失败时第三方透传的信息
     *   }
     * }
     */
    private function _queryLenderInfo($order)
    {
        $user = $order->loanPerson;
        $FinancialLoanRecord = $order->financialLoanRecord;

        if (!$user) {
            return [
                'code' => ErrCode::ORDER_USER_NOT_FOUND,
                'message' => "找不到订单{$order->id}对应的用户",
            ];
        }
        // TODO: 开户只要做一次
        $product_name = FinancialService::KD_PROJECT_NAME;

        $customParams = [
            'merchant_id' => (string)$this->merchant_id,                           //商户id, 必填, 校验商户是否存在
            'biz_order_no' => (string)$FinancialLoanRecord->order_id,               //业务订单号, 用来去重
        ];
        //测试

        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //查询代付接口
        $url = $this->api . $this::query_withdraw_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        if (!$ret || $ret['code'] != '0') {
            return [
                'code' => $ret['code'],
                'message' => "查询代付查询接口失败,请求结果为：{$ret['msg']}",
            ];
        } else {
            if ( isset($ret['data']['status']) && !empty($ret['data']['status'])){
                $resStatus = trim($ret['data']['status']);
                $resCode = $this->resultCode[$resStatus];
                $resErrorMsg = trim($ret['data']['error_msg']);
                return [
                    'code' => $resCode,
                    'message' => "代付{$resStatus} ".$resErrorMsg,
                ];
            }else{
                return [
                    'code' => 1,
                    'message' => "查询代付查询接口失败,请求结果为：{$ret['msg']}",
                ];
            }
        }
    }

    /**
     * 查询代付订单信息
     * @param UserLoanOrder $order
     * @return [] 成功格式：
     * {
     *   "code": 0,
     *   "msg": "ok",
     *   "data": {
     *      "biz_order_no": "201711141021101510626070", // 业务订单号
     *      "status": "success" // 支付状态, start-进行中, success - 成功, fail - 失败
     *      "error_msg": "xxx" // 支付失败时第三方透传的信息
     *   }
     * }
     */
    public function queryLoanRecord($params)
    {
        if (!$params){
            return [
                'code' => 1,
                'message' => "找不到打款记录",
            ];
        }
        // TODO: 开户只要做一次
        $product_name = FinancialService::KD_PROJECT_NAME;

        $customParams = [
            'merchant_id' => (string)$this->merchant_id,                           //商户id, 必填, 校验商户是否存在
            'biz_order_no' => (string)$params['biz_order_no'],               //业务订单号, 用来去重
        ];
        //测试

        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //查询代付接口
        $url = $this->api . $this::query_withdraw_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        if (!$ret || $ret['code'] != '0') {
            return [
                'code' => $ret['code'],
                'message' => "查询代付查询接口失败,请求结果为：{$ret['msg']}",
            ];
        } else {
            if ( isset($ret['data']['status']) && !empty($ret['data']['status'])){
                return $ret;
            }else{
                return [
                    'code' => 1,
                    'message' => "查询代付查询接口失败,请求结果为：{$ret['msg']}",
                ];
            }
        }
    }

    public function actionQueryLender()
    {
        $id = (int)Yii::$app->getRequest()->get('id');
        if (!$id || !($order = UserLoanOrder::findOne($id))) {
            throw new \Exception('订单不存在');
        }
        return $this->_queryLenderInfo($order);
    }

    /**
     * 获取资方服务费
     * @param LoanFund $fund 资方
     * @param integer $principal 本金(分)
     * @param integer $day 天数
     */
    public function getFundServiceFee($fund, $principal, $day)
    {
        return round(($principal * (8.5 / 100) * $day) / 360);
    }

    /**
     * 判断资方是否支持订单
     * @param UserLoanOrder $order 订单
     * @param LoanFund $fund 资方数据
     * @return []
     */
    public function supportOrder($order, $fund)
    {
        new LoanPerson();
        $user_id = $order->user_id;
        $loanPerson = LoanPerson::findOne(['id' => $user_id]);
        if(in_array($loanPerson->source_id,LoanPerson::$source_register_list)){
            return ['code' => 0];
        }
        return ['code' => -1];
    }



    /**
     * 单条代扣
     * @param UserLoanOrder $params 扣款记录
     * @return []
     */
    public function pushWithhold($params){
        if (!$params){
            return [
                'code' => 1,
                'message' => "找不到扣款记录",
            ];
        }

        $product_name = FinancialService::KD_PROJECT_NAME;
        $customParams = [
            // 公共参数
            'merchant_id' => $this->merchant_id,
//            'notify_url'  => $this->url . 'test/merchant-notify',

            // 业务参数
            'biz_order_no' => (string)$params['biz_order_no'],
            'name'         => (string)$params['name'],
            'id_card_no'   => (string)$params['id_card_no'],
            'bank_card_no' => (string)$params['bank_card_no'],
            'bank_id'      => (string)$params['bank_id'],
            'amount'       => (string)$params['amount'],
            'phone'       => (string)$params['phone'],
            'channel_id'  => 2,//1-畅捷，2-为合利宝

            // 业务扩展参数
            'extra'       => [
                'request' => 'withhold',
            ],
        ];


        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //代扣接口
        $url = $this->api . $this::withhold_api_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        return $ret;

    }

    /**
     * 批量代扣
     * @param UserLoanOrder $params 扣款记录
     * @return []
     */
    public function batchWithhold($params){
        if (!isset($params['items'])){
            return [
                'code' => 1,
                'message' => "找不到扣款记录",
            ];
        }

        $items = $params['items'];
        $product_name = FinancialService::KD_PROJECT_NAME;
        $customParams = [
            // 公共参数
            'merchant_id' => $this->merchant_id,
//            'notify_url'  => $this->url . 'test/merchant-notify',

            // 业务参数
            'items'       => $items,

            // 业务扩展参数
            'extra'       => [
                'request' => 'batch-withhold',
            ],
        ];


        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);

        $type = "POST";
        //批量代扣接口
        $url = $this->api . $this::batch_withhold_api_url;

        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        return $ret;


    }


    /**
     * 查询代扣订单信息
     * @param UserLoanOrder $order
     * @return [] 成功格式：
     * {
     *   "code": 0,
     *   "msg": "ok",
     *   "data": {
     *      "biz_order_no": "201711141021101510626070", // 业务订单号
     *      "status": "success" // 支付状态, start-进行中, success - 成功, fail - 失败
     *      "error_msg": "xxx" // 支付失败时第三方透传的信息
     *   }
     * }
     */
    public function withholdQuery($params,$pay_type='')
    {

        if (!isset($params['order_id'])){
            return [
                'code' => 1,
                'message' => "找不到扣款记录",
            ];
        }
        // TODO: 开户只要做一次
        $product_name = FinancialService::KD_PROJECT_NAME;

        $customParams = [
            'merchant_id' => (string)$this->merchant_id,                           //商户id, 必填, 校验商户是否存在
            'biz_order_no' => (string)$params['order_id'],               //业务订单号, 用来去重
        ];
        //测试

        // 添加签名
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $type = "POST";
        //查询代扣接口
        $url = $this->api . $this::query_withhold_url;

        if($pay_type){
            $url = $this->api . $this::withhold_review_query;
        }

        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);

        //记录日志文件里面
//        error_log($url.json_encode($customParams),3,'/data/wzd_log/pay.log');
//        error_log(json_encode($ret)."\n\r",3,'/data/wzd_log/pay.log');

        if (!$ret || $ret['code'] != '0') {
            return [
                'code' => $ret['code'],
                'message' => "查询代扣查询接口失败,请求结果为：{$ret['msg']}",
            ];
        } else {
            if ( isset($ret['data']['status']) && !empty($ret['data']['status'])){
                return $ret;
            }else{
                return [
                    'code' => 1,
                    'message' => "查询代扣查询接口失败,请求结果为：{$ret['msg']}",
                ];
            }
        }
    }


    /**
     * 银行卡四要素鉴权
     * @param  $card_no
     * @param  $phone
     * @param  $id_number
     * @param  $name
     */
    public static function cardVerify($card_no, $phone, $id_number, $name ,$bank_id){
        try{
            if (!$card_no){
                return [
                    'code' => 500,
                    'message' => "未填银行卡号",
                ];
            }
            if (!$phone){
                return [
                    'code' => 500,
                    'message' => "未填手机号",
                ];
            }
            if (!$id_number){
                return [
                    'code' => 500,
                    'message' => "未填身份证",
                ];
            }
            if (!$name){
                return [
                    'code' => 500,
                    'message' => "未填姓名",
                ];
            }
            if (!$bank_id){
                return [
                    'code' => 500,
                    'message' => "未填姓名",
                ];
            }

            $card_no_trim = StringHelper::trimBankCard(trim($card_no)); //消除输入的银行卡中的空格

            $customParams = [
                'name' => (string)$name,                                         //四要素 - 用户名
                'bank_card_no' => (string)$card_no_trim,                         //四要素 - 银行卡号
                'id_card_no' => (string)$id_number,                              //四要素 - 身份证号
                'phone' => (string)$phone,                                       //四要素 - 手机号
                'bank_id' => (string)$bank_id,                                   //畅捷四要素鉴权需要 - 银行卡ID
            ];

            $self_service = new self();

            //请求支付系统四要素验证
            $ret = $self_service->queryCardVer($customParams);
            if ($ret && isset($ret['code']) && $ret['code'] == 0){
                return [
                    'code' => 0,
                    'message' => '四要素鉴权成功',
                ];
            }

            //同盾四要素验证
            $user=BankCardCheckWeb::find()->where(['cardNo' =>$customParams['bank_card_no'],'mobile'=>$customParams['phone'],'bandtype'=>'td'])->asArray()->one();
            if(!$user){
                $ret = $self_service->queryTdCardVer($customParams);
                if($ret['netstatus']==200){
                    $status=($ret['result']?1:0);
                    $db = \Yii::$app->db;
                    $db->createCommand()->insert('`tb_bank_cardcheckweb`',['cardNo'=>$customParams['bank_card_no'],'status'=>$status,'mobile'=>$phone,'bandtype'=>'td','created'=>time()])->execute();
                }

                if ($ret['result']){
                    return [
                        'code' => 0,
                        'message' => '四要素鉴权成功',
                    ];
                }
            }else{

                if($user['status']==1){
                    return [
                        'code' => 0,
                        'message' => '四要素鉴权成功',
                    ];
                }
            }

            return [
                'code' => 500,
                'message' => '四要素鉴权失败',
            ];
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 银行卡四要素快捷鉴权绑卡
     * @param string $card_no
     * @param string $phone
     * @param string $id_number
     * @param string $name
     * @param int $bank_id
     * @param string $channel_id
     * @param string $sms_code
     * @return []
     */
    public static function cardQuickVerify($card_no, $phone, $id_number, $name ,$bank_id ,$channel_id ,$sms_code){

        try{
            if (!$card_no){
                return [
                    'code' => 500,
                    'message' => "未填银行卡号",
                ];
            }
            if (!$phone){
                return [
                    'code' => 500,
                    'message' => "未填手机号",
                ];
            }
            if (!$id_number){
                return [
                    'code' => 500,
                    'message' => "未填身份证",
                ];
            }
            if (!$name){
                return [
                    'code' => 500,
                    'message' => "未填姓名",
                ];
            }
            if (!$bank_id){
                return [
                    'code' => 500,
                    'message' => "未填姓名",
                ];
            }

            $card_no_trim = StringHelper::trimBankCard(trim($card_no)); //消除输入的银行卡中的空格

            $customParams = [
                'name' => (string)$name,                                         //四要素 - 用户名
                'bank_card_no' => (string)$card_no_trim,                         //四要素 - 银行卡号
                'id_card_no' => (string)$id_number,                              //四要素 - 身份证号
                'phone' => (string)$phone,                                       //四要素 - 手机号
                'bank_id' => (string)$bank_id,                                   //畅捷四要素鉴权需要 - 银行卡ID
                'channel_id' => (string)$channel_id,                             //支付渠道id
                'sms_code'  => (string)$sms_code                                 //短信验证码
            ];

            $self_service = new self();

            //请求支付系统四要素验证
            $ret = $self_service->queryQuickCardVer($customParams);
            if ($ret && isset($ret['code']) && $ret['code'] == 0){
                return [
                    'code' => 0,
                    'message' => '四要素鉴权成功',
                ];
            }

            //记录到mongodb中
            Yii::error(json_encode($ret),'card-auth-confirm');
            $msg='四要素鉴权失败';
            if(isset($ret['msg'])){
                $msg=$ret['msg'];
            }
            return [
                'code' => 500,
                'message' => $msg,
            ];
        } catch (\Exception $e) {
            //记录到mongodb中
            Yii::error('error：'.$e->getMessage(),'card-auth-confirm');
            return false;
        }
    }


    /**
     * 畅捷、合利宝 四要素验证请求
     * @param $customParams
     */
    public function queryCardVer($customParams){
        $type = "POST";
        $customParams['merchant_id'] = $this->merchant_id;
        //加密方式
        $product_name = FinancialService::KD_PROJECT_NAME;
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $url = $this->api. self::card_quick_verify_api_url;
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);
        return $ret;
    }

    /**
     * 畅捷、合利宝 四要素验证请求
     * @param $customParams
     */
    public function queryQuickCardVer($customParams){
        $type = "POST";
        $customParams['merchant_id'] = $this->merchant_id;
        //加密方式
        $product_name = FinancialService::KD_PROJECT_NAME;
        $customParams['sign'] = $this->getSign($customParams,$product_name);
        $url = $this->api. self::card_quick_verify_api_url;
        Yii::error($url.'params='.json_encode($customParams),'card-auth-confirm');
        $ret = CurlHelper::FinancialCurl($url, $type, $customParams, 120);
        return $ret;
    }


    /**
     * 同盾四要素验证请求
     * @param  $customParams
     */
    public function queryTdCardVer($customParams){

        $name = $customParams['name'];
        $id_number = $customParams['id_card_no'];
        $phone = $customParams['phone'];
        $card_no = $customParams['bank_card_no'];

        $td_service = Yii::$app->tdService;
        $res = $td_service->bizTdAuthBankCard($name, $id_number, $phone, $card_no);
        return $res;
    }

    /**
     * 检验银行卡信息
     * @param $card_no
     * @param $bank_id
     */
    public static function cardBin($card_no,$bank_id=0){
        try{
            $card_auth = new CardAuthService();
            $res = $card_auth->cardAuth($card_no);
            if ($res && isset($res['code']) && $res['code'] == 0){
                if($bank_id!=0 && intval($bank_id)!=intval($res['data']['bank_id'])){
                    //用户选择银行卡ID跟接口中卡BIN中银行卡ID不一致，然后调用阿里云卡BIN
                    $alipay_card_auth=new AlipayCardAuthService();
                    $alipay_res=$alipay_card_auth->cardAuth($card_no);
                    if ($alipay_res && isset($alipay_res['code']) && $alipay_res['code'] == 0){
                        $alipay_bank_id=intval($alipay_res['data']['bank_id']);
                        if($alipay_bank_id!=intval($res['data']['bank_id'])){
                            $res=$alipay_res;
                        }
                    }
                }
                return $res;
            }else{
                //调用阿里云银行卡卡BIN验证
                $alipay_card_auth=new AlipayCardAuthService();
                $res=$alipay_card_auth->cardAuth($card_no);
                if ($res && isset($res['code']) && $res['code'] == 0){
                    return $res;
                }else{
                    return [
                        'code'=>500,
                        'message'=>'验证银行卡失败'
                    ];
                }
            }
        } catch (\Exception $e) {
            \YII::error(sprintf('验证银行卡失败，原因：%s',$e->getMessage()),'cardbin');
            return [
                'code'=>500,
                'message'=>'验证银行卡失败'
            ];
        }
    }


    /**
     * 实名验证
     * @param $name
     * @param $id_number
     */
    public static function realnameAuth($name, $id_number){
        try{
            $RealNameAuthService = new RealNameAuthService();
            $result = $RealNameAuthService->realNameAuth($name, $id_number);
            if ($result && isset($result['RESULT']) && $result['RESULT'] == 1){
                $sex_res = ToolsUtil::idCard_to_sex($id_number);
                $date = ToolsUtil::idCard_to_birthday($id_number);
                $birthday = date('Y-m-d',$date);

                $sex = 0;
                if ($sex_res == '男'){
                    $sex = 1;
                }elseif($sex_res == '女'){
                    $sex = 2;
                }
                return [
                    'code' => 0,
                    'data' => [
                        'sex' => $sex,
                        'realname' => $name,
                        'idcard' => $id_number,
                        'birthday' => $birthday,
                        'type'     => 'xiaoshi',
                    ]
                ];
            }else{
                /*
                $redis_auth = RedisQueue::get(['key'=>json_encode($name.$id_number)]);
                $res=array();
                if(!$redis_auth){
                    $RealNameYouFenAuthService = new RealNameYouFenAuthService();
                    $res = $RealNameYouFenAuthService->realNameAuth($name, $id_number);
                    if($res && $res['resCode'] == '0000' && $res ['data']['statusCode']=='2005'){
                        $sex_res = ToolsUtil::idCard_to_sex($id_number);
                        $date = ToolsUtil::idCard_to_birthday($id_number);
                        $birthday = date('Y-m-d',$date);
                        $sex = 0;
                        if ($sex_res == '男'){
                            $sex = 1;
                        }elseif($sex_res == '女'){
                            $sex = 2;
                        }
                        return [
                            'code' => 0,
                            'data' => [
                                'sex' => $sex,
                                'realname' => $name,
                                'idcard' => $id_number,
                                'birthday' => $birthday,
                                'type'     => 'youfen',
                            ]
                        ];
                    }
                    RedisQueue::set(['expire'=>86400,'key'=>json_encode($name.$id_number),'value'=>'1']);
                }*/

                //将未通过实名认证信息记录到mongodb，以方便查看
                Yii::error("姓名：{$name}，身份证号：{$id_number},小视科技返回值：".json_encode($result),'realname');
                @MessageHelper::sendSMS(NOTICE_MOBILE,json_encode($result));//发送预警短信
                #@MessageHelper::sendSMS(NOTICE_MOBILE,json_encode([$result,$res]));//发送预警短信
                ErrorMessage::getMessage(0, "姓名：{$name}，身份证号：{$id_number},小视科技返回值：".json_encode($result), ErrorMessage::SOURCE_REALNAME);
                return [
                    'code'=>500,
                    'message'=>'实名认证失败'
                ];
            }
        } catch (\Exception $e) {
            return [
                'code'=>500,
                'message'=>'实名认证失败'
            ];
        }

    }
}

