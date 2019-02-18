<?php
namespace console\server\controllers;

use common\models\CreditJxlQueue;
use common\models\UserContact;
use common\models\UserVerification;
use Yii;
use yii\base\Exception;
use common\api\RedisQueue;
use common\models\LoanPerson;
use common\models\LoanPersonInfo;
use common\models\CreditJxl;
use common\helpers\GlobalHelper;
use common\models\ErrorMessage;

class UserCreditController extends BaseController
{
    //聚信立基本提交报告 用户信息
    public function actionGetJxlUserInfo()
    {
        echo "脚本开始运行";

        pcntl_signal(SIGUSR1, function(){
            echo date('Y-m-d H:i:s') . " 检测到结束信号，关闭当前脚本\n";
            exit;
        });


        while (true) {
            try {
                pcntl_signal_dispatch();
                $id = '';
                $redis = Yii::$app->redis;
                $redis->open();
                $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_USER_INFO]);

                if (!$id) {
                    sleep(2);
                    continue;
                }
                echo date('Y-m-d H:i:s') . " 开始提交ID为：{$id} 的报表,当前内存为" . memory_get_usage(true) . "\n";
                GlobalHelper::connectDb('db_kdkj');
                $queue = CreditJxlQueue::findOne($id);
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
                    $contacts_arr[] = [
                        'contact_tel' => $contacts->mobile,
                        'contact_name' => $contacts->name,
                        'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation]
                    ];
                    if (!empty($contacts->mobile_spare) && !empty($contacts->name_spare)) {
                        $contacts_arr[] = [
                            'contact_tel' => $contacts->mobile_spare,
                            'contact_name' => $contacts->name_spare,
                            'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation_spare]
                        ];
                    }

                }
                $service = Yii::$app->jxlService;
                //提交用户基本信息，并获取token
                $result = $service->getBaseToken($name, $id_number, $phone, $contacts_arr);
                if ($result['code'] != 0) {
                    $queue->current_status = -1;
                    $queue->message = $result['message'];
                    $queue->save();
                    ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                    throw new Exception('token获取失败:' . $result['message']);
                }
                $token = $result['data']['token'];
                $website = $result['data']['website'];
                $result = $service->newPostMobileInfo($token, $website, $phone, $queue['service_code']);
                if ($result['code'] == 0) {
                    $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                    $verification->real_jxl_status = 1;
                    $verification->save();
                    $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                    if (is_null($jxl)) {
                        $jxl = new CreditJxl();
                    }
                    $jxl->person_id = $loanPerson->id;
                    $jxl->token = $token;
                    $jxl->status = CreditJxl::STATUS_FALSE;
                    $jxl->save();
                    $queue->message = '流程完成';
                    $queue->current_status = CreditJxlQueue::STATUS_PROCESS_FINISH;
                    $queue->token = $token;
                    $queue->website = $website;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } elseif ($result['code'] == 12) {
                    $queue->message = $result['data'];
                    $queue->token = $token;
                    $queue->website = $website;
                    $queue->current_status = CreditJxlQueue::STATUS_INPUT_CAPTCHA;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } elseif ($result['code'] == 22) {
                    $queue->message = $result['data'];
                    $queue->token = $token;
                    $queue->website = $website;
                    $queue->current_status = CreditJxlQueue::STATUS_INPUT_QUERY_PWD;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } else {
                    if ($result['code'] == -2) {
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->error_code = $result['message'];
                        $queue->message = $result['data'];
                    } else {
                        ErrorMessage::getMessage($loanPerson->id, '系统异常', ErrorMessage::SOURCE_JXL);
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->error_code = 1;
                        $queue->message = '系统异常，请稍后提交';
                    }
                    $queue->save();
                    throw new Exception('流程码：' . $result['message'] . '，错误信息' . (isset($result['data']) ? $result['data'] : ''));
                }

                unset($queue);
                unset($verification);
                unset($jxl);
                unset($result);
                unset($service);
                unset($loanPerson);
                unset($contacts);

                echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表,当前内存为" . memory_get_usage(true) . "\n";

            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表 $id 的报表失败，错误原因为： " . $e->getMessage() . ",错误行为:" . $e->getLine() . ",当前内存为" . memory_get_usage(true) . "\n";
                echo $e->getFile();
                unset($queue);
                unset($verification);
                unset($jxl);
                unset($result);
                unset($service);
                unset($e);
            }

        }
    }

    //聚信立基本报告 提交验证码
    public function actionGetJxlCaptcha()
    {
        echo "脚本开始运行";
        pcntl_signal(SIGUSR1, function(){
            echo date('Y-m-d H:i:s') . " 检测到结束信号，关闭当前脚本\n";
            exit;
        });
        while (true) {
            try {
                pcntl_signal_dispatch();
                $id = '';
                try {
                    $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_CAPTCHA]);
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
                $queue = CreditJxlQueue::findOne($id);
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
                $website = $queue->website;
                $captcha = $queue->captcha;
                //提交手机验证码
                $service = Yii::$app->jxlService;
                $result = $service->postMobileCaptcha($token, $website, $captcha);
                switch ($result['code']) {
                    case -1:
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('错误信息' . $result['message']);
                        break;
                    case -2 :
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['data'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    case -3:
                        $queue->current_status = CreditJxlQueue::STATUS_CAPTCHA_ERROR;
                        $queue->message = $result['data'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    case -4:
                        $queue->current_status = CreditJxlQueue::STATUS_INPUT_CAPTCHA;
                        $queue->message = $result['data'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    case -5:
                        $queue->current_status = CreditJxlQueue::STATUS_INPUT_CAPTCHA;
                        $queue->message = $result['data'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    default:
                        $queue->message = '流程完成';
                        $queue->current_status = CreditJxlQueue::STATUS_PROCESS_FINISH;
                        if (!$queue->save()) {
                            throw new Exception('队列表保存失败,id:');
                        }
                        $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                        $verification->real_jxl_status = 1;
                        $verification->save();
                        $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                        if (is_null($jxl)) {
                            $jxl = new CreditJxl();
                        }
                        $jxl->person_id = $loanPerson->id;
                        $jxl->token = $token;
                        $jxl->status = CreditJxl::STATUS_FALSE;
                        $jxl->save();
                        echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表\n";
                }

            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表，错误原因为： " . $e->getMessage() . "\n";
                Yii::error([
                    'error' => $e->getMessage(),
                    'id' => $id,
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ], 'jxl');
            }

        }
    }

    //聚信立基本报告 提交查询码
    public function actionGetJxlQueryPwd()
    {
        echo "脚本开始运行";
        pcntl_signal(SIGUSR1, function(){
            echo date('Y-m-d H:i:s') . " 检测到结束信号，关闭当前脚本\n";
            exit;
        });
        while (true) {
            try {
                pcntl_signal_dispatch();
                $id = '';
                try {
                    $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_QUERY_PWD]);
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
                $queue = CreditJxlQueue::findOne($id);
                if (is_null($queue)) {
                    continue;
                }
//                if (!in_array($queue->current_status, [10])) {
                if (!in_array($queue->current_status, [11])) {
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
                //提交手机验证码
                $service = Yii::$app->jxlService;
                $result = $service->postMobileQueryPwd($token, $website, $pwd, $query_pwd);
                switch ($result['code']) {
                    case -1:
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('错误信息' . $result['message']);
                        break;
                    case -2 :
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['data'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    case -3:
                        $queue->current_status = -4;
                        $queue->message = $result['data'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    case -4:
                        $queue->current_status = CreditJxlQueue::STATUS_INPUT_CAPTCHA;
                        $queue->message = $result['data'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    case -5:
                        ErrorMessage::getMessage($loanPerson->id, $result['data'], ErrorMessage::SOURCE_JXL);
                        $queue->current_status = CreditJxlQueue::STATUS_INPUT_CAPTCHA;
                        $queue->message = $result['data'];
                        $queue->save();
                        throw new Exception('流程码：' . $result['message'] . '，错误信息' . $result['data']);
                        break;
                    default:
                        $queue->message = '流程完成';
                        $queue->current_status = CreditJxlQueue::STATUS_PROCESS_FINISH;
                        if (!$queue->save()) {
                            throw new Exception('队列表保存失败,id:');
                        }
                        $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                        $verification->real_jxl_status = 1;
                        $verification->save();
                        $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                        if (is_null($jxl)) {
                            $jxl = new CreditJxl();
                        }
                        $jxl->person_id = $loanPerson->id;
                        $jxl->token = $token;
                        $jxl->status = CreditJxl::STATUS_FALSE;
                        $jxl->save();
                        echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表\n";
                }

            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表，错误原因为： " . $e->getMessage() . "\n";
                Yii::error([
                    'error' => $e->getMessage(),
                    'id' => $id,
                ], 'jxl');
            }

        }
    }





    /**
     *
     * comment Junxinli Interface conversion

     *
     */

    /**
     *
     * function Submit service password to Wealida.
     *
     */
    public function actionSubmitServicePassword(){
        echo "脚本开始运行";

        pcntl_signal(SIGUSR1, function(){
            echo date('Y-m-d H:i:s') . " 检测到结束信号，关闭当前脚本\n";
            exit;
        });


        while (true) {
            try {
                pcntl_signal_dispatch();
                $id = '';
                try {
                    $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_USER_INFO]);
                } catch (\Exception $e) {
                    $redis = Yii::$app->redis;
                    $redis->open();
                }
                if (!$id) {
                    sleep(2);
                    continue;
                }
                echo date('Y-m-d H:i:s') . " 开始提交ID为：{$id} 的报表,当前内存为" . memory_get_usage(true) . "\n";
                GlobalHelper::connectDb('db_kdkj');
                $queue = CreditJxlQueue::findOne($id);
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
                $loanPersonInfo = LoanPersonInfo::findOne($loanPerson['uid']);
                $work_addr = $loanPersonInfo['company_address'];
                $work_tel = $loanPersonInfo['company_phone'];
                $home_addr = $loanPersonInfo['family_address'];
                $home_tel = "";
                $contacts = UserContact::find()->where(['user_id' => $loanPerson->id])->one();
                $contacts_arr = [];
                if (!empty($contacts)) {
                    $contacts_arr[] = [
                        'contact_tel' => $contacts->mobile,
                        'contact_name' => $contacts->name,
                        'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation]
                    ];
                    if (!empty($contacts->mobile_spare) && !empty($contacts->name_spare)) {
                        $contacts_arr[] = [
                            'contact_tel' => $contacts->mobile_spare,
                            'contact_name' => $contacts->name_spare,
                            'contact_type' => UserContact::$relation_types_jxl_map[$contacts->relation_spare]
                        ];
                    }
                }
                $options['work_addr'] = $work_addr;
                $options['work_tel'] = $work_tel;
                $options['home_addr'] = $home_addr;
                $options['home_tel'] = $home_tel;
                $options['contacts'] = $contacts_arr;
                $service = Yii::$app->jxlService;
                $result = $service->getToken();
                if ($result['code'] != 0) {
                    $queue->current_status = -1;
                    $queue->message = $result['message'];
                    $queue->save();
                    ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                    throw new Exception('token获取失败:' . $result['message']);
                }
                $token = $result['token'];
                $result = $service->getCarrierOpenId($token, $name, $id_number, $phone, "", $options);
                if ($result['code'] != 0) {
                    $queue->current_status = -1;
                    $queue->message = $result['message'];
                    $queue->save();
                    ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                    throw new Exception('open_id获取失败:' . $result['message']);
                }
                $open_id = $result['open_id'];
                $queue->token = $open_id;
                $result = $service->submitServicePassword($open_id, $queue['service_code']);
                if ($result['code'] == 0) {
                    $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                    $verification->real_jxl_status = 1;
                    $verification->save();
                    $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                    if (is_null($jxl)) {
                        $jxl = new CreditJxl();
                    }
                    $jxl->person_id = $loanPerson->id;
                    $jxl->token = $open_id;
                    $jxl->status = CreditJxl::STATUS_FALSE;
                    $jxl->save();
                    $queue->message = '流程完成';
                    $queue->current_status = CreditJxlQueue::STATUS_PROCESS_FINISH;
                    $queue->token = $open_id;
                    if (!$queue->save()) {
                        throw new Exception('队列表保存失败');
                    }
                } else {
                    if ($result['code'] == 1001) {
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->error_code = 10003;
                        $queue->message = $result['message'];
                    } else {
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->error_code = 1;
                        $queue->message = $result['message'];
                    }
                    $queue->save();
                    throw new Exception('流程码：' . $result['code'] . '，错误信息' . (isset($result['message']) ? $result['message'] : ''));
                }

                unset($queue);
                unset($verification);
                unset($jxl);
                unset($result);
                unset($service);
                unset($loanPerson);
                unset($contacts);

                echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表,当前内存为" . memory_get_usage(true) . "\n";

            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表 $id 的报表失败，错误原因为： " . $e->getMessage() . ",错误行为:" . $e->getLine() . ",当前内存为" . memory_get_usage(true) . "\n";
                echo $e->getFile();
                unset($queue);
                unset($verification);
                unset($jxl);
                unset($result);
                unset($service);
                unset($e);
            }

        }
    }

    //聚信立基本报告 提交验证码
    public function actionSubmitCaptcha()
    {
        echo "脚本开始运行";
        pcntl_signal(SIGUSR1, function(){
            echo date('Y-m-d H:i:s') . " 检测到结束信号，关闭当前脚本\n";
            exit;
        });
        while (true) {
            try {
                pcntl_signal_dispatch();
                $id = '';
                try {
                    $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_CAPTCHA]);
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
                $queue = CreditJxlQueue::findOne($id);
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
                $open_id = $queue->token;
                $captcha = $queue->captcha;
                $service = Yii::$app->jxlService;
                $result = $service->submitCaptcha($open_id, $captcha);
                switch ($result['code']) {
                    case -1:
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('错误信息' . $result['message']);
                        break;
                    case 1000:
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                        break;
                    case 1001:
                        $queue->current_status = CreditJxlQueue::STATUS_CAPTCHA_ERROR;
                        $queue->message = $result['message'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                        break;
                    default:
                        $queue->message = '流程完成';
                        $queue->current_status = CreditJxlQueue::STATUS_PROCESS_FINISH;
                        if (!$queue->save()) {
                            throw new Exception('队列表保存失败,id:');
                        }
                        $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                        $verification->real_jxl_status = 1;
                        $verification->save();
                        $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                        if (is_null($jxl)) {
                            $jxl = new CreditJxl();
                        }
                        $jxl->person_id = $loanPerson->id;
                        $jxl->token = $open_id;
                        $jxl->status = CreditJxl::STATUS_FALSE;
                        $jxl->save();
                        echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表\n";
                }

            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表，错误原因为： " . $e->getMessage() . "\n";
                Yii::error([
                    'error' => $e->getMessage(),
                    'id' => $id,
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ], 'jxl');
            }

        }
    }

    //聚信立基本报告 提交查询码
    public function actionSubmitQueryPassword()
    {
        echo "脚本开始运行";
        pcntl_signal(SIGUSR1, function(){
            echo date('Y-m-d H:i:s') . " 检测到结束信号，关闭当前脚本\n";
            exit;
        });
        while (true) {
            try {
                pcntl_signal_dispatch();
                $id = '';
                try {
                    $id = RedisQueue::pop([RedisQueue::LIST_GET_USER_JXL_BASIC_REPORT_QUERY_PWD]);
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
                $queue = CreditJxlQueue::findOne($id);
                if (is_null($queue)) {
                    continue;
                }
                if (!in_array($queue->current_status, [11])) {
                    continue;
                }
                $loanPerson = LoanPerson::findOne($queue['user_id']);
                if (is_null($loanPerson)) {
                    throw new Exception('用户不存在,id:' . $id);
                }
                $open_id = $queue->token;
                $query_password = $queue->query_pwd;
                $service = Yii::$app->jxlService;
                $result = $service->submitQueryPassword($open_id, $query_password);
                switch ($result['code']) {
                    case -1:
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('错误信息' . $result['message']);
                        break;
                    case 1000:
                        $queue->current_status = CreditJxlQueue::STATUS_RESTART_PROCESS;
                        $queue->message = $result['message'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                        break;
                    case 1001:
                        $queue->current_status = CreditJxlQueue::STATUS_CAPTCHA_ERROR;
                        $queue->message = $result['message'];
                        $queue->save();
                        ErrorMessage::getMessage($loanPerson->id, $result['message'], ErrorMessage::SOURCE_JXL);
                        throw new Exception('流程码：' . $result['code'] . '，错误信息' . $result['message']);
                        break;
                    default:
                        $queue->message = '流程完成';
                        $queue->current_status = CreditJxlQueue::STATUS_PROCESS_FINISH;
                        if (!$queue->save()) {
                            throw new Exception('队列表保存失败,id:');
                        }
                        $verification = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
                        $verification->real_jxl_status = 1;
                        $verification->save();
                        $jxl = CreditJxl::findLatestOne(['person_id' => $loanPerson->id]);
                        if (is_null($jxl)) {
                            $jxl = new CreditJxl();
                        }
                        $jxl->person_id = $loanPerson->id;
                        $jxl->token = $open_id;
                        $jxl->status = CreditJxl::STATUS_FALSE;
                        $jxl->save();
                        echo date('Y-m-d H:i:s') . " 成功获取ID为：{$id} 的报表\n";
                }

            } catch (\Exception $e) {
                echo date('Y-m-d H:i:s') . "获取ID为：{$id} 的报表，错误原因为： " . $e->getMessage() . "\n";
                Yii::error([
                    'error' => $e->getMessage(),
                    'id' => $id,
                ], 'jxl');
            }

        }
    }

    // protected function getCreditJxlQueue($report){
    //     pcntl_signal_dispatch();
    //     $result['queue'] = "";
    //     $result['id'] = "";
    //     $id = '';
    //     try {
    //         $id = RedisQueue::pop([$report]);
    //     } catch (\Exception $e) {
    //         $redis = Yii::$app->redis;
    //         $redis->open();
    //     }

    //     if (!$id) {
    //         return $result;
    //     }
    //     echo date('Y-m-d H:i:s') . " 开始提交ID为：{$id} 的报表,当前内存为" . memory_get_usage(true) . "\n";
    //     GlobalHelper::connectDb('db_kdkj');
    //     $queue = CreditJxlQueue::findOne($id);
    //     $result['queue'] = $queue;
    //     $result['id'] = $id;
    //     return $result;
    // }


}
