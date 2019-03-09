<?php
namespace common\models;

use Yii;
/**

 * date 2017-05-11
 *
 * Channel model
 * @property integer $status
 * @property integer $channel_rank
 */
class Channel extends \yii\db\ActiveRecord{

    const STATUS_NO = 0;
    const STATUS_YES = 1;

    //渠道类型：下载市场，垂直BD，异业BD，信息流，sem，短信，免费渠道,微信
    const CHANNEL_TYPE_ALL = 0;
    const CHANNEL_TYPE_XZYC = 1;
    const CHANNEL_TYPE_CZBD = 2;
    const CHANNEL_TYPE_YYBD = 3;
    const CHANNEL_TYPE_XXL = 4;
    const CHANNEL_TYPE_SEM = 5;
    const CHANNEL_TYPE_DX = 6;
    const CHANNEL_TYPE_MFQD = 7;
    const CHANNEL_TYPE_WEIXIN = 8;
    const CHANNEL_TYPE_XIAXIAN = 9;
    const CHANNEL_TYPE_NOTFLOW = 10;

    const CHANNEL_RANK_ALL = 0;
    const CHANNEL_RANK_ONE = 1;
    const CHANNEL_RANK_TWO = 2;

    const PERSON_SOURCE_MOBILE_CREDIT = 21; //极速荷包

    const PERSON_SOURCE_DEFAULT = 0;//默认

    //“结算类型"：A、S、A+S、A+S+复借、A阶梯、S阶梯
    const CHANNEL_CALC_TYPE_ALL = 0;
    const CHANNEL_CALC_TYPE_A = 1;
    const CHANNEL_CALC_TYPE_S = 2;
    const CHANNEL_CALC_TYPE_AS = 3;
    const CHANNEL_CALC_TYPE_ASFJ = 4;
    const CHANNEL_CALC_TYPE_AJT = 5;
    const CHANNEL_CALC_TYPE_SJT = 6;

    static $status = [
        self::STATUS_NO  => '停用',
        self::STATUS_YES => '启用'
    ];
    public static $operator_channel = [68,70,8,3,2,140,4,84]; //后台折扣率修改、展示权限人员id  袁琳、吴量、黄波、常昊宇
    static $channel_rank = [
        self::CHANNEL_RANK_ALL  => '全部渠道',
        self::CHANNEL_RANK_ONE  => '一级渠道',
        self::CHANNEL_RANK_TWO => '二级渠道'
    ];

    public static $channel_type = [
        self::CHANNEL_TYPE_ALL => '无',
        self::CHANNEL_TYPE_XZYC => '下载市场',
        self::CHANNEL_TYPE_CZBD => '垂直BD',
        self::CHANNEL_TYPE_YYBD => '异业BD',
        self::CHANNEL_TYPE_XXL => '信息流',
        self::CHANNEL_TYPE_SEM => 'sem',
        self::CHANNEL_TYPE_DX => '短信',
        self::CHANNEL_TYPE_MFQD => '免费渠道',
        self::CHANNEL_TYPE_WEIXIN => '微信',
        self::CHANNEL_TYPE_XIAXIAN => '下线渠道',
    ];

    public static $source = [
        self::PERSON_SOURCE_DEFAULT=>"默认来源",
        self::PERSON_SOURCE_MOBILE_CREDIT=>APP_NAMES,
//        self::PERSON_SOURCE_KDJZ=>"口袋记账",
//        self::PERSON_SOURCE_JBGJ=>"记账管家",
//        self::PERSON_SOURCE_HBJB=>"汇邦钱包",
//        self::PERSON_SOURCE_WZD_LOAN=>"温州贷借款",
    ];

    //结算类型
    public static $calc_type = [
        self::CHANNEL_CALC_TYPE_ALL => '全部类型',
        self::CHANNEL_CALC_TYPE_A => 'A',
        self::CHANNEL_CALC_TYPE_S => 'S',
        self::CHANNEL_CALC_TYPE_AS => 'A+S',
        self::CHANNEL_CALC_TYPE_ASFJ => 'A+S+复借',
        self::CHANNEL_CALC_TYPE_AJT => 'A阶梯',
        self::CHANNEL_CALC_TYPE_SJT => 'S阶梯',
    ];

