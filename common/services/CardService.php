<?php
namespace common\services;

use common\base\LogChannel;
use common\services\fundChannel\JshbService;
use yii\base\Component;
use Yii;
use common\exceptions\UserExceptionExt;
use common\models\UserVerification;
use common\helpers\StringHelper;
use common\soa\KoudaiSoa;
use common\models\CardInfo;
use common\helpers\Util;
use common\models\UserCaptcha;
use common\models\BankConfig;
use common\api\RedisQueue;
use common\models\LoanPerson;
use common\models\BindCardInfo;
use common\models\ErrorMessage;

class CardService extends Component
{

    /**
     * 绑定银行卡
     * @param LoanPerson $curUser
     * @param array $params
     * @return mixed
     */
    public function bindCard($curUser, $params)
    {
        $user_id = $curUser['id'];
        $id_number = $curUser['id_number'];
        $name = $curUser['name'];
        $is_rebind = $params && isset($params['rebind']) && $params['rebind'] ? true : false;
        if (empty($id_number) || empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证11");
        }
        $bank_id = intval($params['bank_id']);
        if (empty($bank_id)) {
            return UserExceptionExt::throwCodeAndMsgExt("缺少 bank_id 参数");
        }
        $phone = trim($params['phone']);
        if (empty($phone)) {
            return UserExceptionExt::throwCodeAndMsgExt("请填写手机号码");
        }
        $verify_info = UserVerification::find()->where(['user_id' => $user_id])->one();
        if (!$verify_info || !$verify_info->real_verify_status) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证22");
        }
        $card_no = StringHelper::trimBankCard(trim($params['card_no'])); //消除输入的银行卡中的空格

        # 如果参数中没有指定 skipCheckCardRecord 对卡记录进行验证 判断卡有没有被使用
        if (empty($params['skipCheckCardRecord'])) {
            if (CardInfo::checkCardIsUsed($card_no,$params['source_id'])) {
                return UserExceptionExt::throwCodeAndMsgExt("对不起，该银行卡已被绑定过");
            }
        }

        if (!$is_rebind) {//重新绑定
            $find = Yii::$container->get('userService')->getMainCardInfo($user_id);
            if ($find) {
                return UserExceptionExt::throwCodeAndMsgExt("对不起，您只能绑定一张借记卡");
            }
        }
        # 如果参数中没有指定 skipValidateCaptcha 对验证码进行验证
//        if (empty($params['skipValidateCaptcha'])) {
//            $code = trim($params['code']);
//            $source_id = $params['source_id'];
//            if (!UserCaptcha::validateCaptcha($phone, $code, UserCaptcha::TYPE_BIND_BANK_CARD,$source_id)) {
//                return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
//            }
//        }

        #如果没有在参数中指定skipSoaValidate 使用soa进行银行卡校验
        if (empty($params['skipSoaValidate'])) {
//            $card_info = KoudaiSoa::instance('BankCard')->cardBin($card_no);
            $card_info = JshbService::cardBin($card_no,$bank_id);
            if (isset($card_info['code']) && (0 != $card_info['code'])) {
                return UserExceptionExt::throwCodeAndMsgExt("请检查银行卡号是否正确");
            }
            if ($bank_id != $card_info['data']['bank_id']) {
                return UserExceptionExt::throwCodeAndMsgExt("请选择正确的银行");
            }

            //验证银行卡
//            $card_info = KoudaiSoa::instance('BankCard')->cardVerify($card_no, $phone, $id_number, $name, ['client_ip' => Util::getUserIP()]);
//            $card_info = JshbService::cardVerify($card_no, $phone, $id_number, $name ,$bank_id);
//            if (false == $card_info) {
//                return UserExceptionExt::throwCodeAndMsgExt("数据有误，验证银行卡类型失败");
//            }
//            if (isset($card_info['code']) && (0 != $card_info['code'])) {
//            	$message = str_replace(["口袋", "口袋理财"], APP_NAMES, $card_info['message']);
//                return UserExceptionExt::throwCodeAndMsgExt($message);
//            }
        }

