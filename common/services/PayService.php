<?php

namespace common\services;

use Yii;
use yii\base\Component;
use yii\web\IdentityInterface;
use yii\helpers\Url;
use common\exceptions\InvestException;
use common\exceptions\PayException;
use common\helpers\TimeHelper;
use common\models\BankConfig;
use common\models\User;
use common\api\HttpRequest;
use common\models\Order;
use common\models\UserLog;

require_once Yii::getAlias('@common') . '/api/umpay/sdk_old/common.php';
require_once Yii::getAlias('@common') . '/api/umpay/sdk_old/mer2Plat.php';
require_once Yii::getAlias('@common') . '/api/umpay/sdk_old/plat2Mer.php';

/**
 * 联动支付模块 service
 */
class PayService extends Component
{
    const LDPay_OID_PARTNER = "";  //联动商户号

    // 日志category
    const LOG_CATEGORY = "koudai.pay.*";

    // 商户id
    const MER_ID = wzd_mer_id;

    // 版本（接口文档中为固定值）
    const SERVICE_VERSION = '4.0';

    // 银行卡类型
    const CARD_TYPE_CREDIT = 'CREDITCARD';
    const CARD_TYPE_DEBIT = 'DEBITCARD';

    public $status = [
        1 => "支付中",
        3 => "失败",
        4 => "成功",
        11 => "待确认",
        12 => "已冻结，待财务审核",
        13 => "待解冻，交易失败",
        14 => "财务已审核，待财务付款",
        15 => "财务审核失败，交易失败",
        16 => "受理成功，交易处理中",
        17 => "交易失败退单中",
        18 => "交易失败退单成功",
    ];



    public static $UMP_Bank_Info = [
        "ICBC" => "工商银行",
        "CCB" => "建设银行",
        "ABC" => "农业银行",
        "BOC" => "中国银行",
        "PSBC" => "邮储银行",
        "COMM" => "交通银行",
        "CITIC" => "中信银行",
        "CEB" => "光大银行",
        "HXB" => "华夏银行",
        "CMBC" => "民生银行",
        "CMB" => "招商银行",
        "SHB" => "上海银行",
        "BJB" => "北京银行",
        "BEA" => "东亚银行",
        "CIB" => "兴业银行",
        "NBB" => "宁波银行",
        "SPDB" => "浦发银行",
        "GDB" => "广发银行",
        "SPAB" => "平安银行",
        "BSB" => "包商银行",
        "CSCB" => "长沙银行",
        "CDB" => "承德银行",
        "CDRCB" => "成都农商银行",
        "CRCB" => "重庆农村商业银行",
        "CQB" => "重庆银行",
        "DLB" => "大连银行",
        "DYCCB" => "东营市商业银行",
        "ORBANK" => "鄂尔多斯银行",
        "FJNXB" => "福建省农村信用社",
        "GYB" => "贵阳银行",
        "GCB" => "广州银行",
        "GRCB" => "广州农村商业银行",
        "HEBB" => "哈尔滨银行",
        "HNNXB" => "湖南省农村信用社",
        "HSB" => "徽商银行",
        "BHB" => "河北银行",
        "HZCB" => "杭州银行",
        "BOJZ" => "锦州银行",
        "CSRCB" => "江苏常熟农村商业银行",
        "JSB" => "江苏银行",
        "JRCB" => "江阴农村商业银行",
        "JJCCB" => "九江银行",
        "LZB" => "兰州银行",
        "DAQINGB" => "龙江银行",
        "QHB" => "青海银行",
        "SHRCB" => "上海农商银行",
        "SRB" => "上饶银行",
        "SDEB" => "顺德农村商业银行",
        "TZCB" => "台州银行",
        "WHSHB" => "威海市商业银行",
        "WFCCB" => "潍坊银行",
        "WZCB" => "温州银行",
        "URMQCCB" => "乌鲁木齐商业银行",
        "WRCB" => "无锡农村商业银行",
        "YCCB" => "宜昌市商业银行",
        "YZB" => "鄞州银行",
        "CZCB" => "浙江稠州商业银行",
        "ZJTLCB" => "浙江泰隆商业银行",
        "MTBANK" => "浙江民泰商业银行",
        "NJCB" => "南京银行",
        "NCB" => "南昌银行",
        "QLBANK" => "齐鲁银行",
        "YDRCB" => "尧都农村商业银行",
    ];