    //数据类型
    public static $data_type = [
        '2' => '返数据',
        '1' => '内部数据',
    ];

    // 推广页模版
    const TEMPLATE_ONE = 0; // 极速荷包专用模版
    const TEMPLATE_TWO = 1; // 温州贷专用模版（wzdai_app专用）
    const TEMPLATE_THREE = 2; // 极速荷包公积金版专用模版
    const TEMPLATE_FOUR = 3; // 随心贷专用模版
    const TEMPLATE_FIVE = 4; // 随心贷专用模版
    const TEMPLATE_SIX = 5; // 极速荷包开心借专用模版
    const TEMPLATE_LUDS = 6; // 极速荷包 - 鲁大师PC版模板
    // const TEMPLATE_FIVE = 4; // 公用模版2
    public static $page_template = [
        self::TEMPLATE_ONE => APP_NAMES.'专用模版',
        self::TEMPLATE_TWO => '温州贷专用模版（wzdai_app专用）',
        self::TEMPLATE_THREE => APP_NAMES.'公积金版专用模版',
        self::TEMPLATE_FOUR => '随心贷专用模版',
        self::TEMPLATE_FIVE => '现金白条福利版专用模版',
        self::TEMPLATE_SIX => APP_NAMES.'开心借',
        self::TEMPLATE_LUDS => APP_NAMES.'鲁大师',
        // self::TEMPLATE_FIVE => '公用模版2',
    ];
    public static $page_template_url = [
        self::TEMPLATE_ONE => 'http://qb.wzdai.com/newh5/web/page/app-reg?source_tag=',
        self::TEMPLATE_TWO => 'http://qb.wzdai.com/newh5/web/page/app-reg-wzd?source_tag=',
        self::TEMPLATE_THREE => 'http://qb.wzdai.com/newh5/web/channel-page/channel-housefund?source_tag=',
        self::TEMPLATE_FOUR => 'http://qb.wzdai.com/newh5/web/channel-page/channel-sxdai?source_tag=',
        self::TEMPLATE_FIVE => 'http://qb.wzdai.com/newh5/web/channel-page/channel-xjbt-welfare?source_tag=',
        self::TEMPLATE_SIX => 'http://qb.wzdai.com/newh5/web/channel-page/register-kxjie?source_tag=',
        self::TEMPLATE_LUDS => 'http://qb.wzdai.com/newh5/web/channel-page/register-xybt-luds?source_tag=',
    ];

    const SOURCE_VIEW1 = 'stream';
    const SOURCE_VIEW2 = 'stream1';
    const SOURCE_VIEW3 = 'stream2';
    const SOURCE_VIEW4 = 'stream3';
    const SOURCE_VIEW5 = 'stream4';
    const SOURCE_VIEW6 = 'stream5';
    const SOURCE_VIEW7 = 'stream6';
    const SOURCE_VIEW8 = 'stream7';
    const SOURCE_VIEW9 = 'stream8';
    const SOURCE_VIEW10 = 'stream9';
    const SOURCE_VIEW11 = 'stream10';
    const SOURCE_VIEW12 = 'stream11';
    const SOURCE_VIEW13 = 'stream12';
    const SOURCE_VIEW14 = 'stream13';
    //渠道皮肤包选择  //前端先写文件 再添加/newh5/views/theme
    public static $source_tag_view = [
        self::SOURCE_VIEW1 => '主题一',
        self::SOURCE_VIEW2 => '主题二',
        self::SOURCE_VIEW3 => '主题三',
        self::SOURCE_VIEW4 => '主题四',
        self::SOURCE_VIEW5 => '主题五',
        self::SOURCE_VIEW6 => '主题六',
        self::SOURCE_VIEW7 => '主题七',
        self::SOURCE_VIEW8 => '主题八',
        self::SOURCE_VIEW9 => '主题九',
        self::SOURCE_VIEW10 => '主题十',
        self::SOURCE_VIEW11 => '主题十一',
        self::SOURCE_VIEW12 => '主题十二',
        self::SOURCE_VIEW13 => '主题十三',
        self::SOURCE_VIEW14 => '主题十四',
    ];

