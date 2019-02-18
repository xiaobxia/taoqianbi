<?php
namespace common\services;

use Yii;
use yii\base\Component;
use yii\base\UserException;
use yii\base\Exception;
use yii\helpers\Url;
use common\models\CreditZmop;
use common\models\info\AlipayInfo;
use common\models\info\TaobaoFormatData;
use common\models\info\TaobaoInfo;
use common\models\LoanOrderSource;
use common\models\LoanPerson;
use common\models\mongo\mobileInfo\PhoneOperatorDataMongo;
use common\models\Rong360LoanOrder;
use common\models\UserContact;
use common\models\UserLoanOrder;

/**
 * 数据同步到中间表
 */
class DataToBaseService extends Component
{

    const TYPE_RONG = 1;

    public static $type = [
        self::TYPE_RONG => '融360',
    ];

    /**
     * 同步有鱼数据到中间表
     */
    public static function synYouYuToBase($order_id)
    {
        $user_loan_order = UserLoanOrder::findOne(['id' => $order_id]);
        if (false == $user_loan_order) {
            return false;
        }

        $person_id = $user_loan_order->user_id;

        $loanOrderSource = LoanOrderSource::findOne(['order_id' => $order_id]);
        if (false == $loanOrderSource) {
            return false;
        }

        $source_order_id = $loanOrderSource->sourceOrderId;
        if (empty($source_order_id)) {
            return false;
        }

        $orderSourceMongo = ChannelMongo::find()->where(['_id' => $source_order_id])->one();

        if (false == $orderSourceMongo) {
            return false;
        }
        $report = $orderSourceMongo->data;

        $bill_list = [];
        if (isset($report['mobileinfo']['bill'])) {
            $billSummaryList = $report['mobileinfo']['bill'];
            foreach ($billSummaryList as $item) {
                if (isset($item['month']) && isset($item['bill_amount'])) {
                    $bill_list[] = [
                        'month' => $item['month'],
                        'amount' => $item['bill_amount']
                    ];
                }
            }
        }

        $real_name_time = '';
        $real_name_status = 0;
        $real_name_name = '';
        $real_name_id_card = '';
        if (isset($report['mobileinfo']['userdata'])) {
            $basicInfo = $report['mobileinfo']['userdata'];

            if (isset($basicInfo['regist_date'])) {
                $real_name_time = $basicInfo['regist_date'];
            }
            if (isset($basicInfo['user_id']) && isset($basicInfo['user_name'])) {
                $real_name_status = 1;
                if (isset($basicInfo['user_name'])) {
                    $real_name_name = $basicInfo['user_name'];
                }
                if (isset($basicInfo['user_id'])) {
                    $real_name_id_card = $basicInfo['user_id'];
                }
            }
        }

        $contact_list = [];
        if (isset($report['mobileinfo']['tel'])) {

            $calls = [];
            foreach ($report['mobileinfo']['tel'] as $key => $val) {
                if (isset($val['call_phone'])) {
                    if (isset($calls[$val['call_phone']])) {
                        $calls[$val['call_phone']]['talk_time'] += $val['talk_time'];
                        $calls[$val['call_phone']]['call_cnt']++;
                    } else {
                        $val['call_cnt'] = 0;
                        $calls[$val['call_phone']] = $val;
                    }
                }
            }

            // $call_log = $report['callDetailList'];
            foreach ($calls as $key => $item) {
                $contact_list[] = [
                    'phone' => isset($item['call_phone']) ? $item['call_phone'] : "",
                    'phone_label' => isset($item['call_addr']) ? $item['call_addr'] : "",
                    'first_contact_date' => isset($item['first_contact_date']) ? $item['first_contact_date'] : "",
                    'last_contact_date' => isset($item['call_time']) ? $item['call_time'] : "",
                    'talk_seconds' => isset($item['talk_time']) ? floatval($item['talk_time']) : 0.0,
                    'talk_cnt' => isset($item['talk_cnt']) ? intval($item['talk_cnt']) : 0,
                    'call_seconds' => isset($item['call_seconds']) ? floatval($item['call_seconds']) : 0.0,
                    'call_cnt' => isset($item['call_cnt']) ? intval($item['call_cnt']) : 0,
                    'called_cnt' => isset($item['called_cnt']) ? intval($item['called_cnt']) : 0,
                    'called_seconds' => isset($item['called_seconds']) ? floatval($item['called_seconds']) : 0.0,
                    'msg_cnt' => isset($item['msg_cnt']) ? intval($item['msg_cnt']) : 0,
                    'send_cnt' => isset($item['send_cnt']) ? intval($item['send_cnt']) : 0,
                    'receive_cnt' => isset($item['receive_cnt']) ? intval($item['send_cnt']) : 0,
                    'unknown_cnt' => isset($item['unknown_cnt']) ? intval($item['receive_cnt']) : 0,
                    'contact_1w' => isset($item['contact_1w']) ? intval($item['contact_1w']) : 0,
                    'contact_1m' => isset($item['contact_1m']) ? intval($item['contact_1m']) : 0,
                    'contact_3m' => isset($item['contact_3m']) ? intval($item['contact_3m']) : 0,
                ];
            }

        }


        $common_contactors = [];
        if (isset($report['extrainfo']['urgent_contacts'])) {
            if (isset($report['extrainfo']['urgent_contacts'][0])) {
                $common_contactors[] = ['name' => $report['extrainfo']['urgent_contacts'][0]['name'], 'phone' => $report['extrainfo']['urgent_contacts'][0]['phone']];
            }
            if (isset($report['extrainfo']['urgent_contacts'][1])) {
                $common_contactors[] = ['name' => $report['extrainfo']['urgent_contacts'][1]['name'], 'phone' => $report['extrainfo']['urgent_contacts'][1]['phone']];
            }
        }
        $data_arr = [
            'user_id' => $person_id,
            'bill_list' => $bill_list,
            'real_name_status' => $real_name_status,
            'real_name_time' => $real_name_time,
            'contact_list' => $contact_list,
            'common_contactors' => $common_contactors,
            'real_name_name' => $real_name_name,
            'real_name_id_card' => $real_name_id_card
        ];
        return PhoneOperatorDataMongo::addPhoneInfo($data_arr);
    }

