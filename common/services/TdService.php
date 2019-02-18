<?php
namespace common\services;

use Yii;
use common\base\RESTComponent;
use common\helpers\CommonHelper;
use common\helpers\CurlHelper;
use common\models\CreditTd;
use common\models\CreditTdLog;
use common\models\ErrorMessage;
use common\models\LoanPerson;

use Curl\Curl;

/**
 * Class TdService
 *
 * @package common\services
 */
class TdService extends RESTComponent
{
    static $biz_pre_loan = 'sdhbdqweb'; //业务流 - 贷前
    static $biz_auth = 'sdhbcheckweb'; //业务流 - 银行卡四要素核验 //TODO

    //信贷保镖API
    public $bodyguard_url = 'https://api.tongdun.cn/bodyguard/apply/v4';
    public $partner_code = 'sdhb';
    public $app_name = 'sdhb_web';
    public $partner_key = '6acc9871d9ac43eb8da1ef9b232596a7';

    private $report_id = '';
    public $data = '';
    public $result = '';
    public $message = '';
    public $model = null;

    /**
     * 旧的v5接口
     * @param LoanPerson $loanPerson
     *
     * @return $this
     */
    public function getReportId(LoanPerson $loanPerson) {
        $result = $this->getReportData($loanPerson->name, $loanPerson->id_number, $loanPerson->phone);
        if($result){
            if(isset($result['success']) && $result['success']){
                $this->report_id = $result['report_id'];
                $model = CreditTd::findLatestOne(['person_id'=>$loanPerson['id']]);
                if(is_null($model)){
                    $model = new CreditTd();
                }
                $model->person_id = $loanPerson['id'];
                $model->report_id = $this->report_id;
                $model->status = 1;

                $log = new CreditTdLog();
                $log->person_id = $loanPerson['id'];
                $log->report_id = $this->report_id;
                try{
                    $log->admin_username = Yii::$app->user->identity->username;
                }catch (\Exception $e){
                    $log->admin_username = 'auto shell';
                }

                $log->price = 0;

                $transaction = Yii::$app->db_kdkj->beginTransaction();
                try{
                    if($model->save() && $log->save()){
                        $transaction->commit();
                        $this->result = true;
                        $this->message = '操作成功';
                    }else{
                        $transaction->rollBack();
                        $this->result = false;
                        $this->message = '数据保存失败，请重试';
                    }
                }catch(\Exception $e){
                    $transaction->rollBack();
                    $this->result = false;
                    $this->message = '数据保存失败，请重试';
                }

            }else{
                $this->result = false;
                $this->message = $result['reason_desc'];
                ErrorMessage::getMessage($loanPerson['id'],$result['reason_desc'],ErrorMessage::SOURCE_TD);
            }
        }else{
            $this->result = false;
            $this->message = '数据获取失败，请重试';
            ErrorMessage::getMessage($loanPerson['id'],'数据获取失败',ErrorMessage::SOURCE_TD);
        }
        return $this;
    }

    /**
     * 获取同盾报告
     * @param $name
     * @param $id_number
     * @param $phone
     * @return bool|mixed
     */
    public function getReportData($name, $id_number, $phone) {
        $url = $this->_requestUrl['getReportData'] . '?partner_code='.$this->partner_code.'&partner_key='.$this->partner_key.'&app_name='.$this->app_name;
        $post_data = [
            "name=$name",
            "id_number=$id_number",
            "mobile=$phone"
        ];
        $post_data = implode('&',$post_data);
        return CurlHelper::curlHttp($url, 'tongdun', $post_data);
    }

    public function getReportContent(LoanPerson $loanPerson) {
        $model = CreditTd::findLatestOne(['person_id'=>$loanPerson['id']]);
        if(is_null($model) || $model['status'] != CreditTd::STATUS_1){
            $this->result = false;
            $this->message = '用户数据未提交';
        }
        $report_id = $model['report_id'];

        $result = $this->getTdReport($report_id);
        if($result){
            if(isset($result['success']) && $result['success']){
                $content = json_encode($result);
                $model->data = $content;
                $model->status = CreditTd::STATUS_2;
                $this->result = true;
                $this->data = $result;
                $this->message = '获取成功';
            }else{
                ErrorMessage::getMessage($loanPerson['id'],$result['reason_desc'],ErrorMessage::SOURCE_TD);
                $model->status = CreditTd::STATUS_3;
                $this->result = false;
                $this->message = 'reason_desc';
            }
        }else{
            $model->status = CreditTd::STATUS_3;
            $this->result = false;
            $this->message = '接口访问失败';
            ErrorMessage::getMessage($loanPerson['id'],'接口访问失败',ErrorMessage::SOURCE_TD);
        }
        if(!$model->save()){
            $this->result = false;
            $this->message = $this->message.',数据保存失败';
        }
        $this->model = $model;
        return $this;
    }

    /**
     * 新版 bodyguard 报告
     * @link http://credittest.tongdun.cn/creditbodyguard/bodyguard/bodyguardApi.htm
     * @param $name
     * @param $id_number
     * @param $phone
     * @param $biz_code 业务流编号
     *
     * @return array
     */
    public function bodyguardReq(array $params) {
        $curl = new Curl();

        $url = $this->bodyguard_url . '?'. http_build_query([
                    'partner_code' => $this->partner_code,
                    'partner_key' => $this->partner_key,
                    'app_name' => $this->app_name,
                ]);
        $curl->setHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8');
        $curl->setDefaultJsonDecoder($assoc = true);
        $curl->setConnectTimeout(5);
        $curl->setTimeout(5);

        if (CommonHelper::isLocal()) {
            $curl->setOpt(CURLOPT_HTTPPROXYTUNNEL, 1);
            $curl->setOpt(CURLOPT_PROXY, '127.0.0.1:8888');
        }

        $curl->post($url, $params);

        if ($curl->error || (! is_array($curl->response)) || empty($curl->response)) {
            if (CommonHelper::isLocal()) {
                var_dump($curl);
            }

            \yii::error($curl, 'credit.td');
            throw new \Exception('同盾请求失败');
        }

        return $curl->response;
    }