    /**
     * 更新一级渠道下的二级渠道
     */
    public static function updateUi($p_id,$theme = ''){
        $arr['themes'] = $theme;
        return self::updateAll($arr,['parent'=>$p_id]);//更新数据
    }

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%channel}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '渠道中文名称',
            'appMarket' => '渠道英文名称',
            'operator_name' => '渠道负责人',
            'status' => '渠道状态：0停用，1启用',
            'loan_show' => '是否显示借款量：0不显示,1显示',
            'rank' => '渠道等级',
            'parent' => '渠道所属上级',
            'link' => '渠道链接',
            'type' => '渠道类型',
            'calc_type' => '结算类型',
            'calc_rule' => '结算规则',
            'effective_at' => '规则生效时间',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
            'rate'=>'打折比率',
            'rate_new'=>'最新的打折比率',
            'rate_time'=>'开始打折的时间',
        ];
    }

    /**
     * 获取渠道id 父级id 类型
     * @param $key
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function  getChannelType($key){
        $appMarket = self::getChannelTypeNew($key);
        $like ="  appMarket = '{$appMarket}'";
        $channel = Channel::find()->where($like)->select('id,appMarket,source_str,name,type,parent')->asArray()->one(Yii::$app->get('db_kdkj_rd_new'));
        return $channel;
    }


    /**
     * @name 处理appMarket字符串
     * @date 2017-10-26
     * @author 黄文派
     * @use 处理appMarket字符串
     * @param string $key appMarket字符串
     */
    public static function getChannelTypeNew($key){
        $arr=explode('_',$key);
        if(strstr($key,'xybt_')){
            if(count($arr)==4||(count($arr)==3&&strstr($key,'toufang_'))){
                $market =$arr[1].'_'.$arr[2];
            }elseif(strstr($key,'P2P')){
                $market =$arr[0].'_'.$arr[1];
            }else{
                $market =$arr[1];
            }
            $appMarket = $market;
        }elseif(( strstr($key,'gdt_') || strstr($key,'jrtt_') || strstr($key,'sxdai_') || strstr($key,'i_dai_') )&&count($arr)==3){
            $appMarket =$arr[0].'_'.$arr[1];
        }elseif(strstr($key,'weixinfw')){
            $appMarket = 'weixinfw';
        }elseif(strstr($key,'kxjie_') && count($arr) >=2){
            $appMarket =$arr[0].'_'.$arr[1];
        }elseif(strstr($key,'sms_')){
            $appMarket = 'sms';
        }elseif(strstr($key,'T360')){
            if (strstr($key,'wzdai_loan')){
                $appMarket = 'wzdai_loan_T360';
            }elseif(strstr($key,'sxdai')){
                $appMarket = 'sxdai_T360';
            }else{
                $appMarket = 'T360';
            }
        }elseif( $key == 'sxd' || $key == 'sxdai' ){
            $appMarket = 'sxdai_none';
        }elseif(strstr($key,'smss_')){
            $appMarket = 'smss_bylf';
        }elseif(strstr($key,'xjbtfuli_')){
            if($arr[1] =='offical'){
                $appMarket = 'xjbtfuli_offical';
            }elseif(count($arr)>=3 && ($arr[1] =='xjbtshundai'|| $arr[1] =='shundai')){
                $appMarket = "xjbt_shundai";
            }elseif(count($arr)>=3 && $arr[1] =='xjbt'){
                $arr1 = $arr[1];
                $arr2 = $arr[2];
                $appMarket = "{$arr1}_{$arr2}";
            }else{
                $appMarket = 'xjbtfuli';
            }
        }else{
            if(strstr($key,'_')){
                if($arr[0]=='wzdai' || $arr[0]=='sxdai'){//$key = sxdai_sms_mengwangsuixindai1027
                    if($arr[0]=='sxdai' && $arr[1]=='sms'){
                        $appMarket = 'sxdai_sms';
                    }else{
                        $appMarket = $key;
                    }
                }else{
                    $appMarket = $arr[0];
                }
            }else{
                $appMarket = $key;
            }
        }
        return $appMarket;
    }
}
