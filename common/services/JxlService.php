<?php

namespace common\services;

use common\helpers\Curl;
use common\models\CreditHouseFundLog;
use common\models\CreditJxlRawData;
use Yii;
use yii\base\Exception;
use yii\base\Component;
use yii\base\UserException;
use yii\helpers\Json;
use yii\helpers\VarDumper;

use common\api\RedisQueue;
use common\helpers\CommonHelper;
use common\helpers\CurlHelper;

use common\models\AccumulationFund;
use common\models\CreditJxl;
use common\models\CreditMg;
use common\models\CreditMgLog;
use common\models\CreditQueryLog;
use common\models\LoanPerson;
use common\models\UserEBusinessInfo;
use common\models\ErrorMessage;
use common\models\CreditJxlQueue;
use common\models\UserVerification;
use common\helpers\MessageHelper;
use common\services\WealidaService;
use common\base\LogChannel;
use common\models\CreditRealTime;

/**
 * 聚信立接口
 */
class JxlService extends Component
{
    /**
     * @var $clientSecret
     * 客户端秘钥
     * ------------------
     * @author Verdient。
     */
    public $clientSecret;

    /**
     * @var $orgName
     * 组织名称
     * -------------
     * @author Verdient。
     */
    public $orgName;

    /**
     * @var $tokenHours
     * token有效期
     * ----------------
     * @author Verdient。
     */
    public $tokenHours = 24;

    /**
     * @var $price
     * 价格
     * -----------
     * @author Verdient。
     */
    public $price = 0;

    /**
     * @var $houseFundPrice
     * 公积金价格
     * --------------------
     * @author Verdient。
     */
    public $houseFundPrice = 0;