    /**
     * 贷前验证
     * @param LoanPerson $loan_person
     * @return mixed
     */
    public function bizPreLoan(LoanPerson $loan_person) {
        $resp = $this->bodyguardReq([
                'account_name' => $loan_person->name,
                'id_number' => $loan_person->id_number,
                'account_mobile' => $loan_person->phone,
                'biz_code' => self::$biz_pre_loan,
            ]);

        if ( $resp['success'] === false || (!isset($resp['result_desc'])) || (!isset($resp['result_desc']['ANTIFRAUD'])) ) {
            $this->result = FALSE;
            $this->message = sprintf('(%s)%s', $resp['reason_code'], $resp['reason_desc']);
            ErrorMessage::getMessage($loan_person->id, $this->message, ErrorMessage::SOURCE_TD);
        }
        else {
            $td = new CreditTd();
            $td->person_id = $loan_person->id;
            $td->data = json_encode($resp);
            $td->report_id = sprintf('%s-%s', self::$biz_pre_loan, $resp['id']);
            $td->status = CreditTd::STATUS_2; //已获取
            if ($td->save()) { //报告保存成功
                $this->result = TRUE;
                $this->message = sprintf('同盾报告获取成功(%s)', $resp['id']);
                $this->model = $td;
            }
            else { //报告保存失败
                $this->result = FALSE;
                $this->message = '同盾报告保存失败';
                ErrorMessage::getMessage($loan_person->id, $this->message, ErrorMessage::SOURCE_TD);
            }
        }

        return $this->result;
    }

    /**
     * 四要素核验
     * @param $name
     * @param $id_number
     * @param $phone
     * @param $card_number
     *
     * @return bool|string 验证结果
     */
    public function bizAuth($name, $id_number, $phone, $card_number) {
        $resp = $this->bodyguardReq([
            'account_name' => $name,
            'id_number' => $id_number,
            'account_mobile' => $phone,
            'card_number' => $card_number,
            'biz_code' => self::$biz_auth,
        ]);
        if (CommonHelper::isLocal()) {
            var_dump($resp);
        }

        if ( $resp['success'] === false || (!isset($resp['result_desc'])) || (!isset($resp['result_desc']['ANTIFRAUD_INFOQUERY'])) ) {
            $this->result = FALSE;
            $this->message = sprintf('(%s)%s', $resp['reason_code'], $resp['reason_desc']);
        }
        else {
            if (isset($resp['result_desc']['ANTIFRAUD_INFOQUERY']['CreditCardNameIdMobileCheck']['card_four_element_consistence'])
                && $resp['result_desc']['ANTIFRAUD_INFOQUERY']['CreditCardNameIdMobileCheck']['card_four_element_consistence'] == 0) {

                $this->result = TRUE;
                $this->message = sprintf('同盾验证成功(%s)', $resp['id']);
            }
            else {
                $this->result = FALSE;
                $this->message = sprintf('同盾验证失败(%s)', $resp['id']);
            }
        }

        return $this->result;
    }

    /**
     * 同盾银行卡四要素核验
     * @param $name
     * @param $id_number
     * @param $phone
     * @param $card_number
     *
     * @return array 验证结果
     */
    public function bizTdAuthBankCard($name, $id_number, $phone, $card_number) {
        $resp = $this->bodyguardReq([
            'account_name' => $name,
            'id_number' => $id_number,
            'account_mobile' => $phone,
            'card_number' => $card_number,
            'biz_code' => self::$biz_auth,
        ]);
        if (CommonHelper::isLocal()) {
            var_dump($resp);
        }

        //网络状态
        $net_status=200;
        if ( $resp['success'] === false || (!isset($resp['result_desc'])) || (!isset($resp['result_desc']['ANTIFRAUD_INFOQUERY'])) ) {
            $this->result = FALSE;
            //调用失败
            $net_status=500;
            $this->message = sprintf('(%s)%s', $resp['reason_code'], $resp['reason_desc']);
        }
        else {
            if (isset($resp['result_desc']['ANTIFRAUD_INFOQUERY']['CreditCardNameIdMobileCheck']['card_four_element_consistence'])
                && $resp['result_desc']['ANTIFRAUD_INFOQUERY']['CreditCardNameIdMobileCheck']['card_four_element_consistence'] == 0) {

                $this->result = TRUE;
                $this->message = sprintf('同盾验证成功(%s)', $resp['id']);
            }
            else {
                $this->result = FALSE;
                $this->message = sprintf('同盾验证失败(%s)', $resp['id']);
            }
        }

        return array('result'=>$this->result,'netstatus'=>$net_status);
    }

    /**
     * 获取同盾报告
     * @param $report_id
     * @return bool|mixed
     */
    public function getTdReport($report_id)
    {
        $url = $this->_requestUrl['getTdReport'] . '?partner_code='.$this->partner_code.'&partner_key='.$this->partner_key.'&app_name='.$this->app_name.'&report_id='.$report_id;
        return CurlHelper::curlHttp($url,'tongdun');
    }


    public function getResult()
    {
        return $this->result;
    }

    public function getMessage()
    {
        return $this->message;
    }

}
