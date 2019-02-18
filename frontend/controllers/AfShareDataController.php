<?php
namespace frontend\controllers;
use common\models\UserLoanOrder;
use \common\services\Yxservice;
use Yii;

/**
 * 宜信数据共享借口
 */
class AfShareDataController extends BaseController{
    var $s= array();
    var $i= 0;
    var $j= 0;
    var $_key;
    /**
     * @name 获取查询的数据
     */
    public function actionGetDataUrl(){
        $postStr = Yii::$app->request->post('params','');
        $test = Yii::$app->request->post('test','');
        $test_name = Yii::$app->request->post('test_name','');
        $test_id = Yii::$app->request->post('test_id','');
        if(!empty($postStr)){
            //解密数据
            $data = json_decode($postStr,true);
            $data_params =  base64_decode(urldecode($data['params']));
            $data_json = $this->rc4(Yxservice::RC4,$data_params);
            $data_arr = json_decode($data_json,true);
            /*if($test == 'test'){
                var_dump($data['params']);
                var_dump(Yxservice::RC4);
                var_dump($data_json);die;
            }*/
            if($data_arr['tx'] == 201 && isset($data_arr['data']['name']) && isset($data_arr['data']['idNo']) || $test == 'test'){
                if($test != 'test'){
                    $params['name'] = $data_arr['data']['name'];
                    $params['idNo'] = $data_arr['data']['idNo'];
                }else{
                    $params['name'] = $test_name;
                    $params['idNo'] = $test_id;
                }
                $data_json_res = Yxservice::getLoanData($params,$test);
                if($test == 'test'){
                    var_dump($data_json_res);die;
                }
                if(!empty($data_json_res)){
                    $data_res['message'] = "查询成功";
                    $data_res['errorCode'] = "0000";
                    $data_res['params'] = urlencode(base64_encode($this->rc4(Yxservice::RC4,$data_json_res)));
                }else{
                    $data_res['message'] = '查询成功无数据';
                    $data_res['errorCode'] = '0001';
                    $data_res['params'] = '';

                }

                return json_decode(json_encode($data_res));
            }else{
                $data_res['message'] = '查询失败';
                $data_res['errorCode'] = '4012';
                $data_res['params'] = '';
                return json_decode(json_encode($data_res));
            }
        }
    }

    public function actionTestInfo(){
        $name =  Yii::$app->request->post('name','');
        $id =  Yii::$app->request->post('id','');
        $data_res['tx'] = 201;
        $data_res['data']['name'] = $name;
        $data_res['data']['idNo'] = $id;
        $data['params'] = urlencode(base64_encode($this->rc4(Yxservice::ZCAF_USER_PWD,json_encode($data_res))));
        $data['errorCode'] = "0000";
        $data['message'] = "queryFromEcho";
        $pa = json_encode($data);
        var_dump($pa);die;
    }

    /*
    * $pwd 秘钥；
    * $data 要加密的数据
    */
    public function rc4($pwd, $data)
    {
        $cipher      = '';
        $key[]       = "";
        $box[]       = "";
        $pwd_length  = strlen($pwd);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++) {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j       = ($j + $box[$i] + $key[$i]) % 256;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a       = ($a + 1) % 256;
            $j       = ($j + $box[$a]) % 256;
            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k       = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return $cipher;
    }

    public function actionTest(){
        $order = UserLoanOrder::find()->where(['id'=>2830])->one();
        $res = Yxservice::getRepayment($order,2);
        var_dump($res);die;
    }
}