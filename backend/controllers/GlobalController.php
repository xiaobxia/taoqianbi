<?php
namespace backend\controllers;

use common\models\BankConfig;
use common\models\CardInfo;
use Yii;
use common\helpers\Url;

use common\models\Setting;
use common\api\RedisQueue;
use yii\db\Query;

/**
 * 后台全局配置 controller
 */
class GlobalController extends BaseController {

    const ACTION_PAGE_DISPLAY = 'page_display';
    const ACTION_PROCESS = 'process';

    public function actionConfig() {
        $skey_list = array(
            'auto_send_withdraw_cmb',
        );
        $message = array();
        foreach ($skey_list as $skey) {
        	$set =  Setting::findByKey($skey);
        	if($set){
        		$message[$skey] = $set->svalue;
        	}
        }
        if ($this->request->get('setting_submit')) {
            $config_setting = $this->request->get();
            foreach ($skey_list as $skey) {
                if (!Setting::updateSetting($skey, $config_setting[$skey])) {
                    return $this->redirectMessage('设置失败', self::MSG_ERROR, Url::toRoute('global/config'));
                }
            }
            return $this->redirectMessage('设置成功', self::MSG_SUCCESS, Url::toRoute('global/config'));
        }

        return $this->render('config', [
            'message' => $message,
        ]);
    }

    /**
     * @name 设置免密码登录手机号
     * 设置免密码登录手机号
     */
    public function actionNoLoginPhone(){
        $param_key_quota = "app_no_login_phone_list";
        $model = Setting::findByKey($param_key_quota);

        $request = Yii::$app->request;
        if ($request->isPost) {
            $postVal = $request->post();
            $setObj  = isset($postVal["Setting"]) ? $postVal["Setting"] : array("svalue"=>"");
            $model->svalue =  $setObj["svalue"] ;
            if ($model->save()) {
                return $this->redirectMessage('编辑成功', self::MSG_SUCCESS, Url::toRoute('no-login-phone'));
            } else {
                return $this->redirectMessage('编辑失败', self::MSG_ERROR);
            }
        }

        return $this->render('no-login-phone', [
            'setting' => $model,
        ]);
    }

