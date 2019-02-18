<?php
/**
 * 同步支付宝交易记录
 */
namespace frontend\controllers;

use common\base\LogChannel;
use common\models\AlipayRepaymentLog;

class SyncAlipayRepaymentController extends BaseController
{
    public $verifyPermission = false;

    public function beforeAction($action)
    {
        header("Access-Control-Allow-Origin: *");
        return true;
    }

    public function actionSyncRun()
    {
        if ($this->request->isPost) {

            $this->response->format = \yii\web\Response::FORMAT_JSON;
            $data = \Yii::$app->request->post('data');
            \Yii::error(json_encode($data),'test_apply_info');
            $sign = \Yii::$app->request->post('sign');
            $source = \Yii::$app->request->post('source_id',0);
            if(empty($data) || empty($sign)){
                return [
                    'code' => -1,
                    'msg' => '参数错误'
                ];
            }
            $public_content=file_get_contents(\Yii::getAlias('@common/attachment/rsa_key/public_key.pem'));
            $public_key=openssl_get_publickey($public_content);
            $sign=base64_decode($sign);

            $check=(bool)openssl_verify($data,$sign,$public_key);
            if(!$check){
                return [
                    'code' => -1,
                    'msg' => '验签失败'
                ];
            }
            $ret = json_decode($data,true);
            \yii::warning($ret,LogChannel::ALIPAY_SYNC_LOG);

            $data = '';
            $repay_name = '';
            foreach ($ret as $v) {
                $alipayTime = trim($v['alipayTime']); //时间
                $alipayRecord = trim($v['alipayRecord']); //订单号
                $alipayMoney = trim($v['alipayMoney']); //金额
                $alipayAccount = trim($v['alipayAccount']); //账户
                $alipayName = trim($v['alipayName']); //姓名
                $alipayRemark = trim($v['alipayRemark']); //备注

                if (empty($alipayRecord)) {
                    continue;
                }
                if (strpos($alipayMoney, '-') !== false) {
                    continue;
                }
                if (AlipayRepaymentLog::findOne(['alipay_order_id' => $alipayRecord])) {
                    continue;
                }
                $data .= $alipayTime . ' ******* ' . $alipayRecord . ' ******* ' . $alipayMoney . ' ******* ' . $alipayAccount . ' ******* ' .
                    $alipayName . ' ******* ' . $alipayRemark . ' @@@@@@ ';
                if ($alipayName) {
                    $repay_name .= ' ' . $alipayName;
                }
            }
            if (empty($data)) {
                return '没有新数据，无需更新';
            }
            $timestamp = 'dsf@#$%&*dsfk';
            $sign = strtolower(md5($timestamp . '#abc!@#'));
            $params = [
                'data' => $data,
                'timestamp' => $timestamp,
                'sign' => $sign,
                'source' => $source
            ];

            if (AlipayRepaymentLog::insertIgnore($params)) {
                return "数据插入成功（对方名称:{$repay_name}）";
            }
            return '数据插入失败' . $data;
        }
    }
}
