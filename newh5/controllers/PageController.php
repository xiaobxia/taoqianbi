<?php
namespace newh5\controllers;

use common\helpers\ToolsUtil;
use common\models\ChannelGeneralCount;
use frontend\controllers\XqbUserController;
use Yii;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\helpers\Html;
use yii\base\UserException;
use yii\filters\AccessControl;
use yii\captcha\CaptchaValidator;

use common\api\RedisQueue;
use common\helpers\Util;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\VisitStat;
use common\models\Channel;
use common\services\GuangDianTong;
use common\helpers\MessageHelper;
use common\exceptions\UserExceptionExt;


class PageController extends BaseController {
    public $layout = 'channel';

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,
                // 仅下面的action需要登录
                'only' => ['data-stat'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actions() {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'testLimit' => 1,
                'height' => 35,
                'width' => 80,
                'padding' => 0,
                'minLength' => 4,
                'maxLength' => 4,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ]
        ];
    }

    /**
     * 线上测试地址 针对uid过滤
     */
    public function actionTest() {
        if (\yii::$app->user->id != 1) {
            throw new NotFoundHttpException('not found');
        }

        $server_vars = [
            'HTTP_CLIENT_IP',
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];
        $ret = [];
        foreach($server_vars as $_var) {
            $ret[ $_var ] = $_SERVER[ $_var ] ?? '';
        }
        VarDumper::dump( $ret );
    }

    // 下载页
    public function actionDownloadApp() {
        return $this->render('download-app');
    }

    //落地页集合
    public function actionLandingPage(){
        $id = intval($this->request->get('id',0)); // 设置默认标题和h5内容
        $view_arr = ['landing-page1'];
        $view_title_arr = [''];
        $retdata = [];
        $view = isset($view_arr[$id]) ? $view_arr[$id] : $view_arr[0];
        $this->view->title = isset($view_title_arr[$id]) ? $view_title_arr[$id] : $view_title_arr[0];
        return $this->render($view,$retdata);
    }

    //手机H5活动页集合 极速荷包
    public function actionActivityPage(){
        $id = $this->request->get('id',''); //1是测试的
        if($id != 'test'){//测试的请求
            $id = intval($id);
        }
        $view_new = 'activity';
        if($id == 0){
            $view_end = 'activity';
        }else{
            $view_end = 'activity'.'-'.$id;
        }
        if($id === 'test' && YII_ENV_DEV){
            $view_end = 'activity-test';
        }
        //判断是否是在手机上使用 且需要登录的banner
        //$clientType = \yii::$app->request->getClient()->clientType; //判断登录的型号
        $data = []; //现有返回值 $data  url
        $data['js'] = 'xybt://com.xybaitiao/openapp';//短信唤醒地址
        if($this->isFromXYBT()){//判断是否来自极速荷包
            $data['js'] = '';
            $curUser = Yii::$app->user->identity;
            $data['jump_js'] = '{"type":"4"}';
            if(isset($curUser)){
                $user_id = $curUser->getId();
                $order = UserLoanOrder::find()->where(['user_id'=>$user_id])->orderBy('id desc')->one();//查询是否有借款信息
                if(!empty($order)){
                    $repayment = UserLoanOrderRepayment::find()->where(['order_id'=>$order->id])->asArray()->one();
                    $status_array = [
                        UserLoanOrderRepayment::STATUS_NORAML,//生息
                        UserLoanOrderRepayment::STATUS_REPAY_COMPLEING,//待扣款
                        UserLoanOrderRepayment::STATUS_WAIT];//扣款中
                    if($order && in_array($repayment['status'],$status_array)){
                        $data['jump_js'] = '{"type":"13"}';
                    }
                }
            }else{
                //跳转到登录
                $data['js'] = 'koudaikj://app.launch/login/applogin';
            }
        }
        try{
            return $this->render($view_end,[
                'data'=>$data,
            ]);//加载对应的页面
        } catch (\Exception $e) {
            return $this->render($view_new,[
                'data'=>$data,
            ]);//记载默认的页面
        }
    }

    //短信发送限制
    public function actionCheckCodeStatus() {
        $this->getResponse()->format = Response::FORMAT_JSON;
        $phone = trim($this->request->post('phone'));
        if (!Util::verifyPhone($phone)) {
            return [
                'code' => -1,
                'message' => '请输入正确的手机号码',
            ];
        }

        $ip = \Yii::$app->getRequest()->getUserIP();
        $key = md5('reg-' . $phone . '-' . ip2long($ip) . '-' . date('m.d'));
        $rules = intval(RedisQueue::get(['key'=>$key]));
        if( $rules > 5 ) {
            $code = trim($this->request->post('code'));

            $captcha = \Yii::createObject([
                'class' => CaptchaValidator::class,
                'captchaAction' => 'page/captcha'
            ]);
            if( ! $code ) {
                return [
                    'code' => 102,
                    'message' => '请输入图片验证码',
                ];
            }
            if( !$captcha->validate($code) ) {
                return [
                    'code' => 101,
                    'message' => '请输入正确的图片验证码',
                ];
            }
        } else {
            ++ $rules;
            RedisQueue::set(['expire'=>3600*(24-date('d')), 'key'=>$key, 'value'=>$rules]);
        }

        return [
            'code' => 0,
            'message' => 'succ',
        ];
    }
    /**
     * 极速荷包下载地址
     */
    public function actionDownloadXybtLoan() {
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Mac  = stripos($_SERVER['HTTP_USER_AGENT'],"Mac");
        //$Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");
        $link = APP_DOWNLOAD_URL;
        if ( $iPod || $iPhone || $iPad || $Mac) {
            $link = APP_IOS_DOWNLOAD_URL;//使用闪电荷包福利版包 guoxiaoyong 2017-07-29
        }

        header("Location: {$link}");
    }

    /**
     * 极速荷包下载地址
     */
    public function actionDownloadXybtLoanUrl() {
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Mac  = stripos($_SERVER['HTTP_USER_AGENT'],"Mac");
        //$Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");
        $link = 'http://qbres.wzdai.com/apk/xybt-latest.apk';
        if ( $iPod || $iPhone || $iPad || $Mac) {
            $link = 'https://itunes.apple.com/app/id1221186366?mt=8';//使用极速荷包福利版包 guoxiaoyong 2017-07-29
        }

        header("Location: {$link}");
    }

    /**
     * app延迟下载连接
     */
    public function actionDelayDownloadApp() {
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Mac  = stripos($_SERVER['HTTP_USER_AGENT'],"Mac");
        //$Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");
        $link = 'http://qbres.wzdai.com/apk/xybt-latest.apk';
        if ( $iPod || $iPhone || $iPad || $Mac) {
            $link = 'http://itunes.apple.com/app/id1235438496?mt=8';//使用极速荷包福利版包 guoxiaoyong 2017-07-29
        }
        return $this->render('delay-download-app', [
            'url' => $link,
        ]);
    }


    /**
     * 温州贷贷款端app下载连接
     */
    public function actionDownloadWzdLoan() {
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");

        $link = 'http://qbres.wzdai.com/wzdai_apk/wzdai_loan-latest.apk';
        if ( $iPod || $iPhone || $iPad ) {
            $link = 'http://itunes.apple.com/app/id1239756949';
        }

        header("Location: {$link}");
    }

    /**
     * 温州贷-公积金版app下载连接
     */
    public function actionDownloadWzdFundLoan() {
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");

        $link = 'http://qbres.wzdai.com/xybt_fund_apk/xybt_fund-latest.apk';
        if ( $iPod || $iPhone || $iPad ) {
            $link = 'http://itunes.apple.com/app/id1248726833?mt=8';
        }

        header("Location: {$link}");
    }

    /**
     * 随心贷贷贷款端app下载连接
     */
    public function actionDownloadSxdLoan() {
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");

        $link = 'http://qbres.wzdai.com/sxdai_apk/sxdai-latest.apk';
        if ( $iPod || $iPhone || $iPad ) {
            $link = 'http://itunes.apple.com/app/id1251292028?mt=8';
        }

        header("Location: {$link}");
    }
    /**
     * 现金白条app下载地址
     */
    public function actionDownloadXjbt(){
        $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $link = APP_DOWNLOAD_URL;
        if ( $iPod || $iPhone || $iPad ) {
            $link = APP_IOS_DOWNLOAD_URL;
        }
        header("Location: {$link}");
    }
    /**
     * 注册落地页（白条）
     * @return string
     */
    public function actionAppReg() {
        $this->_viewSource();
        $key = $this->_codeSmsKey(); // 注册验证码防刷key

        $jrtt_patterns = [
            'jrtt_xinmiao_' => 64868555375,//注册成功的转化
            'jrtt_xxxinmiao' => 69872860743,//注册成功下载APP的转化
        ];
        $source_tag = Yii::$app->request->get('source_tag','');
        $js = '';
        if(!empty($source_tag)){
        foreach($jrtt_patterns as $pattern => $id) {
                if (strpos($source_tag, $pattern) === 0) { //今日头条JS
                    $show = 0;
                    if($pattern == 'jrtt_xxxinmiao'){
                        $show = 1;//注册转换的位置
                    }
                    $convert_id = $jrtt_patterns[ $pattern ];
                    $js = "<script type='text/javascript'>
(function (win) {
win._tt_config = true;
win._tt_convert_id = {$convert_id};
win._tt_show_type = {$show};
var ta = document.createElement('script'); ta.type = 'text/javascript'; ta.async = true;
ta.src = document.location.protocol + '//' + 's3.pstatp.com/bytecom/resource/track_log/src/toutiao-track-log.js';
ta.onerror = function () {
    var request = new XMLHttpRequest();
    var web_url = window.encodeURIComponent(window.location.href);
    var js_url = ta.src;
    var url = '//ad.toutiao.com/link_monitor/cdn_failed?web_url=' + web_url + '&js_url=' + js_url + '&convert_id={$convert_id}';
    request.open('GET', url, true);
    request.send(null);
}
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ta, s);
})(window);
</script>";
                }
            }
        }
        //广点通的数据推送
        $gdt_patterns = 'gdt_yima';
        $gdt = '';//是否上传广点通的数据
        $url = Url::toRoute(['page/send-gdt']);//ajax 请求的地址
        $baseUrl = $this->request->getHostInfo().$this->request->getUrl();
        $qz_gdt = Yii::$app->request->get('qz_gdt','');
        if(strstr($source_tag,$gdt_patterns)){
            $gdt = "<script type='text/javascript'>
var gdt_url = '$url';
var qz_gdt = '$qz_gdt';
var post_url = '$baseUrl';
</script>";
        }
        $view = $this->_change_view($source_tag);
        return $this->render($view, [
            'reg_sms_key' => $key,
            'page_js' => $js,
            'gdt'=>$gdt,
        ]);
    }


	/**
	 * 急速荷包落地页（急速荷包）
	 * @return string
	 */
	public function actionJshbreg() {
	    $url="http://".SITE_DOMAIN."/newh5/web/page/sdhsreg";
	    if(isset($_GET['source_tag'])){
	        $url.="?source_tag=".trim($_GET['source_tag']);
        }
        header('Location:'.$url);
        die();
		$this->_viewSource();
		$key = $this->_codeSmsKey(); // 注册验证码防刷key
		return $this->render('jshb-reg', [
			'reg_sms_key' => $key,
		]);
	}

    /**
     * 闪电回收落地页（闪电回收）
     * @return string
     */
    public function actionSdhsreg() {
        $this->_viewSource();
        $key = $this->_codeSmsKey(); // 注册验证码防刷key
        return $this->render('jshb-reg', [
            'reg_sms_key' => $key,
        ]);
    }

    /**
     * 信合宝落地页（信合宝）
     * @return string
     */
    public function actionSdzreg() {
        $this->_viewSource();
        $key = $this->_codeSmsKey(); // 注册验证码防刷key
        return $this->render('jshb-reg', [
            'reg_sms_key' => $key,
        ]);
    }

	 /* JSHB短信推广2 */
	 public function actionJshbSmsTwo(){
		 return $this->render(
		 'jshb-sms-two',[]
		 );
	 }

    /**
     * 短信唤醒APP
     */
    public function actionWeakApp(){
        $headers=Yii::$app->response->headers;
        $headers->add("Access-Control-Allow-Origin","*");
        $headers->add("Access-Control-Allow-Methods","GET,POST");
        $url = 'xybt://com.xybaitiao/openapp';
        return $this->render('weak-app',[
            'url'=>$url,
        ]);
    }

    /**
     *  5元优惠券下载落地页
     */
    public function actionFreeCoupons(){
        return $this->render('free-coupons',[
        ]);
    }

    public function actionCherishtrees(){
        return $this->render('cherishtrees',[
        ]);
    }

    /**
     *  JSHB 短信推广
     */
    public function actionJshbSmsOne(){
        return $this->render('jshb-sms-one',[
        ]);
    }
    /**
     * JSHB 落地页 Ios操作下载页
     */
    public function actionIosTips(){
        //统计注册下载ios APP
        VisitStat::getDb()->createCommand()->insert('tb_visit_stat', [
            'ip' => ToolsUtil::getIp(),
            'source_tag' => 'downapp',
            'created_at' => time(),
            'source_url' => 'ios',
            'current_url' => 'success',
            'remark' => '',
        ])->execute();
        return $this->render('ios-tips',[
        ]);
    }
    /**
     * 落地页上报
     * @return array
     */
    public function actionVisitStat(){
        $this->getResponse()->format = Response::FORMAT_JSON;
        $source_url = Html::encode($this->request->post('source_url'));
        $current_url = Html::encode($this->request->post('current_url'));
        $source_tag = Html::encode($this->request->post('source_tag'));
        $remark = Html::encode($this->request->post('remark'));
        if ( !$current_url ) {
            return [
                'code' => -1,
                'message' => sprintf('上报失败(%s)', __LINE__),
            ];
        }

        $result = VisitStat::getDb()->createCommand()->insert('tb_visit_stat', [
            'ip' => ToolsUtil::getIp(),
            'source_tag' => $source_tag,
            'created_at' => time(),
            'source_url' => $source_url,
            'current_url' => $current_url,
            'remark' => $remark,
        ])->execute();
        if (!$result) {
            return [
                'code' => -1,
                'message' => sprintf('上报失败(%s)', __LINE__),
            ];
        }

        return [
            'code' => 0,
            'message' => '上报成功',
        ];
    }

    public function actionDataStat() {
        //myron，大叔
        $white_list = ['13651979025'];
        if( !in_array($this->view->userName, $white_list) ){
            throw new UserException('Non white list user');
        }
        $this->layout = 'pc-main';
        $condition = '1 = 1';
        //默认今天
        $_date_satrt = date('Y-m-d 00:00:00');
        $_date_end = date('Y-m-d 23:59:59');
        $date_satrt = strtotime($_date_satrt);
        $date_end = strtotime($_date_end);
        $condition .= " AND created_at BETWEEN {$date_satrt} AND {$date_end}";
        // PV 统计
        $pv_count = VisitStat::find()->select('count(1)')->where($condition)->count();
        $pv_data = VisitStat::find()->select(['current_url','count(current_url) pv'])->where($condition)->groupBy('current_url')->orderBy('pv desc')->asArray()->all();
        // UV 统计
        $uv_count = VisitStat::find()->select('count(1)')->where($condition)->groupBy('ip')->count();
        $uv_query = VisitStat::find()->select(['current_url','count(ip) ip_count'])->where($condition)->groupBy('current_url,ip')->orderBy('current_url desc')->asArray()->all();
        $uv_data = [];
        foreach ($uv_query as $v => $uv_val) {
            $url_str = $uv_val['current_url'];
            if(isset($uv_data[$url_str])){
                $uv_data[$url_str] += 1;
            }else{
                $uv_data[$url_str] = 1;
            }
        }
        return $this->render('data-stat', [
            'date_tips' => "{$_date_satrt}~{$_date_end}",
            'pv_count' => $pv_count,
            'uv_count' => $uv_count,
            'pv' => $pv_data,
            'uv' => $uv_data
        ]);
    }

    /*--------------- 私有方法 ---------------------------------------------------------*/

    // 生成注册验证码防刷key
    private function _codeSmsKey() {
        $key = \common\components\Session::getSmsKey();
        \yii::$app->session->set('reg_sms_key', $key);
        return $key;
    }

    private function _viewSource($default_source = LoanPerson::APPMARKET_XYBT,$default_source_tag = '')
    {
        $source_tag = $default_source_tag ? $default_source_tag : trim($this->request->get('source_tag', 'NoneAppMarket'));
        $source_app = $default_source; // 默认
        if($source_tag && $source_tag != 'NoneAppMarket'){
            $channel_source = Channel::findOne(['appMarket' => $source_tag,'status' => Channel::STATUS_YES]);
            if($channel_source){
                $source_app = $channel_source->source_str;
            }
        }
        //统计渠道链接点击数
        (new ChannelGeneralCount())->saveClick($source_tag);
        //初始化渠道变量值
        new LoanPerson();
        $this->view->source_id = LoanPerson::$source_app_info[$source_app]['source_id'];
        $this->view->title = LoanPerson::$source_app_info[$source_app]['title'];
        $this->view->keywords = LoanPerson::$source_app_info[$source_app]['keywords'];
        $this->view->icon = LoanPerson::$source_app_info[$source_app]['icon'];
        $this->view->shareLogo = LoanPerson::$source_app_info[$source_app]['share_logo'];
        $this->view->source_app = LoanPerson::$source_app_info[$source_app]['source_app'];
        $this->view->source_tag = $source_tag;
        $this->view->showDownload = intval($this->request->get('show_download', 0)) ? 1 : 0; // 是否展示下载浮框
        return true;
    }
    /**
     * 二级渠道切换样式
     */
    private function _change_view($source_tag = ''){
        $view = Channel::SOURCE_VIEW12;
        $channel_info = Channel::find()
            ->where(['not in','parent','0'])
            ->andWhere(['Not',['themes'=>null]])
            ->andWhere(['status'=>1])
            ->select(['link','themes'])->asArray()->all();
        foreach ($channel_info as $key =>$val){
            $data = explode('=',$val['link']);
            if(isset($data[1]) && $source_tag == $data[1]){
                $view = $val['themes'];
                break;
            }
        }
        //匹配字符串
        unset($channel_info);
        $view_res = '/theme/'.$view.'/stream';
        return $view_res;
    }
    /**
     *二级渠道是否输入密码
     */
    private function _password_view($source_tag = ''){

    }


    /**
     * 广点通推送
     */
    public function actionSendGdt(){
        $this->getResponse()->format = Response::FORMAT_JSON;
        $data = Yii::$app->request->post();
        $click_id = $data['qz_gdt']??'';
        $url = $data['url']??'';
        if(!empty($click_id)){
            $server = new GuangDianTong();
            return $server->sendUserInfo($click_id,$url);
        }
    }

    /**
     * @return string
     */
    public function actionValentineMsg()
    {
        return $this->render('valentine-msg');
    }

    /**
     * @return 短信活动2
     */
    public function actionCherishtreesV2()
    {
        return $this->render('cherishtrees-v2');
    }

    /**
     * @return 短信活动3
     */
    public function actionCherishtreesV3()
    {
        return $this->render('cherishtrees-v3');
    }

    /**
     * @return 教师节短信活动2
     */
    public function actionTeacherDay()
    {
        return $this->render('teacher-day');
    }

    /**
     * @return 中秋节短信活动2
     */
     public function actionMoonDay()
     {
         return $this->render('moon-day');
     }


}
