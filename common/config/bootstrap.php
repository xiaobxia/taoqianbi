<?php
defined('APP_DOWNLOAD_URL') or define('APP_DOWNLOAD_URL','http://www.jisuhh.cn/tqb.apk_1.0.1.apk');
defined('APP_IOS_DOWNLOAD_URL') or define('APP_IOS_DOWNLOAD_URL','https://fir.im/7l4v');

defined('SITE_TEL') or define('SITE_TEL', '13285711611');

//报警手机号
defined('NOTICE_MOBILE') or define('NOTICE_MOBILE', '13285711611');//主要的
defined('NOTICE_MOBILE2') or define('NOTICE_MOBILE2', '17682449388');//异常报警

//报警邮箱
defined('NOTICE_MAIL') or define('NOTICE_MAIL', '1771372000@qq.com');

defined('QQ_SERVICE') or define('QQ_SERVICE', '1771372000');

defined('API_PAYURL') or define('API_PAYURL', 'pay.jisuhh.cn:15422');//网关支付 端口 pay.jisuhh.cn:15422 47.110.250.145
defined('WEIXIN_APPID') or define('WEIXIN_APPID', 'wx8407c2f12362fc2c');//微信公众号id
defined('WEIXIN_SECRET') or define('WEIXIN_SECRET', '12070845213cff02fccd4f6617f1ab05');//微信公众号key
defined('WEIXIN_Token') or define('WEIXIN_Token', 'weixin_jshh_online');//微信公众号token

defined('ALIPAY_ACCOUNT') or define('ALIPAY_ACCOUNT', '13285711611');
defined('ALIPAY_NAME') or define('ALIPAY_NAME', '长沙顺网互联网科技有限公司');//浙江省杭州市西湖区文二西路669号407室（入驻创富港商务秘书托管013号）

define('WEIXIN_GONGZHONGNHAO', '淘钱币');
define('WEIXIN_GONGZHONGNHAO_SHORENAME', 'XHBGZH');//微信公众号英文名称
defined('APP_NAMES') or define('APP_NAMES', '淘钱币');

defined('COMPANY_NAME') or define('COMPANY_NAME', '长沙顺网互联网科技有限公司');
defined('COMPANY_ADDRESS') or define('COMPANY_ADDRESS', '长沙经济技术开发区板仓>南路29号新长海中心服务外包基地3栋A座501');
defined('COMPANY_AREA') or define('COMPANY_AREA', '长沙市');

defined('SITE_EMAIL') or define('SITE_EMAIL', 'service@jisuhh.com');
defined('SITE_ICP') or define('SITE_ICP', '湘ICP备18016418号');
defined('POLICE_ICP_CODE') or define('POLICE_ICP_CODE', '31011002000164');//公安机关备案号http://www.beian.gov.cn/ 申请
defined('POLICE_ICP') or define('POLICE_ICP', '浙公网安备 31011002000164号');//公安机关备案号

defined('SITE_DOMAIN') or define('SITE_DOMAIN', 'www.jisuhh.cn');//配置域名
defined('SHORT_DOWNLOAD_URL') or define('SHORT_DOWNLOAD_URL','http://'.SITE_DOMAIN.'/newh5/web/page/sdhsreg');

if(isset($_SERVER['HTTP_HOST'])){
    $url = explode('.', $_SERVER['HTTP_HOST']);
    unset($url[0]);
    defined('APP_DOMAIN') or define('APP_DOMAIN', '.' . implode('.', $url));
}else{
    defined('APP_DOMAIN') or define('APP_DOMAIN', '.jisuhh.cn');
}

defined('OSS_RES_DOMAIN') or define('OSS_RES_DOMAIN', 'jshh.oss-cn-beijing.aliyuncs.com');
defined('OSS_RES_PROTOCOL') or define('OSS_RES_PROTOCOL', 'http');
defined('OSS_RES_URL') or define('OSS_RES_URL', OSS_RES_PROTOCOL.'://'.OSS_RES_DOMAIN.'/');
defined('OSS_RES_URL_OUTSIDE') or define('OSS_RES_URL_OUTSIDE', OSS_RES_PROTOCOL.'://jshh.oss-cn-beijing.aliyuncs.com/');//外网地址
defined('OSS_HOST') or define('OSS_HOST', 'oss-cn-beijing.aliyuncs.com');

defined('APP_IP_ADDRESS') or define('APP_IP_ADDRESS', isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : "");


