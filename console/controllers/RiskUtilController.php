<?php

namespace console\controllers;

use common\api\RedisMQ;
use common\base\LogChannel;
use common\helpers\CommonHelper;
use common\api\RedisQueue;
use common\helpers\Lock;
use common\helpers\MailHelper;
use common\helpers\Util;
use common\models\AccumulationFund;
use common\models\credit_line\CreditLine;
use common\models\CreditJxlQueue;
use common\models\CreditShumei;
use common\models\IcekreditAlipay;
use common\models\UserCreditDetail;
use common\models\UserLoanOrder;
use common\models\UserVerification;
use common\services\credit_line\CreditLineService;
use common\services\IceKreditService;
use common\services\JxlService;
use common\helpers\MessageHelper;
use common\models\LoanPerson;
use common\services\ShumeiService;
use common\services\UserService;
use Yii;

class RiskUtilController extends BaseController
{
    /**
     * 获取聚信立公积金用户token
     */
    public function actionGetHouseFundToken()
    {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);
        $now = time();
        $jxl = Yii::$app->jxlService;
        while (true) {
            if (\time() - $now > 160) {
                print 'runing_reach_3_mins, exit.';
                exit;
            }
            try {
                $record_id = RedisQueue::pop([RedisQueue::LIST_HOUSEFUND_TOKEN]);
                if (!$record_id) {
                    exit();
                }

                print \sprintf("开始处理记录%s\n", $record_id);

                if (strpos($record_id, '_') !== false) {
                    $id_arr = \explode('_', $record_id);
                    $record_id = $id_arr[0];
                    $get_times = $id_arr[1];
                } else {
                    $get_times = 1;
                }

                if (!is_numeric($record_id)) {
                    Yii::error(\sprintf("%s：ID不为数字\n", $record_id), LogChannel::CREDIT_JXL);
                    continue;
                }

                $record = AccumulationFund::findOne($record_id);
                if (!$record || AccumulationFund::STATUS_INIT != $record['status']) {
                    Yii::error(\sprintf("%s不存在或不在获取token状态\n", $record_id), LogChannel::CREDIT_JXL);
                    continue;
                }

                $token = $jxl->getUserToken($record);
                if ($token) {
                    print \sprintf("%s获取token成功\n", $record_id);
                    $record->token = $token;
                    $record->status = AccumulationFund::STATUS_GET_TOKEN;
                    $record->save();
                    continue;
                } else {
                    Yii::error(\sprintf("%s第%s次请求token失败\n", $record_id, $get_times), LogChannel::CREDIT_JXL);
                    $get_times = $get_times + 1;
                    if ($get_times > 3) {
                        throw new \Exception('认证失败，请稍后重试');
                    } else {
                        RedisQueue::push([RedisQueue::LIST_HOUSEFUND_TOKEN, $record_id . '_' . $get_times]);
                    }
                }
            } catch (\Exception $e) {
                if (isset($record)) {
                    $record->message = $e->getMessage();
                    $record->status = AccumulationFund::STATUS_FAILED;
                    $record->save();
                    Yii::error(\sprintf('%s公积金获取token失败：%s', $record->id, $e->getMessage()), LogChannel::CREDIT_JXL);
                }
            }
        }