        //签约绑卡
//        $data = [
//            // 业务参数
//            'name'         => (string)$name,
//            'phone'        => (string)$phone,
//            'id_card_no'   => (string)$id_number,
//            'bank_card_no' => (string)$card_no,
//            'bank_id'      => (string)$bank_id,
//        ];
//        $service = new JshbService();
//        $ret = $service->preSignNew($data);
//        $service = Yii::$container->get('JshbService');
//        $res = $service->preSignNew($data);
//        if ($res && isset($res['code']) ){
//            if ( (!isset($res['data']) && $res['code'] == 0 && $res['data']['status'] != 'bind') || $res['code'] == 1 ){
//                $message = '对不起，您有银行卡签约失败！';
//                return UserExceptionExt::throwCodeAndMsgExt($message);
//            }
//        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $info = new CardInfo();
            $info->user_id = $curUser->id;
            $info->bank_id = $bank_id;
            $info->bank_name = BankConfig::$bankInfo[$bank_id];
            $info->card_no = $card_no;
            $info->type = 2;
            $info->phone = $phone;
            $info->status = CardInfo::STATUS_SUCCESS;
            $info->main_card = CardInfo::MAIN_CARD;//现在只能绑卡一次，默认设置主卡
            $info->source_id = (isset($params['source_id']) && $params['source_id']) ? $params['source_id'] : LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
            $verify_info->real_bind_bank_card_status = UserVerification::VERIFICATION_BIND_BANK_CARD;

            $channel_id=$params['channel_id'];
            $bind_card_info = new BindCardInfo();
            $bind_card_info->user_id = $user_id;
            $bind_card_info->result = 1;
            $bind_card_info->pay_channel = CardInfo::$channel_abbreviation[$channel_id];
            $bind_card_info->customer_status = 'bind';
            $bind_card_info->payment_sign_status = '01';
            $bind_card_info->bank_sign_status = '00';
            $bind_card_info->created_at=time();
            $bind_card_info->updated_at=time();

            if ($info->validate() && $verify_info->validate()) {
                if ($info->save() && $verify_info->save()) {
                    $bind_card_info->card_id = $info->id;
                    if(!$bind_card_info->save()){
                        $transaction->rollBack();
                        return UserExceptionExt::throwCodeAndMsgExt("银行卡状态保存失败");
                    }
                    if ($is_rebind) {//重新绑定的话，将之前的卡设置为副卡
                        CardInfo::updateAll(['main_card' => CardInfo::MAIN_CARD_NO], 'user_id=' . $info->user_id . ' and main_card=' . CardInfo::MAIN_CARD . ' and id <>' . $info->id);
                    }
                    $transaction->commit();
                    UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_BIND_BANK_CARD]);
                    $data = [];
                    $baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
                    $data[] = [
                        'card_id' => $info->id,
                        'url' => $baseUrl . "/image/bank/bank_" . $bank_id . ".png",
                        'bank_info' => BankConfig::$bankInfo[$bank_id] . CardInfo::$type[$info->type] . " 尾号" . substr($card_no, -4),
                        'main_card' => $info->main_card,
                    ];

