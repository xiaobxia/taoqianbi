<?php

namespace console\server\controllers;

use common\models\CreditYysQueue;
use common\models\CreditQueryLog;
use common\models\UserContact;
use common\models\UserVerification;
use Yii;
use yii\base\Exception;
use common\api\RedisQueue;
use common\models\LoanPerson;
use common\models\CreditYys;
use common\helpers\GlobalHelper;
use common\services\YysService;

class YysUserCreditController extends BaseController {

    //运营商基本提交报告 用户信息
    public function actionGetYysUserInfo() {
        echo "脚本开始运行";
        while (true) {
            try {
                $id = '';
                $redis = Yii::$app->redis;
                $redis->open();
                $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_YYS_BASIC_REPORT_USER_INFO]);

                if (!$id) {
                    sleep(2);
                    continue;
                }
                echo date('Y-m-d H:i:s') . " 开始提交ID为：{$id} 的报表,当前内存为" . memory_get_usage(true) . "\n";
                GlobalHelper::connectDb('db_kdkj');
                $queue = CreditYysQueue::findOne($id);
                if (is_null($queue)) {
                    continue;
                }
                if ($queue->current_status != 2) {
                    continue;
                }
                $loanPerson = LoanPerson::findOne($queue['user_id']);
                if (is_null($loanPerson)) {
                    throw new Exception('用户不存在,id:' . $id);
                }
                $phone = $loanPerson['phone'];
                $id_number = $loanPerson['id_number'];
                $name = $loanPerson['name'];
                $contacts = UserContact::find()->where(['user_id' => $loanPerson->id])->one();
                $contacts_arr = [];
                if (!empty($contacts)) {
                    $contacts_arr = [
                        'contact_tel' => $contacts->mobile,
                        'contact_name' => $contacts->name,
                        'contact_type' => UserContact::$relation_types_yys_map[$contacts->relation],
                        'contact_tel2' => isset($contacts->mobile_spare)?$contacts->mobile_spare:'',
                        'contact_name2' => isset($contacts->name_spare)?$contacts->name_spare:'',
                        'contact_type2' => isset(UserContact::$relation_types_yys_map[$contacts->relation_spare]) ? UserContact::$relation_types_yys_map[$contacts->relation_spare] : ""
                    ];
                }

                $service = new YysService();
                //提交用户基本信息，并获取token
                $result = $service->getBaseToken($name, $id_number, $phone, $contacts_arr);
                if ($result['code'] != 6) {
                    $queue->current_status = -1;
                    $queue->message = $result['message'];
                    $queue->save();
                    throw new Exception('token获取失败:' . $result['message']);
                }
                $token = $result['data']['token'];
                $website = $result['data']['collect_website'];
                //服务密码 登录接口
                $result = $service->postMobileInfo($token, $queue['service_code']);
                if ($result['code'] == 6) {
                    $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                    $verification->real_yys_status = 1; //是否完成流程
                    $verification->save();
                    $yys = CreditYys::find()->where(['person_id' => $loanPerson->id])->one();
                    if (is_null($yys)) {
                        $yys = new CreditYys();
                    }
                    $yys->person_id = $loanPerson->id;
                    $yys->token = $token;
                    $yys->status = CreditYys::STATUS_FALSE;
                    $yys->save();
                    $queue->message = '流程完成';
                    $queue->current_status = CreditYysQueue::STATUS_PROCESS_FINISH;
                    $queue->token = $token;
                    $queue->website = $website;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } elseif ($result['code'] == 10) {
                    $queue->message = $result['message'];
                    $queue->token = $token;
                    $queue->website = $website;
                    $queue->current_status = CreditYysQueue::STATUS_INPUT_QUERY_PWD;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } elseif ($result['code'] == 3) {
                    $queue->message = $result['message'];
                    $queue->token = $token;
                    $queue->website = $website;
                    $queue->current_status = CreditYysQueue::STATUS_INPUT_CAPTCHA;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } elseif ($result['code'] == 1) {
                    $queue->message = $result['message'];
                    $queue->token = $token;
                    $queue->website = $website;
                    $queue->current_status = CreditYysQueue::STATUS_INPUT_PHONE_PWD;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } else {
                    if ($result['code'] == -2) {
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->error_code = $result['code'];
                        $queue->message = $result['message'];
                    } else {
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->error_code = -1;
                        $queue->message = $result['message'];
                    }                   
                    if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                }


                unset($queue);
                unset($verification);
                unset($yys);
                unset($result);
                unset($service);
                unset($loanPerson);
                unset($contacts);

                echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表,当前内存为" . memory_get_usage(true) . "\n";
            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表 $id 的报表失败，\r\n错误原因为： " 
                        . $e->getMessage() . ",错误行为:" . $e->getLine() .  ",错误文件为:" . $e->getFile() . ",当前内存为" . memory_get_usage(true) . "\n";
                unset($queue);
                unset($verification);
                unset($yys);
                unset($result);
                unset($service);
                unset($e);
            }
        }
    }

    //运营商基本报告 提交验证码 
    public function actionGetYysCaptcha() {
        echo "脚本开始运行";
        while (true) {
            try {
                $id = '';
                try {
                    $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_YYS_BASIC_REPORT_CAPTCHA]);
                } catch (\Exception $e) {
                    $redis = Yii::$app->redis;
                    $redis->open();
                }

                if (!$id) {
                    sleep(2);
                    continue;
                }
                echo date('Y-m-d H:i:s') . " 开始提交ID为：{$id} 的报表\n";
                GlobalHelper::connectDb('db_kdkj');
                $queue = CreditYysQueue::findOne($id);
                if (is_null($queue)) {
                    continue;
                }
                if (!in_array($queue->current_status, [4])) {
                    continue;
                }
                $loanPerson = LoanPerson::findOne($queue['user_id']);
                if (is_null($loanPerson)) {
                    throw new Exception('用户不存在,id:' . $id);
                }
                $token = $queue->token;
                $captcha = $queue->captcha;
                //提交手机验证码
                $service = new YysService();
                $result = $service->postMobileCaptcha($token, $captcha);
                switch ($result['code']) {
                    case -1:
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                        break;
                    case -2 :
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                        break;
                    case 3:
                        $queue->current_status = CreditYysQueue::STATUS_INPUT_CAPTCHA;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                        break;
                    case 10:
                        $queue->current_status = CreditYysQueue::STATUS_INPUT_QUERY_PWD;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                        break;
                    case 6:
                        $queue->message = '流程完成';
                        $queue->current_status = CreditYysQueue::STATUS_PROCESS_FINISH;
                        if (!$queue->save()) {
                            throw new Exception('队列表保存失败,id:');
                        }
                        $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                        $verification->real_yys_status = 1;
                        $verification->save();
                        $yys = CreditYys::find()->where(['person_id' => $loanPerson->id])->one();
                        if (is_null($yys)) {
                            $yys = new CreditYys();
                        }
                        $yys->person_id = $loanPerson->id;
                        $yys->token = $token;
                        $yys->status = CreditYys::STATUS_FALSE;
                        $yys->save();
                        echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表\n";
                        break;
                    default:
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                }
            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表，错误原因为： " . $e->getMessage().$e->getLine(). $e->getFile() . "\n";
                Yii::error([
                    'error' => $e->getMessage(),
                    'id' => $id,
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                        ], 'yys');
            }
        }
    }

    //运营商基本报告 提交查询码
    public function actionGetYysQueryPwd() {
        echo "脚本开始运行";
        while (true) {
            try {
                $id = '';
                try {
                    $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_YYS_BASIC_REPORT_QUERY_PWD]);
                } catch (\Exception $e) {
                    $redis = Yii::$app->redis;
                    $redis->open();
                }

                if (!$id) {
                    sleep(2);
                    continue;
                }
                echo date('Y-m-d H:i:s') . " 开始提交ID为：{$id} 的报表\n";
                GlobalHelper::connectDb('db_kdkj');
                $queue = CreditYysQueue::findOne($id);
                if (is_null($queue)) {
                    continue;
                }
                if (!in_array($queue->current_status, [10])) {
                    continue;
                }
                $loanPerson = LoanPerson::findOne($queue['user_id']);
                if (is_null($loanPerson)) {
                    throw new Exception('用户不存在,id:' . $id);
                }
                $token = $queue->token;
                $website = $queue->website;
                $query_pwd = $queue->query_pwd;
                $pwd = $queue->service_code;
                //提交手机查询码
                $service = new YysService();
                $result = $service->postMobileQueryPwd($token, $query_pwd);
                switch ($result['code']) {
                    case -1:
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                        break;
                    case -2 :
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                        break;
                    case 3:
                        $queue->current_status = CreditYysQueue::STATUS_INPUT_QUERY_PWD;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                        break;
                    case 6:
                        $queue->message = '流程完成';
                        $queue->current_status = CreditYysQueue::STATUS_PROCESS_FINISH;
                        if (!$queue->save()) {
                            throw new Exception('队列表保存失败,id:');
                        }
                        $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                        $verification->real_yys_status = 1;
                        $verification->save();
                        $yys = CreditYys::find()->where(['person_id' => $loanPerson->id])->one();
                        if (is_null($yys)) {
                            $yys = new CreditYys();
                        }
                        $yys->person_id = $loanPerson->id;
                        $yys->token = $token;
                        $yys->status = CreditYys::STATUS_FALSE;
                        $yys->save();
                        echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表\n";
                        break;
                    default:
                        $queue->current_status = CreditYysQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        if (!$queue->save()) {
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                    }
                }
            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表，错误原因为： " . $e->getMessage() . "\n";
                Yii::error([
                    'error' => $e->getMessage(),
                    'id' => $id,
                        ], 'yys');
            }
        }
    }

    //获取用户基本报告
    public function actionGetUserBaseReport() {

        $info = CreditYys::find()->where(['status' => 0])->all();
        if (is_null($info)) {
            unset($info);
            throw new Exception('运营商数据不存在');
        }
        $service = new YysService();
        foreach ($info as $key => $value) {
            $service->getUserBaseReport($value->person_id);
        }
        
    }

}
