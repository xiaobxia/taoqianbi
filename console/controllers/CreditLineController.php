<?php
namespace console\controllers;

use common\api\RedisQueue;
use common\helpers\Lock;
use common\helpers\MessageHelper;
use common\models\credit_line\CreditLine;
use common\models\credit_line\CreditLineMsgCount;
use common\models\credit_line\CreditLineTimeLog;
use common\models\CreditCheckHitMap;
use common\models\CreditJxlQueue;
use common\models\LoanPerson;
use common\models\UserCreditDetail;
use common\models\UserVerification;
use common\services\credit_line\CreditLineService;
use common\services\CreditCheckService;
use common\services\UserService;
use Yii;
use common\helpers\CommonHelper;
use common\base\LogChannel;
use common\helpers\Util;

class CreditLineController extends BaseController {

    protected function printMessage($message)
    {
        $pid = function_exists('posix_getpid') ? posix_getpid() : '';
        $date = date('y-m-d H:i:s');
        $mem = \floor(\memory_get_usage(true) / 1024 / 1024) . 'MB';
        //时间 进程号 内存使用量 日志内容
        echo "{$date} {$pid} $mem {$message} \n";
        //Yii::error("{$date} {$pid} $mem {$message}", LogChannel::CREDIT_SET_CREDIT_LINE);
    }

    /**
     * 授信，采集征信数据。（提交资料之后的第一步）
     * touch /tmp/close_credit_line_set.tag 关闭脚本。
     * @param int $rule_id
     */
    public function actionSetCreditLine($rule_id = 347) {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        $now = time();
        $tag_file = '/tmp/close_credit_line_set.tag';

        $creditLineService = new CreditLineService();

        $user_id = '';

        pcntl_signal(SIGUSR1, function() use ($user_id) {
            $this->printMessage("检测到结束信号，关闭当前脚本.当前工作uid: {$user_id}");
            exit;
        });

        while (true) {
            pcntl_signal_dispatch();

            if (\file_exists($tag_file)) {
                if (! unlink($tag_file) ) {
                    CommonHelper::error("delete $tag_file failed.");
                }
                $this->printMessage('检测到标识文件，关闭当前脚本');
                exit;
            }

            if (\time() - $now > 172) {
                $this->printMessage('runing_reach_3_mins, exit.');
                exit;
            }

            try {
                $user_id = RedisQueue::pop([RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD]);
                if (!$user_id) {
                    if (time() % 10 == 0) {
                        $this->printMessage('无任务');
                    }
                    \sleep(1);
                    continue;
                }

                if (preg_match('/&/', $user_id)) {
                    $user_arr = \explode('&', $user_id);
                    $one_time = $user_arr[1];
                    $two_time = $user_arr[2];
                    if ((time() - $two_time) < 60) {
                        RedisQueue::push([RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD, $user_id]);
                        continue;
                    } else {
                        $user_id = $user_arr[0];
                    }
                }

                //单个用户加锁
                $lock_name = 'set_credit_line_' . $user_id;
                if (!Lock::get($lock_name, 1)) {
                    $this->printMessage("user_{$user_id} 并发锁跳过.");
                    continue;
                }

                $loanPerson = LoanPerson::findOne($user_id);
                if (empty($loanPerson)) {
                    $this->printMessage("user_{$user_id} none.");
                    continue;
                }

                $age = Util::getAgeFromIdNumber($loanPerson->id_number);
                if ( $age < 20 ) { //20岁以下
                    $this->printMessage("skip_uid_{$user_id} - {$loanPerson->id_number}, age < 20");
                    \yii::$container->get('userService')->setUserCreditDetail($user_id, 0, 0, "年龄<20", '-1');
                    continue;
                }

                /* @var $userServiceInst UserService */
                $userServiceInst = Yii::$container->get('userService');

                $credit_line = CreditLine::findLatestOne([
                    'user_id' => $user_id,
                    'status' => CreditLine::STATUS_ACTIVE,
                ]);
                if ($credit_line && $credit_line->valid_time > \date('Y-m-d H:i:s')) { // 重复授信，跳过
                    $userCreditDetail = $userServiceInst->getCreditDetail($user_id);
                    $userCreditDetail->credit_status = UserCreditDetail::STATUS_FINISH;
                    $userCreditDetail->updated_at = time();
                    $userCreditDetail->save();
                    $this->printMessage("user_{$user_id} 重复授信，跳过");
                    continue;
                }

                $this->printMessage("user_{$user_id} start_get_data");
                //获取额度
                $result = $creditLineService->getCreditLines($loanPerson, $credit_line, $rule_id);

                $credit_line = $result['credit_line'];
                $valid_time = strtotime($result['valid_time']);

                $this->printMessage("user_{$user_id} line:{$credit_line}. expire:{$valid_time}");

                /* @var $userServiceInst UserService */
                $userServiceInst = Yii::$container->get('userService');

                $flag = $userServiceInst->setUserCreditDetail($user_id, $credit_line, $valid_time);


                if (!$flag) {
                    $this->printMessage("user_{$user_id} setUserCreditDetail failed");
                    $userServiceInst->setUserCreditDetail($user_id, $credit_line, $valid_time, '', '-1');
                }

                CreditLineTimeLog::updateEndTime($user_id, CreditLineTimeLog::CREDIT_STATUS_1);
                $this->printMessage("用户{$user_id}计算额度完毕");
            }
            catch (\Exception $e) {
                \yii::error(\sprintf('[%s] %s,code : %s, user_id : %s', Util::short(__CLASS__, __FUNCTION__), $e, $e->getCode(), $user_id), LogChannel::RISK_CONTROL);

                if ($e->getCode() == 3000) {
                    if (preg_match('/请重新采集/', $e->getMessage())) {
                        $message = "尊敬的{$loanPerson->name}，由于运营商官网不稳定，导致您的手机运营商信息获取失败，无法进行授信，请稍后重新认证。";
                        Yii::$container->get('userService')->resetYysStatus($loanPerson, $message);
                    }
                    elseif (!isset($one_time)) {
                        RedisQueue::push([RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD, $user_id . '&' . time() . '&' . time()]);
                        Lock::del($lock_name);
                    }
                    elseif ((time() - $one_time) > 60 * 20) {
                        $message = "尊敬的{$loanPerson->name}，由于您的手机运营商信息获取失败，无法进行授信，请登录APP至认证中心重新提交手机运营商信息。如已认证成功请忽略。";
                        Yii::$container->get('userService')->resetYysStatus($loanPerson, $message);
                    } else {
                        RedisQueue::push([RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD, $user_id . '&' . $one_time . '&' . time()]);
                        Lock::del($lock_name);
                        $this->printMessage("user_{$user_id} get_nothing, wait_one_minute");
                    }
                }
                else {
                    var_dump($e->getMessage(), $e->getFile(), $e->getLine());die;
                    CreditLineTimeLog::updateEndTime($user_id, CreditLineTimeLog::CREDIT_STATUS_2);
                    Yii::$container->get('userService')->setUserCreditDetail($user_id, $credit_line, $valid_time, $e->getMessage(), '-1');
                }
            }
        }
    }
}
