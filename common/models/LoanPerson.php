<?php
namespace common\models;


use common\base\LogChannel;
use common\models\loan\LoanCollectionOrder;
use common\services\RiskControlNewService;
use common\services\RiskControlService;
use console\models\NetUtil;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\db\Query;
use common\models\UserPassword;
use common\models\UserDetail;
use common\models\mongo\risk\RuleReportMongo;
use yii\base\UserException;
use common\api\RedisQueue;
use common\models\Setting;
use common\services\UserService;
use common\models\asset\AssetLoadPlat;
use common\helpers\Util;
use common\models\Channel;

/**
 * 借款人
 * @property integer $id
 * @property string $name 姓名
 * @property string $id_number 身份证号码
 * @property int $phone 手机号码
 * @property integer $birthday 年龄
 * @property integer $source_id 来源ID
 *
 * @property UserVerification $userVerification 用户认证关联
 */
class LoanPerson extends ActiveRecord implements IdentityInterface {

    const CUSTOMER_TYPE_NEW  = 0;
    const CUSTOMER_TYPE_OLD  = 1;

    public static $cunstomer_type = [
        self::CUSTOMER_TYPE_NEW =>'新用户',
        self::CUSTOMER_TYPE_OLD =>'老用户',
    ];

    public static $customer_type = [
        self::CUSTOMER_TYPE_NEW =>'新用户',
        self::CUSTOMER_TYPE_OLD =>'老用户',
    ];

    const WEIXIN_WARING_FQSC = 13;
    const WEIXIN_NOTICE_YGD_LQB_LOAN_CS = 20;
    const WEIXIN_NOTICE_YGD_ALL_LOAN_FS = 21;
    const WEIXIN_NOTICE_YGD_ALL_LOAN_ZCFK = 22;
    const WEIXIN_NOTICE_YGD_ALL_LOAN_CWSH = 23;
    const WEIXIN_NOTICE_YGD_FZB_LOAN_CS = 24;
    const WEIXIN_NOTICE_YGD_FQG_LOAN_CS = 25;

    public static $weixin_market_id_warning = array(
        self::WEIXIN_WARING_FQSC =>'分期购',
        self::WEIXIN_NOTICE_YGD_LQB_LOAN_CS =>APP_NAMES.'零钱包初审',
        self::WEIXIN_NOTICE_YGD_ALL_LOAN_FS =>APP_NAMES.'风控借款复审',
        self::WEIXIN_NOTICE_YGD_ALL_LOAN_ZCFK =>APP_NAMES.'资产放款',
        self::WEIXIN_NOTICE_YGD_ALL_LOAN_CWSH =>APP_NAMES.'财务放款审核',
        self::WEIXIN_NOTICE_YGD_FZB_LOAN_CS =>APP_NAMES.'风控房租宝借款初审',
        self::WEIXIN_NOTICE_YGD_FQG_LOAN_CS =>APP_NAMES.'风控分期购借款初审',
    );