        print \sprintf("[%s][%s][%s] finish.\n", \date('y-m-d H:i'), \basename(__FILE__), __LINE__);
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * 重置公积金为待获取token状态
     * @param $record_id
     */
    public function actionHouseFundGetToken($id)
    {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        if (!$id) {
            print "id缺失\n";
            exit();
        }

        if (stripos($id, ',') !== false) {
            $record_id_arr = explode(',', $id);
        } else {
            $record_id_arr = [$id];
        }

        foreach ($record_id_arr as $record_id) {
            if (!is_numeric($record_id)) {
                print "{$record_id} 类型错误\n";
                continue;
            }
            if (!$record = AccumulationFund::findOne($record_id)) {
                print "{$record_id} 记录不存在\n";
                continue;
            }

            $record->status = AccumulationFund::STATUS_INIT;

            $transaction = AccumulationFund::getDb()->beginTransaction();
            try {
                if ($record->save() && RedisQueue::push([RedisQueue::LIST_HOUSEFUND_TOKEN, $record_id])) {
                    $transaction->commit();
                    print "{$record_id} 更新成功\n";
                } else {
                    $transaction->rollBack();
                    print "{$record_id} 更新失败\n";
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                print "{$record_id} 更新失败\n";
            }
        }

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * 拉取聚信立公积金报告
     */
    public function actionGetHouseFundReport($step = 100, $mod_base = 0, $mod_left = 0) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);

        $interval = 60; //数据采集时间间隔，避免获取空数据

        $query = AccumulationFund::find()
            ->where(['status' => AccumulationFund::STATUS_GET_TOKEN])
            ->andWhere(['<', 'updated_at', time() - $interval])
            ->orderBy('id');

        $max_id = $query->max('id');
        if (empty($max_id)) {
            print "empty accumulationFund records, quit.\n";
            return self::EXIT_CODE_ERROR;
        }

        print \sprintf("[%s][%s][%s-%s] get max id: %s.\n",
            date('y-m-d H:i:s'), posix_getpid(), $mod_base, $mod_left, $max_id);

        $jxl = Yii::$app->jxlService;
        if ($mod_base > 0) {
            $query = $query->andWhere(" id % {$mod_base} = {$mod_left} ");
        }

        $records = $query->limit($step)->all();
        $try_limit = 3;
        $now = time();
        foreach ($records as $_record) {
            if (\time() - $now > 170) {
                echo 'running_3mins，close';
                exit;
            }
            print \sprintf("[%s][%s] process: %s.\n", __CLASS__, __FUNCTION__, $_record->id);
            try {
                for ($i = 0; $i <= $try_limit; $i++) {

                    if ($i >= $try_limit) {//三次失败后认证失败
                        throw new \Exception('认证失败，请稍后重试');
                    }

                    //获取公积金报告
                    $report = $jxl->getHouseFundReport($_record);
                    if (!$report) {
                        //Yii::error(\sprintf('record: %s 第%s次公积金信息请求失败', $_record->id, $i + 1));
                        continue;
                    }
                    $_record->data = json_encode($report, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

                    $loanPerson = LoanPerson::findOne($_record->user_id);
                    $match_result = AccumulationFund::gjjInfoMatch($report, $loanPerson);
                    if (!$match_result) {
                        $_record->status = AccumulationFund::STATUS_FAILED;
                        $_record->message = '填写用户名与公积金用户名不匹配';
                        $_record->save();
                        break;
                    }

                    if (isset($report['details'])) { //计算缴纳平均额度与缴纳月份
                        $total = 0;
                        $amt = [];
                        $now_ts = \time();
                        $month_last_ts = \strtotime(\date('Y-m-t', $now_ts));
                        $last_year_ts = $month_last_ts - 31536000; //86400 * 365 = 31536000
                        $last_year_ts = \strtotime(\date('Y-m-01', $last_year_ts));
                        foreach ($report['details'] as $key => $detail) {
                            $trading_ts = \strtotime($detail['trading_date']);
                            if (count($amt) >= 12) {
                                break;
                            }
                            if ($trading_ts > $last_year_ts && $last_year_ts < $now_ts && (\strstr($detail['note'], '汇缴') !== false || \strstr($detail['note'], '缴存') !== false || \strstr($detail['note'], '汇交') !== false || \strstr($detail['note'], '缴交') !== false)) {
                                $amt[] = $detail['trading_amt'];
                                $total += $detail['trading_amt'];
                            }
                        }
                        $counts = \count($amt);
                        $average_amt = !empty($counts) && !empty($total) ? (sprintf('%.2f', $total / $counts) * 100) : 0;
                    }

                    $_record->status = AccumulationFund::STATUS_SUCCESS;
                    $_record->pay_months = $counts ?? 0;
                    $_record->average_amt = $average_amt ?? 0;
                    $_record->message = '数据获取成功';

                    $transaction = UserVerification::getDb()->beginTransaction();
                    $verification_res = UserVerification::saveUserVerificationInfo([
                        'user_id' => $_record->user_id,
                        'real_accumulation_fund' => UserVerification::VERIFICATION_YES,
                        'operator_name' => $_record->user_id,
                    ]);
                    if ($_record->save() && $verification_res) {
                        $transaction->commit();
                        if (YII_ENV_PROD) {
                            new LoanPerson();
                            $source_name = LoanPerson::$person_source[$loanPerson->source_id] ?? APP_NAMES;
                            MessageHelper::sendSMS($loanPerson->phone, '尊敬的' . $source_name . '用户，您的公积金数据获取成功；请登录APP借款吧。', 'smsService_TianChang_HY', $loanPerson->source_id);
                        }
                        //更新额度
                        try {
                            $credit_line = CreditLine::findOne(['user_id' => $_record->user_id]);
                            if ($credit_line) {
                                $credit_line->valid_time = date('Y-m-d H:i:s');
                                if (!$credit_line->save()) {
                                    Yii::error(\sprintf('user %s 更新额度有效期保存失败', $_record->user_id), LogChannel::CREDIT_JXL);
                                }
                                $this->updateUserCredit($_record->user_id);
                            }
                        } catch (\Exception $e) {
                            Yii::error(\sprintf('user %s 更新额度: %s', $_record->user_id, $e->getMessage()), LogChannel::CREDIT_JXL);
                        }

                        break;
                    } else {
                        $transaction->rollBack();
                        \yii::error(\sprintf('公积金: %s 保存失败', $_record->id), LogChannel::CREDIT_JXL);
                    }
                }
            } catch (\Exception $e) {
                //Yii::error(\sprintf('record: %s 公积金信息请求失败：%s', $_record->id, $e->getMessage()));
                $_record->message = $e->getMessage();
                $_record->status = AccumulationFund::STATUS_FAILED;
                $save_res = $_record->save();
                $verification_res = UserVerification::saveUserVerificationInfo([
                    'user_id' => $_record->user_id,
                    'real_accumulation_fund' => UserVerification::VERIFICATION_NO,
                    'operator_name' => $_record->user_id,
                ]);
                if (!$save_res || !$verification_res) {
                    //Yii::error(\sprintf('公积金: %s 保存失败', $_record->id), LogChannel::CREDIT_JXL);
                }
            }
        }

        print \sprintf("[%s][%s] finish\n", date('ymd H:i:s'), posix_getpid(), __LINE__);
        return self::EXIT_CODE_NORMAL;
    }

    /**
     * 通知风控更新用户额度
     * @param $user_id
     */
    protected function updateUserCredit($user_id)
    {
        /** @var UserService $userService */
        $userService = Yii::$container->get('userService');

        $user_verification = UserVerification::findOne(['user_id' => $user_id]);
        if (
            $user_verification &&
            UserVerification::VERIFICATION_YES == $user_verification->real_verify_status &&
            UserVerification::VERIFICATION_YES == $user_verification->real_contact_status &&
            UserVerification::VERIFICATION_YES == $user_verification->real_bind_bank_card_status &&
            UserVerification::VERIFICATION_YES == $user_verification->real_jxl_status
        ) {
            $card_detail_info = $userService->getCreditDetail($user_id);
            if (UserCreditDetail::STATUS_FINISH == $card_detail_info->credit_status) {
                $card_detail_info->credit_status = UserCreditDetail::STATUS_ING;
                $card_detail_info->credit_total += 1;
                $card_detail_info->save();
                CreditLineService::checkUserCreditLines($user_id);
            }
        }
    }

    /**
     * 缓存聚信立公积金城市列表
     */
    public function actionCacheJxlCity()
    {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);
        $jxl = new \common\services\JxlService();
        $methods = $jxl->getCitysLoginMethods();
        if (empty($methods)) {
            CommonHelper::error('getCitysLoginMethods failed.');
            return self::EXIT_CODE_ERROR;
        }

        $ret = RedisQueue::set([
            'key' => RedisQueue::STR_JXL_FUND,
            'value' => \json_encode($methods),
            'expire' => 86400 * 8,
        ]);
        if ($ret) {
            CommonHelper::info('getCitysLoginMethods success.');
            return self::EXIT_CODE_NORMAL;
        }

        CommonHelper::error('getCitysLoginMethods failed.');
        return self::EXIT_CODE_ERROR;
    }

