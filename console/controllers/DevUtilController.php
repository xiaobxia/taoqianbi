<?php
namespace console\controllers;

use common\api\RedisQueue;
use common\helpers\CommonHelper;
use common\helpers\MailHelper;
use common\helpers\MessageHelper;
use common\helpers\Util;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserProofMateria;
use common\services\RiskControlService;
use common\services\UserService;


class DevUtilController extends BaseController {

    /**
     * 测试邮件
     * @param string $email
     */
    public function actionMail($email='') {
        if (empty($email)) {
            $email = NOTICE_MAIL;
        }

        $ret = MailHelper::sendMail(
            sprintf('mail test @ %s', date('y-m-d H:i:s')),
            'just test',
            $email
        );
        var_dump($ret);
    }

    /**
     * 短信测试
     * @param string $phone
     */
    public function actionSms($phone = '') {
        if (empty($phone)) {
            $phone = NOTICE_MOBILE2;
        }

        $channels = [
            'smsService_TianChang_HY',
            'smsService_TianChang_XY',
//            'smsServiceXQB_XiAo',
//            'smsService_MengWang',
        ];

        foreach($channels as $_channel) {
            $ret = MessageHelper::sendSMS($phone, '测试内容', $_channel);
            print sprintf('%s - %s', $_channel, $ret) . PHP_EOL;
        }
    }

    /**
     * 清除所有表缓存
     * @return [type] [description]
     */
    public function actionClearTableCache() {
        $ret = \yii::$app->db->schema->refresh(); //flush all the schema cache
        print sprintf('refresh %s', $ret) . PHP_EOL;
    }

    /**
     * push redis队列
     * @param $key
     * @param $value
     */
    public function actionPushRedis($key, $value) {
        $res = RedisQueue::push([$key, $value]);
        print $res . PHP_EOL;
    }

    public function actionSendMailTest()
    {
        $to1 = 'leishuanghe@wzdai.com';
        $to2 = 'yuchen@wzdai.com';
        $title = 'xyjk test title';
        $content = 'xyjk test content';

        $ret1 = MailHelper::sendMail($title, $content, $to1);
        $ret2 = MailHelper::sendMail($title, $content, $to2);

        var_dump($ret1, $ret2);
    }

    /**
     *清空单个用户表数据
     * @param user_id
     */
    public function actionClearUser($user_id)
    {
        $lock = CommonHelper::lock();
        if (!$lock) {
            return self::EXIT_CODE_ERROR;
        }

        Util::cliLimitChange(512);


        if (empty($user_id)) {
            CommonHelper::stderr('empty user_id');
            return self::EXIT_CODE_ERROR;
        }


//        $loan_order = UserLoanOrder::findOne(['user_id' => $user_id]);
//        if ($loan_order) {
//            CommonHelper::stderr("loan order founded, cannot delete.\n");
//            return self::EXIT_CODE_ERROR;
//        }

        $list = [
            'tb_loan_person',
            'tb_user_detail',
            'tb_user_credit_total',
            'tb_user_credit_detail',
            'tb_user_password',
            'tb_user_register_info',
            'tb_user_verification',
            'tb_user_realname_verify',
            'tb_card_info',
            'tb_user_quota_person_info',
            'tb_user_channel_map',
            'tb_credit_jxl',
            'tb_credit_jxl_queue',
            'tb_user_loan_order',
            'tb_credit_zmop',
            'tb_credit_zmop_log',
        ];

        //判断文件是否存在
        foreach ($list as $res) {
            if ($res == 'tb_loan_person') {
                $condition = 'id = ' . $user_id;
            } elseif ($res == 'tb_credit_jxl' || $res == 'tb_credit_zmop' || $res == 'tb_credit_zmop_log') {
                $condition = 'person_id = ' . $user_id;
            } else {
                $condition = 'user_id = ' . $user_id;
            }

            $delete_res = \yii::$app->db->createCommand()->delete($res, $condition)->execute();
            if ($delete_res !== false) {
                CommonHelper::stdout("delete from {$res} where {$condition} success.\n");
            } else {
                CommonHelper::error("delete from {$res} where {$condition} failed.\n");
            }
        }

        //清除图片
        $img_list = UserProofMateria::find()->select('url')->where(['user_id' => $user_id])->asArray()->all();
//        echo COMMON_PATH.'/api/oss/sdk.class.php';die;
        require_once COMMON_PATH.'/api/oss/sdk.class.php';
        $ossService = new \ALIOSS();
        $img_res = [];
        if (empty($img_list)) {
            CommonHelper::stderr("empty $user_id user_img \n");
        } else {
            foreach ($img_list as $key) {
                $img = $key['url'];
                $res = $ossService->get_object_group(DEFAULT_OSS_BUCKET, $img);
                if ($res->status == 200) {
                    $img_res[] = $img;
                }
            }
            if (!empty($img_res)) {
                CommonHelper::stderr("empty oss_img \n");
            } else {
                $condition = 'user_id = ' . $user_id;
                $del_res = $ossService->delete_objects(DEFAULT_OSS_BUCKET, $img_res); //删除多个对象
                $delete_res = \yii::$app->db->createCommand()->delete('tb_user_proof_materia', $condition)->execute();
                if ($del_res->status == 200 && $delete_res) { //清空表
                    CommonHelper::stdout('clear oss_img user_id= ' . $user_id . " success.\n");
                }
            }
        }

        return self::EXIT_CODE_NORMAL;
    }
}
