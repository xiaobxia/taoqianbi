<?php
/**
 *
 * Created by PhpStorm.
 * User: user
 * Date: 2017/4/20
 * Time: 16:33
 */

namespace common\services;

use yii\base\Exception;
use yii\base\Component;
use common\models\CreditMxBill;
use common\models\CreditMxReport;
use common\models\MoxieCreditTask;
use \Curl\Curl;


class MoxieService extends Component
{
    // 摩蝎访问验证配置
    private $_access_api_key = '';
    private $_access_token = '';
    private $_access_secret = '';

    // 摩蝎数据url
    const URL_CREDIT_ALL_BILL = "https://api.51datakey.com/email/v2/alldata?task_id=%s";
    const URL_CREDIT_ALL_REPORT = "https://api.51datakey.com/email/v2/report/%s/%s";
    const URL_ONLINE_BANK_BILL = 'https://api.51datakey.com/bank/v3/allcards?task_id=%s';//获取网银账单
    const URL_ONLINE_BANK_REPORT = 'https://api.51datakey.com/bank/v3/report?task_id=%s';//获取网银数据报告

    //一次查询用户的支付宝所有信息
    const URL_ALIPAY_ALL = "https://api.51datakey.com/gateway/alipay/v2/data/%s";

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->_access_api_key = '';
        $this->_access_token = '';
        $this->_access_secret = '';
    }

    /**
     * 获取账单详情
     * @param $task_id
     * @param $credit_type
     * @return array
     * @throws Exception
     */
    public function getBills($task_id, $credit_type = 1)
    {
        try {
            if($credit_type==1){
                //邮箱数据
                $url = sprintf(self::URL_CREDIT_ALL_BILL, $task_id);
            }else{
                //$credit_type==2网银数据
                $url = sprintf(self::URL_ONLINE_BANK_BILL, $task_id);
            }
            $bill_list = $this->curlGet($url);

            return $bill_list;
        } catch (Exception $e) {
            throw new Exception();
        }
    }

    /**
     * 获取报告详情
     * @param $email_id
     * @param $task_id
     * @param $credit_type
     * @return mixed
     * @throws Exception
     */
    public function getReport($email_id, $task_id,$credit_type=1)
    {
        try {
            if($credit_type==1){
                //邮箱数据
                $url = sprintf(self::URL_CREDIT_ALL_REPORT, $email_id, $task_id);
            }else{
                //$credit_type==2网银数据
                $url = sprintf(self::URL_ONLINE_BANK_REPORT, $task_id);
            }
            return $this->curlGet($url);
        } catch (Exception $e) {
            throw new Exception();
        }
    }

    /**
     * 获取账单详情
     * @param $task_id
     * @return array
     * @throws Exception
     */
    public function getAlipayInfo($task_id)
    {
        $url = \sprintf(self::URL_ALIPAY_ALL, $task_id);
        return $this->curlGet($url);
    }

    /**
     * 保存信用卡账单任务
     * @param string $user_id
     * @param array $taskData
     * @return boolean
     */
    public function saveCreditTask($user_id, $taskData)
    {
        $moxieTask = new MoxieCreditTask();
        $moxieTask->user_id = $user_id;
        $moxieTask->task_id = $taskData['taskId'];
        $moxieTask->email = $taskData['account'];
        $moxieTask->status = $this->mappingTaskStatus($taskData['code']);
        $moxieTask->message = $taskData['message'];
        $moxieTask->credit_type = $taskData['credit_type'] ?? 1;

        return $moxieTask->save();
    }

    /**
     * 转换摩蝎任务状态码
     * @param int $status
     * @return int
     */
    public function mappingTaskStatus($status)
    {
        if ($status < 1) {
            return MoxieCreditTask::STATUS_BILL_FAILED;
        }

        return MoxieCreditTask::STATUS_BILL_ING;
    }

    /**
     * 保存全部邮箱信用卡账单、邮箱账单
     * @param $user_id
     * @param $data
     * @param $credit_type
     * @param $bank_name
     * @return bool
     */
    public function saveAllEmailBill($user_id, $data,$credit_type,$bank_name='') {
        if($credit_type==1){
            $type = CreditMxBill::TYPE_CREDIT_EMAIL_ALL;
            $mxAllBill = CreditMxBill::findOne(['user_id' => $user_id, 'type' => $type]);
            if (!$mxAllBill)
            {
                $mxAllBill = new CreditMxBill();
                $mxAllBill->user_id = $user_id;
                $mxAllBill->type = $type;
            }

            $mxAllBill->data = $data;
            return $mxAllBill->save();
        }else{
            $type = CreditMxBill::TYPE_CREDIT_ONLINE_BANK;
            $mxAllBill = CreditMxBill::findOne(['user_id' => $user_id, 'type' => $type,'bank_name'=>$bank_name]);
            if (!$mxAllBill)
            {
                $mxAllBill = new CreditMxBill();
                $mxAllBill->user_id = $user_id;
                $mxAllBill->type = $type;
                $mxAllBill->bank_name = $bank_name;
            }

            $mxAllBill->data = $data;
            return $mxAllBill->save();
        }

    }

    /**
     * 保存全部邮箱报告
     * @param $user_id
     * @param $data
     * @param $credit_type
     * @return bool
     */
    public function saveAllEmailReport($user_id, $data,$credit_type=1) {
        if($credit_type ==1 ){
            //信用卡邮箱
            $credit_type = CreditMxReport::TYPE_CREDIT_EMAIL;
        }else{
            //网银
            $credit_type = CreditMxReport::TYPE_CREDIT_ONLINE_BANK;
        }
        $mxAllReport = CreditMxReport::findOne(['user_id' => $user_id, 'type' =>$credit_type ]);
        if (!$mxAllReport)
        {
            $mxAllReport = new CreditMxReport();
            $mxAllReport->user_id = $user_id;
            $mxAllReport->type = $credit_type;

        }

        $mxAllReport->data = $data;
        return $mxAllReport->save();
    }

    /**
     * get数据请求
     * @param string $url
     * @return mixed
     */
    private function curlGet($url)
    {
        $header = ['Authorization: token ' . $this->_access_token];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        return json_decode(curl_exec($ch), true);
    }

    /**
     * 响应摩蝎回调
     * @return mixed
     */
    public function resposeCall()
    {
        header("HTTP/1.1 201 Created");
        exit;
    }

    /**
     * 验证数据有效性
     * @param $bodyData
     * @return boolean
     */
    public function verificated($bodyData)
    {
        $sign = $_SERVER['HTTP_X_MOXIE_SIGNATURE'] ?? '';
        $hash = hash_hmac('sha256', $bodyData, $this->_access_secret, true);

        return base64_encode($hash) == $sign;
    }

    /**
     * 根据task_id 查询网银 对应的数据
     * @param $task_id
     * @param $url
     * @return array
     */
    public function onlineBankData($url,$task_id){

        $curl = new Curl();
        $curl->setHeader('Authorization', 'token '.$this->_access_token);

        $curl->get($url,[
            'task_id'=>$task_id
        ]);

        if ($curl->error) {
            return [
                'status'=>-1,
                'error'=>'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage
            ];
        }

        $data = json_decode($curl->rawResponse,true);

        if(json_last_error() !== JSON_ERROR_NONE){
            return [
                'status'=>-1,
                'error'=>'Error： json解析错误'
            ];
        };

        return [
            'status'=>0,
            'data'=>$data
        ];
    }
}