    /**
     * @name 设置首页待抢金额
     */
    public function actionDaily() {
        $param_key_quota = "app_max_daily_quota";
        $param_golden_quota = "app_golden_daily_quota";
        $param_key_ratio = "app_enlarge_ratio";

        $request = Yii::$app->request;
        $max_loan_cache_key = sprintf("%s:%s", RedisQueue::USER_TODAY_LOAN_MAX_AMOUNT, date("Ymd"));
        $nowAmount = RedisQueue::get(["key" => $max_loan_cache_key]);

        $max_loan_golden_key = sprintf("%s:%s",RedisQueue::USER_TODAY_LOAN_GOLDEN_AMOUNT,date("Ymd"));
        $goldenAmount = RedisQueue::get(["key"=>$max_loan_golden_key]);

        // 递减的费率
        $setting_ratio = Setting::findByKey($param_key_ratio);
         // 白卡的费率
        $setting_obj = Setting::findByKey($param_key_quota);
        // 金卡的放款额度
        $setting_golden = Setting::findByKey($param_golden_quota);

        $db = new Query();
        $zhifubao=$db->select('status,bentime,endtime')->from('tb_zhifustatus')->where(['id'=>1])->one();

        if ($request->isPost) {
            $postVal = $request->post();
            $setObj  = isset($postVal["Setting"]) ? $postVal["Setting"] : array("svalue"=>0);

            if($postVal["type"] == "4"){
                $start_date=strtotime($postVal["start_date"]);
                $end_date=strtotime($postVal["end_date"]);
                $db = \Yii::$app->db;
                $db->createCommand()->update('`tb_zhifustatus`',['bentime'=>$start_date,'endtime'=>$end_date],'id=:id',[':id'=>1])->execute();
            }
            elseif ($postVal["type"] == "3") { // 处理金卡的递减额度
                $amount  = intval(["svalue"]) > 0 ? intval($setObj["svalue"]) * 1000000 : 200000000;
                $setting_golden->svalue = sprintf("%s",$amount);
                if(!$setting_golden->save()){
                    return $this->redirectMessage('保存失败', self::MSG_ERROR, Url::toRoute('global/daily'));
                }

                $expire_time = strtotime(date('Y-m-d 23:59:59', time())) - time();
                RedisQueue::set(["expire" => $expire_time, "key" => $max_loan_golden_key, "value" => $amount]);
            }
            elseif ($postVal["type"] == "2") { // 白卡待抢金额
                $amount  = intval(["svalue"]) > 0 ? intval($setObj["svalue"]) * 1000000 : 250000000;

                $setting_obj->svalue = sprintf("%s",$amount);
                if (!$setting_obj->save()) {
                    return $this->redirectMessage( '保存失败', self::MSG_ERROR, Url::toRoute( 'global/daily' ) );
                }

                $expire_time = strtotime(date('Y-m-d 23:59:59')) - time();
                RedisQueue::set(["expire" => $expire_time, "key"=>$max_loan_cache_key, "value" => $amount]);
            }
            else {
                $amount  = intval( [ "svalue" ] ) > 0 ? intval( $setObj[ "svalue" ] ) * 100 : 100;
                $setting_ratio->svalue = sprintf("%s", $amount);
                if (!$setting_ratio->save()) {
                    return $this->redirectMessage( '保存失败', self::MSG_ERROR, Url::toRoute( 'global/daily' ) );
                }
            }

            return $this->redirectMessage('保存成功', self::MSG_SUCCESS, Url::toRoute('global/daily'));
        }

        if (false == $setting_obj) { //白卡
            $setting_obj = new Setting();
            $setting_obj->skey   = $param_key_quota;
            $setting_obj->svalue = 2500000000;
            $setting_obj->save();
        }
        else {
            $setting_obj->svalue = $setting_obj->svalue / 1000000;
        }

        if (false == $setting_golden) { //金卡
            $setting_golden = new Setting();
            $setting_golden->skey   = $param_golden_quota;
            $setting_golden->svalue = 200000000;
            $setting_golden->stext  = "发薪卡的待抢额度";
            $setting_golden->save();
        }
        else {
            $setting_golden->svalue = $setting_golden->svalue / 1000000;
        }

        if (false == $setting_ratio) { //递减的费率
            $setting_ratio = new Setting();
            $setting_ratio->skey = $param_key_ratio;
            $setting_ratio->svalue = 200;
            $setting_ratio->save();
        }
        else {
            $setting_ratio->svalue = $setting_ratio->svalue / 100;
        }

        return $this->render('daily', [
            'setting_obj' => $setting_obj,
            'setting_ratio' => $setting_ratio,
            'setting_golden' => $setting_golden,
            'now_amount'   => number_format($nowAmount/100,2,".",","),
            'golden_amount'   => number_format($goldenAmount/100,2,".",","),
            'zhifubao'=>$zhifubao,
        ]);
    }


    /**
     * @name App警告额度
     */
    public function actionAppCardWarnQuota()
    {
        if($this->request->isPost) {
            /*白卡*/
            $white = Setting::find()->where(['skey'=>'white_card_warn_quota'])->one();
            $w_svalue = intval($this->request->post('white_card_warn_quota'))*100;
            if($white) {
                $white->svalue = $w_svalue;
                $white->save();
            } else {
                $white = new Setting();
                $white->svalue =  $w_svalue;
                $white->skey =  'white_card_warn_quota';
                $white->stext =  APP_NAMES.'警告额度';
                $white->save();
            }
            /*金卡*/
            $golden = Setting::find()->where(['skey'=>'golden_card_warn_quota'])->one();
            $g_svalue = intval($this->request->post('golden_card_warn_quota'))*100;
            if($golden) {
                $golden->svalue = $g_svalue;
                $golden->save();
            } else {
                $golden = new Setting();
                $golden->svalue =  $g_svalue;
                $golden->skey =  'golden_card_warn_quota';
                $golden->stext =  '发行新卡警告额度';
                $golden->save();
            }
            Yii::$app->session->setFlash('message','更新成功');
            return $this->redirect(['global/app-card-warn-quota']);

        } else {
            $configs = Setting::find()->where(['skey'=>'white_card_warn_quota'])->orWhere(['skey'=>'golden_card_warn_quota'])->asArray()->all(Yii::$app->get('db_kdkj_rd'));

            $valus = array_column($configs,'svalue','skey');
            return $this->render('app-card-warn-quota',[
                'values' => $valus,
            ]);
        }
    }


