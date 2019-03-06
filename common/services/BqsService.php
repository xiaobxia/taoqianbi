<?php

namespace common\services;

use Yii;
use yii\base\Exception;
use yii\base\Component;
use common\helpers\CurlHelper;
use common\models\LoanPerson;
use common\models\CreditBqs;
use common\models\CreditBqsLog;
use common\models\CreditQueryLog;
use common\models\CreditCheckHitMap;

/**
 * 白骑士接口
 */
class BqsService extends Component
{
    protected $loanPerson = null;
    protected $type = null;
    protected $data = null;
    public $map = null;
    protected $repostId = null;
    protected $productId = null;
    protected $orderId = null;
    protected $reportData = null;

    public function __get($name)
    {
        return parent::__get($name); // TODO: Change the autogenerated stub
    }

    //获取决策信息
    public function getDecision($params){
        $ret = $this->getData($params);
        if (!$ret) {
            throw new Exception('白骑士接口访问失败');
        }

        if ($ret['resultCode'] != 'BQS000'){
            throw new Exception('白骑士 ' . CreditBqs::$resultCode[$ret['resultCode']]);
        }

        $this->data = json_encode($ret);
        return $this->data;
    }

    /**
     * 请求数据
     * @param $params
     * @return bool|mixed
     */
    public function getData($params){
        $params['partnerId'] = 'gyxx';
        $params['verifyKey'] = 'a04b0cc1a49940ebb207fb52f6b68f92';
        $params['appId'] = 'sdz201902';
        $params = json_encode($params);
        $url = 'https://api.baiqishi.com/services/decision';
        return CurlHelper::curlHttp($url, 'JXL', $params);
    }

    public function getLoanPersonDecision(LoanPerson $loanPerson,$product_type,$order_id){
        if(is_null($loanPerson)){
            throw new Exception('借款人信息不能为空');
        }
        $this->productId = $product_type;
        $this->orderId = $order_id;
        $this->loanPerson = $loanPerson;
        $params['eventType'] = 'blacklist';
        $params['name'] = trim($loanPerson->name);
        $params['mobile'] = trim($loanPerson->phone);
        $params['certNo'] = trim($loanPerson->id_number);
        foreach ($params as $k => $param) {
            if(empty($param)){
                throw new Exception("{$k}不能为空");
            }
        }
        $ret = $this->getDecision($params);
        $result = $this->saveData();
        return $result;

    }
    public function saveData(){
        if(is_null($this->loanPerson) || is_null($this->data)){
            throw new Exception('参数缺失');
        }
        $loanPerson = $this->loanPerson;
        $data = $this->data;
        try{
            $admin_username = Yii::$app->user->identity->username;
        }catch(Exception $e){
            $admin_username = 'auto shell';
        }

        $log = new CreditBqsLog();
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try{
            $log->person_id = $loanPerson->id;
            $log->admin_username = $admin_username;
            $log->price = 0;
            $log->created_at = time();
            if(!$log->save()){
                throw new Exception('credit_bqs_log日志保存失败');
            }
            $credit_bqs = CreditBqs::find()->where(['person_id'=>$loanPerson->id])->one();
            if(!$credit_bqs){
                $credit_bqs =  new CreditBqs();
                $credit_bqs->person_id = $loanPerson->id;
            }
            $arr = json_decode($data,true);
            $credit_bqs->person_id = $loanPerson->id;
            $credit_bqs->resultcode = $arr['resultCode'];
            $credit_bqs->log_id = $log->id;
            $credit_bqs->data = $data;
            $credit_bqs->status = CreditCheckHitMap::STATUS_MISS;
            if(!$credit_bqs->save()){
                throw new Exception('credit_bqs保存失败');
            }
            $transaction->commit();
            return true;
        }catch(Exception $e){
            $transaction->rollBack();
            throw new Exception($e->getMessage());
        }

    }


}