    public function accessRawDataByToken($token) {
        $url = 'https://www.juxinli.com/api/access_raw_data_by_token';
        $access_token = $this->getAccessToken();
        $param = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token['data'],
            'token' => $token,
        ];
        $result = CurlHelper::curlHttp($url,'get',$param);
        if ($result['success'] == true && isset($result['raw_data']['members']['transactions'][0]['calls'])) {
            return $result['raw_data']['members']['transactions'][0]['calls'];
        }else{
            return [
                'code' => -1,
                'message' => '基本报告获取数据接口访问失败',
            ];
        }
    }

    //聚信立报告短信
    public function jxlPhoneMessage($token) {
        $url = 'https://www.juxinli.com/api/access_raw_data_by_token';
        $access_token = $this->getAccessToken();
        $param = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token['data'],
            'token' => $token,
        ];
        $result = CurlHelper::curlHttp($url,'get',$param);
        if ($result['success'] == true && isset($result['raw_data']['members']['transactions'][0]['smses'])) {
            return $result['raw_data']['members']['transactions'][0]['smses'];
        }else{
            return [
                'code' => -1,
                'message' => '基本报告获取数据接口访问失败',
            ];
        }
    }
    /**
     * @param $name
     * @param $idcard
     * @param $phone
     * @return bool|mixed
     * 获取蜜罐信息
     */
    public function getMiGuanInfo($name,$idcard,$phone) {
        $url = 'https://www.juxinli.com/api/user_grid/search';
        $access_token = $this->getAccessToken();
        if ($access_token['code'] != 0) {
            return false;
        }

        $params = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token['data'],
            'name' => $name,
            'phone' => $phone,
            'idCard' => $idcard,
        ];
        $result = CurlHelper::curlHttp($url,'get',$params);
        //        $result = $this->getCurl($url,$params);
        return $result;
    }


    /**
     * @param $name
     * @param $idcard
     * @param $phone
     * @param $person_id
     * @return array
     * 获取不良信息的接口
     */
    public function getBadInfo($name,$idcard,$phone,$person_id) {
        $mg_service = new MgService();
        $ret = $mg_service->getBadInfo($name, $idcard, $phone, $person_id);
        //Yii::warning($ret['code'], 'new_mg');
        return $ret;
    }

    /**
     * @param $ret
     * @param $person_id
     * @return bool
     * 保存蜜罐数据
     */
    public function saveMgData($ret,$person_id) {
        //保存日志
        $creditMgLog = new CreditMgLog();
        $creditMgLog->person_id = $person_id;
        $creditMgLog->type = 0;
        $creditMgLog->price = $this->price;

        $creditMgLog->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username:'auto shell';
        $creditMgLog->save();

        $result = $ret['grid_info']['result'];
        $update_time = $ret['grid_info']['update_time'];
        $creditMg = CreditMg::findLatestOne(['person_id'=>$person_id]);
        if (is_null($creditMg)) {
            $creditMg = new CreditMg();
        }
        $creditMg->person_id = $person_id;
        $creditMg->update_time = $update_time;
        $creditMg->data = json_encode($result);
        $ret = $creditMg->save();
        if (!$ret) {
            return false;
        }else{
            return $result;
        }

    }

    //-------------------------------------- 聚信立基本报告开始  ------------------------------------------------------//
    //第0步 获取支持的数据源列表（假设接口可用，因此没有调用）
    // https://www.juxinli.com/orgApi/rest/v2/orgs/{orgAccount}/datasources

    //第1步 提交用户信息，获取报表token
    public function getBaseToken($name,$id_number,$phone,$contacts_arr) {
        $url = "https://www.juxinli.com/orgApi/rest/v2/applications/".$this->orgName;
        $post_data = [
            'selected_website' => [],
            'basic_info' => [
                'name' => $name,
                'id_card_num' => $id_number,
                'cell_phone_num' => $phone,
            ],
            'contacts' => $contacts_arr,
            'skip_mobile' => false,
        ];

        $result = CurlHelper::curlHttp($url, 'jxl', json_encode($post_data), 300);
        if ($result) {
            if ($result['success'] == true) {
                $token = $result['data']['token'];
                $website = $result['data']['datasource']['website'];
                $reset_pwd_method = $result['data']['datasource']['reset_pwd_method'];
                unset($result);
                return [
                    'code' => 0,
                    'data' => [
                        'token' =>$token,
                        'website' => $website,
                        'reset_pwd_method' => $reset_pwd_method,
                    ]
                ];
            }
            else{
                $message = $result['message'];
                unset($result);
                return [
                    'code' => -1,
                    'message' => $message,
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '系统错误，请稍后重试'
            ];
        }

    }

    //第2步 提交手机号及服务密码
    public function postMobileInfo($token,$website,$phone,$pasword) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/collect/req';
        $post_data = [
            'token'=>$token,
            'account'=>$phone,
            'password'=>strval($pasword),
            'website'=>$website,
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($post_data),300);
        if ($result) {
            if ( ($result['success'] == true)) {
                $data = $result['data'];
                unset($result);
                switch($data['process_code']) {
                    case 10002:
                        return [
                            'code' => 12,
                            'data' => 'SUBMIT_CAPTCHA',
                        ];
                        break;
                    case 10008:
                        return [
                            'code' => 0,
                            'data' => '',
                        ];
                        break;
                    default:
                        return [
                            'code' => -2,
                            'message' => $data['process_code'],
                            'data' => $data['content']
                        ];
                        break;
                }


            }else{
                $message = $result['message'];
                unset($result);
                return [
                    'code' => -1,
                    'message' => $message,
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '提交手机信息接口访问失败',
            ];
        }
    }

    //新提交手机号及服务密码接口
    public function newPostMobileInfo($token,$website,$phone,$pasword) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/collect/req';
        $post_data = [
            'token'=>$token,
            'account'=>$phone,
            'password'=>strval($pasword),
            'website'=>$website,
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($post_data),300);
        if ($result) {
            if ( ($result['success'] == true)) {
                $data = $result['data'];
                unset($result);
                switch($data['process_code']) {
                    case 10002:
                        return [
                            'code' => 12,
                            'data' => $data['content'],
                        ];
                        break;
                    case 10017:
                        return [
                            'code' => 12,
                            'data' => $data['content'],
                        ];
                        break;
                    case 10022:
                        return [
                            'code' => 22,
                            'data'=>$data['content']
                        ];
                    case 10008:
                        return [
                            'code' => 0,
                            'data' => $data['content'],
                        ];
                        break;
                    case 0:
                        return [
                            'code' => -2,
                            'message' => $data['process_code'],
                            'data' => '运营商网站异常或者服务更新升级导致不可用'
                        ];
                        break;
                    default:
                        return [
                            'code' => -2,
                            'message' => $data['process_code'],
                            'data' => $data['content']
                        ];
                        break;
                }


            }else{
                $message = $result['message'];
                unset($result);
                return [
                    'code' => -1,
                    'message' => $message,
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '提交手机信息接口访问失败',
            ];
        }
    }
    //第2.1步 提交用户验证码，已知联通用户可跳过这步
    public function postMobileCaptcha($token,$website,$captcha) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/collect/req';
        $post_data = [
            'token'=>$token,
            'website'=>$website,
            'captcha'=>strval($captcha),
            'type'=>'SUBMIT_CAPTCHA',
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($post_data),300);
        if ($result) {
            if ( ($result['success'] == true)) {
                if ($result['data']['process_code'] != 10008) {
                    if ($result['data']['process_code'] == 30000) {
                        return [
                            'code' => -2,
                            'message' => $result['data']['process_code'],
                            'data' => isset($result['data']['content']) ? $result['data']['content'] : '系统异常，请稍后再试'
                        ];
                    } elseif ($result['data']['process_code'] == 0) {
                        return [
                            'code' => -2,
                            'message' => $result['data']['process_code'],
                            'data' => isset($result['data']['content']) ? $result['data']['content'] : '运营商网站异常或者服务更新升级导致不可用'
                        ];
                    } elseif ($result['data']['process_code'] == 10004) {
                        return [
                            'code' => -3,
                            'message' =>$result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    } elseif ($result['data']['process_code'] == 10017) {
                        return [
                            'code' => -3,
                            'message' =>$result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    } elseif ($result['data']['process_code'] == 10006) {
                        return [
                            'code' => -4,
                            'message' => $result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    } elseif ($result['data']['process_code'] == 10001 || $result['data']['process_code'] == 10002) {
                        return [
                            'code' => -5,
                            'message' => $result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    } else{
                        return [
                            'code' => -2,
                            'message' => $result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    }

                }else{
                    return [
                        'code' => 0,
                        'data' => '',
                    ];
                }

            }else{
                return [
                    'code' => -1,
                    'message' => $result['message'],
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '提交手机动态密码接口访问失败',
            ];
        }

    }

    //第2.12步 提交用户查询密码，仅北京移动会出现
    public function postMobileQueryPwd($token,$website,$pwd,$query_pwd) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/collect/req';
        $post_data = [
            'token'=>$token,
            'website'=>$website,
            'password'=>$pwd,
            'queryPwd'=>strval($query_pwd),
            'type'=>'SUBMIT_QUERY_PWD',
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($post_data),300);
        if ($result) {
            if ( ($result['success'] == true)) {
                if ($result['data']['process_code'] != 10008) {
                    if ($result['data']['process_code'] == 30000) {
                        return [
                            'code' => -2,
                            'message' => $result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    }
                    if ($result['data']['process_code'] == 10004) {
                        return [
                            'code' => -3,
                            'message' =>$result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    }
                    if ($result['data']['process_code'] == 10006) {
                        return [
                            'code' => -4,
                            'message' => $result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    }
                    if ($result['data']['process_code'] == 10001 || $result['data']['process_code'] == 10002) {
                        return [
                            'code' => -5,
                            'message' => $result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    }
                    if ($result['data']['process_code'] == 10023) {
                        return [
                            'code' => -5,
                            'message' => $result['data']['process_code'],
                            'data' => $result['data']['content']
                        ];
                    }
                    return [
                        'code' => -1,
                        'message' => $result['data']['content'],
                        'data' => $result['data']['content']
                    ];
                }else{
                    return [
                        'code' => 0,
                        'data' => '',
                    ];
                }

            }else{
                return [
                    'code' => -1,
                    'message' => $result['message'],
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '提交手机动态密码接口访问失败',
            ];
        }

    }
    //第2.2步 重发验证码
    public function resendMobileCaptcha($token,$website) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/collect/req';
        $post_data = [
            'token'=>$token,
            'website'=>$website,
            'type'=>'RESEND_CAPTCHA',
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($post_data),300);
        if ($result) {
            if (($result['success'] == true)) {
                if ($result['data']['process_code'] == 10002) {
                    return [
                        'code' => 0,
                        'data' => '',
                    ];
                }
                else {
                    return [
                        'code' => -1,
                        'message' =>$result['data']['content'] ?? json_encode($result),
                    ];
                }
            }
            else {
                return [
                    'code' => -1,
                    'message' => $result['message'],
                ];
            }
        }
        else {
            return [
                'code' => -1,
                'message' => '提交手机信息接口访问失败',
            ];
        }
    }

    //第3步 提交京东信息
    public function getJxlInfo($token,$account,$password) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/collect/req';
        $post_data = [
            'token'=>$token,
            'account'=>$account,
            'password'=>strval($password),
            'website'=>'jingdong',
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($post_data),120);

        if ($result) {
            if ($result['success'] == true) {
                if (  ($result['data']['process_code'] == 10008) && ($result['data']['finish'] == true) ) {
                    return [
                        'code' => 0,
                        'data' => '',
                    ];
                }else{
                    return [
                        'code' => -1,
                        'message' => $result['data']['content'],
                    ];
                }
            }else{
                return [
                    'code' => -1,
                    'message' => $result['message'],
                ];
            }

        }else{
            return [
                'code' => -1,
                'message' => '数据提交接口访问失败',
            ];
        }

    }

    //跳过当前数据接口
    public function skipCurrentProcess($token) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/collect/skip';
        $post_data = [
            'token'=>$token
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($post_data),120);
        if ($result) {
            if ( ($result['success'] == true) && ($result['data']['process_code'] == 10008) ) {
                return [
                    'code' => 0,
                    'data' => '',
                ];
            }else{
                return [
                    'code' => -1,
                    'message' => $result['data']['content'],
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '数据提交接口访问失败',
            ];
        }
    }

    //第4步 获取access token，与报表token不同
    public function getAccessToken() {
        $url = 'https://www.juxinli.com/api/access_report_token';  //接口地址
        $param = [
            'client_secret' => $this->clientSecret,
            'hours' => $this->tokenHours,
            'org_name' => $this->orgName,
        ];

        /*$curl = new Curl();
        $res = $curl->get($url, $param);
        $result = json_decode($res->body, true);*/
        $result = CurlHelper::curlHttp($url, 'get', $param);
        if ($result && $result['success']) {
            return [
                'code' => 0,
                'data' =>$result['access_token'],
            ];
        }
        return [
            'code' => -1,
            'message' => 'access token获取失败'
        ];

    }

    //第5步 通过获取基本报告信息
    public function getPersonBasicReport($access_token,$name,$phone,$id_number) {
        $url = 'https://www.juxinli.com/api/access_report_data';
        $param = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token,
            'name' => $name,
            'phone' => $phone,
            'idcard' => $id_number,
        ];
        $result = CurlHelper::curlHttp($url,'get',$param);
        if ($result) {
            if ($result['success'] == 'true') {
                return [
                    'code' => 0,
                    'data' => $result['report_data'],
                ];
            }
            else{
                return [
                    'code' => -1,
                    'message' => $result['note'],
                ];
            }
        }
        else {
            return [
                'code' => -1,
                'message' => '基本报告获取接口访问失败',
            ];
        }
    }

    public function getBasicReportForToken($access_token,$report_token) {
        $url = 'https://www.juxinli.com/api/access_report_data_by_token';
        $param = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token,
            'token' => $report_token,
        ];
        $result = CurlHelper::curlHttp($url,'get',$param);
        if ($result) {
            if ($result['success'] == 'true') {
                return [
                    'code' => 0,
                    'data' => $result['report_data'],
                ];
            }
            else {
                return [
                    'code' => -1,
                    'message' => $result['note'],
                ];
            }
        }
        else {
            return [
                'code' => -1,
                'message' => '基本报告获取接口访问失败',
            ];
        }
    }

    /**
     * 通过姓名、手机号、身份证号获得聚信立移动运营商原始数据
     * @param string $access_token
     * @param object $loanPerson
     * @return array
    **/
    public function getJxlUserOperatorsRawData($access_token,$loanPerson) {
        $url = 'https://www.juxinli.com/api/access_raw_data';
        $param = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token,
            'name' => urlencode($loanPerson->name),
            'phone' => $loanPerson->phone,
            'idcard'=> $loanPerson->id_number
        ];
        $result = CurlHelper::curlHttp($url,'get',$param);
        if ($result) {
            if ($result['success'] == 'true') {
                return [
                    'code' => 0,
                    'data' => $result['raw_data'],
                ];
            }
            else {
                //记录mongodb
                \Yii::error('user_id：'.$loanPerson->id.' jxl_raw：'.json_encode($result),'jxlrawdata');
                return [
                    'code' => -1,
                    'message' => $result['note'],
                ];
            }
        }
        else {
            //记录mongodb
            \Yii::error('user_id：'.$loanPerson->id.' jxl_raw：'.json_encode($result),'jxlrawdata');
            return [
                'code' => -1,
                'message' => '基本报告获取接口访问失败',
            ];
        }
    }

    /**
     * 通过token获得聚信立移动运营商原始数据
     * @param string $access_token
     * @param string $report_token
     * @param int $loanPerson_id
     * @return array
     **/
    public function getJxlUserOperatorsRawDataForToken($access_token,$report_token,$loanPerson_id) {
        $url = 'https://www.juxinli.com/api/access_raw_data_by_token';
        $param = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token,
            'token' => $report_token,
        ];
        $result = CurlHelper::curlHttp($url,'get',$param);
        if ($result) {
            if ($result['success'] == 'true') {
                return [
                    'code' => 0,
                    'data' => $result['raw_data'],
                ];
            }
            else {
                //记录mongodb
                \Yii::error('user_id：'.$loanPerson_id.' jxl_raw：'.json_encode($result),'jxlrawdata');
                return [
                    'code' => -1,
                    'message' => $result['note'],
                ];
            }
        }
        else {
            //记录mongodb
            \Yii::error('user_id：'.$loanPerson_id.' jxl_raw：'.json_encode($result),'jxlrawdata');
            return [
                'code' => -1,
                'message' => '基本报告获取接口访问失败',
            ];
        }
    }
    //---------------------------------------------------聚信立基本报告结束--------------------------------------------------//

    //获取用户基本报告
    public function getUserBaseReport(LoanPerson $loanPerson, $force = false) {
        $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
        if (\is_null($jxl)) {
            throw new Exception('聚信立数据不存在');
        }

        if (!$force) {
            if ($jxl->status == 1) {
                $result = \json_decode($jxl->data, true);
                return $result;
            }
        }

        $result = $this->getAccessToken();
        if ($result['code'] != 0) {
            $message = $result['message'];
            ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
            throw new Exception($message);
        }

        $access_token = $result['data'];
        $report_token = $jxl->token;
        $ret = $this->getBasicReportForToken($access_token, $report_token);
        if ($ret['code'] != 0) {
            $message = $ret['message'];
            ErrorMessage::getMessage($loanPerson->id,$ret['message'],ErrorMessage::SOURCE_JXL);
            throw new Exception($message);
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $jxl->status = CreditJxl::STATUS_TURE;
            $jxl->data = json_encode($ret['data']);
            if (!$jxl->save()) {
                throw new Exception('聚信立信息表保存失败');
            }
            /*$queryLog = new CreditQueryLog();
            $queryLog->person_id = $loanPerson->id;
            $queryLog->credit_id = CreditQueryLog::Credit_JXL;
            $queryLog->credit_type = CreditJxl::TYPE_BASE_REPORT;
            $queryLog->data = json_encode($ret['data']) ;
            $queryLog->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username : 'auto shell';
            $queryLog->price = 8;
            if (!$queryLog->save()) {
                throw new Exception('征信日志表保存失败');
            }*/
            $transaction->commit();
            $data = $ret['data'];
            unset($ret);
            unset($loanPerson);
            return $data;
        }
        catch (\Exception $e) {
            $transaction->rollBack();
            unset($jxl);
            unset($queryLog);
            unset($ret);
            unset($result);
            unset($loanPerson);

            throw new Exception($e->getMessage());
        }
    }

    //发起重置服务密码请求
    public function postResetPwdQuery($token,$account,$website,$new_pwd) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/reset/req';
        $param = [
            'token' => $token,
            'account' => $account,
            'website' => $website,
            'password' => $new_pwd,
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($param),300);
        if ($result) {
            if ( ($result['success'] == true)) {
                $data = $result['data'];
                unset($result);
                switch($data['process_code']) {
                    case 10002:
                        return [
                            'code' => 12,
                            'data' => '输入动态密码',
                        ];
                        break;
                    case 10008:
                        return [
                            'code' => 0,
                            'data' => '',
                        ];
                        break;
                    default:
                        return [
                            'code' => -2,
                            'message' => $data['process_code'],
                            'data' => $data['content']
                        ];
                        break;
                }


            }else{
                $message = $result['message'];
                unset($result);
                return [
                    'code' => -1,
                    'message' => $message,
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '重置服务密码接口不可用',
            ];
        }

    }

    //提交重置服务密码的验证码和新密码
    public function postResetPwdCaption($token,$account,$website,$captcha,$new_pwd) {
        $url = 'https://www.juxinli.com/orgApi/rest/v2/messages/reset/req';
        $param = [
            'token' => $token,
            'account' => $account,
            'website' => $website,
            'captcha' => $captcha,
            'password' => $new_pwd,
            'type' => 'SUBMIT_RESET_PWD'
        ];
        $result = CurlHelper::curlHttp($url,'jxl',json_encode($param),300);
        if ($result) {
            if ( ($result['success'] == true)) {
                $data = $result['data'];
                unset($result);
                switch($data['process_code']) {
                    case 10002:
                        return [
                            'code' => 12,
                            'data' => 'SUBMIT_CAPTCHA',
                        ];
                        break;
                    case 10008:
                        return [
                            'code' => 0,
                            'data' => '',
                        ];
                        break;
                    default:
                        return [
                            'code' => -2,
                            'message' => $data['process_code'],
                            'data' => $data['content']
                        ];
                        break;
                }


            }else{
                $message = $result['message'];
                unset($result);
                return [
                    'code' => -1,
                    'message' => $message,
                ];
            }
        }else{
            return [
                'code' => -1,
                'message' => '提交手机信息接口访问失败',
            ];
        }
    }

    /**
     * 获取公积金城市
     */
    public function getCitysList() {
        $url = "https://www.juxinli.com/orgApi/rest/v2/get_all_cities";
        $city_data = CurlHelper::curlHttp($url, 'get');
        if ($city_data && $city_data['success']) {
            return $city_data['data'];
        }

        return false;
    }

    /**
     * 获取某个城市（$region_code）公积金登录方式
     */
    public function getCitysLogin($region_code) {
        $url = "https://www.juxinli.com/orgApi/rest/v2/house_fund/get_type/$region_code";
        $login_data = CurlHelper::curlHttp($url, 'get');
        if ($login_data && $login_data['success']) {
            return $login_data['data'][0];
        }

        return false;
    }

    /**
     * 返回全部（缓存中）支持城市的公积金登录方式
     */
    public function getHouseFundMethods() {
        $json = RedisQueue::get([
            'key' => RedisQueue::STR_JXL_FUND
        ]);
        if (! $json) {
            return false;
        }

        return \json_decode($json, true);
    }

    /**
     * 获取全部的城市登录方式
     * @tutorial 注意超时控制
     * @return array
     */
    public function getCitysLoginMethods() {
        $in_console = \yii::$app instanceof \yii\console\Application;

        $ret = [];

        $province_data = $this->getCitysList();
        if (\is_array($province_data)) {
            foreach($province_data as $_province) {
                $province = [];
                $province['province_name'] = $_province['name'];

                foreach($_province['sub'] as $_city) {
                    if (\strpos($_city['name'], '省直') !== false) {
                        if ($in_console) {
                            CommonHelper::info( \sprintf('[skip1] %s - %s, %s', $_province['name'], $_city['name'], $_city['fullcode']) );
                        }
                        continue;
                    }

                    if ($in_console) {
                        CommonHelper::info( \sprintf('[get_data]%s - %s, %s', $_province['name'], $_city['name'], $_city['fullcode']) );
                    }
                    $city_data = $this->getCitysLogin($_city['fullcode']);
                    if (\is_array($city_data) && (!empty($city_data['tabs']))) {
                        $city = [];

                        $city['name'] = $_city['name'];
                        foreach($city_data['tabs'] as $tab) {
                            $login_info = [];

                            $login_info['sort'] = $tab['sort'];
                            $login_info['type'] = $tab['type'];
                            $login_info['website'] = $tab['website'];
                            $login_info['desc'] = $tab['descript'];
                            foreach ($tab['field'] as $_param_idx => $_param_info) {
                                $login_info['field'][$_param_idx]['name'] = $_param_info['parameter_name'];
                                $login_info['field'][$_param_idx]['code'] = $_param_info['parameter_code'];
                                $login_info['field'][$_param_idx]['type'] = $_param_info['parameter_type'];
                            }

                            $city['login_info'][] = $login_info;
                        }

                        $province['citys'][] = $city;
                    }
                    else {
                        if ($in_console) {
                            CommonHelper::info( \sprintf('[skip2] %s - %s, %s', $_province['name'], $_city['name'], $_city['fullcode']) );
                        }
                    }
                }

                if (isset($province['citys']) && count($province['citys']) > 0) {
                    $ret[] = $province;
                }
            }
        }

        return $ret;
    }

    /**
     * 提交公积金采集申请。
     * @param string website	【必填】数据源名称【英文缩写】
     * @param string sort	【必填】数据源编码
     * @param string type	【必填】采集方式
     *
     * @param string id_card_num	【必填】用户身份证号码
     * @param string cell_phone_num	【必填】用户手机号码
     * @param string name	【必填】用户姓名
     *
     * @param string account	【动态参数】数据源账号【公积金账号】
     * @param string password	【动态参数】公积金密码
     *
     * @param string uid	【非必填】如果需要推送报告时推送机构账号系统中的ID需要设置此字段类型为string
     * @return bool
     */
    public function submitHouseFundReq(array $params) {
        foreach(['website', 'sort', 'type', 'id_card_num', 'cell_phone_num', 'name'] as $_key) {
            if (empty($params[ $_key ])) {
                throw new UserException("请求参数{$_key}缺失");
            }
        }

        $user_id = \yii::$app->user->identity->id;

        $record = new AccumulationFund();
        $record->status = AccumulationFund::STATUS_INIT;
        $record->token = '';
        $record->user_id = $user_id;
        $record->channel = AccumulationFund::CHANNEL_JXL;
        $record->params = \json_encode($params);
        $record->city = $params['website'];

        $transaction = AccumulationFund::getDb()->beginTransaction();
        if($record->save() && RedisQueue::push([RedisQueue::LIST_HOUSEFUND_TOKEN, $record->id])) {
            $transaction->commit();
            return true;
        } else {
            $transaction->rollBack();
            return false;
        }
    }

    /**
     * 获取公积金报告
     * @param AccumulationFund $record
     * @return bool IO操作失败返回false
     * @throws \Exception 不符合要求抛出异常
     */
    public function getHouseFundReport(AccumulationFund $record) {

        if (empty($record->token) || ($record->created_at + 7776000) < \time()) {
            $record->status = AccumulationFund::STATUS_INIT;
            $record->save();
            //Yii::error(\sprintf('公积金%s未获取token', $record->id), 'get_house_fund_report_failed');
            return false;
        }

        $result = $this->getHouseFundReportData($record->token);
        if (
            $result
            && $result['success']
            && isset($result['raw_data']['members']['status'])
            && \strtolower($result['raw_data']['members']['status']) == 'success' )
        {
            if ( isset($result['raw_data']['members']['transactions']) && count($result['raw_data']['members']['transactions']) > 0 ) {
                $credit_house_fund_log = new CreditHouseFundLog();
                $credit_house_fund_log->person_id = $record->user_id;
                $credit_house_fund_log->token = $record->token;
                $credit_house_fund_log->type = 0;
                $credit_house_fund_log->price = $this->houseFundPrice;
                $credit_house_fund_log->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username:'auto shell';

                $credit_house_fund_log->save();

                return $result['raw_data']['members']['transactions'][0];
            }
            else {
                $tmp = $result['raw_data']['members'];
                throw new \Exception($tmp['error_msg'], $tmp['error_code']);
            }
        }

        return false;

    }

    /**
     * 请求公积金报告
     * @param $token
     * @return bool|mixed
     */
    public function getHouseFundReportData($token)
    {
        $access_token = $this->getAccessToken();
        if ($access_token['code'] != 0) {
            return false;
        }

        $url = 'https://www.juxinli.com/api/access_house_fund_raw_data';
        $params = [
            'client_secret' => $this->clientSecret,
            'access_token' => $access_token['data'],
            'token' => $token,
        ];

        return CurlHelper::curlHttp($url, 'GET', $params, 120);
    }

    /**
     * 获取用户token
     * @param AccumulationFund $recode
     * @return bool
     * @throws \Exception
     */
    public function getUserToken(AccumulationFund $record)
    {
        $response = $this->getUserTokenData($record->params);
        if (!$response) {
            Yii::warning(\sprintf('%s获取token失败：%s', $record->id, CurlHelper::$err_msg), LogChannel::CREDIT_JXL);
            return false;
        }
        //Yii::warning(\sprintf('%s获取token：%s', $record->id, json_encode($response, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)), LogChannel::CREDIT_JXL);
        if (isset($response['success'])
            && isset($response['data'])
            && isset($response['data']['token'])
            && strlen($response['data']['token']) > 0 ) {

            if ($response['data']['type'] != 'ERROR' || $response['data']['content'] == '当前网站已经采集完成,请不要重复采集！') {
                return $response['data']['token'];
            } else {
                throw new \Exception($response['data']['content']);
            }
        } elseif (isset($response['data']['type']) && $response['data']['type'] == 'ERROR') {
            throw new \Exception($response['data']['content']);
        } else {
            throw new \Exception('获取token失败');
        }
    }

    /**
     * 请求用户token数据
     * @param $params
     * @return bool|mixed
     */
    public function getUserTokenData($params)
    {
        $url = "https://www.juxinli.com/orgApi/rest/v2/house_fund/{$this->orgName}";
        return CurlHelper::curlHttp($url, AccumulationFund::CHANNEL_JXL, $params, 120);
    }


    private $username = "";
    private $password = "";
    const STATE_INIT = 0;
    const STATE_RESTART = 1;
    const STATE_SERVICE_PWD_SUBMITTED = 11;
    const STATE_CAPTCHA_REQUIRED = 21;
    const STATE_CAPTCHA_SUBMITTED = 22;
    const STATE_CAPTCHA_ERROR = 23;
    const STATE_CAPTCHA_RESEND = 24;
    const STATE_QUERY_PWD_REQUIRED = 31;
    const STATE_QUERY_PWD_SUBMITTED = 32;
    const STATE_QUERY_PWD_SUBMITTED_ERROR = 33;
    const STATE_COLLECT_FINISHED = 41;
    const STATE_COLLECT_FAILED = 42;
    const STATE_DATA_READY = 43;

    //jxl
    const PROCESS_CODE_INPUT_CAPTCHA_AGAIN = 10001;
    const PROCESS_CODE_INPUT_CAPTCHA = 10002;
    const PROCESS_CODE_PASSWD_WRONG = 10003;
    const PROCESS_CODE_CAPTCHA_WRONG = 10004;
    const PROCESS_CODE_CAPTCHA_EXPIRE = 10006;
    const PROCESS_CODE_PASSWD_INVALID = 10007;
    const PROCESS_CODE_COLLECT_FINISH = 10008;
    const PROCESS_CODE_INFO_INVALID = 10009;
    const PROCESS_CODE_NEW_PASSWD_INVALID = 10010;
    const PROCESS_CODE_NEED_CAPTCHA_PASSWD = 10017;
    const PROCESS_CODE_NEED_CAPTCHA_PASSWD_TO_SEND = 10018;
    const PROCESS_CODE_QUERY_PASSWD = 10022;
    const PROCESS_CODE_QUERY_PASSWD_INVALID = 10023;
    const PROCESS_CODE_RESET_PASSWD_SUCCESS = 11000;
    const PROCUSS_CODE_ACCESS_TOKEN_FAILED = 20007;
    const PROCESS_CODE_COLLECT_FAILED = 30000;
    const PROCESS_CODE_RESET_PASSWD_FAILED = 31000;
    const PROCESS_CODE_COLLECT_TIMEOUT = 0;
    const PROCESS_CODE_COLLECT_NO_DATA = 31204;
    const PROCESS_CODE_RAW_DATA_SUCCESS = 31200;

    public static $query_state = [
        self::STATE_INIT => "查询已初始化",
        self::STATE_RESTART => "流程重新开始",
        self::STATE_SERVICE_PWD_SUBMITTED => "已提交服务密码",
        self::STATE_CAPTCHA_REQUIRED => "需要验证码",
        self::STATE_CAPTCHA_SUBMITTED => "验证码已提交",
        self::STATE_CAPTCHA_ERROR => "验证码错误或超时",
        self::STATE_CAPTCHA_RESEND => "已重发验证码",
        self::STATE_QUERY_PWD_REQUIRED => "需要输入查询密码",
        self::STATE_QUERY_PWD_SUBMITTED => "查询密码已提交",
        self::STATE_QUERY_PWD_SUBMITTED_ERROR => "查询密码错误",
        self::STATE_COLLECT_FINISHED => "完成账户采集流程",
        self::STATE_COLLECT_FAILED => "采集流程失败",
        self::STATE_DATA_READY => "数据可用",
    ];

    public function getJxlState($processCode){
        switch ($processCode)
        {
            case self::PROCESS_CODE_COLLECT_FINISH:
                $state = self::STATE_COLLECT_FINISHED;
                break;

            case self::PROCESS_CODE_INPUT_CAPTCHA:
                $state = self::STATE_CAPTCHA_REQUIRED;
                break;

            case self::PROCESS_CODE_NEED_CAPTCHA_PASSWD:
                $state = self::STATE_CAPTCHA_REQUIRED;
                break;

            case self::PROCESS_CODE_NEED_CAPTCHA_PASSWD_TO_SEND:
                $state = self::STATE_CAPTCHA_REQUIRED;
                break;

            case self::PROCESS_CODE_QUERY_PASSWD:
                $state = self::STATE_QUERY_PWD_REQUIRED;
                break;

            case self::PROCESS_CODE_COLLECT_TIMEOUT:
                $state = self::STATE_RESTART;
                break;

            default:
                $state = self::STATE_RESTART;
                break;
        }

        return $state;
    }

    public function getToken($force = false){
        /** @var WealidaService $service */
        $service = Yii::$container->get('wealidaService');
        $result = $service->getToken($this->username, $this->password, $this->tokenHours, $force);
        return $result;
    }


    public function getCarrierOpenIdNew($name, $id_card, $mobile, $service_password = "", $options = []){
        $orgAccount = $this->orgName;
        $url = "https://www.juxinli.com/orgApi/rest/v3/applications/{$orgAccount}";
        $id_card = str_replace('x', 'X', $id_card);
        $post_data = [
            'selected_website'=>[],
            'skip_mobile'=>false,
            'basic_info'=>[
                'name' => $name,
                'id_card_num' => $id_card,
                'cell_phone_num' => $mobile,
                'home_tel'=>$options['home_tel']
            ],
            'contacts'=>[],
            'uid'=>''
        ];

        $result = CurlHelper::curlHttp($url, 'JXL', json_encode($post_data), 300);
        if(!empty($result)){
            if($result['code'] == 65557 && $result['success']){
                return [
                    'code' => 0,
                    'open_id' => $result['data']['token'],
                    'website' => $result['data']['datasource']['website'],
                    'message' => isset($result['message'])?$result['message']:'',
                ];
            }
            return [
                'code' => $result['code'],
                'message' => isset($result['message'])?$result['message']:'',
            ];
        }else{
            return [
                'code' => -1,
                'message' => 'open_id获取失败，请求发送失败',
            ];
        }
    }


    /**
     *
     * function Query blacklist info.
     *
     */
    public function queryBlacklist($token, $name, $id_card, $mobile){
        $url = "https://credit.wealida.com/blacklist/search/single";
        //$url = "https://credit-test.wealida.com/blacklist/search/single";
        $id_card = str_replace('x', 'X', $id_card);
        $param = [
            'token' => $token,
            'name' => $name,
            'id_card' => $id_card,
            'mobile' => $mobile,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $param, 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'is_in' => $result['data']['is_in'],
                    'message' => $result['msg'],
                ];
            }
            return [
                'code' => $result['code'],
                'message' =>$result['msg'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '黑名单信息获取失败，请求发送失败',
            ];
        }
    }

    /**
     * @name 获取openid
     * @param $token
     * @param $name
     * @param $id_card
     * @param $mobile
     * @param string $service_password
     * @param array $options
     * @return array
     *
     */
    public function getCarrierOpenId($token, $name, $id_card, $mobile, $service_password = "", $options = []){
        $url = "https://credit.wealida.com/telecom/collect/open-id";
//         $url = "https://credit-test.wealida.com/telecom/collect/open-id";
        $id_card = str_replace('x', 'X', $id_card);
        $post_data = [
            'token' => $token,
            'name' => $name,
            'id_card' => $id_card,
            'mobile' => $mobile,
            'service_password' => $service_password,
        ];
        if(!empty($options)){
            $post_data['options'] = $options;
        }

        $result = CurlHelper::curlHttp($url, 'wealida', http_build_query($post_data), 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'open_id' => $result['data']['open_id'],
                    'message' => isset($result['msg'])?$result['msg']:'',
                ];
            }
            return [
                'code' => $result['code'],
                'message' => isset($result['msg'])?$result['msg']:'',
            ];
        }else{
            return [
                'code' => -1,
                'message' => 'open_id获取失败，请求发送失败',
            ];
        }
    }

    /**
     *
     * @param $open_id
     * @param $service_password
     * @return array
     *
     */
    public function submitServicePassword($open_id, $service_password){
        $url = "https://credit.wealida.com/telecom/collect/service-password";
//         $url = "https://credit-test.wealida.com/telecom/collect/service-password";
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
            'service_password' => $service_password,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $post_data, 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'message' => "服务密码提交成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' =>$result['msg'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '运营商服务密码提交失败，请求发送失败',
            ];
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
                'message' =>$result['data']['content'] ?? json_encode($result),
            ];
        }else{
            return [
                'code' => -1,
                'message' => '运营商服务密码提交失败，请求发送失败',
            ];
        }
    }

    /**
     *
     * function Submit carrier query password
     * comment Designed for user whose mobile phone number belongs to Beijing Mobile Communications Corporation.
     *
     */
    public function submitQueryPassword($open_id, $query_password){
        $url = "https://credit.wealida.com/telecom/collect/query-password";
//         $url = "https://credit-test.wealida.com/telecom/collect/query-password";
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
            'query_password' => $query_password,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $post_data, 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'message' => "查询密码提交成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' =>$result['msg'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '运营商查询密码提交失败，请求发送失败',
            ];
        }
    }

    public function submitQueryPasswordNew($loanPerson,$open_id,$service_code,$website,$query_password){
        $url = "https://www.juxinli.com/orgApi/rest/v2/messages/collect/req";
        $phone = $loanPerson->phone;
        $post_data = [
            'account' => $phone,
            'token' => $open_id,
            'password' => $service_code,
            'website' => $website,
            'queryPwd' => strval($query_password),
            'type' => 'SUBMIT_QUERY_PWD',
        ];
        $result = CurlHelper::curlHttp($url, 'JXL', json_encode($post_data), 300);
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
                'message' =>$result['data']['content'] ?? json_encode($result),
            ];
        }else{
            return [
                'code' => -1,
                'message' => '运营商服务密码提交失败，请求发送失败',
            ];
        }
    }

    /**
     * function Submit mobile captcha
     */
    public function submitCaptcha($open_id, $captcha){
        $url = "https://credit.wealida.com/telecom/collect/captcha";
//         $url = "https://credit-test.wealida.com/telecom/collect/captcha";
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
            'captcha' => $captcha,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $post_data, 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'message' => "验证码提交成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' =>$result['msg'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '手机验证码提交失败，请求发送失败',
            ];
        }
    }

    public function submitCaptchaNew($loanPerson,$open_id,$service_code,$website,$captcha){
        $url = "https://www.juxinli.com/orgApi/rest/v2/messages/collect/req";
        $phone = $loanPerson->phone;
        $post_data = [
//            'account' => $phone,
            'token' => $open_id,
//            'password' => $service_code,
            'website' => $website,
            'captcha' => strval($captcha),
            'type' => 'SUBMIT_CAPTCHA',
        ];
        $result = CurlHelper::curlHttp($url, 'JXL', json_encode($post_data), 300);
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
                'message' =>$result['data']['content'] ?? json_encode($result),
            ];
        }else{
            return [
                'code' => -1,
                'message' => '运营商服务密码提交失败，请求发送失败',
            ];
        }
    }

    /**
     *
     * function Resend mobile captcha
     *
     */
    public function resendCaptcha($open_id){
        $url = "https://credit.wealida.com/telecom/collect/resend-captcha";
//         $url = "https://credit-test.wealida.com/telecom/collect/resend-captcha";
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $post_data, 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'message' => "验证码重发成功",
                ];
            }
            return [
                'code' => $result['code'],
                'message' =>$result['msg'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '手机验证码重发失败，请求发送失败',
            ];
        }
    }

    /**
     *
     * function Wealida query state.
     *
     */
    public function getState($open_id){
        $url = "https://credit.wealida.com/telecom/collect/get-state";
//         $url = "https://credit-test.wealida.com/telecom/collect/get-state";
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $post_data, 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'state' => $result['data']['state'],
                    //'message' => self::$query_state[$result['data']['state']],
                    'message' => $result['data']['message'],
                ];
            }
            return [
                'code' => $result['code'],
                'message' =>$result['msg'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '状态查询失败，请求发送失败002',
            ];
        }
    }


    public function postJxlCaptcha($queue){
        $url = "https://www.juxinli.com/orgApi/rest/v2/messages/collect/req";
//         $url = "https://credit-test.wealida.com/telecom/collect/service-password";
        $open_id = $queue->token;
        $service_code = $queue->service_code;
        $website = $queue->website;
        $loanPerson = LoanPerson::findOne($queue->user_id);
        $phone = $loanPerson->phone;
        $post_data = [
            'account' => $phone,
            'token' => $open_id,
            'password' => $service_code,
            'website' => $website,
            'captcha' => '',
            'type' => '',
        ];
        $result = CurlHelper::curlHttp($url, 'JXL', json_encode($post_data), 300);
        return $result;
    }

    /**
     * 获取聚信立认证状态
     * @param CreditJxlQueue $queue
     * @return array
     */
    public function getStatus(CreditJxlQueue $queue){
        $open_id = $queue->token;

        $code = -1;
        $process_code = 0;
        if ($queue->process_code){
            $code = 0;
            $process_code = $this->getJxlState($queue->process_code);
        }
        $result = [
            'code' => $code,
            'state' => $process_code,
            'message' => '',

        ];

        if(!empty($result)){
            if($result['code'] == 0){
                if(!empty($queue)){
                    switch ($result['state']) {
                        case 0:
                            break;
                        case 1:
                            $queue->current_status = -1;
                            break;
                        case 11:
                            $queue->current_status = 2;
                            break;
                        case 21:
                            $queue->current_status = 3;
                            break;
                        case 22:
                            $queue->current_status = 4;
                            break;
                        case 23:
                            $queue->current_status = -4;
                            break;
                        case 24:
                            $queue->current_status = 14;
                            break;
                        case 31:
                            $queue->current_status = 10;
                            break;
                        case 32:
                            $queue->current_status = 11;
                            break;
                        case 33:
                            $queue->current_status = 10;
                            break;
                        case 41:
                            $queue->current_status = 6;
                            break;
                        case 42:
                            $queue->current_status = -1;
                            break;
                        case 43:
                            $queue->current_status = 6;
                            break;
                    }
                    $queue->message = $result['message'];
                    if(empty($queue->message)){
                        $queue->message = self::$query_state[$result['state']];
                    }
                    if (!$queue->save()) {
                        ErrorMessage::getMessage($queue->user_id, 'CreditJxlQueue队列表状态更新失败', ErrorMessage::SOURCE_JXL);
                    }
                    if($queue->current_status == 6){
                        $verification = UserVerification::find()->where(['user_id' => $queue->user_id])->one();
                        $verification->real_jxl_status = 1;
                        $verification->save();
                    }
                    return [
                        'code' => $result['code'],
                        'status' => $queue->current_status,
                        'message' => $queue->message,
                    ];
                } else {
                    return [
                        'code' => 1000,
                        'message' => "未找到对应CreditJxlQueue",
                    ];
                }

            }
            return [
                'code' => $result['code'],
                'message' =>$result['message'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '状态查询失败，请求发送失败001',
            ];
        }
    }


    /**
     *
     * function Get raw data
     *
     */
    public function getRawData($open_id){
        $url = "https://credit.wealida.com/telecom/data/raw-data";
//         $url = "https://credit-test.wealida.com/telecom/data/raw-data";
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $post_data, 300);
        if(!empty($result)){
            if($result['code'] == 0){
                return [
                    'code' => $result['code'],
                    'raw_data' => $result['data'],
                    'message' => $result['msg'],
                ];
            }
            return [
                'code' => $result['code'],
                'message' =>$result['msg'],
            ];
        }else{
            return [
                'code' => -1,
                'message' => '原始数据查询失败，请求发送失败',
            ];
        }
    }

    /**
     *
     * function Get report
     *
     */
    public function getReport($open_id){
        $url = "https://credit.wealida.com/telecom/data/report";
//         $url = "https://credit-test.wealida.com/telecom/data/report";
        $post_data = [
            'username' => $this->username,
            'open_id' => $open_id,
        ];
        $result = CurlHelper::curlHttp($url, 'wealida', $post_data, 300);
        if (empty($result)) {
            return [
                'code' => -1,
                'message' => '运营商报告查询失败，请求发送失败',
            ];
        }

        if ($result['code'] == 0) {
            return [
                'code' => $result['code'],
                'report' => $result['data'],
                'message' => $result['msg'],
            ];
        }

        return [
            'code' => $result['code'],
            'message' => $result['msg'],
        ];
    }

    /**
     *
     * function Get call record. Mapping to function accessRawDataByToken().
     *
     */
    public function getCallRecord($open_id){
        $result = $this->getRawData($open_id);
        if($result['code'] != 0){
            return $result;
        }
        $callRecord = $result['raw_data']['transactions'][0]['calls'];
        return $callRecord;
    }


    /**
     *
     * function Get basic personal report. Mapping to function getBasicReportForToken().
     *
     */
    public function getBasicPersonReport($open_id){
        $result = $this->getReport($open_id);
        if($result['code'] != 0){
            return $result;
        }
        $report['contact_list'] = $result['report']['contact_list'];
        $report['data_source'] = [];
        $report['behavior_check'] = $result['report']['behavior_check'];
        $report['collection_contact'] = $result['report']['collection_contact'];
        $report['trip_consume'] = [];
        $report['ebusiness_expense'] = $result['report']['ebusiness_expense'];

        $check_points = $result['report']['application_check'][1]['check_points'];
        $person['province'] = $check_points['province'];
        $person['city'] = $check_points['city'];
        $person['gender'] = $check_points['gender'];
        $person['age'] = $check_points['age'];
        $person['sign'] = "暂无";
        $person['state'] = $check_points['province'];
        $person['status'] = true;
        $person['real_name'] = $result['report']['application_check'][0]['check_points']['key_value'];
        $person['region'] = $check_points['region'];
        $person['id_card_num'] = $check_points['key_value'];
        $report['person'] = $person;

        $report['main_service'] = $result['report']['main_service'];
        $report['contact_region'] = $result['report']['contact_region'];

        // $sourceData = $result['result']['application_check'];
        // switch ($sourceData['app_point']) {
        //     case 'user_name':
        //         # code...
        //         break;

        //     default:
        //         # code...
        //         break;
        // }

        $report['application_check'] = $result['report']['application_check'];
        $report['deliver_address'] = $result['report']['deliver_address'];

        $tmp['token'] = "已移除";
        $tmp['updt'] = [ 'date' => $result['report']['report']['update_time']];
        $tmp['id'] = "已移除";
        $tmp['verson'] = "已移除";
        $report['report'] = $tmp;

        $report['trip_info'] = $result['report']['trip_info'];
        //$report['_id'] = $result['report']['_id'];
        $report['cell_behavior'] = $result['report']['cell_behavior'];

        $result['code'] = 0;
        $result['report'] = $report;

        return $result;
    }

    /**
     * 不同渠道获取报告
     * @param LoanPerson $loanPerson
     * @param CreditJxlQueue $queue
     */
    public function getUserReport(LoanPerson $loanPerson, CreditJxlQueue $queue, $force = false)
    {
        $jxl = CreditJxl::findLatestOne(['person_id'=>$loanPerson->id]);
        if(is_null($jxl)){
            unset($jxl);
            throw new Exception('聚信立数据不存在');
        }

        if (!$force) {
            if($jxl->status == 1){
                $result = json_decode($jxl->data,true);
                unset($jxl);
                return $result;
            }
        }

        $open_id = $jxl->token;

        if (CreditJxlQueue::CHANNEL_JSQB == $queue->channel) {
            /** @var JsqbService $service */
            $service = Yii::$app->jxlService;
        } else {
            /** @var JxlService $service */
            $service = Yii::$app->jxlService;
        }

        $report = $service->getReport($open_id);
        if ($report['code'] != 0) {
            $message = isset($report['message']) ? $report['message'] : print_r($report, true);
            ErrorMessage::getMessage($loanPerson->id, $report['message'], ErrorMessage::SOURCE_JXL);
            throw new Exception($message);
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $jxl->status = CreditJxl::STATUS_TURE;
            $jxl->data = json_encode($report['report']);
            if(!$jxl->save()){
                throw new Exception('聚信立信息表保存失败');
            }
            /*$queryLog = new CreditQueryLog();
            $queryLog->person_id = $loanPerson->id;
            $queryLog->credit_id = CreditQueryLog::Credit_JXL;
            $queryLog->credit_type = CreditJxl::TYPE_BASE_REPORT;
            $queryLog->data = json_encode($report['report']) ;
            $queryLog->admin_username = isset(Yii::$app->user) ? Yii::$app->user->identity->username : 'auto shell';
            $queryLog->price = 8;
            if(!$queryLog->save()){
                throw new Exception('征信日志表保存失败');
            }*/
            $transaction->commit();
            $data = $report['report'];
            unset($report);
            unset($loanPerson);
            return $data;
        }catch (\Exception $e){
            $transaction->rollBack();
            unset($jxl);
            unset($queryLog);
            unset($report);
            unset($result);
            unset($loanPerson);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取原始数据
     * @param $user_id
     * @param CreditJxlQueue $queue
     * @param bool $force
     * @return mixed
     * @throws Exception
     */
    public function getUserRawData($user_id, $force = false)
    {
        $queue = CreditJxlQueue::findOne(['user_id' => $user_id, 'current_status' => CreditJxlQueue::STATUS_PROCESS_FINISH]);
        if (is_null($queue)) {
            unset($queue);
            throw new Exception('聚信立数据不存在');
        }

        if (!$force) {
            if(!empty($data = CreditJxlRawData::findOne(['user_id' => $user_id]))){
                $result = json_decode($data->data,true);
                unset($jxl);
                return $result;
            }
        }

        $open_id = $queue->token;

        if (CreditJxlQueue::CHANNEL_JSQB == $queue->channel) {
            /** @var JsqbService $service */
            $service = Yii::$app->jxlService;
        } else {
            /** @var JxlService $service */
            $service = Yii::$app->jxlService;
        }

        //原始数据
        $raw_data = $service->getRawData($open_id);

        if ($raw_data['code'] != 0) {
            $message = $raw_data['message'];
            ErrorMessage::getMessage($user_id, $raw_data['message'], ErrorMessage::SOURCE_JXL);
            unset($raw_data);
            unset($jxl);
            throw new Exception($message);
        }

        try{
            $raw_data_model = CreditJxlRawData::findOne(['user_id' => $user_id]) ? : new CreditJxlRawData();
            $raw_data_model->user_id = $user_id;
            $raw_data_model->data = json_encode($raw_data['raw_data'], JSON_UNESCAPED_UNICODE);

            if(!$raw_data_model->save()){
                unset($raw_data);
                unset($jxl);
                throw new Exception('聚信立原始数据保存失败');
            }

            return $raw_data['raw_data'];
        }catch (\Exception $e){
            unset($raw_data);
            unset($jxl);
            throw new Exception($e->getMessage());
        }
    }
    /**
     * 获取手机运营商的状态
     */
    public static function getJxlQueryStatus($user_id = ''){
        if(!$user_id){
            return false;
        }
        $stauts = CreditJxlQueue::find()
            ->where(['user_id'=>$user_id])->select(['current_status'])
            ->orderBy(['id'=>'Desc'])->one();
        if($stauts && ($stauts->current_status < 0)){//认证失败
            return UserService::USER_AUTH_FAIL;
        }
        if($stauts && ($stauts->current_status > 0) &&
            $stauts->current_status != CreditJxlQueue::STATUS_PROCESS_FINISH
        ){//认证中 状态不等于完成
            return UserService::USER_AUTH_DONING;
        }
        if(empty($stauts)){
            return UserService::USER_AUTH_NOMORE;
        }
    }

    /**
     * 获得用户运营商原始数据
    **/
    public function getUserOperatorsRawData(LoanPerson $loanPerson, $force = false) {
        $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
        if (\is_null($jxl)) {
            throw new Exception('聚信立数据不存在');
        }

        if (!$force) {
            if ($jxl->raw_status == 1) {
                $result = \json_decode($jxl->raw_data, true);
                return $result;
            }
        }

        $result = $this->getAccessToken();
        if ($result['code'] != 0) {
            $message = $result['message'];
            ErrorMessage::getMessage($loanPerson->id, '用户运营商原始数据：'.$result['message'], ErrorMessage::SOURCE_JXL);
            throw new Exception($message);
        }

        $access_token = $result['data'];
        //通过姓名、手机号、身份证号获得运营商原始数据
//        $ret = $this->getJxlUserOperatorsRawData($access_token, $loanPerson);
        //通过token获得运营商原始数据
        $report_token = $jxl->token;
        $ret = $this->getJxlUserOperatorsRawDataForToken($access_token, $report_token, $loanPerson->id);
        if ($ret['code'] != 0) {
            $message = $ret['message'];
            ErrorMessage::getMessage($loanPerson->id, '用户运营商原始数据：'.$ret['message'],ErrorMessage::SOURCE_JXL);
            throw new Exception($message);
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $jxl->raw_status = CreditJxl::RAW_STATUS_TRUE;
            //raw_data只保存calls，数据量过大，别的不保存
            $jxl->raw_data = json_encode($ret['data']['members']['transactions'][0]['calls']);
            if (!$jxl->save()) {
                throw new Exception('聚信立信息表保存失败');
            }
            $transaction->commit();
            $data = $ret['data'];
            unset($ret);
            unset($loanPerson);
            return $data;
        }
        catch (\Exception $e) {
            $transaction->rollBack();
            unset($jxl);
            unset($ret);
            unset($result);
            unset($loanPerson);

            throw new Exception($e->getMessage());
        }
    }
}