                    //事件处理队列    绑卡成功
                    RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                        'event_name' => AppEventService::EVENT_SUCCESS_BIND_CARD,
                        'params' => ['user_id' => $user_id, 'from_app' => Util::t('from_app')],
                    ])]);

                    return [
                        'code' => 0,
                        'message' => '绑定银行卡成功',
                        'data' => [
                            'item' => $data
                        ]
                    ];
                }
            }
            $transaction->rollBack();
            return UserExceptionExt::throwCodeAndMsgExt("银行卡状态保存失败");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }



    /**
     *
     * 绑定信用卡
     * @author guoxiaoyong
     * @param LoanPerson $curUser
     * @param array $params
     * @return mixed
     */
    public function bindCreditCard($curUser, $params)
    {
        $user_id = $curUser['id'];
        $id_number = $curUser['id_number'];
        $name = $curUser['name'];
        $is_rebind = $params && isset($params['rebind']) && $params['rebind'] ? true : false;
        if (empty($id_number) || empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证11");
        }
        $bank_id = intval($params['bank_id']);
        if (empty($bank_id)) {
            return UserExceptionExt::throwCodeAndMsgExt("缺少 bank_id 参数");
        }
        $phone = trim($params['phone']);
        if (empty($phone)) {
            return UserExceptionExt::throwCodeAndMsgExt("请填写手机号码");
        }
        $verify_info = UserVerification::find()->where(['user_id' => $user_id])->one();
        if (!$verify_info || !$verify_info->real_verify_status) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证22");
        }
        $card_no = StringHelper::trimBankCard(trim($params['card_no'])); //消除输入的银行卡中的空格

        # 如果参数中没有指定 skipCheckCardRecord 对卡记录进行验证 判断卡有没有被使用
        if (empty($params['skipCheckCardRecord'])) {

            if (CardInfo::checkCardIsUsed($card_no,$params['source_id'])) {
                return UserExceptionExt::throwCodeAndMsgExt("对不起，该信用卡已被绑定过");
            }
        }

        if (!$is_rebind) {//重新绑定
            $find = Yii::$container->get('userService')->getMainCardInfo($user_id);
            if ($find) {
                return UserExceptionExt::throwCodeAndMsgExt("对不起，您只能绑定一张信用卡");
            }
        }
        # 如果参数中没有指定 skipValidateCaptcha 对验证码进行验证
        if (empty($params['skipValidateCaptcha'])) {
            $code = trim($params['code']);
            $source_id = $params['source_id'];
            if (!UserCaptcha::validateCaptcha($phone, $code, UserCaptcha::TYPE_BIND_CREDIT_CARD,$source_id)) {
                return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
            }
        }

        #如果没有在参数中指定skipSoaValidate 使用soa进行银行卡校验
        if (empty($params['skipSoaValidate'])) {

            $service = new CreditCardAuthenticationService();
            $res = $service->checkCardBin( $card_no, $params['bank_id']);

            if($res['code'] != 0)
            {
                return UserExceptionExt::throwCodeAndMsgExt('请检查信用卡信息是否正确');
            }

            //验证信用卡
            $card_info = $service->check($card_no, $id_number, $name, $phone, $user_id);
            if (false == $card_info) {
                return UserExceptionExt::throwCodeAndMsgExt("数据有误，验证银行卡类型失败");
            }
            if (isset($card_info['code']) && (0 != $card_info['code'])) {
                $message = str_replace(["口袋", "口袋理财"], APP_NAMES, $card_info['message']);
                return UserExceptionExt::throwCodeAndMsgExt($message);
            }
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $info = new CardInfo();
            $info->user_id = $curUser->id;
            $info->bank_id = $bank_id;
            $info->bank_name = BankConfig::$bankInfo[$bank_id];
            $info->card_no = $card_no;
            $info->type = 1;
            $info->phone = $phone;
            $info->status = CardInfo::STATUS_SUCCESS;
            $info->main_card = CardInfo::MAIN_CARD;//现在只能绑卡一次，默认设置主卡
            $info->source_id = (isset($params['source_id']) && $params['source_id']) ? $params['source_id'] : LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
            $verify_info->real_bind_bank_card_status = UserVerification::VERIFICATION_BIND_BANK_CARD;

            if ($info->validate() && $verify_info->validate()) {
                if ($info->save() && $verify_info->save()) {
                    if ($is_rebind) {//重新绑定的话，将之前的卡设置为副卡
                        CardInfo::updateAll(['main_card' => CardInfo::MAIN_CARD_NO], 'user_id=' . $info->user_id . ' and main_card=' . CardInfo::MAIN_CARD . ' and id <>' . $info->id);
                    }
                    $transaction->commit();
                    UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_BIND_BANK_CARD]);
                    $data = [];
                    $baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
                    $data[] = [
                        'card_id' => $info->id,
                        'url' => $baseUrl . "/image/bank/bank_" . $bank_id . ".png",
                        'bank_info' => BankConfig::$bankInfo[$bank_id] . CardInfo::$type[$info->type] . " 尾号" . substr($card_no, -4),
                        'main_card' => $info->main_card,
                    ];

                    //事件处理队列    绑卡成功
                    RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                        'event_name' => AppEventService::EVENT_SUCCESS_BIND_CARD,
                        'params' => ['user_id' => $user_id, 'from_app' => Util::t('from_app')],
                    ])]);

                    return [
                        'code' => 0,
                        'message' => '绑定银行卡成功',
                        'data' => [
                            'item' => $data
                        ]
                    ];
                }
            }
            $transaction->rollBack();
            return UserExceptionExt::throwCodeAndMsgExt("银行卡状态保存失败");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 对已经绑定的主卡和副卡进行切换
     * @param LoanPerson $user 用户
     * @param CardInfo $main_card 主卡 切换后将变成副卡
     * @param CardInfo $old_card 副卡 切换后将变成主卡
     * @param array $params 自定义参数
     * @param float $old_card_phone 旧卡手机号 在参数$old_card没有手机号的情况下， 通过该参数设置旧卡的手机号
     * @param int $source 马甲包id
     * @return []
     */
    public function switchCard($user, $main_card, $old_card, $params = [], $old_card_phone = null,$source= null)
    {
        $user_id = $user->id;
        $id_number = $user->id_number;
        $name = $user->name;
        if (empty($id_number) || empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        if (!$user->userVerification || !$user->userVerification->real_verify_status) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        if ($main_card->user_id != $user->id || $old_card->user_id != $user->id) {
            return UserExceptionExt::throwCodeAndMsgExt("用户ID不一致");
        }

        if ($old_card->type != CardInfo::TYPE_DEBIT_CARD) {
            return UserExceptionExt::throwCodeAndMsgExt("只支持切换到储蓄卡");
        }

        #检查订单的记录 可以设定参数跳过
        if (empty($params['skipCheckOrder']) && !CardInfo::checkCanRebind($user_id)) {
            return UserExceptionExt::throwCodeAndMsgExt("还有未完成订单，因此不能重新绑卡");
        }

        if (!$old_card->phone && !$old_card_phone) {
            return UserExceptionExt::throwCodeAndMsgExt("由于该银行卡之前没有手机号，需要传入手机号");
        } else if (!$old_card->phone || $old_card->phone != $old_card_phone) {
            $old_card->phone = $old_card_phone;
            $old_card->updateAttributes(['phone']);
        }

        if (!$old_card->bank_id || !$old_card->card_no) {
            return UserExceptionExt::throwCodeAndMsgExt("该银行卡缺少银行卡号，因此不能重新绑卡");
        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            if(isset($params['channel_id'])){
                $channel_id=$params['channel_id'];
                $pay_channel=CardInfo::$channel_abbreviation[$channel_id];
                $bind_card_info=BindCardInfo::find()->where(['card_id'=>$old_card->id,'pay_channel'=>$pay_channel])->one();
                if(empty($bind_card_info)){
                    $bind_card_info = new BindCardInfo();
                    $bind_card_info->user_id = $user_id;
                    $bind_card_info->result = 1;
                    $bind_card_info->pay_channel = $pay_channel;
                    $bind_card_info->customer_status = 'bind';
                    $bind_card_info->payment_sign_status = '01';
                    $bind_card_info->bank_sign_status = '00';
                    $bind_card_info->created_at=time();
                    $bind_card_info->updated_at=time();
                    $bind_card_info->card_id = $old_card->id;
                    $bind_card_info->save();
                }
            }
            $old_card->main_card = CardInfo::MAIN_CARD;
            $old_card->updateAttributes(['main_card']);
            CardInfo::updateAll(['main_card' => CardInfo::MAIN_CARD_NO], 'user_id=' . (int)$user_id . ' and main_card=' . CardInfo::MAIN_CARD . ' and id <>' . $old_card->id .' and source_id = '.$source);
            if (!$user->userVerification->real_bind_bank_card_status) {
                $user->userVerification->real_bind_bank_card_status = UserVerification::VERIFICATION_BIND_BANK_CARD;
                $user->userVerification->updateAttributes(['real_bind_bank_card_status']);
            }

            $transaction->commit();

            $data = [
                [
                    'card_id' => $old_card->id,
                    'url' => Yii::$app->getRequest()->getAbsoluteBaseUrl() . "/image/bank/bank_" . $old_card->bank_id . ".png",
                    'bank_info' => BankConfig::$bankInfo[$old_card->bank_id] . CardInfo::$type[$old_card->type] . " 尾号" . substr($old_card->card_no, -4),
                    'main_card' => $old_card->main_card,
                ]
            ];

            //事件处理队列    绑卡成功
            RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                'event_name' => AppEventService::EVENT_SUCCESS_BIND_CARD,
                'params' => ['user_id' => $user_id, 'from_app' => Util::t('from_app')],
            ])]);
            return [
                'code' => 0,
                'message' => '绑定银行卡成功',
                'data' => [
                    'item' => $data
                ]
            ];
        } catch (\Exception $ex) {
            $transaction->rollBack();
            return [
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }

    /**
     * 外部接口（融360等）调用绑定银行卡,不需要验证码 请在bindCard添加params相关的参数跳过短信验证和soa验证
     * @deprecated
     * @param \common\models\LoanPerson $loanPerson 借款人
     * @param array $params 银行卡信息
     * @return array  银行卡绑定成功或失败信息
     */
    public static function interfaceBindCard($loanPerson, $params)
    {
        if (!$loanPerson->name || !$loanPerson->id_number) {
            throw new \Exception("您还没有实名认证");
        }
        $bank_id = intval($params['bank_id']);
        if (empty($bank_id)) {
            throw new \Exception("缺少 bank_id 参数");
        }
        $phone = floatval($params['phone']);
        if (!$phone) {
            throw new \Exception("请填写手机号码");
        }
        $verify_info = UserVerification::find()->where(['user_id' => $loanPerson->id])->one();
        if (!$verify_info) {
            throw new \Exception("缺少用户验证信息");
        }
        $card_no = StringHelper::trimBankCard($params['card_no']); //消除输入的银行卡中的空格

        //$check = CardInfo::checkCardIsUsed($card_no);
        //if ($check) {
        //    throw new \Exception("对不起，该银行卡已被绑定过");
        //}
        //if(!$is_rebind){//重新绑定
        //    $find = Yii::$container->get('userService')->getMainCardInfo($user_id);
        //    if($find) {
        //        return UserExceptionExt::throwCodeAndMsgExt("对不起，您只能绑定一张借记卡");
        //    }
        //}

        //$code = trim($params['code']);
        //if (!UserCaptcha::validateCaptcha($phone, $code, UserCaptcha::TYPE_BIND_BANK_CARD)) {
        //    return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        //}
//        $card_info = KoudaiSoa::instance('BankCard')->cardBin($card_no);
        $card_info = JshbService::cardBin($card_no,$bank_id);

        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            throw new \Exception("请检查银行卡号是否正确");
        }

        if ($bank_id != $card_info['data']['bank_id']) {
            throw new \Exception("请选择正确的银行");
        }

        //test或dev环境下 并且设置了 skipVerify 不验证code
        if ((YII_ENV == 'test' || YII_ENV == 'dev') && !empty($params['skipVerify'])) {
            $card_info = [
                'code' => 0
            ];
        } else {
//            $card_info = KoudaiSoa::instance('BankCard')->cardVerify($card_no, $phone, $loanPerson->id_number, $loanPerson->name, ['client_ip' => Util::getUserIP()]);
            $card_info = JshbService::cardVerify($card_no, $phone, $loanPerson->id_number, $loanPerson->name ,$bank_id);
        }

        if (false == $card_info) {
            throw new \Exception("数据有误，验证银行卡类型失败");
        }
        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            $message = str_replace(["口袋", "口袋理财"], APP_NAMES, $card_info['message']);
            throw new \Exception($message);
        }

        $data = [
            // 业务参数
            'name'         => (string)$loanPerson->name,
            'phone'        => (string)$phone,
            'id_card_no'   => (string)$loanPerson->id_number,
            'bank_card_no' => (string)$card_no,
            'bank_id'      => (string)$bank_id,
        ];
        $service = Yii::$container->get('JshbService');
        $service->preSignNew($data);

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $info = new CardInfo();
            $info->user_id = $loanPerson->id;
            $info->bank_id = $bank_id;
            $info->bank_name = BankConfig::$bankInfo[$bank_id];
            $info->card_no = $card_no;
            $info->type = 2;
            $info->phone = $phone;
            $info->status = CardInfo::STATUS_SUCCESS;
            $info->main_card = CardInfo::MAIN_CARD;//现在只能绑卡一次，默认设置主卡
            $verify_info->real_bind_bank_card_status = UserVerification::VERIFICATION_BIND_BANK_CARD;

            if ($info->validate() && $verify_info->validate()) {
                if ($info->save() && $verify_info->save()) {
                    //if($is_rebind){//重新绑定的话，将之前的卡设置为副卡
                    //    CardInfo::updateAll(['main_card'=>CardInfo::MAIN_CARD_NO],'user_id='.$info->user_id.' and main_card='.CardInfo::MAIN_CARD.' and id <>'.$info->id);
                    //}
                    $transaction->commit();
                    //UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_BIND_BANK_CARD]);
                    //$data = [];
                    //$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
                    //$data[] = [
                    //    'card_id' => $info->id,
                    //    'url' => $baseUrl."/image/bank/bank_".$bank_id.".png",
                    //    'bank_info' => BankConfig::$bankInfo[$bank_id].CardInfo::$type[$info->type]." 尾号".substr($card_no,-4),
                    //    'main_card' => $info->main_card,
                    //];

                    //事件处理队列    绑卡成功
                    RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                        'event_name' => AppEventService::EVENT_SUCCESS_BIND_CARD,
                        'params' => ['user_id' => $loanPerson->id, 'from_app' => Util::t('from_app')],
                    ])]);

                    return [
                        'code' => 0,
                        'card_id' => $info->id,
                        'message' => '绑卡成功',
                    ];
                }
            }
            $transaction->rollBack();
            throw new \Exception("银行卡状态保存失败");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 添加副卡
     * @param LoanPerson $curUser
     * @param array $params
     * @return unknown
     */
    public function bindAssistCard($curUser, $params)
    {
        $user_id = $curUser['id'];
        $id_number = $curUser['id_number'];
        $name = $curUser['name'];
        if (empty($id_number) || empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }
        $bank_id = intval($params['bank_id']);
        if (empty($bank_id)) {
            return UserExceptionExt::throwCodeAndMsgExt("缺少 bank_id 参数");
        }
        $phone = trim($params['phone']);
        if (empty($phone)) {
            return UserExceptionExt::throwCodeAndMsgExt("请填写手机号码");
        }
        $verify_info = UserVerification::find()->where(['user_id' => $user_id])->one();
        if (!$verify_info || !$verify_info->real_verify_status) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }
        $card_no = StringHelper::trimBankCard(trim($params['card_no'])); //消除输入的银行卡中的空格

        # 如果参数中没有指定 skipValidateCaptcha 对验证码进行验证
        $code = trim($params['code']);
        if (empty($params['skipValidateCaptcha'])) {
            if (empty($params['source_id'])) {
                \yii::warning( sprintf('bindAssistCard_source_missing: %s', json_encode($params)), LogChannel::SMS_GENERAL );
                $params['source_id'] = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
            }
//            if (!UserCaptcha::validateCaptcha($phone, $code, UserCaptcha::TYPE_BIND_BANK_CARD, $params['source_id'])) {
//                return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
//            }
        }
        $source_id = $params['source_id'];
        # 如果参数中没有指定 skipCheckCardRecord 对卡记录进行验证 判断卡有没有被使用
        if (empty($params['skipCheckCardRecord'])) {
            if ($check = CardInfo::checkCardIsUsed($card_no,$source_id)) {
                if (count($check) == 1 && $check[0] == $user_id) {
                    $card_record = CardInfo::findOne(['user_id' => $user_id, 'card_no' => $card_no, 'type' => CardInfo::TYPE_DEBIT_CARD]);
                    if ($card_record) {
                        return UserExceptionExt::throwCodeAndMsgExt("对不起，该银行卡已被绑定过");
//                        if ($card_record->phone != $phone) {
//                            $card_record->phone = $phone;
//                            $card_record->status = CardInfo::STATUS_SUCCESS;
//                            $card_record->updated_at = time();
//                            $card_record->save();
//                        }
//                        UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_BIND_BANK_CARD]);
//                        return [
//                            'code' => 0,
//                            'message' => '绑定银行卡成功',
//                            'data' => $card_record->id
//                        ];
                    }
                }
//                return UserExceptionExt::throwCodeAndMsgExt("对不起，该银行卡已被绑定过");
            }
        }

        #如果没有在参数中指定skipSoaValidate 使用soa进行银行卡校验
        if (empty($params['skipSoaValidate'])) {
//            $card_info = KoudaiSoa::instance('BankCard')->cardBin($card_no);
            $card_info = JshbService::cardBin($card_no,$bank_id);

            if (isset($card_info['code']) && (0 != $card_info['code'])) {
                return UserExceptionExt::throwCodeAndMsgExt("请检查银行卡号是否正确");
            }
            if ($bank_id != $card_info['data']['bank_id']) {
                return UserExceptionExt::throwCodeAndMsgExt("请选择正确的银行");
            }

            //验证银行卡
//            $card_info = KoudaiSoa::instance('BankCard')->cardVerify($card_no, $phone, $id_number, $name, ['client_ip' => Util::getUserIP()]);
            $channel_id = 2;
            $card_info = JshbService::cardQuickVerify($card_no, $phone, $id_number, $name ,$bank_id ,$channel_id ,$code);
            if (false == $card_info) {
                return UserExceptionExt::throwCodeAndMsgExt("数据有误，验证银行卡类型失败");
            }
            if (isset($card_info['code']) && (0 != $card_info['code'])) {
                ErrorMessage::getMessage($user_id, "银行卡四要素鉴权失败，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
                return UserExceptionExt::throwCodeAndMsgExt($card_info['message']);
            }
        }

        //签约绑卡
//        $data = [
//            // 业务参数
//            'name'         => (string)$name,
//            'phone'        => (string)$phone,
//            'id_card_no'   => (string)$id_number,
//            'bank_card_no' => (string)$card_no,
//            'bank_id'      => (string)$bank_id,
//            'code'      => (string)$code,
//            'source_id'      => (string)$source_id,
//        ];
//        $service = new JshbService();
//        $res = $service->preSignNew($code);
//        if ($res && isset($res['code']) ){
//            if ( (!isset($res['data']) && $res['code'] == 0 && $res['data']['status'] != 'bind') || $res['code'] == 1 ){
//                return UserExceptionExt::throwCodeAndMsgExt("对不起，您有银行卡签约失败！");
//            }
//        }

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $info = new CardInfo();
            $info->user_id = $curUser->id;
            $info->bank_id = $bank_id;
            $info->bank_name = BankConfig::$bankInfo[$bank_id];
            $info->card_no = $card_no;
            $info->type = 2;
            $info->phone = $phone;
            $info->status = CardInfo::STATUS_SUCCESS;
            $info->main_card = CardInfo::MAIN_CARD_NO;//现在只能绑卡一次，默认设置主卡
            $info->source_id = $source_id;//现在只能绑卡一次，默认设置主卡

            if ($info->validate() && $info->save()) {
                $transaction->commit();
                UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_BIND_BANK_CARD]);
                return [
                    'code' => 0,
                    'message' => '绑定银行卡成功',
                    'data' => $info->id
                ];
            }
            $transaction->rollBack();
            return UserExceptionExt::throwCodeAndMsgExt("银行卡状态保存失败");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 检查银行卡对应的银行是否正确
     * @param string $card_no 银行卡号
     * @param integer $bank_id 银行ID
     * @throws \Exception
     */
    public static function checkCardBank($card_no, $bank_id)
    {
//        $card_bank_info = KoudaiSoa::instance('BankCard')->cardBin($card_no);
        $card_bank_info = JshbService::cardBin($card_no,$bank_id);

        if (isset($card_bank_info['code']) && (0 != $card_bank_info['code'])) {
            Yii::error("检查卡银行信息错误（银行ID {$bank_id} - 卡号 {$card_no}），结果为:" . var_export($card_bank_info, 1));
            throw new \Exception("请检查卡号对应的银行是否正确");
        }
        if ($bank_id != $card_bank_info['data']['bank_id']) {
            throw new \Exception("请选择正确的银行");
        }
    }


    /**
     * 检查银行卡对应的银行是否正确 | 银行卡类型
     * @param string $card_no 银行卡号
     * @param integer $bank_id 银行ID
     * @throws \Exception
     */
    public static function checkCardBin($card_no, $bank_id)
    {
//        $card_info = KoudaiSoa::instance('BankCard')->cardBin($card_no);
        $card_info = JshbService::cardBin($card_no,$bank_id);

        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            throw new \Exception("请检查卡号对应的银行是否正确");
        }

        if(isset($card_info['data']['card_type']) && $card_info['data']['card_type'] != 1){
            \yii::warning( sprintf('uid_bind_credit_card %s, %s, %s',
                '', json_encode($card_no), json_encode($card_info)
            ), LogChannel::USER_CARD );
            throw new \Exception("卡片类型错误, 不支持储蓄卡之外的卡。");
        }

        if ($bank_id != $card_info['data']['bank_id']) {
            throw new \Exception("请选择正确的银行");
        }
    }



    /**
     * 检查银行卡信息是否合法
     * @param string $card_no 银行卡号
     * @param string $phone 手机号
     * @param string $id_number 身份证号码
     * @param string $name 姓名
     * @throw \Exception
     */
    public static function checkCardInfo($card_no, $phone, $id_number, $name ,$bank_id)
    {
        //验证银行卡
//        $card_info = KoudaiSoa::instance('BankCard')->cardVerify($card_no, $phone, $id_number, $name, ['client_ip' => Util::getUserIP()]);
        $card_info = JshbService::cardVerify($card_no, $phone, $id_number, $name ,$bank_id);
        if (false == $card_info) {
            throw new \Exception("数据有误，验证银行卡类型失败");
        }
        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            $message = str_replace(["口袋", "口袋理财"], APP_NAMES, $card_info['message']);
            if ($message == '系统异常') {
                $message = '绑卡失败';
            }
            throw new \Exception($message);
        }
    }

    /**
     * 获取用户的旧卡记录
     * @param string $card_no 卡号
     * @param integer $user_id 用户ID
     * @return CardInfo|null 有用户的卡记录返回卡模型， 没有返回空
     * @throws \Exception 有其他用户ID的卡记录返回抛出异常
     */
    public static function getOldCardRecord($card_no, $user_id,$soucre = '')
    {
        $card_infos = CardInfo::find()->where(["card_no" => $card_no,'source_id'=>$soucre])->all();
        $card_info_record = null;
        foreach ($card_infos as $card_info) {
            if ($card_info->user_id != $user_id) {
                throw new \Exception("该银行卡已被绑定");
            } else {
                $card_info_record = $card_info;
            }
        }
        return $card_info_record;
    }
}