    /**
     *根据用户ID，返回基本信息
     */
    public static function baseinfo_ids($ids = array()){
        if(empty($ids)) return array();
        $result = array();
        $res = self::find()->select(['name', 'phone', 'id'])
            ->where("`id` IN (".implode(',', $ids).")")
            ->asArray()->all(self::getDb_rd());
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

    // 实名认证状态
    const REAL_STATUS_NO = 0;
    const REAL_STATUS_YES = 1;
    public static $is_real_verify = [
        self::REAL_STATUS_YES => '是',
        self::REAL_STATUS_NO  => '否',
    ];

    //是否曾经被拒
    const SEARCH_STATUS_YES = 1;
    const SEARCH_STATUS_NO = 2;
    public static $search_status = [
        self::SEARCH_STATUS_YES => '是',
        self::SEARCH_STATUS_NO  => '否',
    ];

    //用户可再借时间类型
    const PERSON_TYPE_LOAN_ONE = 1;
    const PERSON_TYPE_LOAN_TWO = 2;
    const PERSON_TYPE_LOAN_THREE = 3;

    const PERSON_TYPE_FACTORY = 1;
    const PERSON_TYPE_PERSON = 2;
    const PERSON_TYPE_YUANGONG = 3;

    const PERSON_IMAGE_TYPE_ONE = 1;//身份证正面
    const PERSON_IMAGE_TYPE_TWO =2 ;//身份证反面
    const PERSON_IMAGE_TYPE_THREE = 3 ;//身份证和本人合影
    const PERSON_IMAGE_TYPE_FOUR =4 ; //工作证或者学生证

    const PERSON_STATUS_CHECK=0;
    const PERSON_STATUS_PASS=1;
    const STATUS_TO_REGISTER = 2; // 自动注册，待真实注册
    const PERSON_STATUS_NOPASS=-1;
    const PERSON_STATUS_DELETE=-2;
    const PERSON_STATUS_DISABLE = -3;

    const CONSUMER_FINANCE_TYPE = 1;
    const TRUST_TYPE = 2;

    const REATION_FATHER=1;
    const REATION_MOTHER=2;
    const REATION_SPOUSE=3;
    const REATION_GUARDIAN=4;
    const REATION_CLASSMATE=5;
    const REATION_FRIEND = 6;

    const DOCTOR = 1;
    const MASTER = 2;
    const UNDERGRADUATE = 3;
    const JUNIOR_COLLEGE = 4;
    const SECONDARY_SPECIALIZED_SCHOOL=5;
    const HIGH_SCHOOL = 6;
    const JUNIOR_MIDDLE_SCHOOL = 7;
    const JUNIOR_HIGH_SCHOOL=8;

    public static $education_level = [
        self::DOCTOR =>'博士',
        self::MASTER =>'硕士',
        self::UNDERGRADUATE =>'本科',
        self::JUNIOR_COLLEGE =>'大专',
        self::SECONDARY_SPECIALIZED_SCHOOL =>'中专',
        self::HIGH_SCHOOL =>'高中',
        self::JUNIOR_MIDDLE_SCHOOL =>'初中',
        self::JUNIOR_HIGH_SCHOOL =>'初中以下',
    ];

    // 性别
    const SEX_NOSET = 0;
    const SEX_MALE = 1;
    const SEX_FEMALE = 2;
    public static $sexes = array(
        self::SEX_NOSET => '未知',
        self::SEX_MALE => '男',
        self::SEX_FEMALE => '女',
    );

    // 手机验证正则表达式
    const PHONE_PATTERN = '/^1[0-9]{10}$/';

    public static $person_create_type = [
        self::CONSUMER_FINANCE_TYPE =>'消费金融',
        self::TRUST_TYPE=>'标类资产',
    ];

    public static $reation_type = [
        self::REATION_FATHER=>'父亲',
        self::REATION_MOTHER=>'母亲',
        self::REATION_SPOUSE=>'配偶',
        self::REATION_GUARDIAN=>'监护人',
        self::REATION_CLASSMATE=>'同学',
        self::REATION_FRIEND=>'朋友',
    ];

    public static $status = [
        self::PERSON_STATUS_CHECK => '待审核',
        self::PERSON_STATUS_PASS => '通过',
        self::PERSON_STATUS_NOPASS => '不通过',
        self::PERSON_STATUS_DELETE => '已删除',
        self::PERSON_STATUS_DISABLE =>'已禁用',
        self::STATUS_TO_REGISTER =>'自动注册，待真实注册',
    ];

    public static $person_image_type = [
        self::PERSON_IMAGE_TYPE_ONE => '身份证正面',
        self::PERSON_IMAGE_TYPE_TWO => '身份证反面',
        self::PERSON_IMAGE_TYPE_THREE => '身份证和本人合影',
        self::PERSON_IMAGE_TYPE_FOUR => '工作证或者学生证',
    ];

    public static $person_image_max_num = [
        self::PERSON_IMAGE_TYPE_ONE =>1,
        self::PERSON_IMAGE_TYPE_TWO =>1,
        self::PERSON_IMAGE_TYPE_THREE =>1,
        self::PERSON_IMAGE_TYPE_FOUR =>1,
    ];

    public static $person_type = [
        self::PERSON_TYPE_FACTORY => '企业',
        self::PERSON_TYPE_PERSON => '个人',
        self::PERSON_TYPE_YUANGONG => '员工',
    ];

//    const PERSON_SOURCE_YGB = 5;
    const PERSON_SOURCE_MOBILE_CREDIT = 21; //极速荷包
    const DEFAUTL_VALUES = 0;//请选择
    const PERSON_SOURCE_KDJZ = 119; //口袋记账
    const PERSON_SOURCE_JBGJ = 120; //记账管家
    const PERSON_SOURCE_HBJB = 121; //汇邦钱包
    const PERSON_SOURCE_WZD_LOAN = 122; //温州贷
    const PERSON_SOURCE_SX_LOAN = 123; //随心贷
    const PERSON_SOURCE_HUAN_KA_LOAN = 124; //还卡锦囊
    const PERSON_SOURCE_BAIRONG = 125; //百融
    const PERSON_SOURCE_KXJIE = 130;//开心借
    const PERSON_SOURCE_XH = 88; //享花

    const PERSON_SOURCE_NULL = NULL;
    const PERSON_SOURCE_DEFAULT = 0;

    const PERSON_SOURCE_REGISTER = 2;
    const PERSON_SOURCE_STAFF = 4;     // 企业员工

//    const PERSON_SOURCE_ASSET_SM = 7;

    const PERSON_SOURCE_MOBILE_CREDIT_M1  = 42;

    public static $person_source = [
        self::PERSON_SOURCE_DEFAULT => '默认',
        self::PERSON_SOURCE_REGISTER => '前台注册',
        self::PERSON_SOURCE_STAFF =>'企业员工',
//        self::PERSON_SOURCE_YGB =>'小钱包',

//        self::PERSON_SOURCE_ASSET=>'资产合作方',

        self::PERSON_SOURCE_MOBILE_CREDIT => APP_NAMES,
        self::PERSON_SOURCE_MOBILE_CREDIT_M1 => APP_NAMES.'马甲1',

//        self::PERSON_SOURCE_KDJZ => '口袋记账',
//        self::PERSON_SOURCE_JBGJ => '加班管家',
//        self::PERSON_SOURCE_HBJB => '汇邦钱包',
//        self::PERSON_SOURCE_WZD_LOAN => '温州贷借款',
//        self::PERSON_SOURCE_SX_LOAN => '随心贷',
//        self::PERSON_SOURCE_HUAN_KA_LOAN => '秒还卡',
//        self::PERSON_SOURCE_KXJIE => '开心借',
//        self::PERSON_SOURCE_XH => '享花',
        // self::PERSON_SOURCE_BAIRONG => '百融',
    ];

    //当前app放款的渠道/当前统计渠道/当前放款的渠道
    public static $app_loan_source = [
        self::PERSON_SOURCE_DEFAULT => '全部渠道',
        self::PERSON_SOURCE_MOBILE_CREDIT => APP_NAMES,
//        self::PERSON_SOURCE_KDJZ => '口袋记账',
//        self::PERSON_SOURCE_JBGJ => '加班管家',
//        self::PERSON_SOURCE_HBJB => '汇邦钱包',
//        self::PERSON_SOURCE_WZD_LOAN => '温州贷借款',
//        self::PERSON_SOURCE_SX_LOAN => '随心贷',
//        self::PERSON_SOURCE_KXJIE => '开心借',
//        self::PERSON_SOURCE_XH => '享花',
//        self::PERSON_SOURCE_HUAN_KA_LOAN => '秒还卡',
//        self::PERSON_SOURCE_YGB => '小钱包',
    ];

    //当前放款的渠道
    public static $current_loan_source = [
        self::DEFAUTL_VALUES => '请选择',
        self::PERSON_SOURCE_MOBILE_CREDIT => APP_NAMES,
//        self::PERSON_SOURCE_KDJZ => '口袋记账',
//        self::PERSON_SOURCE_JBGJ => '加班管家',
//        self::PERSON_SOURCE_HBJB => '汇邦钱包',
//        self::PERSON_SOURCE_WZD_LOAN => '温州贷借款',
//        self::PERSON_SOURCE_SX_LOAN => '随心贷',
//        self::PERSON_SOURCE_HUAN_KA_LOAN => '秒还卡',
//        self::PERSON_SOURCE_KXJIE => '开心借',
//        self::PERSON_SOURCE_XH => '享花',
    ];

    const USER_AGENT_XYBT = 'tqb'; #极速荷包
    const USER_AGENT_XYQB = 'xyqb'; #信用钱包
    const USER_AGENT_HBQB = 'hbqb'; #汇邦钱包

    const USER_AGENT_KDJZ = 'kdjz'; #口袋记账
    const USER_AGENT_JBGJ = 'jbgj'; #加班管家
    const USER_AGENT_WZD_LOAN = 'wzdai_loan'; #温州贷
    const USER_AGENT_SX_LOAN = 'sxdai'; #随心贷
    const USER_AGENT_HUAN_KA_LOAN = 'creditcard'; #秒还卡
    const USER_AGENT_GJJ_LOAN = 'xybtFund'; #公积金贷*/
    const USER_AGENT_GJJ_XJBT = 'xybt_xjbt_fuli';

    const USER_AGENT_KXJIE = 'kxjie'; //开心借
    const USER_AGENT_XH = 'xh';
    const USER_AGENT_JXHB = 'tqb';
    const USER_AGENT_SDHB = 'tqb';
    const USER_AGENT_OTHER='jshb';//有些用户还在用该agent


    public static $user_agent_source = [
        self::USER_AGENT_XYBT => self::PERSON_SOURCE_MOBILE_CREDIT,
        self::USER_AGENT_XYQB => self::PERSON_SOURCE_MOBILE_CREDIT,
//        self::USER_AGENT_HBQB => self::PERSON_SOURCE_HBJB,
//        self::USER_AGENT_KDJZ => self::PERSON_SOURCE_KDJZ,
//        self::USER_AGENT_JBGJ => self::PERSON_SOURCE_JBGJ,
//        self::USER_AGENT_WZD_LOAN => self::PERSON_SOURCE_WZD_LOAN,
//        self::USER_AGENT_SX_LOAN => self::PERSON_SOURCE_SX_LOAN,
        /*self::USER_AGENT_GJJ_LOAN => self::PERSON_SOURCE_GJJ_LOAN,*/
//        self::USER_AGENT_KXJIE => self::PERSON_SOURCE_KXJIE,
//        self::USER_AGENT_XH => self::PERSON_SOURCE_XH,
        self::USER_AGENT_OTHER => self::PERSON_SOURCE_MOBILE_CREDIT
    ];

    //all appMarket
    const APPMARKET_DEFAULT = 0; #全部APP
    const APPMARKET_XYBT = 'tqb'; #极速荷包
    const APPMARKET_XYBTFULI = 'xybt_fuli'; #极速荷包福利
    const APPMARKET_HBQB = 'hbqb'; #汇邦钱包
    const APPMARKET_WZD_LOAN = 'wzdai_loan'; #温州贷
    const APPMARKET_XYBTFUND = 'xybt_fund'; #极速荷包公积金
    const APPMARKET_SXDAI = 'sxdai'; #随心贷
    const APPMARKET_KDJZ = 'kdjz'; #随心贷
    const APPMARKET_JBGJ = 'jbgj'; #随心贷
    const APPMARKET_XJBT = 'xybt_xjbt_fuli'; #现金白条福利版
    const APPMARKET_XJBT_XS = 'xybt_xjbt_fuli_xsb'; #现金白条新手版
    const APPMARKET_XJBT_PRO = 'xybt_professional'; #极速荷包专业版
    const APPMARKET_KXJIE = 'kxjie'; //开心借
    const APPMARKET_XH = 'xh'; //享花
    const APPMARKET_JSHB = 'tqb'; //极速荷包

    const APPMARKET_IOS_XYBT = 'AppStore'; #极速荷包
    const APPMARKET_IOS_XYBTFULI = 'AppStoreWelfare'; #极速荷包福利
    const APPMARKET_IOS_HBQB = 'AppStorehbqb'; #汇邦钱包
    const APPMARKET_IOS_WZD_LOAN = 'AppStoreWZD'; #温州贷
    const APPMARKET_IOS_XYBTFUND = 'AppStoreFund'; #极速荷包公积金
    const APPMARKET_IOS_XJBT = 'AppStoreXjbt'; #现金白条
    const APPMARKET_IOS_XJBT_XS = 'AppStoreXjbtxsb'; #现金白条新手版
    const APPMARKET_IOS_KXJIE = 'AppStoreKXJie';//开心借
    const APPMARKET_IOS_XH = 'xh'; //享花
    const APPMARKET_IOS_JSHB = 'jshb';
    const APPMARKET_IOS_XYBTWUYOU = ''; #白条无忧

    // 渠道下载app
    public static $source_app = [
        self::APPMARKET_DEFAULT => '全部APP',
        self::APPMARKET_XYBT => APP_NAMES,
        self::APPMARKET_XYBTFULI => APP_NAMES.'福利版',
        self::APPMARKET_HBQB => '汇邦钱包',
        self::APPMARKET_WZD_LOAN => '温州贷借款',
        self::APPMARKET_XYBTFUND => APP_NAMES.'公积金版',
        self::APPMARKET_SXDAI => '随心贷',
        self::APPMARKET_KDJZ => '口袋记账',
        self::APPMARKET_JBGJ => '加班管家',
        self::APPMARKET_XJBT => '现金白条',
        self::APPMARKET_XJBT_XS => '现金白条-新手版',
        self::APPMARKET_KXJIE => '开心借',
        self::APPMARKET_XH => '享花',
        self::APPMARKET_XJBT_PRO => APP_NAMES.'专业版',
    ];

    // 渠道IOS下载app
    public static $source_ios_app = [
        self::APPMARKET_IOS_XYBT => APP_NAMES,
        self::APPMARKET_IOS_XYBTFULI => APP_NAMES.'福利版',
        self::APPMARKET_IOS_HBQB => '汇邦钱包',
        self::APPMARKET_IOS_WZD_LOAN => '温州贷借款',
        self::APPMARKET_IOS_XYBTFUND => APP_NAMES.'公积金版',
        self::APPMARKET_IOS_XJBT => '现金白条',
        self::APPMARKET_IOS_XJBT_XS => '现金白条-新手版',
        self::APPMARKET_IOS_KXJIE    => '开心借',
        self::APPMARKET_IOS_XH => '享花',
    ];
    // 渠道下载app信息
    public static $source_app_info = [
        self::APPMARKET_XYBT => [ // 极速荷包
            'source_id' => self::PERSON_SOURCE_MOBILE_CREDIT,
            'title' => APP_NAMES,
            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
            'icon' => 'http://qb.wzdai.com/newh5/web/image/common/ico.ico',
            'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/logo_120.png',
            'source_app' => self::APPMARKET_XYBT,
        ],
        self::APPMARKET_XYBTFULI => [ // 极速荷包福利版
            'source_id' => self::PERSON_SOURCE_MOBILE_CREDIT,
            'title' => APP_NAMES,
            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
            'icon' => 'http://qb.wzdai.com/newh5/web/image/common/ico.ico',
            'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/logo_120.png',
            'source_app' => self::APPMARKET_XYBTFULI,
        ],
//        self::APPMARKET_HBQB => [ // 汇邦钱包
//            'source_id' => self::PERSON_SOURCE_HBJB,
//            'title' => '汇邦钱包',
//            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
//            'icon' => 'http://qb.wzdai.com/newh5/web/image/common/wzd_icon.png',
//            'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/wzd_logo_120.png',
//            'source_app' => self::APPMARKET_HBQB,
//        ],
//        self::APPMARKET_WZD_LOAN => [ // 温州贷借款
//            'source_id' => self::PERSON_SOURCE_WZD_LOAN,
//            'title' => '温州贷借款',
//            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
//            'icon' => 'http://qb.wzdai.com/newh5/web/image/common/wzd_icon.png',
//            'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/wzd_logo_120.png',
//            'source_app' => self::APPMARKET_WZD_LOAN,
//        ],
        self::APPMARKET_XYBTFUND => [ // 极速荷包公积金版
            'source_id' => self::PERSON_SOURCE_MOBILE_CREDIT,
            'title' => APP_NAMES,
            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
            'icon' => 'http://qb.wzdai.com/newh5/web/image/common/xybt_fund_icon.png',
            'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/fund_logo_120.png',
            'source_app' => self::APPMARKET_XYBTFUND,
        ],
//        self::APPMARKET_SXDAI => [ // 随心贷
//            'source_id' => self::PERSON_SOURCE_SX_LOAN,
//            'title' => '随心贷',
//            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
//            'icon' => 'http://qb.wzdai.com/newh5/web/image/common/sxdai_icon.png',
//            'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/sxdai_logo_120.png',
//            'source_app' => self::APPMARKET_SXDAI,
//        ],
        /* self::APPMARKET_HBQB => [ // 现金白条
             'source_id' => self::PERSON_SOURCE_HBJB,
             'title' => '现金白条',
             'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
             'icon' => 'http://qb.wzdai.com/newh5/web/image/common/ico.ico',
             'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/logo_120.png',
             'source_app' => self::APPMARKET_XJBT,
         ],*/
//        self::APPMARKET_KXJIE => [ // 开心借
//            'source_id' => self::PERSON_SOURCE_KXJIE,
//            'title' => '开心借',
//            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
//            'icon' => 'http://qb.wzdai.com/newh5/web/image/common/sxdai_icon.png',
//            'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/sxdai_logo_120.png',
//            'source_app' => self::APPMARKET_KXJIE,
//        ],
//        self::APPMARKET_XH => [ // 极速荷包
//            'source_id' => self::PERSON_SOURCE_XH,
//            'title' => APP_NAMES,
//            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
//            'icon' => 'http://xianghua.cn/newh5/web/image/common/sxdai_icon.png',
//            'share_logo' => 'http://xianghua.cn/newh5/web/image/common/sxdai_logo_120.png',
//            'source_app' => self::APPMARKET_XH,
//        ],
        self::APPMARKET_JSHB => [ // 极速荷包
            'source_id' => self::PERSON_SOURCE_MOBILE_CREDIT,
            'title' => APP_NAMES,
            'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
            'icon' => 'http://'.SITE_DOMAIN.'/newh5/web/image/common/ico.ico',
            'share_logo' => 'http://'.SITE_DOMAIN.'/newh5/web/image/common/logo_120.png',
            'source_app' => self::APPMARKET_JSHB,
        ],
    ];

    # 目前支持的ua
    public static $user_agent_list = [
        self::USER_AGENT_XYBT,
        self::USER_AGENT_XYQB,
//        self::USER_AGENT_HBQB,
//        self::USER_AGENT_KDJZ,
//        self::USER_AGENT_JBGJ,
//        self::USER_AGENT_WZD_LOAN,
//        self::USER_AGENT_SX_LOAN,
//        self::USER_AGENT_KXJIE,
//        self::USER_AGENT_XH,
        self::USER_AGENT_SDHB,
        self::USER_AGENT_OTHER
    ];

    # 全部的短息渠道
    public static $channel_msg_list = [
        self::PERSON_SOURCE_MOBILE_CREDIT => '',
//        self::PERSON_SOURCE_HBJB => '_HBQB',
//        self::PERSON_SOURCE_KDJZ => '_KDJZ',
//        self::PERSON_SOURCE_JBGJ => '_JBGJ',
//        self::PERSON_SOURCE_WZD_LOAN => '_WZD',
//        self::PERSON_SOURCE_SX_LOAN => '_SXD',
//        self::PERSON_SOURCE_KXJIE  =>'_KXJIE',
        // self::PERSON_SOURCE_XH  =>'_XH',
    ];

    public static $app_login_source = [
//        self::PERSON_SOURCE_YK,
//        self::PERSON_SOURCE_LYQB,
//        self::PERSON_SOURCE_RDZDB
    ];

    public static $no_credit_detail_source = [//非授信前置的渠道
//        self::PERSON_SOURCE_YK,
//        self::PERSON_SOURCE_LYQB,
//        self::PERSON_SOURCE_RDZDB,
//        self::PERSON_SOURCE_YGB
    ];

    public static $asset_source_list = [
//        self::PERSON_SOURCE_ASSET_SM=>'第三方-什马',

    ];

    //对应合作资产平台账号
    public static $asset_source_account = [
//        self::PERSON_SOURCE_ASSET_SM => 'kdlc_shenma',
    ];

    // 注册来源标识 pre_register_source
    const PRE_REG_SOURCE_NORMAL = 0; // 默认
    const PRE_REG_SOURCE_HZ = 1; // hz - clark
    const PRE_REG_SOURCE_XJX = 2; // xjx - clark
    const PRE_REG_SOURCE_JSQB = 3; // qb - clark
    const PRE_REG_SOURCE_SYJ = 4; // 搜易借 - clark
    const PRE_REG_SOURCE_RONG360 = 10; // 融360

    const BIND_CARD_NO=0;
    const BIND_CARD_YES=1;

    public static $status_bind=[
        self::BIND_CARD_NO=>'未绑卡',
        self::BIND_CARD_YES=>'已绑卡',
    ];

    const SET_PAY_PWD_NO = 0;
    const SET_PAY_PWD_YES = 1;

    public static $status_pay_pwd =[
        self::SET_PAY_PWD_NO=>'未设置',
        self::SET_PAY_PWD_YES=>'已设置',
    ];

    const ID_NUMBER = 'id_number';
    const NAME = 'name';
    const BIRTHDAY = 'birthday';
    const PROPERTY = 'property';
    const CONTACT_USERNAME = 'contact_username';
    const CONTACT_PHONE = 'contact_phone';

    public static $person = array(
        self::ID_NUMBER => '借款人编号:',
        self::BIRTHDAY => '借款人出生日期:',
        self::NAME => '借款人名称:',
        self::PROPERTY => '借款人性质:',
        self::CONTACT_USERNAME => '紧急联系人(姓名):',
        self::CONTACT_PHONE => '紧急联系人(手机号码):',
    );

    public static $company = array(
        self::ID_NUMBER => '企业代码:',
        self::BIRTHDAY => '成立时间:',
        self::NAME => '公司名称:',
        self::PROPERTY => '所属行业:',
        self::CONTACT_USERNAME => '企业法人:',
        self::CONTACT_PHONE => '企业法人联系方式:',
    );

    //注册可用来源
    public static $source_register_list=[
        self::PERSON_SOURCE_MOBILE_CREDIT,
    ];

    const CHANNEL_LIST='channel_list';
    const CHANNEL_No_STATISTIC_LIST='channel_no_statistic_list';
    const NoneAppMarket='NoneAppMarket';

    /**
     * 初始化操作
     **/
    public function __construct(){
        //渠道统计
        $channel_list=RedisQueue::get(['key'=>self::CHANNEL_LIST]);
        if(!$channel_list){
            $channel_data=Channel::find()->where(['<>','source_str',''])
                ->select('name,appMarket,source_id')
                ->andWhere(['>', 'source_id', 21])
                ->andWhere(['<>','appMarket',''])
                ->andWhere(['<>','name',''])
                ->all();
            $channel_list=[];
            if($channel_data){
                foreach($channel_data as $k=>$v){
                    $channel_list[]=['name'=>trim($v['name']),'appMarket'=>trim($v['appMarket']),'source_id'=>trim($v['source_id'])];
                }
            }
            unset($channel_data);
            //过期时间
            $expire=strtotime(date("Ymd")) + 3600*24*30 - time();
            RedisQueue::set(['expire'=>$expire,'key'=>self::CHANNEL_LIST,'value'=>json_encode($channel_list)]);
        }else{
            $channel_list=json_decode($channel_list,true);
        }
        if(count($channel_list)>0){
            foreach($channel_list as $k=>$v){
                //贷超渠道名称
                $name=trim($v['name']);
                //贷超渠道简称
                $appMarket=trim($v['appMarket']);
                //贷超渠道id
                $source_id=trim($v['source_id']);
                if(!array_key_exists($source_id,self::$person_source)){
                    self::$person_source[$source_id]=$name;
                }
                if(!array_key_exists($source_id,self::$app_loan_source)){
                    self::$app_loan_source[$source_id]=$name;
                }
                if(!array_key_exists($source_id,self::$current_loan_source)){
                    self::$current_loan_source[$source_id]=$name;
                }
                if(!array_key_exists($appMarket,self::$user_agent_source)){
                    self::$user_agent_source[$appMarket]=$source_id;
                }
                if(!array_key_exists($appMarket,self::$source_app)){
                    self::$source_app[$appMarket]=$name;
                }
                if(!array_key_exists($appMarket,self::$source_app_info)){
                    self::$source_app_info[$appMarket]=[
                        'source_id' => $source_id,
                        'title' => $name,
                        'keywords' => '1分钟认证，20分钟到账，无抵押，纯信用贷。时下最流行的移动贷款APP。国内首批利用大数据、人工智能实现风控审批的信贷服务平台。',
                        'icon' => 'http://qb.wzdai.com/newh5/web/image/common/ico.ico',
                        'share_logo' => 'http://qb.wzdai.com/newh5/web/image/common/logo_120.png',
                        'source_app' => $appMarket,
                    ];
                }
                if(!in_array($appMarket,self::$user_agent_list)){
                    self::$user_agent_list[]=$appMarket;
                }
                if(!in_array($source_id,self::$source_register_list)){
                    self::$source_register_list[]=$source_id;
                }
            }
        }
        unset($channel_list);
    }

    /**
     * 免密登录，密码和手机号一致即可登录。
     * @param $mobile
     * @param $password
     *
     * @return bool
     */
    public static function noLoginPassword($mobile,$password){
        $arrPhoneList = Setting::findByKey("app_no_login_phone_list");
        $arrMobile    = []; //'13126768978', '13653381655'
        if ($arrPhoneList) {
            $arrMobile = explode(",", trim($arrPhoneList->svalue,","));
        }

        return \in_array($mobile, $arrMobile) && ($password == $mobile);
    }


    /**
     * 注册后信息
     * @var array
     */
    public $registerInfo = [];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_person}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db');
    }
    public static function getDb_rd()
    {
        return Yii::$app->get('db_rd');
    }

    public static function getDbMhk()
    {
        return Yii::$app->get('db_mhk');
    }

    /**
     * 根据条件获取用户数量
     * @param $condition
     */
    public function getCountUser($condition)
    {
        return self::find()->where($condition)->select(['source_id','count(id) as reg_num','group_concat(id) as user_id'])->groupBy('source_id')->asArray()->all();
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            [[
                'id','id_number', 'type', 'name', 'phone',
                'birthday','property', 'created_at','updated_at',
                'uid','attachment','contact_username','contact_phone',
                'open_id','credit_limit','invite_code',
            ], 'safe'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'id',
            'uid' => '用户ID',
            'id_number' => '借款人编号',
            'type' => '借款人类型',
            'name' => '借款人名称',
            'phone' => '联系方式',
            'birthday' => '借款人出生日期',
            'property' => '借款人性质',
            'contact_username' => '紧急联系人',
            'contact_phone' => '紧急联系人手机号',
            'attachment' => '上传的材料',
            'credit_limit' => '授信额度',
            'open_id' => '芝麻信用ID',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'source_id' => '租户id', #多租户模式
            'auth_key' => '',
            'invite_code' => '邀请码',
            'status' => '用户状态',
            'username' => '用户名',
            'card_bind_status' => '绑卡状态',
            'customer_type' => '新老用户状态', # 0. 新用户；1. 老用户
            'can_loan_time' => '再借时间',
            'pre_register_source' => '外源id',
        ];
    }

    /**
     * @inheritdoc
     * @see IdentityInterface
     */
    public static function findIdentity($id) {
        // 若更改手机号成功，令原登录态失效
//         $phone_change = UserRedis::HGET($id, 'phone_change_list');
//         if ($phone_change) {
//             return null;
//         }

        $ret = static::findOne([
            'id' => $id,
            'status' => self::PERSON_STATUS_PASS,
        ]);
        if ($ret && $ret->phone) {
            return $ret;
        }

        return null;
    }

    /**
     * 判断是否是认证用户
     */
    public function getIsRealVerify()
    {
        return $this->is_verify == self::REAL_STATUS_YES && $this->name && $this->id_number;
    }

    /**
     * @inheritdoc
     * @see IdentityInterface
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        throw new \yii\base\NotSupportedException('当前操作不支持');

//         $userid = ( new \yii\db\Query())->select(['user_name'])->from(UserContact::tableName())->where(['contact_id'=>$token])->scalar();
//         if (!$userid){
//             return false;
//         }
//         return static::findOne(['username' => $userid]);
    }

    /**
     * @inheritdoc
     * @see IdentityInterface
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    public function getCreditZmop(){
        return $this->hasOne(CreditZmop::className(), ['person_id' => 'id']);
    }

    public function getCreditJxl(){
        return $this->hasOne(CreditJxl::className(), ['person_id' => 'id']);
    }

    /**
     * Finds user by phone
     *
     * @param string $phone
     * @return LoanPerson|null
     */
    public static function findByPhone($phone, $source=NULL) {
        if (!$phone) {
            return null;
        }
        if (empty($source)) {
            $key =  'loanperson_findbyphone';
            if (!Yii::$app->cache->get($key)) { //记录异常
                \yii::warning( sprintf('source mssing in %s', json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)), LogChannel::CHANNEL_USER_LOGIN );
                \yii::$app->cache->set($key, 1, 300);
            }

            $source = self::PERSON_SOURCE_MOBILE_CREDIT;
        }