    /**
     * 同步百融数据到中间表
     */
    public static function synBaiRongToBase($order_id)
    {
        $user_loan_order = UserLoanOrder::findOne(['id' => $order_id]);
        if (false == $user_loan_order) {
            return false;
        }

        $person_id = $user_loan_order->user_id;

        $loanOrderSource = LoanOrderSource::findOne(['order_id' => $order_id]);
        if (false == $loanOrderSource) {
            return false;
        }

        $source_order_id = $loanOrderSource->sourceOrderId;
        if (empty($source_order_id)) {
            return false;
        }

        //获取mogoDb里面融360数据
        $mobileMongo = ChannelMobileMongo::find()->where(['_id' => (string)$source_order_id])->one();
        $orderSourceMongo = ChannelMongo::find()->where(['_id' => $source_order_id])->one();

        if (false == $mobileMongo) {
            return false;
        }
        if (false == $orderSourceMongo) {
            return false;
        }
        if (false == $mobileMongo->info) {
            return false;
        }
        $device_data = $orderSourceMongo->device_data;
        $report = json_decode($mobileMongo->info['operatorData'], true);
        // $report = $orderSourceMongo['data'];

        $bill_list = [];

        if (isset($report['billSummaryList'])) {
            $billSummaryList = $report['billSummaryList'];
            foreach ($billSummaryList as $item) {
                if (isset($item['billDate']) && isset($item['totalFee'])) {
                    $bill_list[] = [
                        'month' => $item['billDate'],
                        'amount' => $item['totalFee']
                    ];
                }
            }
        }

        $real_name_time = '';
        $real_name_status = 0;
        $real_name_name = '';
        $real_name_id_card = '';
        if (isset($report['basicInfo'])) {
            $basicInfo = $report['basicInfo'];

            if (isset($basicInfo['inNetDate'])) {
                $real_name_time = rtrim(str_replace(['年', '月', '日'], '-', $basicInfo['inNetDate']), '-');
                // $real_name_time = $basicInfo['inNetDate'];
            }
            if (isset($basicInfo['idcardNumber']) && isset($basicInfo['realName'])) {
                $real_name_status = 1;
                if (isset($basicInfo['realName'])) {
                    $real_name_name = $basicInfo['realName'];
                }
                if (isset($basicInfo['idcardNumber'])) {
                    $real_name_id_card = $basicInfo['idcardNumber'];
                }
            }
        }

        $contact_list = [];
        if (isset($report['callDetailList'])) {
            $call_log = $report['callDetailList'];
            foreach ($call_log as $item) {
                $contact_list[] = [
                    'phone' => isset($item['peerNumber']) ? $item['peerNumber'] : "",
                    'phone_label' => isset($item['location']) ? $item['location'] : "",
                    'first_contact_date' => isset($item['first_contact_date']) ? $item['first_contact_date'] : "",
                    'last_contact_date' => isset($item['time']) ? $item['time'] : "",
                    'talk_seconds' => isset($item['durationSec']) ? floatval($item['durationSec']) : 0.0,
                    'talk_cnt' => isset($item['talk_cnt']) ? intval($item['talk_cnt']) : 0,
                    'call_seconds' => isset($item['call_seconds']) ? floatval($item['call_seconds']) : 0.0,
                    'call_cnt' => isset($item['call_cnt']) ? intval($item['call_cnt']) : 0,
                    'called_cnt' => isset($item['called_cnt']) ? intval($item['called_cnt']) : 0,
                    'called_seconds' => isset($item['called_seconds']) ? floatval($item['called_seconds']) : 0.0,
                    'msg_cnt' => isset($item['msg_cnt']) ? intval($item['msg_cnt']) : 0,
                    'send_cnt' => isset($item['send_cnt']) ? intval($item['send_cnt']) : 0,
                    'receive_cnt' => isset($item['receive_cnt']) ? intval($item['send_cnt']) : 0,
                    'unknown_cnt' => isset($item['unknown_cnt']) ? intval($item['receive_cnt']) : 0,
                    'contact_1w' => isset($item['contact_1w']) ? intval($item['contact_1w']) : 0,
                    'contact_1m' => isset($item['contact_1m']) ? intval($item['contact_1m']) : 0,
                    'contact_3m' => isset($item['contact_3m']) ? intval($item['contact_3m']) : 0,
                ];
            }

        }


        $common_contactors = [];
        if (isset($device_data['contact'])) {
            if (isset($device_data['contact']['names']) && isset($device_data['contact']['phones'])) {
                $common_contactors[] = ['name' => current($device_data['contact']['names']), 'phone' => current($device_data['contact']['phones'])];
            }
        }
        $data_arr = [
            'user_id' => $person_id,
            'bill_list' => $bill_list,
            'real_name_status' => $real_name_status,
            'real_name_time' => $real_name_time,
            'contact_list' => $contact_list,
            'common_contactors' => $common_contactors,
            'real_name_name' => $real_name_name,
            'real_name_id_card' => $real_name_id_card
        ];
        Yii::error(json_encode($data_arr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 'debug-bairong');
        return PhoneOperatorDataMongo::addPhoneInfo($data_arr);
    }


    public static function synJXLToBase($data, $user_id)
    {
        $bill_list = [];
        if (isset($data['cell_behavior'])) {
            foreach ($data['cell_behavior'] as $val) {
                foreach ($val['behavior'] as $j) {
                    $bill_list[] = [
                        'month' => isset($j['cell_mth']) ? $j['cell_mth'] : '',
                        'amount' => isset($j['total_amount']) ? $j['total_amount'] : ''
                    ];
                }
            }
        }

        if (isset($data['data_source']) && isset($data['data_source'][0])) {
            $real_name_time = $data['data_source'][0]['binding_time'];
            $real_name_status = $data['data_source'][0]['reliability'] == '实名认证' ? 1 : 0;
        } else if (isset($data['application_check']['2']) && isset($data['application_check']['2']['check_points'])) {
            if ($data['application_check']['2']['check_points']['reliability'] == '非实名认证' || $data['application_check']['2']['check_points']['reliability'] == '未实名认证') {
                $real_name_time = "";
                $real_name_status = 0;
            } else {
                $real_name_time = $data['application_check']['2']['check_points']['reg_time'];
                if(isset($data['application_check']['2']['check_points']['check_name'])){
                    $check_name = strpos($data['application_check']['2']['check_points']['check_name'], '匹配成功') > -1 ? 1 : 0;
                }else{
                    $check_name = 0;
                }
                if(isset($data['application_check']['2']['check_points']['check_idcard'])){
                    $check_idcard = (strpos($data['application_check']['2']['check_points']['check_idcard'], '匹配失败') > -1 || strpos($data['application_check']['2']['check_points']['check_idcard'], '不匹配') > -1) ? 0 : 1;
                }else{
                    $check_idcard = 0;
                }
                $real_name_status = ($check_name == 1 && $check_idcard == 1) ? 1 : 0;
            }
        } else {
            $real_name_time = "";
            $real_name_status = 0;
        }
        if (isset($data['person'])) {
            $real_name_name = $data['person']['real_name'];
            $real_name_id_card = $data['person']['id_card_num'];
        } else {
            $real_name_name = '';
            $real_name_id_card = "";
        }

        $contact_list = [];
        if (isset($data['contact_list'])) {
            foreach ($data['contact_list'] as $k => $item) {
                $contact_list[] = [
                    'phone' => isset($item['phone_num']) ? $item['phone_num'] : 0,
                    'phone_label' => isset($item['contact_name']) ? $item['contact_name'] : '',
                    'first_contact_date' => '',
                    'last_contact_date' => '',
                    'talk_seconds' => isset($item['call_len']) ? $item['call_len'] : '',
                    'talk_cnt' => isset($item['call_cnt']) ? $item['call_cnt'] : 0,
                    'call_seconds' => isset($item['call_out_len']) ? $item['call_out_len'] : 0,
                    'call_cnt' => isset($item['call_out_cnt']) ? $item['call_out_cnt'] : 0,
                    'called_seconds' => isset($item['call_in_len']) ? $item['call_in_len'] : 0,
                    'called_cnt' => isset($item['call_in_cnt']) ? $item['call_in_cnt'] : 0,
                    'msg_cnt' => 0,
                    'send_cnt' => 0,
                    'receive_cnt' => 0,
                    'unknown_cnt' => 0,
                    'contact_1w' => isset($item['contact_1w']) ? $item['contact_1w'] : 0,
                    'contact_1m' => isset($item['contact_1m']) ? $item['contact_1m'] : 0,
                    'contact_3m' => isset($item['contact_3m']) ? $item['contact_3m'] : 0,
                ];
                if (isset($data['collection_contact'][0]['contact_details'][0]['phone_num']) && $data['collection_contact'][0]['contact_details'][0]['phone_num'] == $contact_list[$k]['phone']) {
                    $contact_list[$k]['first_contact_date'] = $data['collection_contact'][0]['contact_details'][0]['trans_start'];
                    $contact_list[$k]['last_contact_date'] = $data['collection_contact'][0]['contact_details'][0]['trans_end'];
                }
                if (isset($data['collection_contact'][1]['contact_details'][0]['phone_num']) && $data['collection_contact'][1]['contact_details'][0]['phone_num'] == $contact_list[$k]['phone']) {
                    $contact_list[$k]['first_contact_date'] = $data['collection_contact'][1]['contact_details'][0]['trans_start'];
                    $contact_list[$k]['last_contact_date'] = $data['collection_contact'][1]['contact_details'][0]['trans_end'];
                }
            }
        }
        usort($contact_list, function ($a, $b) {
            $al = $a['talk_seconds'];
            $bl = $b['talk_seconds'];
            if ($al == $bl) {
                return 0;
            } else {
                return ($al > $bl) ? -1 : 1;
            }
        });

        if (isset($data['cell_behavior'])) {
            foreach ($data['cell_behavior'] as $msg) {
                foreach ($msg['behavior'] as $msage) {
                    foreach ($contact_list as $k => $contact) {
                        if (intval($contact['phone']) == intval($msage['cell_phone_num'])) {
                            $contact_list[$k]['msg_cnt'] = $contact['msg_cnt'] + $msage['sms_cnt'];
                        }
                    }
                }
            }
        }
        $common_contactors = [];
        if (isset($data['collection_contact'])) {
            foreach ($data['collection_contact'] as $common) {
                $common_contactors[] = [
                    'name' => $common['contact_name'],
                    'phone' => $common['contact_details'][0]['phone_num'],
                ];
            }
        }
        $data_arr = [
            'user_id' => $user_id,
            'bill_list' => $bill_list,
            'real_name_status' => $real_name_status,
            'real_name_time' => $real_name_time,
            'contact_list' => $contact_list,
            'common_contactors' => $common_contactors,
            'real_name_name' => $real_name_name,
            'real_name_id_card' => $real_name_id_card
        ];
        PhoneOperatorDataMongo::addPhoneInfo($data_arr);
    }

    public static function synJXLRawToBase($data, $user_id){
        //运营商原始数据中的基本信息
        $raw_basic='';
        //运营商原始数据中的通话信息
        $raw_calls='';
        //运营商原始数据中的短信信息
        $raw_smses='';
        //运营商原始数据中的流量使用信息
        $raw_nets='';
        //运营商原始数据中的账单信息
        $raw_transactions='';
        if(isset($data['members']['transactions'][0]['calls'])){
            $raw_basic=$data['members']['transactions'][0]['basic'];
            $raw_calls=$data['members']['transactions'][0]['calls'];
            $raw_smses=$data['members']['transactions'][0]['smses'];
            $raw_nets=$data['members']['transactions'][0]['nets'];
            $raw_transactions=$data['members']['transactions'][0]['transactions'];

            $data_arr=[
                'user_id' => $user_id,
                'raw_basic'=>$raw_basic,
                'raw_calls'=>$raw_calls,
                'raw_smses'=>$raw_smses,
                'raw_nets'=>$raw_nets,
                'raw_transactions'=>$raw_transactions
            ];
            PhoneOperatorDataMongo::addRawPhoneInfo($data_arr);
        }
    }

    /**
     * 整理支付宝数据
     * @param
     */
    public static function synAlipayToBase($user_id) {
        return false;
        $info = AlipayInfo::find()->where(['user_id' => $user_id])->orderBy('created_time desc')->asArray()->one();

        $ants_lines = [];
        if (isset($info['antsLines'])) {
            $patterns = "/\d+/";
            preg_match_all($patterns, $info['antsLines'], $arr);
            if (isset($arr[0][0]) && isset($arr[0][1])) {
                $hilt = $arr[0][0] . '.' . $arr[0][1];
                $ants_lines['ants_lines_total'] = $hilt;
            }
            if (isset($arr[0][2]) && isset($arr[0][3])) {
                $ants_lines['ants_lines_usable'] = $arr[0][2] . '.' . $arr[0][3];
            }
        }
        if (isset($info['antsArrears'])) {
            $patterns = "/\d+/";
            preg_match_all($patterns, $info['antsArrears'], $arr);
            if (isset($arr[0][0]) && isset($arr[0][1])) {
                $ants_lines['ants_arrears'] = $arr[0][0] . '.' . $arr[0][1];
            }
        }
        $created_time = '';
        if (isset($info['created_time'])) {
            $created_time = $info['created_time'];
        }
        $wealth = 0;
        if (isset($info['wealth'])) {
            $patterns = "/\d+/";
            preg_match_all($patterns, $info['wealth'], $arr);
            if (isset($arr[0][0]) && isset($arr[0][1])) {
                $wealth = $arr[0][0] . '.' . $arr[0][1];
            }
        }
        $register_time = '';
        if (isset($info['registerTime'])) {
            $register_time = self::dateSwitch($info['registerTime']);
        }
        $balance = 0;
        if (isset($info['balance'])) {
            $balance = $info['balance'];
        }
        $balance_bao = 0;
        if (isset($info['balanceBao'])) {
            $balance_bao = $info['balanceBao'];
        }
        $fortune_bao = 0;
        if (isset($info['fortuneBao'])) {
            $fortune_bao = $info['fortuneBao'];
        }
        $fund = 0;
        if (isset($info['fund'])) {
            $fund = $info['fund'];
        }
        $deposit_bao = 0;
        if (isset($info['depositBao'])) {
            $deposit_bao = $info['depositBao'];
        }
        $taobao_financial = 0;
        if (isset($info['taobaoFinancial'])) {
            $taobao_financial = $info['taobaoFinancial'];
        }
        $bank_cards = [];
        if (isset($info['bankCards']) && $info['bankCards']) {
            $data = self::getBankCardInfo($info['bankCards']);
            if ($data) {
                $bank_cards = $data;
            }

        }
        $real_name_status = 0;
        $real_name = '';
        if (isset($info['realName']) && $info['realName']) {
            $real_name_status = substr_count($info['realName'], '已认证') ? 1 : 0;
            $arr = explode('|', $info['realName']);
            $real_name = $arr[0];
        }
        $email = '';
        if (isset($info['email'])) {
            $email = $info['email'];
        }
        $phone = '';
        if (isset($info['mobile'])) {

            $phone = $info['mobile'];
        }
        $taobao_name = '';
        if (isset($info['taobaoName'])) {
            $taobao_name = $info['taobaoName'];
        }
        $friends_contact = [];
        if (isset($info['friendsContact']) && $info['friendsContact']) {
            $friends_contact = self::getFriendInfo($info['friendsContact']);
        }
        $trade_conract = [];
        if (isset($info['tradeContact']) && $info['tradeContact']) {
            $trade_conract = self::getFriendInfo($info['tradeContact']);
        }
        $deal_record = [];
        if (isset($info['dealRecord']) && $info['dealRecord']) {
            $deal_record = self::generalDealArray($info['dealRecord']);
        }
        $data = [
            'user_id' => $user_id,
            'ants_lines' => $ants_lines,
            'created_time' => $created_time,
            'wealth' => $wealth,
            'register_time' => $register_time,
            'balance' => $balance,
            'balance_bao' => $balance_bao,
            'fortune_bao' => $fortune_bao,
            'fund' => $fund,
            'deposit_bao' => $deposit_bao,
            'bank_cards' => $bank_cards,
            'email' => $email,
            'phone' => $phone,
            'taobao_name' => $taobao_name,
            'friends_contact' => $friends_contact,
            'trade_conract' => $trade_conract,
            'deal_record' => $deal_record,
            'real_name_status' => $real_name_status,
            'real_name' => $real_name,
            'taobao_financial' => $taobao_financial,
        ];
        AlipayFormatReportMongo::addAlipayInfo($data);


    }

    public static function dateSwitch($date)
    {
        $date = str_replace('年', '-', $date);
        $date = str_replace('月', '-', $date);
        $date = str_replace('日', '', $date);
        return $date;
    }

    public static function getBankCardInfo($info)
    {
        $arr = explode(';', $info);
        $new_arr = [];
        foreach ($arr as $key => $value) {
            if ($value) {
                $a = explode('-', $value);
                $new_arr[$key]['bank_name'] = $a[0];

                $patterns = "/\d+/";

                preg_match_all($patterns, $a[1], $arr);

                if (empty($arr[0][0])) {

                    preg_match_all($patterns, $a[2], $arr1);
                    $new_arr[$key]['type'] = $a[1];

                    $new_arr[$key]['card_no'] = isset($arr1[0][0]) ? $arr1[0][0] : "";
                } else {
                    // code...
                    $new_arr[$key]['type'] = '未知';
                    $new_arr[$key]['card_no'] = isset($arr[0][0]) ? $arr[0][0] : "";
                }


            }
        }
        return $new_arr;
    }

    public static function getFriendInfo($info)
    {

        $arr = explode(')', $info);
        $new_arr = [];
        foreach ($arr as $k => $value) {
            if ($value) {
                $a = explode('(', $value);
                $new_arr[$k]['name'] = isset($a[0]) ? $a[0] : "";
                $new_arr[$k]['account'] = isset($a[1]) ? $a[1] : "";
            }

        }
        return $new_arr;
    }

    public static function generalDealArray($info)
    {
        $arr = explode('详情', $info);

        $new_arr = [];
        $result = [];
        foreach ($arr as $k => $value) {
            if (!$value) {
                continue;
            }

            if (strpos($value, "\n") != 2) {
                $value = "\n" . $value;
            }

            $format_data = explode("\n", $value);

            $new_arr['deal_time'] = str_replace('.', '-', isset($format_data[1]) ? $format_data[1] : "") . " " . (isset($format_data[2]) ? $format_data[2] : "");
            $new_arr['name'] = isset($format_data[3]) ? $format_data[3] : "";
            $new_arr['order_no'] = isset($format_data[4]) ? $format_data[4] : "";
            $new_arr['other_party'] = isset($format_data[5]) ? $format_data[5] : "";
            $patterns = "/\d+/";

            preg_match_all($patterns, isset($format_data[6]) ? $format_data[6] : "", $s);
            $hilt = isset($s[0][0]) ? $s[0][0] : "";
            $new_arr['deal_amount'] = $hilt;
            if (count($format_data) == 10) {

                if (substr_count($format_data[8], '亲密付') || substr_count($format_data[8], '代付')) {
                    $new_arr['detail_amount'] = isset($format_data[6]) ? $format_data[6] : "";
                    $new_arr['detail_amount'] = str_replace("\t", '', $new_arr['detail_amount']);
                    $new_arr['deal_amount'] = isset($format_data[6]) ? $format_data[6] : "";
                    $new_arr['status'] = isset($format_data[8]) ? $format_data[8] : "";

                } else {
                    $new_arr['detail_amount'] = isset($format_data[7]) ? $format_data[7] : "";
                    $new_arr['detail_amount'] = str_replace("\t", '', $new_arr['detail_amount']);
                    $new_arr['deal_amount'] = isset($format_data[7]) ? $format_data[7] : "";
                    $new_arr['status'] = isset($format_data[8]) ? $format_data[8] : "";
                }

            } else {
                $new_arr['detail_amount'] = isset($format_data[6]) ? $format_data[6] : "";
                $new_arr['detail_amount'] = str_replace("\t", '', $new_arr['detail_amount']);
                $new_arr['status'] = isset($format_data[7]) ? $format_data[7] : "";
                if ((substr_count($new_arr['name'], '网商银行') && !substr_count($new_arr['name'], '网商银行贷款')) || substr_count($new_arr['name'], '蚂蚁借呗放款至银行卡')) {
                    $new_arr['detail_amount'] = isset($format_data[7]) ? $format_data[7] : "";
                    $new_arr['detail_amount'] = str_replace("\t", '', $new_arr['detail_amount']);
                    $new_arr['deal_amount'] = isset($format_data[7]) ? $format_data[7] : "";
                    $new_arr['status'] = isset($format_data[8]) ? $format_data[8] : "";
                }
            }
            $result[] = $new_arr;
        }
        return $result;
    }

    public static function synTaoBaoDataToBase($user_id)
    {

        $info = TaobaoInfo::find()->where(['user_id' => $user_id])->orderBy('created_time desc')->asArray()->one();
        $taobao_name = '';
        if (isset($info['taobaoName'])) {
            $taobao_name = $info['taobaoName'];
        }
        $login_email = '';
        if (isset($info['loginEmail'])) {
            $login_email = $info['loginEmail'];
        }
        $binding_mobile = '';
        if (isset($info['bindingMobile'])) {
            $binding_mobile = $info['bindingMobile'];
        }
        $growth = '';
        if (isset($info['growth'])) {
            $growth = $info['growth'];
        }
        $alipay_email = '';
        if (isset($info['alipayEmail'])) {
            $alipay_email = $info['alipayEmail'];
        }
        $alipay_mobile = '';
        if (isset($info['alipayMobile'])) {
            $alipay_mobile = $info['alipayMobile'];
        }
        $taobao_address = '';
        if (isset($info['taobaoAddress'])) {
            $taobao_address = $info['taobaoAddress'];
        }
        $account_type = '';
        if (isset($info['accountType'])) {
            $account_type = $info['accountType'];
        }
        $real_name_status = 0;
        if (isset($info['realName']) && $info['realName']) {
            $real_name_status = substr_count($info['realName'], '已认证') ? 1 : 0;
        }
        $deal_record = '';
        if (isset($info['dealRecord'])) {
            $deal_record = self::getDealRecord($info['dealRecord']);
        }
        $credit_point = '';
        if (isset($info['creditPoint'])) {
            $credit_point = $info['creditPoint'];
        }
        $good_rate = '';
        if (isset($info['goodRate'])) {
            $good_rate = $info['goodRate'];
        }
        $tian_mao_point = '';
        if (isset($info['tianMaoPoint'])) {
            $tian_mao_point = $info['tianMaoPoint'];
        }
        $middle_rate = '';
        if (isset($info['middleRate'])) {
            $middle_rate = $info['middleRate'];
        }
        $bad_rate = '';
        if (isset($info['badRate'])) {
            $bad_rate = $info['badRate'];
        }

        $tian_mao_credit_level = '';
        if (isset($info['tianMaoCreditLevel'])) {
            $tian_mao_credit_level = $info['tianMaoCreditLevel'];
        }
        $tian_mao_level = '';
        if (isset($info['tianMaoLevel'])) {
            $tian_mao_level = $info['tianMaoLevel'];
        }
        $created_time = '';
        if (isset($info['created_time'])) {
            $created_time = $info['created_time'];
        }
        $tian_mao_experience = '';
        if (isset($info['tianMaoExperience'])) {
            $tian_mao_experience = $info['tianMaoExperience'];
        }
        $exception = '';
        if (isset($info['exception'])) {
            $exception = $info['exception'];
        }
        $user_id = $info['user_id'] ? $info['user_id'] : "";
        $data_arr = [
            'user_id' => $user_id,
            'taobaoName' => $taobao_name,
            'loginEmail' => $login_email,
            'bindingMobile' => $binding_mobile,
            'growth' => $growth,
            'alipayEmail' => $alipay_email,
            'alipayMobile' => $alipay_mobile,
            'accountType' => $account_type,
            'realName' => $real_name_status,
            'taobaoAddress' => $taobao_address,
            'dealRecord' => $deal_record,
            'creditPoint' => $credit_point,
            'goodRate' => $good_rate,
            'middleRate' => $middle_rate,
            'badRate' => $bad_rate,
            'tianMaoPoint' => $tian_mao_point,
            'tianMaoCreditLevel' => $tian_mao_credit_level,
            'tianMaoLevel' => $tian_mao_level,
            'tianMaoExperience' => $tian_mao_experience,
            'created_time' => $created_time,
            'exception' => $exception,
        ];
        TaobaoFormatData::addFormatData($data_arr);
    }

    private function getDealRecord($info)
    {

        $arr = explode("\r\n", $info);
        $format_new_arr = [];
        $result = [];
        foreach ($arr as $value) {
            $format = explode('---', $value);
            $format_new_arr['deal_time'] = isset($format['0']) ? $format['0'] : "";
            $format_new_arr['order_no'] = isset($format['1']) ? $format['1'] : "";
            $format_new_arr['other_part'] = isset($format['2']) ? $format['2'] : "";
            $format_new_arr['name'] = isset($format['3']) ? $format['3'] : "";
            $format_new_arr['price'] = isset($format['4']) ? $format['4'] : "";
            $format_new_arr['num'] = isset($format['5']) ? $format['5'] : "";
            $format_new_arr['sum'] = isset($format['6']) ? $format['6'] : "";
            $format_new_arr['status'] = isset($format['7']) ? $format['7'] : "";
            $result[] = $format_new_arr;
        }
        return $result;
    }

    //同步给你花数据到中间表;
    public static function synGnhDataToBase($order){
        $data = [];
        $source = LoanOrderSource::find()->where(['order_id' => $order->id])->one();
        if($source){
            $data = ChannelMongo::getInfo(LoanPerson::PERSON_SOURCE_WYXYK,$source->source_order_id);
        }

        $bill_list = [];
        $contact_list = [];
        $real_name_id_card= [];
        $common_contactors = [];
        $real_name_status = '';
        $real_name_time = '';
        $real_name_name = '';
        if(isset($data->operator)){
            $operators_report = json_decode($data->operator,true);
            if (isset($operators_report['contact_list'])) {

                foreach ($operators_report['contact_list'] as $item) {
                    $contact_list[] = [
                        'phone' => isset($item['phone']) ? $item['phone'] : "",
                        'phone_label' => "",
                        'first_contact_date' => "",
                        'last_contact_date' => "",
                        'talk_seconds' =>isset($item['talk_seconds']) ? $item['talk_seconds'] : "",
                        'talk_cnt' =>isset($item['talk_cnt']) ? $item['talk_cnt'] : "",
                        'call_seconds'=>isset($item['call_seconds']) ? $item['call_seconds'] : "",
                        'call_cnt' => isset($item['call_cnt']) ? $item['call_cnt'] : "",
                        'called_cnt' => isset($item['called_cnt']) ? $item['called_cnt'] : "",
                        'called_seconds'=>isset($item['called_seconds']) ? $item['called_seconds'] : "",
                        'msg_cnt' => 0,
                        'send_cnt' => 0,
                        'receive_cnt' => 0,
                        'unknown_cnt' => 0,
                        'contact_1w' =>isset($item['contact_1w']) ? $item['contact_1w'] : "",
                        'contact_1m' =>isset($item['contact_1m']) ? $item['contact_1m'] : "",
                        'contact_3m' =>isset($item['contact_3m']) ? $item['contact_3m'] : "",
                    ];
                }
            }

            if(isset($operators_report['bill_list'])){
                foreach ($operators_report['bill_list'] as $value) {
                    $bill_list[] = [
                        'month' => isset($value['month']) ? $value['month'] : "",
                        'amount' => isset($value['amount']) ? $value['amount']: "",
                    ];
                }
            }
            if (isset($operator['common_contactors'])) {
                foreach ($operator['common_contactors'] as $contactList) {
                    $common_contactors[] = array(
                        'name' => isset($contactList['name']) ? $contactList['name'] : "",
                        'phone' => isset($contactList['phone']) ? $contactList['phone'] : 0);
                }
            }

            if(isset($operators_report['real_name_status']) ){
                if ($operators_report['real_name_status'] == '实名认证') {
                    $real_name_status = 1;
                }else {
                    $real_name_status = 0;
                }

            }
            if(isset($operators_report['real_name_time']) ){
                $real_name_time = strtotime($operators_report['real_name_time']);
            }
            if(isset($operators_report['real_name_name']) ){
                $real_name_name = $operators_report['real_name_name'];
            }
        }

        $data_arr = array(
            'user_id' => $order->user_id,
            'bill_list' => $bill_list,
            'contact_list' => $contact_list,
            'common_contactors' => $common_contactors,
            'real_name_status' => $real_name_status,
            'real_name_time' => $real_name_time,
            'real_name_name' => $real_name_name,
            'real_name_id_card' => $real_name_id_card,
        );
        unset($order,$bill_list,$contact_list,$common_contactors,$real_name_status,$real_name_time,$real_name_name,$real_name_id_card);
        return PhoneOperatorDataMongo::addPhoneInfo($data_arr);
    }

    public static function snyGnhToZmopData($order){
        $data = [];
            $source = LoanOrderSource::find()->where(['order_id' => $order->id])->one();
            if($source){
            $data = ChannelMongo::getInfo(LoanPerson::PERSON_SOURCE_WYXYK,$source->source_order_id);
        }

        $zm_score = '';
        $watch_info = [];
        if(isset($data->zm)){
            $zm = json_decode($data->zm,true);
            if(isset($zm['zm_score'])){
                $zm_score = $zm['zm_score'];
            }
            if(isset($zm['zm_watch'])){
                $watch_info = $zm['zm_watch'];
            }
        }
        $zmop = CreditZmop::find()->where(['person_id'=>$order->user_id])->one();
        if(!empty($zmop)){
            $zmop->watch_info = json_encode($watch_info);
            $zmop->zm_score = $zm_score;
            if($watch_info){
                $zmop->watch_matched = 2;
            }else{
                $zmop->watch_matched = 1;
            }
            $zmop->save();
        }
    }

    /**
     * 借点钱数据至MONGO
     * @param $order
     * @return bool
     *
     */
    public static function synJdqToBase($order)
    {
        $source = LoanOrderSource::find()->where(['order_id' => $order->id])->one();
        if($source){
            $model = ChannelMongo::getInfo(LoanPerson::PERSON_SOURCE_JDQ,$source->source_order_id);
        }
        $result = !empty($model->operator) ? $model->operator : '';
        $result = base64_decode($result);
        $result = gzdecode($result);
        $result = json_decode($result, true);

        $user_contact = UserContact::find()->where(['user_id'=>$order->user_id])->one();

        $contact_list = [];
        $common_contactors = [];
        $common_contactors[0]['name'] = isset($user_contact['name'])?$user_contact['name']:'';
        $common_contactors[0]['phone'] = isset($user_contact['mobile'])?$user_contact['mobile']:'';
        $common_contactors[1]['name'] = isset($user_contact['name_spare'])?$user_contact['name_spare']:'';
        $common_contactors[1]['phone'] = isset($user_contact['mobile_spare'])?$user_contact['mobile_spare']:'';
        $bill_list = [];
        $contact_list = [];
        if (isset($result[0])) {
            $all_calls = [];
            $cell_data = [];
            if (isset($result[0]['calls'])) {
                    foreach ($result[0]['calls'] as $calls) {
                            $other_cell_phone = $calls['other_cell_phone'];  //联系人电话
                            if(!isset($cell_data[$other_cell_phone])) {
                                $cell_data[$other_cell_phone] = [
                                    'call_count'=>0,
                                    'call_time'=>0,
                                    'called_count'=>0,
                                    'called_time'=>0,
                                    'contact_1w'=>0,
                                    'contact_1m'=>0,
                                    'contact_3m'=>0
                                ];
                            }

                            if(!isset($cell_data[$other_cell_phone]['first_contact_date'])){
                                $cell_data[$other_cell_phone]['first_contact_date'] = $calls['start_time'];
                            }
                            $cell_data[$other_cell_phone]['last_contact_date'] = $calls['start_time'];
                            if ($calls['init_type'] == '主叫') {
                                $cell_data[$other_cell_phone]['call_count'] += 1;                  //主叫次数
                                $cell_data[$other_cell_phone]['call_time'] += $calls['use_time'];   //主叫时间
                            } else {
                                $cell_data[$other_cell_phone]['called_count'] += 1;
                                $cell_data[$other_cell_phone]['called_time'] += $calls['use_time'];
                            }

                            if (time() - strtotime($calls['update_time']) <= 86400 * 7) {
                                $cell_data[$other_cell_phone]['contact_1w'] += 1;
                            }
                            if (time() - strtotime($calls['update_time']) <= 86400 * 31) {
                                $cell_data[$other_cell_phone]['contact_1m'] += 1;
                            }
                            if (time() - strtotime($calls['update_time']) <= 86400 * 92) {
                                $cell_data[$other_cell_phone]['contact_3m'] += 1;
                            }

                            $all_calls[] = $calls['other_cell_phone'];

                    }
            }

            foreach($cell_data as $key=>$data_call){
                $contact_list[] = [
                    'phone' => $key,
                    'phone_label' => "",
                    'first_contact_date' => "",
                    'last_contact_date' => "",
                    'talk_seconds' => $data_call['call_time'] + $data_call['called_time'],
                    'talk_cnt' => $data_call['call_count'] + $data_call['called_count'],
                    'call_seconds' => $data_call['call_time'],
                    'call_cnt' => $data_call['call_count'],
                    'called_cnt' => $data_call['called_count'],
                    'called_seconds' => $data_call['called_time'],
                    'msg_cnt' => 0,
                    'send_cnt' => 0,
                    'receive_cnt' => 0,
                    'unknown_cnt' => 0,
                    'contact_1w' => $data_call['contact_1w'],
                    'contact_1m' => $data_call['contact_1m'],
                    'contact_3m' => $data_call['contact_3m'],
                ];
            }

            $bill_list = [];
            if (isset($result[0]['transactions'])) {
                foreach ($result[0]['transactions'] as $bill) {
                    $bill_list[] = [
                        'month' => date('Y-m', strtotime($bill['bill_cycle'])),
                        'amount' => $bill['total_amt']
                    ];
                }
            }

            if (isset($result[0]['basic']['idcard']) && isset($result[0]['basic']['real_name'])) {
                $real_name_status = 1;   //实名认证
            } else {
                $real_name_status = 0;
            }

            $real_name = isset($result[0]['basic']['real_name']) ? $result[0]['basic']['real_name'] : '';
            $id_card = isset($result[0]['basic']['idcard']) ? $result[0]['basic']['idcard'] : '';
        }

        $data_arr = array(
            'user_id' => isset($order->user_id) ? $order->user_id : '',
            'bill_list' => $bill_list,
            'contact_list' => $contact_list,
            'common_contactors' => $common_contactors,
            'real_name_status' => isset($real_name_status) ? $real_name_status:'',
            'real_name_time' => '',
            'real_name_name' => isset($real_name) ? $real_name:'',
            'real_name_id_card' => isset($id_card) ? $id_card:'',
        );
        unset($order, $contact_list, $common_contactors, $bill_list, $real_name_status, $real_name, $id_card);
        return PhoneOperatorDataMongo::addPhoneInfo($data_arr);
    }

    //同步58数据到中间表;
    public function synWubaDataToBase($data, $user_id)
    {

        $bill_list = [];
        if (isset($data['originalData'])) {
            if (isset($data['originalData']['phoneBillDataList'])) {
                foreach ($data['originalData']['phoneBillDataList'] as $val) {

                    $bill_list[] = [
                        'month' => isset($j['cell_mth']) ? $j['cell_mth'] : '',
                        'amount' => isset($j['total_amount']) ? $j['total_amount'] : ''
                    ];

                }
            }
        }
        $contact_list = [];


    }
    public  static function  synZmTobase($order){
        $data=[];
        $source = LoanOrderSource::find()->where(['order_id' => $order->id])->one();
        if($source){
            $data = ChannelMongo::getInfo(LoanPerson::PERSON_SOURCE_JDQ,$source->source_order_id);
        }
        $zm_score="";
        $watch_info=[];
        if(isset($data->zm)){
            $zm=json_decode($data->zm,true);
            if(isset($zm['score'])){
                $zm_score=$zm['score'];
            }
            if(isset($zm['watch_info'])){
                $watch_info=$zm['watch_info'];
            }
            $zmop=CreditZmop::find()->where(['person_id'=>$order->user_id])->one();
            if(!empty($zmop)){
                $zmop->watch_info=json_encode($watch_info);
                $zmop->zm_score=$zm_score;
                if($watch_info){
                    $zmop->watch_matched=2;
                }else{
                    $zmop->watch_matched=1;
                }
                $zmop->save();
            }
        }
    }





}



