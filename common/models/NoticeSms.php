<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use common\helpers\TimeHelper;
use common\helpers\MessageHelper;
use common\models\LoanPerson;

class NoticeSms extends ActiveRecord
{
    // 消息状态
    const SEND_WAIT    = 0 ;     // 等待信息发送
    const SEND_SUCCESS = 1 ;     // 信息发送成功
    const SEND_FAIL    = 2 ;     // 信息发送失败
    public static $send_status = [
        self::SEND_WAIT    => '等待',
        self::SEND_SUCCESS => '成功',
        self::SEND_FAIL    => '失败',
    ];

    // 消息阅读状态
    const READ_NO      = 0 ;     // 信息未读
    const READ_YES     = 1 ;     // 信息已读
    public static $read_status = [
        self::READ_NO    => '未读',
        self::READ_YES   => '已读',
    ];

    // 消息类型
    const NOTICE_REPAYMENT = 1;  // 还款当日提示消息
    public static $types = [
        self::NOTICE_REPAYMENT => '今日还款通知'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%notice_sms}}';
    }

    /**
     * Created by JohNnY
     * @return Model
     */
    private static $instances = array();
//    public static function &instance() {
//        $class = get_called_class();
//        if (!isset(self::$instances[$class])) {
//            self::$instances[$class] = new $class();
//        }
//        return self::$instances[$class];
//    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [];
    }

    // 插入消息中心表
    public function InsertDate($user_id, $type, $content, $source, $status=0){
        if (empty($user_id) || empty($type) || empty($content || empty($source))){
            return false;
        }

        $result = Yii::$app->db_kdkj->createCommand()->insert(self::tableName(), [
            'user_id' => $user_id,
            'type' => $type,
            'status' => $status,
            'source' => $source,
            'content' => $content,
            'created_at' => time(),
            'updated_at' => time(),
        ])->execute();
        // 插入失败
        if (!$result) {
            Yii::error("Notice_SMS insert failed, user_id:$user_id, type:$type ,content : $content,status:$status,source:$source");
        }
        return $result;
    }

    /**
     * 拼接消息信息字符串
     * @param $user_id 用户ID
     * @param $type 消息类型
     * @param array $memo 消息内容参数
     * @param bool $send_msg 是否作为短信发送
     * @param bool $nowsend 是否立即发送短信，默认否即定时脚本触发
     * @return bool
     */
    const STARTSTR = '';
    public function init_sms_str($user_id, $type, $memo=array(), $send_msg=true, $send_way='smsServiceXQB_XiAo'){
        if (empty($user_id) || empty($type) || empty($memo) ){
            return false;
        }

        $str = '';
        $now = TimeHelper::Now();
        switch ($type){
            case self::NOTICE_REPAYMENT:  //【还款当日提示消息】
                $str .= self::STARTSTR."尊敬的".$memo['name']."，您在【".$memo['source_name']."】的".$memo['money']."元借款于今日到期，请打开APP进行还款；若到期未进行主动还款，平台将从您尾号".$memo['card']."银行卡里自动扣款，请确保资金充足，以免产生逾期费用。如已还款，请忽略。";
                break;
            default:
                break;
        }
        if ($str){
            $user = LoanPerson::findOne($user_id);
            if($send_msg){ // 作为短信发送
                $result = MessageHelper::sendSMS($user->username, $str, $send_way, $user->source_id);
                $status = $result ? self::SEND_SUCCESS : self::SEND_FAIL;
            }else{
                $status = self::SEND_SUCCESS;
            }
            $this->InsertDate($user_id ,$type , $str , $user->source_id , $status);
        }

        return true;
    }
}