defined('ZHANQI_LOAN_LV') or define('ZHANQI_LOAN_LV', 0.25);//展期还款汇率
defined('HELIPAYTIPS') or define('HELIPAYTIPS', '请输入合利宝验证码');

Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');
Yii::setAlias('@credit', dirname(dirname(__DIR__)) . '/credit');

Yii::setAlias('@creditUrl', '//qbcredit.wzdai.com');
Yii::setAlias('@mobileUrl', '//qbm.wzdai.com');

/**
 * 设置别名，即可以通过Yii::$container->get('userService')的方式获得对应的service对象
 * 当然也可以通过构造函数注入到成员变量中
 */
Yii::$container->set('userService', 'common\services\UserService');
Yii::$container->set('accountService', 'common\services\AccountService');
Yii::$container->set('userContactService', 'common\services\UserContactService');
Yii::$container->set('payService', 'common\services\PayService');
Yii::$container->set('llPayService', 'common\services\LLPayService');
Yii::$container->set('buildingService', 'common\services\BuildingService');
Yii::$container->set('zmopService', 'common\services\ZmopService');
Yii::$container->set('fkbService', 'common\services\FkbService');
Yii::$container->set('loanService', 'common\services\LoanService');
Yii::$container->set('loanPersonBadInfoService', 'common\services\LoanPersonBadInfoService');
Yii::$container->set('haoDaiService', 'common\services\HaoDaiService');
Yii::$container->set('financialCommonService', 'common\services\FinancialCommonService');
Yii::$container->set('financialService', 'common\services\FinancialService');
Yii::$container->set('yeePayService', 'common\services\YeePayService');
Yii::$container->set('orderService', 'common\services\OrderService');
Yii::$container->set('autoDebitService', 'common\services\AutoDebitService');
Yii::$container->set('InviteRebatesService', 'common\services\InviteRebatesService');
Yii::$container->set('installmentShopService', 'common\services\InstallmentShopService');
Yii::$container->set('contractService', 'common\services\ContractService');
Yii::$container->set('huluService', 'common\services\HuluService');
Yii::$container->set('loanPersonInfoService', 'common\services\LoanPersonInfoService');
Yii::$container->set('loanCollectionService', 'common\services\LoanCollectionService');
Yii::$container->set('repaymentService', 'common\services\RepaymentService');
Yii::$container->set('loanBlackListService', 'common\services\LoanBlackListService');
Yii::$container->set('assetService', 'common\services\AssetService');
Yii::$container->set('assetNotifyService', 'common\services\AssetNotifyService');
Yii::$container->set('hdService', 'common\services\HdService');
Yii::$container->set('yxzcService', 'common\services\YxzcService');
Yii::$container->set('zzcService', 'common\services\ZzcService');
Yii::$container->set('creditCheckService', 'common\services\CreditCheckService');
Yii::$container->set('ydService', 'common\services\YdService');
Yii::$container->set('bqsService', 'common\services\BqsService');
Yii::$container->set('shzxService', 'common\services\ShzxService');
Yii::$container->set('shService', 'common\services\ShService');
Yii::$container->set('hfdService', 'common\services\HfdService');
Yii::$container->set('creditTotalService', 'common\services\CreditTotalService');
Yii::$container->set('creditService', 'common\services\CreditService');
Yii::$container->set('zsService', 'common\services\ZsService');
Yii::$container->set('jyService', 'common\services\JyService');
Yii::$container->set('cardService', 'common\services\CardService');
Yii::$container->set('monitorService', 'common\services\MonitorService');//监控服务
Yii::$container->set('fundService', 'common\services\FundService');
Yii::$container->set('wealidaService', 'common\services\WealidaService');
Yii::$container->set('brService', 'common\services\BrService');
Yii::$container->set('lzfService', 'common\services\LzfService');//灵芝分
Yii::$container->set('iceKreditService', 'common\services\IceKreditService');//冰鉴
Yii::$container->set('moxieService', 'common\services\MoxieService');//冰鉴
Yii::$container->set('jpushService', 'common\services\JPushService');//极光推送
Yii::$container->set('SensitiveCensorService', 'common\services\SensitiveCensorService');//敏感词检测
Yii::$container->set('jsqbService', 'common\services\JsqbService');//极速钱包征信服务
Yii::$container->set('rongService', 'common\services\InterfaceRongService');//融360服务
Yii::$container->set('JshbService', 'common\services\fundChannel\JshbService');//极速荷包服务