    /**
     * 用户直接签约（请求的时候建议加锁）
     * @param unknown $realname
     * @param unknown $idcard
     * @param unknown $bank_card
     * @param unknown $phone
     * @return multitype
     */
    public function directBindCard($realname, $idcard, $bank_card, $phone) {
        // 1. 发起绑卡请求
        $map = self::serviceMap("user_reg");
        // 业务参数
        $map->put('pub_pri_flag', '2');
        $map->put('identity_type', 'IDENTITY_CARD');
        $map->put('identity_code', $idcard);
        $map->put('card_id', $bank_card);
        $map->put('card_holder', $realname);
        $map->put('media_id', $phone);
        $map->put('card_type', '0');
        $map->put('busi_no', '1');
        self::sendRequest($map, $httpResp, $httpRespMap);
        $code = $httpRespMap->get('ret_code');
        $bindResult = $httpRespMap->H_table;
        if( intval($code) == 0 || $code == "00160083") { //绑定成功，或者重复绑定 (/00160083  银行卡绑定关系已经存在)
            $code = 0;
            $status = 1;
        }
        else {
            //绑定失败
            $status = 0;
        }
        return [
                'code' => $code,
                'message' => $httpRespMap->get('ret_msg'),
                'status' => $status
        ];
    }

    /**
     * 用户直接支付（不发送短信）
     * @param $phone        用户绑卡手机号
     * @param $amount       支付金额
     * @param int $bind_id  绑定银行卡ID
     * @return array
     * @throws \yii\db\Exception
     */
    public function directCharge($phone, $amount, $order_id)
    {
        $map = self::serviceMap("syn_pay");
        $map->put("media_id", $phone);
        $map->put("media_type", "MOBILE");
        $map->put("order_id", $order_id);
        $map->put("mer_date", date("Ymd"));
        $map->put("amt_type", "RMB");
        $map->put("busi_no", "1");
        $map->put("amount", $amount);
        // 发送请求
        self::sendRequest($map, $httpResp, $httpRespMap);
        $ret_code = 0;
        $ret_msg  = '';
        $trade_no = '';
        if (!empty($httpRespMap->H_table)) {
            // 联动支付是实时的
            $ret_code = $httpRespMap->get('ret_code');
            $ret_code = $ret_code === '0000' ? 0 : $ret_code;
            $trade_no = !empty($httpRespMap->H_table['trade_no']) ? $httpRespMap->H_table['trade_no'] : "";
            if($ret_code == 0 || $ret_code == "00131062" ) {//支付成功（00131062 该订单已支付成功请不要重复提交）
                return [
                    'trade_state' => 0,
                    'order_id' => $order_id,
                    'trade_no' => $trade_no,
                    'message'  => '支付成功',
                    'amount'   => $amount,
                ];
            }
            else {
                $ret_msg = $httpRespMap->get('ret_msg');
            }
        } else {
            $ret_code = -1;
            $ret_msg  = '支付失败';
        }
        return [
                'trade_state'  => $ret_code,
                'order_id' => $order_id,
                'trade_no' => $trade_no,
                'message' => $ret_msg,
                'amount'   => 0,
        ];
    }

    /**
     * 用户解绑
     */
    public function unBindCard($phone_no)
    {
        $map = self::serviceMap("user_cancel");

        // 业务参数
        $map->put("media_id", $phone_no);
        $map->put("media_type", "MOBILE");
        $map->put("busi_no", "1");

        // 发送请求
        self::sendRequest($map, $httpResp, $httpRespMap);

        return [
            'httpCode' => $httpResp['code'],
            'code' => $httpRespMap->get('ret_code'),
            'message' => $httpRespMap->get('ret_msg')
        ];
    }

    /**
     * 获取对账文件
     *
     * @param string $date
     */
    public function getSettleFile($date) {

        $map = self::serviceMap("download_settle_file");

        // 业务参数
        $map->put("settle_date", $date);
        $map->put("settle_type", 'ENPAY');//提现

        $reqData = \MerToPlat::makeRequestDataByGet($map);
        return $reqData->getUrl();

    }

    /**
     * 获得商户号可用余额，单位分
     */
    public function getRemainMoney()
    {
        $map = self::serviceMap("query_account_balance");
        self::sendRequest($map, $httpResp, $httpRespMap);
        $result = $httpRespMap->H_table;
        if ($result['ret_code'] == '0000') {
            return intval($result['bal_sign']);
        } else {
            return 0;
        }
    }

    /*
     * 获取Map，包含公共的参数
     * return HashMap $map
     * */
    private static function serviceMap($service){
        $map = new \HashMap();
        $map->put("service", $service);
        $map->put('sign_type', 'RSA');
        $map->put('mer_id', wzd_mer_id);
        $map->put('version', '4.0');
        $map->put('charset', 'UTF-8');
        return $map;
    }

    /*
    * 发送请求获取返回值
    * param HashMap $map
    * param Array $httpResp
    * param HashMap $httpRespMap
    * return HashMap $map
    * */
    private static function sendRequest($map, &$httpResp, &$httpRespMap)
    {
        $reqData = \MerToPlat::makeRequestDataByGet($map);
        if($reqData === 1301)
        {
            // 证书配置错误
            InvestException::throwCodeExt(1301);
        }
        $httpReq = new HttpRequest();
        $httpReq->url = $reqData->getUrl();
        // http 请求返回结果
        $httpResp = $httpReq->send();
        // $httpResp['resp'] 为页面返回结果，解析过后放到 $httpRespMap中
        $httpRespMap = \PlatToMer::getResDataByHtml($httpResp['resp']);
    }

}