    /**
     * @name 银行卡通道黑名单列表
     */
    public function actionBankCardBlackList()
    {
        $config = Setting::find()->where(['skey'=>'bank_card_black_list'])->one(Yii::$app->get('db_kdkj_rd'));
        $list = json_decode($config->svalue,true)??[];

        $cardList = CardInfo::getCardConfigList();

        $cardTemp = [];
        foreach ($cardList as $card)
        {
            $cardTemp[$card['bank_id']] = $card;
        }

        $data = [];
        foreach ($list as $k=>$l)
        {
            $data[] = array_merge($cardTemp[$k],$l);
        }

        return $this->render('bank-card-black-list',[
            'data_list' => $data,
        ]);
    }

    /**
     * @name 银行卡通道黑名单删除
     */
    public function actionBankCardBlackDel()
    {
        $bankId = intval($this->request->get('bank_id'));
        if (!$bankId)
            return $this->redirectMessage('参数错误！', self::MSG_ERROR);

        $config = Setting::find()->where(['skey' => 'bank_card_black_list'])->limit(1)->one();
        $list = json_decode($config->svalue, true);

        unset($list[$bankId]);

        $config->svalue = json_encode($list);

        if ($config->save())
            return $this->redirect(['global/bank-card-black-list']);
        else
            return $this->redirectMessage('保存失败！', self::MSG_ERROR);
    }


    /**
     * @name 银行卡通道黑名单增加
     */
    public function actionBankCardBlackAdd()
    {
        if($this->request->isPost)
        {
            $config = Setting::find()->where(['skey' => 'bank_card_black_list'])->limit(1)->one();
            if(!isset($config))
                return $this->redirectMessage('没有这个项目！', self::MSG_ERROR);

            $list = json_decode($config->svalue, true)??[];


            $card = $this->request->post('card');

            $begin_time = strtotime($this->request->post('begin_time'));
            $end_time = strtotime($this->request->post('end_time'));

            $remark = $this->request->post('remark');

            $list[$card] = ['begin_time' =>$begin_time, 'end_time' =>$end_time, 'remark' => $remark];
            $config->svalue = json_encode($list);
            if ($config->save())
                return $this->redirect(['global/bank-card-black-list']);
            else
                return $this->redirectMessage('保存失败！', self::MSG_ERROR);
        } else {

            $bankList = BankConfig::$use_platform;
            $cardList = CardInfo::getCardConfigList();

            $cards = array_column($cardList,'bank_name','bank_id');

            return $this->render('bank-card-black-form',[
                'black_info' => [],
                'cards' => $cards,
                'bank_list' => $bankList,
            ]);
        }
    }

    /**
     * @name 银行卡通道黑名单编辑
     */
    public function actionBankCardBlackEdit()
    {
        $bankId = intval($this->request->get('bank_id'));
        if (!$bankId)
            return $this->redirectMessage('参数错误！', self::MSG_ERROR);

        $config = Setting::find()->where(['skey' => 'bank_card_black_list'])->limit(1)->one();
        $config && $list = json_decode($config->svalue, true);
        $list && $blackInfo = $list[$bankId];

        if(!isset($blackInfo)) {
            return $this->redirectMessage('没有这个项目！', self::MSG_ERROR);
        }

        if($this->request->isPost)
        {
            $card = $this->request->post('card');

            $begin_time = strtotime($this->request->post('begin_time'));
            $end_time = strtotime($this->request->post('end_time'));

            $remark = $this->request->post('remark');

            $list[$card] = ['begin_time' =>$begin_time, 'end_time' =>$end_time, 'remark' => $remark];
            $config->svalue = json_encode($list);
            if ($config->save())
                return $this->redirect(['global/bank-card-black-list']);
            else
                return $this->redirectMessage('保存失败！', self::MSG_ERROR);


        } else {

            $cardList = CardInfo::getCardConfigList();

            $cards = array_column($cardList,'bank_name','bank_id');
            $cards = [$bankId=>$cards[$bankId]];

            return $this->render('bank-card-black-form',[
                'black_info' => $blackInfo,
                'cards' => $cards
            ]);
        }
    }

}