    /**
     * 重置无效的运营商认证状态
     * @return int
     */
    public function actionResetJxlQueueStatus() {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);

        $time = time() - 3600;
        $tb = CreditJxlQueue::tableName();
        $status_restart = CreditJxlQueue::STATUS_RESTART_PROCESS;
        $status_finish = CreditJxlQueue::STATUS_PROCESS_FINISH;
        $sql = "UPDATE $tb
                   SET current_status = {$status_restart}
                 WHERE current_status != {$status_finish}
                   AND current_status != {$status_restart}
                   AND updated_at < $time";
        $db = CreditJxlQueue::getDb();
        $command = $db->createCommand($sql);
        $res = $command->execute();
        $msg = \sprintf('[%s] reset_jxl_status: %s', date('ymd H:i:s'), $res);
        print $msg . PHP_EOL;
        MailHelper::sendQueueMail($msg, '', NOTICE_MAIL);

        return self::EXIT_CODE_NORMAL;
    }

    /**
     * 发送延迟邮件
     * @return int
     */
    public function actionSendQueueMail() {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(1024);

        $mails = [];
        while ($raw = RedisMQ::receive(RedisQueue::USER_QUEUE_MAIL)) {
            $mail = json_decode($raw, TRUE);
            if (empty($mail['subject']) || empty($mail['to'])) {
                print sprintf('[%s]skip_data: %s%s', date('ymd H:i:s'), json_encode($mail), PHP_EOL);
                continue;
            }

            if (is_array($mail['to'])) {
                foreach($mail['to'] as $_to) {
                    $mails[ $_to ][] = [
                        'subject' => $mail['subject'],
                        'content' => $mail['content'] ?? '',
                    ];
                }
            }
            else {
                $mails[ $mail['to'] ][] = [
                    'subject' => $mail['subject'],
                    'content' => $mail['content'] ?? '',
                ];
            }
        }

        if (empty($mails)) {
            print sprintf('[%s]empty. %s', date('ymd H:i:s'), PHP_EOL);
            return self::EXIT_CODE_NORMAL;
        }

        $mail_title = sprintf(APP_NAMES.'邮件通知 @%s', date('y-m-d'));
        foreach($mails as $_to => $mail_ary) {
            $content = sprintf("---%s---\r\n", date('H:i'));
            foreach($mail_ary as $_dat) {
                $content .= sprintf("%s-%s\r\n", $_dat['subject'], $_dat['content']);
            }

            $ret = MailHelper::sendMail($mail_title, $content, $_to);
            print sprintf('[%s]send to %s, %s%s', date('ymd H:i:s'), $_to, $ret, PHP_EOL);
        }

        return self::EXIT_CODE_NORMAL;
    }

}
