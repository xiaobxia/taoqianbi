<?php
namespace common\services;

use yii\base\Component;
use common\helpers\CurlHelper;

class CardAuthService extends Component
{

    //post请求url
    public $post_url='http://'.SITE_DOMAIN.'/frontend/web/app/card-bin';
    //商户号
    public $merchant='dichang';
    //MD5_KEY
    public $md5_key='5830621275e66078fc9359308d23a730';


    // 需要和 /common/models/CardInfo.php 里的 ID 对应
    public static $bankIdInfo = [
        'ABC'=>'2',		    //农业银行
        'BCOM'=>'14',		//交通银行
        'BOC'=>'9',		    //中国银行
        'CCB'=>'7',		    //中国建设银行
        'CEBB'=>'3',		//光大银行
        'CIB'=>'5',		    //兴业银行
        'CMB'=>'8',		    //招商银行
        'CMBC'=>'15',		//民生银行
        'ECITIC'=>'13',		//中信银行
        'GDB'=>'16',		//广发银行股份有限公司
        'HXB'=>'12',		//华夏银行
        'ICBC'=>'1',		//工商银行
        'PAB'=>'11',		//平安银行
        'PSBC'=>'4',		//邮储银行
        'SPDB'=>'10',		//浦东发展银行

        'BCCB'=>'17',		//北京银行
        'BOS'=>'18',		//上海银行
        'SRCB'=>'19',		//上海农商银行
        'BOCD'=>'20',		//成都商业银行
        'CBHB'=>'21',		//渤海银行
        'NJCB'=>'22',		//南京银行
        'NBCB'=>'23',		//宁波银行
        'NCCB'=>'49',		//江西银行
    ];

    public static $bankNameInfo = [
        '农业银行'=>'ABC',
        '北京银行'=>'BCCB',
        '交通银行'=>'BCOM',
        '中国银行'=>'BOC',
        '成都商业银行'=>'BOCD',
        '上海银行'=>'BOS',
        '渤海银行'=>'CBHB',
        '中国建设银行'=>'CCB',
        '光大银行'=>'CEBB',
        '兴业银行'=>'CIB',
        '招商银行'=>'CMB',
        '民生银行'=>'CMBC',
        '中信银行'=>'ECITIC',
        '广发银行股份有限公司'=>'GDB',
        '华夏银行'=>'HXB',
        '中国工商银行'=>'ICBC',
        '江西银行'=>'NCCB',
        '南京银行'=>'NJCB',
        '宁波银行'=>' NBCB',
        '平安银行'=>'PAB',
        '平安银行股份有限公司'=>'PAB',
        '邮储银行'=>'PSBC',
        '浦东发展银行'=>'SPDB',
        '上海农商银行'=>'SRCB',
    ];

    /**
     * 生成token
     * @param $name
     * @param $id_number
     */
    public function getCardSign($cardNo){
        $time = time();
        $dataArray=array('merchant'=>$this->merchant,'md5_key'=>$this->md5_key,'cardno'=>$cardNo,'time'=>$time);
        ksort($dataArray);
        $token_str='';
        foreach ($dataArray as $key=>$val){
            $token_str.=trim($val);
        }
        $dataArray['token']=md5($token_str);
        unset($dataArray['md5_key']);

        return  $dataArray;
    }

    /**
     * 银行卡验证
     * @param $name
     * @param $id_number
     */
    public function cardAuth($cardNo){
        try{
            $url = $this->post_url;
            $post_data = $this->getCardSign($cardNo);
            $data = CurlHelper::curlHttp($url, 'POST', $post_data, 300);
            if(!empty($data)){
                if(!is_array($data)){
                   return false;
                }
                if($data['err_code']!=0){
                    //异常信息
                    return [
                        'code'=>500,
                        'message'=>$data['result']
                    ];
                }

                $result=$data['result'];
                if(count($result)==0){
                    return [
                        'code'=>500,
                        'message'=>'未找到银行卡信息'
                    ];
                }

                //英文简称验证成功并匹配到获取bank_id
                if (isset($result['abbreviation']) && isset(self::$bankIdInfo[$result['abbreviation']])){
                    $bank_id = self::$bankIdInfo[$result['abbreviation']];
                    return [
                        'code' => 0,
                        'data' => [
                            'card_type' => 1,
                            'bank_id' => intval($bank_id)
                        ]
                    ];
                }

                //中文验证成功并匹配到获取bank_id
                if (isset($result['bankname']) && isset(self::$bankNameInfo[$result['bankname']])){
                    $bank_abbreviation = self::$bankNameInfo[$result['bankname']];
                    if (isset(self::$bankIdInfo[$bank_abbreviation])){
                        $bank_id = self::$bankIdInfo[$bank_abbreviation];
                        return [
                            'code' => 0,
                            'data' => [
                                'card_type' => 1,
                                'bank_id' => intval($bank_id)
                            ]
                        ];
                    }
                }

                return [
                    'code'=>500,
                    'message'=>'未匹配到bank_id'
                ];
            }

            return [
                'code'=>500,
                'message'=>'未找到银行卡信息'
            ];
        } catch (\Exception $e) {
            \YII::error(sprintf('验证银行卡失败，原因：%s',$e->getMessage()),'cardauthbin');
            return false;
        }

    }


}