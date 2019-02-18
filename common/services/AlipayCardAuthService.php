<?php
namespace common\services;
use yii\base\Component;
use common\helpers\CurlHelper;

class AlipayCardAuthService extends Component
{
    //请求URL
    public $post_url='https://ccdcapi.alipay.com/validateAndCacheCardInfo.json?_input_charset=utf-8&cardNo=%s&cardBinCheck=true';
    //储蓄卡
    const DC='DC';
    //信用卡
    const CC='CC';
    //准贷记卡
    const SCC='SCC';
    //预付费卡
    const PC='PC';

    public static $card_type=[
        self::DC => '储蓄卡',
        self::CC => '信用卡',
        self::SCC => '准贷记卡',
        self::PC => '预付费卡'
    ];

    // 需要和 /common/models/CardInfo.php 里的 ID 对应
    public static $bankIdInfo = [
        'ICBC'=>'1',		//工商银行
        'ABC'=>'2',		    //农业银行
        'CEBB'=>'3',		//光大银行
        'PSBC'=>'4',		//邮储银行
        'CIB'=>'5',		    //兴业银行
        'CCB'=>'7',		    //中国建设银行
        'CMB'=>'8',		    //招商银行
        'BOC'=>'9',		    //中国银行
        'SPDB'=>'10',		//浦东发展银行
        'PAB'=>'11',		//平安银行
        'HXB'=>'12',		//华夏银行
        'ECITIC'=>'13',		//中信银行
        'COMM'=>'14',		//交通银行 BCOM
        'CMBC'=>'15',		//民生银行
        'GDB'=>'16',		//广发银行股份有限公司
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
        '交通银行'=>'COMM', //BCOM
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
     * 验证银行卡卡BIN
     * @param string $cardNo
     * @return array
     **/
    public function cardAuth($cardNo){
        $this->post_url=sprintf($this->post_url,$cardNo);
        $data = CurlHelper::curlHttp($this->post_url, 'GET');
        if(!empty($data)){
            if(is_array($data)){
                if(isset($data['validated'])){
                    $validated=trim($data['validated']);
                }
            }
        }
        if(!empty($validated)){
            //银行卡卡类型
            $cardType=trim($data['cardType']);
            //银行卡卡简称
            $bank=trim($data['bank']);
            //只要借记卡
            if(strtoupper($cardType)==self::DC && isset(self::$bankIdInfo[$bank])){
                $bank_id = self::$bankIdInfo[$bank];
                return [
                    'code' => 0,
                    'data' => [
                        'card_type' => 1,
                        'bank_id' => intval($bank_id)
                    ]
                ];
            }
        }
        //对于不符合数据保存mongodb中
        \YII::error(sprintf('请求url：%s，返回结果：%s',$this->post_url,json_encode($data,JSON_UNESCAPED_UNICODE)),'alipaycardbin');
        return [
            'code'=>500,
            'message'=>'未找到银行卡信息'
        ];
    }
}