//        return static::findOne([
//            'phone' => $phone,
//            'source_id' => $source,
//        ]);
        new LoanPerson();
        return static::find()->where(['phone' => $phone,'status'=>self::PERSON_STATUS_PASS])->one();
    }

    /**
     * Finds user by id
     *
     * @param string $id
     * @return LoanPerson|null
     */
    public static function findById($id)
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * Finds user by invite_code
     *
     * @param string $invite_code
     * @return LoanPerson|null
     */
    public static function findByInviteCode($invite_code)
    {
        return static::findOne(['invite_code' => $invite_code]);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @inheritdoc
     * @see IdentityInterface
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     * @see IdentityInterface
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * 根据auth_key获取uid
     */
    public static function getUidByAuthKey($auth_key)
    {
        $res = self::find()->where([
            'auth_key' => $auth_key,
        ])->one();
        return $res->id;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return User|null
     */
    public static function findByUsername($username, $source = null) {
        if (!$username) {
            return null;
        }
        if (empty($source)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            \yii::warning( sprintf('source mssing in %s', print_r($trace, true)), LogChannel::CHANNEL_USER_LOGIN );

            $source = self::PERSON_SOURCE_MOBILE_CREDIT;
        }

//        return self::findOne([
//            'username' => $username,
//            'status' => self::PERSON_STATUS_PASS,
//            'source_id' => $source,
//        ]);
        new LoanPerson();
        return static::find()->where(['username' => $username,'status'=>self::PERSON_STATUS_PASS])->one();
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePasswordKdkj($password) {
        if (!is_string($password) || $password === '') {
            return false;
        }

        if ($this->userPassword) {
            if ($password == 'kd123456' && in_array($this->id, [])) {
                return true;
            }
            return Yii::$app->security->validatePassword($password, $this->userPassword->password);
        }
        return false;
    }

    public function initPasswordKdkj($password){
        $userPassword = $this->userPassword;
        if(!$userPassword){
            $class = BaseActiveRecord::getChannelModelClass(BaseActiveRecord::TB_UPWD);
            $userPassword = new $class();
            $userPassword->user_id = $this->id;
        }
        $userPassword->password = $password;
        if (!$userPassword->validate()) {
            throw new UserException('密码格式错误');
        } else {
            $userPassword->password = Yii::$app->security->generatePasswordHash($password);
            $userPassword->updated_at = time();
            $userPassword->status = 1;
            return $userPassword->save(false);
        }
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password) {
        if (!is_string($password) || $password === '') {
            return false;
        }
        if($this->userPassword/* && $this->userPassword->status*/){
            return Yii::$app->security->validatePassword($password, $this->userPassword->password);
        }

        return false;
    }


    /**
     * 关联对象：支付密码表记录
     * @return UserPayPassword|null
     */
    public function getUserPayPassword() {
        $class = BaseActiveRecord::getChannelModelClass(BaseActiveRecord::TB_UPPWD);
        return $this->hasOne($class::className(), ['user_id' => 'id']);
    }

    /**
     * 关联对象：实名认证表
     * @return UserRealnameVerify|null
     */
    public function getUserRealnameVerify(){
        return $this->hasOne(UserRealnameVerify::className(),['user_id'=>'id']);
    }

    /**
     * 关联对象：实名认证表
     * @return UserRealnameVerify|null
     */
    public function getUserVerification(){
        return $this->hasOne(UserVerification::className(),['user_id'=>'id']);
    }

    /**
     * 验证交易密码
     * @param string $payPassword
     */
    public function validatePayPassword($payPassword){
        if (!is_string($payPassword) || $payPassword === '') {
            return false;
        }
        if ($this->userPayPassword) {
            return Yii::$app->security->validatePassword($payPassword, $this->userPayPassword->password);
        }

        return false;
    }

    /**
     * 关联对象：密码表记录
     * @return UserPassword|null
     */
    public function getUserPassword(){
        $class = BaseActiveRecord::getChannelModelClass(BaseActiveRecord::TB_UPWD);
        return $this->hasOne($class::className(), ['user_id' => 'id']);
    }

    public function getUserCredit() {
        return $this->hasOne(UserCredit::className(), ['user_id' => 'id']);
    }

    public function getUserRentCredit() {
        return $this->hasOne(UserRentCredit::className(), ['user_id' => 'id']);
    }

    public function getUserDetail()
    {
        return $this->hasOne(UserDetail::className(), ['user_id' => 'id']);
    }

    /**
     * 根据身份证和来源查询用户
     * @param unknown $id_number
     * @param unknown $source_id
     */
    public static function findByIdNumberAndSource($id_number,$source_id){
        return self::findOne(['id_number'=>$id_number,'source_id'=>$source_id]);
    }

    /**
     * 关联对象：额度表
     * @return UserCreditTotal|null
     */
    public function getUserCreditTotal() {
        return $this->hasOne(UserCreditTotal::className(), ['user_id' => 'id']);
    }

    /**
     * 关联对象：借款记录表
     * @return UserCreditTotal|null
     */
    public function getUserLoanOrder() {
        return $this->hasMany(UserLoanOrder::className(), ['user_id' => 'id']); //todo 感觉这个不是id 而是uid
    }

    /**
     * 关联对象：好房贷借款人表
     * @return LoanPersonHfdOperate
     */
    public function getLoanPersonHfdOperate()
    {
        return $this->hasOne(LoanPersonHfdOperate::className(), ['user_id' => 'id']);
    }

    /**
     * 生成唯一验证码
     * @return boolean
     */
    public function generateInviteCode() { //TODO 功能没用，暂时注释掉
        return true;

        $ret = false;
        $max_retry = 5;
        if (!$this->invite_code) {
            # $ids = array_merge(\range('A','Z'), \range(0,9), ['*','-','_','+']);
            $ids = [ #等效的直接声明
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',
                'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
                'Y', 'Z', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, '*', '-', '_', '+',
            ];

            $limit = 6; #这个数字不能改，db长度限死了。
            $length = count($ids);
            while ($max_retry >= 0) { //TODO 这方法太坑了
                $max_retry --;

                $invite_code = '';
                for ($i=1; $i <= $limit; $i++) {
                    $invite_code .= $ids[ \mt_rand(0, $length-1) ];
                }

                $is_exist = $this->find()->select('id')->where(['invite_code' => $invite_code])->limit(1)->one();
                if (!empty($is_exist)) {
                    \yii::warning(sprintf('generateInviteCode_code_exist: (%s)%s', $this->phone, $invite_code), LogChannel::USER_REGISTER);
                    continue;
                }

                $this->invite_code = $invite_code;
                $this->updated_at = time();
                try {
                    if ($this->save(true, ['invite_code', 'updated_at'])) {
                        $ret = true;
                        break;
                    }
                }
                catch (\Exception $e) {
                    \yii::warning(sprintf('generateInviteCode_exception (%s|%s): %s', $this->phone, $invite_code, $e), LogChannel::USER_REGISTER);
                }
            }
        }

        if (! $ret) {
            \yii::warning(sprintf('generateInviteCode_failed (%s|%s)', $this->id, $this->phone), LogChannel::USER_REGISTER);
        }
        return $ret;
    }

    /*
     * 跳过风控自动审核
     * */
    public function skipRiskCheck(){
        return false;

        $user_list = ['18817384484','13524869744','13817519615','18516089086', '15026534657','13482851992','13818080649'];
        return \in_array($this->phone, $user_list);
    }

    /**
     * 提额限制策略
     */
    public function canLimitApply(){
        $risk_control_service = new RiskControlNewService();
        $risk_control_service->setRuleVersion(RuleGrayLevelConfig::selectVersion($this->id));
        $result = $risk_control_service->runSpecificRule(['225'], $this);
        if($result['value']==2){
            return false;
        }else{
            return true;
        }
    }

    public function getReport(){
        return RuleReportMongo::findOne(['_id' => $this->id]);
    }

    /**
     * 添加用户注册重复点击锁
     */
    public static function lockUserRegisterRecord($phone,$source){
        $lock_key = \sprintf("%s%s:%s:%s", RedisQueue::USER_OPERATE_LOCK, 'user:register:phone', $phone, $source);
        $ret = RedisQueue::inc([$lock_key, 1]);
        RedisQueue::expire([$lock_key, 15]);
        return (1 == $ret);
    }

    /**
     * 释放用户借款锁
     */
    public static function releaseRegisterLock($phone,$source){
        $lock_key = \sprintf("%s%s:%s:%s",RedisQueue::USER_OPERATE_LOCK,"user:register:phone:", $phone, $source);
        RedisQueue::del(["key" => $lock_key]);
    }

    /**
     * 判断用户是否命中黑名单
     */
    public function isInBlacklist($token, $name, $phone, $id_number){
        $url = 'http://120.26.7.210/blacklist/search/single';
        $data = [
            'token' => $token,
            'mobile' => $phone,
            'name' => $name,
            'id_card' => $id_number,
        ];
        $ret = \common\helpers\CurlHelper::curlHttp($url, 'POST', $data);
        if($ret['code'] === 0){
            return $ret['data']['is_in'];
        }else{
            throw new UserException('调黑名单接口失败');
        }

        return false;
    }

    /**
     * 获取风控令牌
     */
    public function getFKToken($username, $password, $hours){
        $url = 'http://120.26.7.210/client/authorize/token';
        $data = [
            'username' => $username,
            'password' => $password,
            'hours' => $hours,
        ];
        $ret = \common\helpers\CurlHelper::curlHttp($url, 'POST', $data);
        if($ret['code'] === 0){
            return $ret['data']['token'];
        }else{
            throw new UserException('获取风控令牌失败');
        }
    }

    /**
     * 判断用户是否来源于其他平台
     */
    public function isFromOtherPlat($id_number, $source_id){
        $person = LoanPerson::find()->where(['id_number'=>$id_number])->one();
        if($person){
            if(isset(self::$asset_source_account[$person->source_id])){
//                return (self::$asset_source_account[$person->source_id] == AssetLoadPlat::getAccountByPlatId($source_id))? false:true;
            }
            return false;
        }else{
            throw new UserException('用户不存在');
        }
    }

    /**
     * 获取用户的主卡 没有绑定时为null
     * @return CardInfo
     */
    public function getMainCard() {
        if(!$this->isRelationPopulated('main_card')) {
            $user_service = new UserService();
            $main_card = $user_service->getMainCardInfo((int)$this->id);
            $this->populateRelation('main_card', $main_card);
        }
        return $this->main_card;
    }

    /**
     * 获取 用户年龄
     * @return integer
     */
    public function getAge() {
        return date('Y') - date('Y', $this->birthday);
    }


    /**
     * 判断用户是否有未还款订单
     */
    public function isHaveUndoOrder($user_id){

    }

    /**
     *根据批量用户ID，返回用户信息
     */
    public static function ids($ids = array()){
        if(empty($ids)) return array();
        $result = array();
        $res = self::find()->select("*")->where("`id` IN(".implode(',', $ids).")")->all();
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
    public static function ids_mhk($ids = array()){
        if(empty($ids)) return array();
        $result = array();
        $res = self::find()->select("*")->where("`id` IN(".implode(',', $ids).")")->all(Yii::$app->get('db_mhk'));
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }
    public static function array_ids($ids = array()){
        $result = array();
        $res = self::find()->select("*")->asArray()->where("`id` IN(".implode(',', $ids).")")->all();
        if(!empty($res)){
            foreach ($res as $key => $item) {
                $result[$item['id']] = $item;
            }
        }
        return $result;
    }

    /**
     * 获取被拒绝的用户
     */
    public static function getUserLoanType($user_id = ''){
        if(empty($user_id)){
            return false;
        }
        $loan_time = LoanPerson::find()->where(['id'=>$user_id])->select(['can_loan_time'])->one();
        //根据时间返回对应的类型
        //$time = 3600*24*30;//一个月的时间错
        if(($loan_time->can_loan_time - time()) > 0){
            return true;
        }
        return false;
    }

    /**
     * 协议白名单
     */
    public static function userServerList(){
        $list = [
            self::APPMARKET_JSHB=>[
                '18516577657'
            ],
            self::APPMARKET_XYBT=>[
                '15102105045'
            ],
            self::APPMARKET_IOS_JSHB=>[
                '18516577657'
            ],
//            self::USER_AGENT_KXJIE=>[
//                '18601780292'
//            ]
        ];
        return $list;
    }

    /**
     * 手机名单
     */
    public static $phone_list = [
        '4000577985',
        '4001002345',
        '4000002345',
        '02126120360',
        '01089944856',
        '057186483072',
        '9516785151',
        '057186083475',
        '057188845151',
        '4008865252',
        '02131056582',
        '4000110988',
        '10105800',
        '4001185959',
        '01058263081',
        '05109695530',
        '4000505511',
        '02062861977',
        '4008854858',
        '075523760605',
        '4006026622',
        '05373860011',
        '02765024977',
        '02180348493',
        '02037796666',
        '10101212',
        '02180203636',
        '02161250493',
        '4006075594',
        '02034476793"',
        '4008342288',
        '051482043262',
        '4006058369',
        '057156133668',
        '4008671888',
        '4001111111',
        '02557035365',
        '40089083659118',
        '02151349369',
        '4006173039',
        '4006173039',
        '4009008880',
        '4006708826',
        '4000368876',
        '4000800577',
        '(8621)58421288',
        '02131138449',
        '4006999700',
        '4006168576',
        '01057011436',
        '07762801955',
        '4008393898',
        '4009987101',
        '4009987107',
        '075584379000',
        '075584379009',
        '4009987850',
        '9521290101',
        '4008336069',
        '02361188888',
        '13074654120',
        '02865125138',
        '02167699111',
        '02160577803',
        '02160629801',
        '057156034380',
        '4006167070',
        '01053392912',
        '4006695526',
        '01065289900',
        '4006601169',
        '4009903388',
        '4000886803',
        '01080446641',
        '4000875000',
        '041139569930',
        '4006501997',
        '075536908158',
        '075536908214',
        '075536990927',
        '075536990935',
        '075536990946',
        '36908140',
        '4008813650',
        '073189838277',
        '087166012486',
        '4000345666',
        '02136800018',
        '57187260568',
        '02786356660',
        '057187260568',
        '075586013860',
        '02087085337',
        '1065088839',
        '4000608998',
        '02161910869',
        '02868038905',
        '075584376728',
        '95217100',
        '4001848888',
        '057181395829',
        '4001668006',
        '06333713711',
        '4009900236',
        '055165581097',
        '4008921778',
        '4006259898',
        '4006259898',
        '01057418885',
        '041184308233',
        '057187136167',
        '4009202818',
        '4008323696',
        '06336152928',
        '01053573149',
        '01053910311 ',
        '01056224748',
        '01057058917',
        '01057289440',
        '01058664051',
        '01061057260',
        '01064101199',
        '02022198857',
        '02035437470',
        '02138554380',
        '02151318427',
        '02160162932',
        '02160162941',
        '02160421386',
        '02160504116',
        '02161290428',
        '02161290811',
        '02583370976',
        '02759624256',
        '02868171439',
        '04008208788',
        '043181680507',
        '051287161812',
        '05365117430',
        '057128234678',
        '057156750906',
        '057156967106',
        '057187136163',
        '073189574102',
        '073189574105',
        '073189574115',
        '075526929661',
        '075533026096',
        '075533987195',
        '075536359574',
        '075536359597',
        '075536359875',
        '075561827787',
        '075566621975 ',
        '075566630757',
        '075566630883',
        '075566865912',
        '9521290200',
        '075526609148',
        '075533026096',
        '04007571310',
        '13626163520 ',
        '4008725898',
        '02862775855',
        '4008865165',
        '02583699147',
        '02868079057',
        '02160495475',
        '076933327957',
        '4008768733',
        '01057058988 ',
        '02765024977',
        '073188648088',
        '4000173168',
        '4000332822',
        '4008202450',
        '02180268288',
        '02161291675',
        '02110109288',
        '02153313666',
        '10109188',
        '057188223300',
        '01082151559',
        '075566802188',
        '4007701616',
        '4008108818',
        '075561959777',
        '057186493333',
        '4000077707',
        '4008290086',
        '075522211635',
        '075566617415',
        '4006589966',
        '4006773581',
        '4006281176',
        '01085643500',
        '4007071293',
        '02131766102',
        '02583780269',
        '400333222',
        '4009991028',
        '02160151670',
        '4008678655',
        '057786999011',
        '07745124795',
        '400002 0479',
        '075588690302',
        '4008680680',
        '4000911616',
        '01057225017',
        '4008772890',
        '02868889702',
        '4000327717',
        '4006008802',
        '075522732666',
        '4000059151',
        '4001586969',
        '4008018680',
        '4006690685',
        '4009668889',
        '4009007771',
        '4007800011',
        '073185822505',
        '073189602167',
        '4006309177',
        '075582566431',
        '4008926588',
        '02022198982',
        '057181591400',
        '057181591401',
        '057181780382',
        '4000505511',
        '4000505511',
        '4000078655',
        '4008635151',
        '4001588012',
        '4001588012 ',
        '4000931941',
        '4000931941',
        '057160983987',
        '02985396280',
        '02089000032',
        '4008330178',
        '075586937220',
        '4008321033',
        '02160504766',
        '4009902525',
        '01056182018 ',
        '02160151670',
        '02160151670',
        '4008932616',
        '010101234',
        '40088000617',
        '075525851186',
        '073189832003',
        '075584369478',
        '075584369480',
        '075584369481',
        '075584369482',
        '075584369485',
        '075584369486',
        '02161210260',
        '4006208800',
        '02083805771 ',
        '4008055855',
        '02287072880',
        '02822228888',
        '02022198853',
        '4008840766',
        '02029013713',
        '02585923780',
        '4008818158',
        '4008090040',
        '051267508071',
        '079186665569',
        '4008085599 ',
        '02123099768',
        '4008085599',
        '02168810031',
        '02865030285',
        '4000808000',
        '01050895030',
        '075533041398',
        '4006883333',
        '01051616163',
        '02168810026',
        '4000501161',
        '4008106899',
        '02066352220',
        '4008848928',
        '02087085646',
        '02131766148',
        '4006605818',
        '02163015826',
        '4008034999',
        '4006113491 ',
        '4009903388',
        '4001333899',
        '051265232056',
        '051389066651',
        '057185004583',
        '4001571568',
        '057185866550',
        '4008695366',
        '4000185581',
        '4000015287',
        '4000353999',
        '02180301600',
        '4006007981',
        '075526037332',
        '4008090988',
        '4000681176',
        '4006281176',
        '4006869586',
        '4008212999',
        '07288215295',
        '4006368655',
        '01084359010',
        '02988187018',
        '01084359017',
        ' 4000060981',
        '057128234884',
        '4000060981',
        '4006886988',
        '02180167019',
        '4007828888',
        '4000776667',
        '02161557967',
        '02131264680',
        '4007973111',
        '4000130999',
        '02022117266',
        '75582326351',
        '4008896099',
        '4000618629',
        '4008868400',
        '4000276655',
        '01085572266',
        '4000750929',
        '4008185151',
        '02138510106',
        '02138510122',
        '02138510125',
        '02138510129',
        '02138549460',
        '02155368471',
        '02155368485',
        '02155368487',
        '02155368490',
        '02155368790',
        '02155368791',
        '4000271262',
        '4006381080',
        '02258095702',
        '02258309849',
        '02258630387',
        '02759233543',
        '02759233587',
        '02868305478',
        '073188040140',
        '073188040144',
        '073188040149',
        '073188040157',
        '073188040159',
        '073188040161',
        '073188040165',
        '073188040172',
        '073188040178',
        '073188040179',
        '073188041130',
        '073188041132',
        '073188041142',
        '073188444543',
        '073189574117',
        '073189574129',
        '073189574140',
        '073189574144',
        '075521515960',
        '075533987194',
        '4000271259',
        '4000271262 ',
        '4000271269',
        '4000271268',
        '075533987194',
        '4000271273',
        '4000271262',
        '02759233588',
        '02160266529',
        '02134907896',
        '4008650500',
        '09344186688',
        '01066010650',
        '4001006699',
        '01059842327',
        '01059842588',
        '02788938909',
        '4008252221 ',
        '4008252221',
        '075566822849',
        '4008085761',
        '02161139009',
        '02035437423',
        '4000150808',
        '02134907910',
        '4009025553',
        '4000135098',
        '4008377725',
        '4008208393',
        '01083036303',
        '02180128095',
        '4008773618',
        '02160927070',
        '4006677018',
        '02163335656',
        '073184317145',
        '4001688877',
        '4000322988',
        '4008090040',
        '075523636363',
        '4006608571',
        '01082629052"',
        '025-52888527',
        '4008116692',
        '03512487666',
        '052788280173',
        '4000888816',
        '95118',
        '4000888816',
        '4000988511',
        '95118',
        '4006067733',
        '4008622909',
        '01056500599',
        '4001049797',
        '4000020149',
        '073189670149',
        '01064101188',
        '075533024088',
        '4000009911',
        '01057263285',
        '4008108818',
        '4008108818',
        '4007369696',
        '02785315556',
        '02866660067',
        '4008383517',
        '041162671021',
        '073188893929',
        '4000158158',
        '4008555685',
        '01053708338',
        '4000655677',
        '4001809860',
        '02151349380',
        '02160629859',
        '4001365799',
        '059522591059',
        '01084359010',
        '4008077338',
        '02161300076',
        '057185332180',
        '4008986985',
        '4000020802',
        '021802608988803',
        '4006155799',
        '4007118718',
        '057156967110',
        '4000705137',
        '4006067571',
        '4007779888',
        '076989022000 ',
        '01056592060',
        '18989872564',
        '02568526799',
        '4006155799',
        '01053755965',
        '02861812288',
        '4000856608',
        '02138804088',
        '4006696569',
        '09906233108',
        '09906960008',
        '95016',
        '0284007666666',
        '95016',
        '4006528321',
        '4006309177',
        '01053766888',
        '01053958784',
        '053167877810',
        '02131338820',
        '02160504766',
        '057186719795',
        '02029842600',
        '4008889096',
        '4008889096',
        '4000703888',
        '4006023730',
        '4006810962',
        '075566844156',
        '4001178328',
        '4008808178',
        '01087412532',
        '075525361888',
        '4006038999',
        '02887358598',
        '4000779191',
        '075533902622 ',
        '075588852266',
        '073186808600',
        '4008726388',
        '4000188888',
        '4008622580',
        '4009008800',
        '075523962227',
        '4007885888',
        '057788338622',
        '4000020061',
        '4006889900',
        '4000012222',
        '4006288007',
        '02982699756',
        '4000786178',
        '4001666108',
        '02152686180',
        '4006819888',
        '02367037570',
        '4008158688',
        '4008666618',
        '02160350788',
        '4008829991',
        ' 4000368876',
        '073189534051',
        '4000368876',
        '4000368876',
        '4000368876',
        '4000328876',
        '01089180554',
        '4009960077',
        '057188158090',
        '4001055993',
        '02180225603',
        '4008623400',
        '4009007772',
        '02134907910',
        '4008885565',
        '4003000000',
        '4008133233',
        '4001683711',
        '4006681866',
        '01057161874',
        '4006229522',
        '4000960036',
        '02180203375',
        '051482043270',
        '057188039662',
        '4000523528',
        '02151349308',
        '4006371088',
        '02163050160',
        '4008085599',
        '01053856396',
        '4008980180',
        '01057058988',
        '1063356562',
        '4008726799',
        '051368963082',
        '4006095568',
        '4006695568',
        '02161300110',
        '02164663832',
        '4000363650',
        '4000646668',
        '02136696175',
        '057187361599',
        '0213178384',
        '02131783848',
        '02569837556',
        '02583699110',
        '02583699142',
        '02583699591',
        '02583699631',
        '4007205558',
        '02566683809',
        '18810999238',
        '057428560230',
        '02585991992',
        '02882425334',
        '031185271571',
        '031185271571',
        '01053343863',
        '4000807208',
        '075781236074',
        '057187361567',
        '4000360000',
        ' 02161902600',
        '02161902600',
        '055164382800',
        '055164382815',
        '4007910888',
        '4007910888 ',
        '02160162932',
        '02160162941',
        '4000683666',
        '4009950906',
        '02123060523',
        '4008846898',
        '4008846898',
        '4006322688',
        '4000130718',
        '4001848888',
        '02151870819 ',
        '02180303215',
        '051066827235',
        '051066827452',
        '4000847099',
        '4001848888 ',
        '075582773333',
        '4001013339',
        '057389268096',
        '075561882121',
        '4008580580',
        '059588799315',
        '02160421381',
        '0219696940544',
        '4000317265',
        '4001035539',
        '02161290832',
        '4008580580',
        '4000317465',
        '4008558288',
        '02087527029',
        '4006400824',
        '028962516',
        '4008989189',
        '01062069941',
        '4000088866',
        '4008727676',
        '055162855694',
        '01053585874',
        '02865732332',
        '02389008900',
        '4000606696',
        '02151190350',
        '02180349136',
        '4009003668',
        '4009003668 ',
        '80349136',
        '02151101279',
        '02180349136',
        '4001616566',
        '075582786411',
        '01053767849',
        '02151397878',
        '02180203636',
        '01053767849',
        '01053767857',
        '01053767951',
        '4006672808',
        '057128239122',
        '4000047172',
        '4009088988',
        '057188170185',
        '4000009810',
        '057156532988',
        '4009216988',
        '02131338933',
        '057187755371',
        '4008780213',
        '4006668980',
        '02168453591',
        '02160504116',
        '02180322650',
        '4006864366',
        '4008128018',
        '01056768327',
        '02161611122',
        '4006285159',
        '4000858383',
        '4006465858',
        '4008905360',
        '01050868752',
        '01050868753',
        '4000990707',
        '01053766988',
        '01057203304',
        '01056592059',
        '4008558730',
        '4000990707',
        '01051615907',
        '02035437423',
        '02035437423 ',
        '02035437465',
        '02061009459',
        '075561801410',
        '4009305558',
        '4008108866',
        '4008705151',
        '4008705151 ',
        '01056595430 ',
        '01059842318',
        '01059842339',
        '01059842515',
        ' 4000906600',
        '01053232880',
        '4000906600',
        '4000906600 ',
        '4009105151',
        '057156583188',
        '4006558858',
        '4007800087',
        '4001862115',
        '02131658143',
        '4000060918',
        '4009676735',
        '07715710789',
        '02158368808',
        '010100360',
        '02968214444',
        '10100360',
        '037155553132',
        '075526224585',
        '01089178240',
        '4000701702',
        '4009198555',
        '02161518288',
        '4001177777',
        '05352931816',
        '01053392382',
        '01064101188',
        '02028690164',
        '02138510125',
        '02138510170',
        '02138549460',
        '02151349382',
        '02160162933',
        '02160162941',
        '02160421389',
        '02160504766',
        '02161291608',
        '02168664630',
        '02180322349',
        '02180349150',
        '02180349150 ',
        '02583699155',
        '02750164341',
        '02759420826',
        '02759420868',
        '02759704759',
        '02781615555',
        '02883322655',
        '02988187023',
        '031185271318',
        '031185276755',
        '031187163700',
        '051286893214',
        '051288163245',
        '051368636849 ',
        '055165581097',
        '073188040150',
        '073189581574',
        '075532980083',
        '075532980111',
        '075533024314',
        '075533041398',
        '075533529400',
        '075533540359',
        '075536907016',
        '075561807045',
        '075561821829',
        '075566894749',
        '07712359870',
        '07745660946',
        '079182260974',
        '4000960066',
        '075582900700',
        '4006862852',
        '02126125430 ',
        '4000268888',
        '01056340403',
        '02787631097',
        '4000268888 ',
        '031187163700',
        '02180322727',
        '01080697298',
        '075583253889',
        '031185271571',
        '057187755371',
        '4008780213',
        '01053560788',
        '01056332088',
        '01080697298',
        '02166608011',
        '4008133233',
        '02062334469',
        '01080697298',
        '125909888123',
        '4006083030',
        '01059647068',
        '02150688888',
        '4007285588',
        '02160128634',
        '02161910851',
        '02161910857',
        '02163621818',
        '02151088222',
        '076989974312',
        '4009930013',
        '4007280655',
        '4006862671',
        '4006027318',
        '4000023321',
        '059163078227',
        '075533075452',
        '075561827797',
        '075536948178',
        '4008840088',
        '02131056156',
        '4006363636',
        '01084170786',
        '02089166181',
        '057126201150',
        '02180349316',
        '02752101870',
        '4006061160',
        '4008276999',
        '01053511168',
        '051286182931',
        '02160467180 ',
        '02160467182',
        '4000105577',
        '4000168866',
        '01056372888',
        '4008365365转9',
        '4008365365',
        '4006812016',
        '4006050050',
        '4008330226',
        '4009208285',
        '4008030278',
        '4000887626',
        '02886782392',
        '021349183833012',
        '075561808666 ',
        '075566863580',
        '075566863581 ',
        '4006988018',
        '02882418753',
        '075586647595',
        '02595522',
        '01057022558',
        '01057180805',
        '40007910888',
        '075583485999',
        '400694006991',
        '4000596083',
        '01062215518',
        '02968877889',
        '4006269431',
        '089832872081',
        '02138996300',
        '057188160101',
        '0571967037',
        '07715552029',
        '4009909698',
        '057189986310',
        '4006082233',
        '075584354334',
        '075584354345',
        '01052260475',
        '057982097300',
        '15336923730',
        '4007973066',
        '076927222043',
        '4006410888',
        '05942273189',
        '059529016155',
        '4007571310',
        '4007571310',
        '075586958849',
        '4009628688',
        '020814922228738',
        '4001855580',
        '073189581570',
        '4008571577',
        '4006673500',
        '4008821802',
        '01083036567',
        '073189574101',
        '057983225173',
        '05925193169',
        '4008909888',
        '01056709988',
        '01083056163',
        '057126201179',
        '057126206163',
        '4009218887',
        '4000288888',
        '057126206529',
        '057126206666',
        '02126053340',
        '4007901901',
        '02180177603',
        '075589468888',
        '4009998800',
        '057186898896',
        '4009998877',
        '4006789888',
        '4000523528',
        '02131837069',
        '02131837077',
        '02131837079',
        '02131837092',
        '02131837099',
        '02131837122',
        '051285552017',
        '4006773581',
        '057789999771',
        '057788532792',
        '02787341925',
        '02787632845',
        '075532983321',
        '075532983323',
        '4006003323',
        '400600332351',
        '4009008880',
        '02788238432',
        '01051921360',
        '03155780601',
        '4008230011',
        '4000105800',
        '10532998992440',
        '01081539188',
        '01082660237',
        '95167859',
        '95167860',
        '01081539188',
        '95167850',
        '01010101058',
        '02110101058',
        '02155366006',
        '10101058',
        '10101058 ',
        '02566683817',
        '057128234676',
        '4006812016',
        '13074654120',
        '4000360808',
        '4000360808 ',
        '02180260897',
        '02156350010',
        '051183990166。',
        '051185901089',
        '4009978780',
        '01052284855',
        '4006465151',
        '075588694773',
        '01062702270',
        '02180322727',
        '075586561841',
        '02131005555',
        '075584370009',
        '4001005678',
        '4007995111',
        '4007771268',
        '4006210810',
        '057126890019',
        '4008695568',
        '01053856435',
        '4000100788',
        '4000100788',
        '02155609977',
        '4000188299',
        '4007054006',
        '4000385888',
        '053283082252',
        '4006197007',
        '02888615000',
        '02782773471',
        '02784225075',
        '4006003323',
        '057181968279',
        '4008218616',
        '4009699559',
        '02160325999 ',
        '02881475978',
        '02131006060',
        '073189799366',
        '4000901199',
        '4000150808',
        '055166014561',
        '055166014564',
        '055166014566',
        '055165709688',
        '059588766978',
        '059188703081',
        '059188703081',
        '075522915580',
        '4007779888',
        '4008826960',
        '08515830950',
        '4000805356',
        '075584391000',
        '4006051088',
        '01084417208',
        '4000020061',
        '4009675600',
        '4006379850',
        '4006379850 ',
        '4001095561',
        '02138130005',
        '02138130005 ',
        '02120309000',
        '400-601-0002',
        '4001023608',
        '4008555400',
        '4006265576',
        '4009698982',
        '4000070077',
        '02160525916',
        '075588292044',
        '01095510',
        '4006777615',
        '01082911070',
        '4008866456',
        '073188923029',
        '4009008008',
        '075523674599',
        '075583196888',
        '4008002999',
        '079183818539',
        '4008183737',
        '01059535200',
        '01059535230',
        '01059535231',
        '01059535260',
        '01059535261',
        '01059535262',
        '4000609191',
        '4006099400',
        '01057382000',
        '01059647022',
        '4008112288',
        '01059541429',
        '95183',
        '059522001700',
        '4006099600',
        '95183',
        '057157139470',
        '057157139474',
        '4000215599',
        '4009987860',
        '02138562000',
        '02987237676',
        '4000671571',
        '057189980653',
        '4006688090',
        '4006688090',
        '01052834510',
        '4000645156"',
        '01068888586',
        '059522222330',
        '4008155609',
        '02120742345',
        '073182563145',
        '01010100011',
        '073183071706',
        '01068706981',
        '4000805055',
        '05583808851',
        '4008011888',
        '4008886633',
        '1050828500',
        '4006916196',
        '4008333222',
        '02161515059',
        '4008889858',
        '02131825831',
        '4006910006',
        '02083521415',
        '02180203636',
        '01053708282',
        '01057154087',
        '01059797537',
        '01059797585',
        '02151318427',
        '02151318428',
        '02388253288',
        '10108818',
        '4006016611',
        '4000810707',
        '10101088',
        '02868594555',
        '02868594555',
        '4001508990',
        '01051395580',
        '4008895580',
        '02180217084',
        '031186779171',
        '057989185173',
        '4000826218',
        '4001640016',
        '4009955855',
        '075500000000',
        '02195075134',
        '01080453731',
        '01080453732',
        '01080453733',
        '01080453734',
        '01080453735',
        '01080453736',
        '01080453737',
        '01080453738',
        '01080453739',
        '01080453740',
        '01080453741',
        '01080453742',
        '01080453743',
        '01080453744',
        '01080453745',
        '01080453746',
        '01080453747',
        '01080453748',
        '01080453749',
        '01080453629',
        '01061843234',
        '01061843237',
        '051355089886',
        '4009936111',
        '02131830037',
        '075536357660',
        '4006662017',
        '4006662017  ',
        '01080453605',
        '01080453733',
        '05332329511',
        '02160370794',
        '4008005518',
        '02160370788',
        '4008788195',
        '4006970707',
        '02180260891',
        '010100360 ',
        '01084359010',
        '4006599696',
        '4009689686',
        '4009662011',
        '01053958731',
        '02765024977',
        '05372299881',
        '4000012222',
        '4008555056',
        '085182238700',
        '02160151670',
        '02160151670',
        '01057041761',
        '01057041761 ',
        '4006587788',
        '057181605888',
        '051080629985',
        '057186756600',
        '4006118602',
        '075523729610',
        '4000897869',
        '4008636086',
        '075528765851 ',
        '075588242103',
        '075528680101',
        '075528765856',
        '075528765858',
        '075584680888',
        '075588242000',
        '075588242001',
        '075588242002',
        '075588242003',
        '075588242004',
        '075588242005',
        '075588242007',
        '075588242008',
        '075588242009',
        '075588242010',
        '075588242011',
        '075588242012',
        '075588242013',
        '075588242014',
        '075588242015',
        '075588242016',
        '075588242017',
        '075588242018',
        '075588242019',
        '075588242020',
        '075588242021',
        '075588242022',
        '075588242023',
        '075588242024',
        '075588242025',
        '075588242026',
        '075588242027',
        '075588242028',
        '075588242029',
        '075588242030',
        '075588242031',
        '075588242032',
        '075588242033',
        '075588242034',
        '075588242035',
        '075588242036',
        '075588242037',
        '075588242038',
        '075588242039',
        '075588242040',
        '075588242041',
        '075588242049',
        '075588242101',
        '075588242103',
        '075588242108',
        '075588242110',
        '075588242111',
        '075588242113 ',
        '075588242144',
        '075588242152',
        '075588242171',
        '4006888188',
        '4001122015',
        '01082970088',
        '01063509651',
        '4000696600',
        '01059840469',
        '01010101600',
        '01010101600 ',
        '059528685807',
        '4009160160',
        '075536907016',
        '051066880832',
        '4006565655',
        '4000333878',
        '75525631961',
        '4006117911',
        '02028178807',
        '02038619855',
        '075788334435',
        '4000303007',
        '4006835151',
        '057188173258',
        '4009003168',
        '4008083168',
        '4000090000',
        '010-85910855',
        '4001001111',
        '01065859789',
        '4000082828',
        '4000482300'
    ];

    /**
     * @name 通讯录敏感词
     */
    public static $name_arr_list = [
        '赌','毒','借','贷','分期','提现','套现','金融','小额','小贷'
    ];

    /**
     *民族关键词
     */
    public static $nation_danger_list = [
        '藏','彝','维'
    ];

    /**
     * 高风险户籍
     */
    public static $id_card_name = [
        '新疆','内蒙','西藏','宁夏','青海'
    ];

    /**
     * @name 高危行业
     */
    public  $danger_hangye_list = [
        '金融',
        '担保',
        '融资',
        '理财',
        '财富',
        '资产管理',
        '小额贷款',
        '小贷',
        '保理',
        '期货',
        '现货',
        '公安局',
        '检察院',
        '法院',
        '纪律检查',
        '纪检',
        '司法局',
        '人民银行',
        '银行业监督',
        '证券业监督',
        '保险业监督',
        '工商',
        '律师',
        '电视台',
        '报刊',
        '日报',
        '晚报',
        '都市报',
        '商报',
        '记者',
        '押运',
        '派出所'
    ];

    /**
     * 高风险户籍区域(新疆=65、西藏=54)
     **/
    public static $id_card_area = [
        65,54
    ];
}
