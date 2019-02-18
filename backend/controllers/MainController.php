<?php
namespace backend\controllers;

use Yii;
use common\models\UserCreditData;
use yii\data\Pagination;
use common\helpers\Url;
use yii\filters\AccessControl;
use yii\helpers\VarDumper;
use yii\web\Response;

use backend\models\LoginForm;
use backend\models\AdminUserRole;
use backend\models\AdminUser;
use backend\models\AdminCaptchaLog;
use backend\models\AdminLoginLog;

use common\models\UserCaptcha;
use common\services\UserService;
use common\api\RedisQueue;

/**
 * Main controller
 */
class MainController extends BaseController
{
    public $verifyPermission = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error', 'captcha', 'phone-captcha'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['index', 'logout', 'home', 'get-list', 'reset-role'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
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
            ],
        ];
    }

    /**
     * 登录
     */
    public function actionLogin(){
        // 已经登录则直接跳首页
        if (!\Yii::$app->user->isGuest) {
            if (LoginForm::channel_redirect(Yii::$app->user->identity->role) == true){
                return $this->redirect('/channel/channel-statistic-detail');
            }
            return $this->goHome();
        }

        $this->layout = true;
        $model = new LoginForm();
        if ($model->load($this->request->post()) && $model->login()) {
            // 把权限信息存到session中
            if ($model->getUser()->role && $roleModel = AdminUserRole::find()->andWhere("name in('" . implode("','", explode(',', $model->getUser()->role)) . "')")->all()) {
                $arr = array();
                foreach ($roleModel as $val) {
                    if ($val->permissions)
                        $arr = array_unique(array_merge($arr, json_decode($val->permissions)));
                }
                Yii::$app->getSession()->set('permissions', json_encode($arr));
            }

            $user = $model->getUser();
            //记录登录日志
            $admin_login_log = new AdminLoginLog();
            $admin_login_log->user_id = $user->id;
            $admin_login_log->ip = $this->request->getUserIP();
            $admin_login_log->username = $user->username;
            $admin_login_log->phone = $user->phone;
            $admin_login_log->save();

            UserCaptcha::deleteAll(['phone' => $model->getUser()->phone, 'type' => UserCaptcha::TYPE_ADMIN_LOGIN]);

            //限制催收登录管理系统，只登录催收系统
            if(in_array($model->getUser()->role,['001','002'])){
                header('http://cs'.APP_DOMAIN);
                die();
            }

            return $this->goHome();
        }

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * 获取登录验证码
     */
    public function actionPhoneCaptcha()
    {
        $this->getResponse()->format = Response::FORMAT_JSON;

        $username = trim($this->request->get('username'));
        if (!$username) {
            return ['code' => -1, 'message' => '用户名不正确，请用手机号登录'];
        }
        if (is_numeric($username)) {
            $user = AdminUser::findByPhone($username);
        } else {
            $user = AdminUser::findByUsername($username);
        }

        $userService = Yii::$container->get('userService');
        if (!$user || !$user->phone) {
            return ['code' => -1, 'message' => '用户名不正确，请用手机号登录'];
        } else if ($userService->generateAndSendCaptcha(trim($user->phone), UserCaptcha::TYPE_ADMIN_LOGIN, false, 21)) {
            //记录发送验证码信息
            try {
                $admin_captcha_log = new AdminCaptchaLog();
                $admin_captcha_log->user_id = 0;
                $admin_captcha_log->username = $username;
                $admin_captcha_log->phone = trim($user->phone);
                $admin_captcha_log->ip = $this->request->getUserIP();
                $admin_captcha_log->type = UserCaptcha::TYPE_ADMIN_LOGIN;
                $admin_captcha_log->save();
            } catch (\Exception $e) {
                var_dump($e);
                exit;
            }
            return ['code' => 0];
        } else {
            return ['code' => -1, 'message' => '发送验证码失败'];
        }
    }

    /**
     * 外层框架首页
     */
    public function actionIndex()
    {
        include_once '../config/menu.php';
        $this->layout = false;

        //当前人员不是超级管理员，隐藏顶部菜单的【数据统计】菜单项
        if (Yii::$app->user->identity->role != 'superadmin') {
//            unset($topmenu['data_analysis_st']);
        }

        $role = Yii::$app->user->identity->role;
        $userGroups = AdminUserRole::groups_of_roles($role);


        //当前人员只是委外机构角色(collection_out, weiwaigaojiquanxian)时，顶部菜单只显示【首页】、【催收管理】：
        $roles = explode(',', str_replace(' ', '', $role));
        $diff = array_diff($roles, array('collection_out', 'weiwaigaojiquanxian'));
        if (empty($diff)) {
            $collection_system = $topmenu['collection_system'];
            $index = $topmenu['index'];
            unset($topmenu);
            $topmenu['index'] = $index;
            $now = time();
            if ($now < strtotime('2017-01-22')) {
                $topmenu['collection_system'] = $collection_system;//1.22号起，关闭催收菜单
            }

            $menu['index'] = array(
                'menu_home' => array('当前任务', Url::toRoute(['main/home'])),
            );
        } else {
            unset($topmenu['collection_system']);
        }

        if (count($userGroups) == 1 && $userGroups[0] == AdminUserRole::TYPE_CHANNEL) {
//            unset($topmenu);
//            unset($menu);
//            $topmenu = $topmenuchannel;
//            $menu = $menuchannel;
        }
        //开始
        $roleModel = AdminUserRole::find()->andWhere("name in('" . implode("','", explode(',', $role)) . "')")->all();
        if ($roleModel) {
            $arr = array();
            foreach ($roleModel as $val) {
                if ($val->menu)
                    $arr = array_unique(array_merge($arr, json_decode($val->menu)));
            }

            Yii::$app->getSession()->set('menu', json_encode($arr));
        }
        $mu = Yii::$app->getSession()->get('menu');
        $menus = json_decode($mu, true);

        if(empty($menus)){
            $topmenu_res=$topmenu;
            $array=$menu;
        }else{
            foreach($menus as $vals){
                $menuname=explode('/', $vals);
                $topmenu_res[$menuname[0]] = $topmenu[$menuname[0]];
            }
            $array['user'] =[];
            foreach($menus as $v){
                $menunames=explode('/', $v);
                foreach($menu as $m_k=>$m_v){
                    if(!empty($menu[$m_k][$menunames[1]])){
                        $array[$m_k][$menunames[1]]= $menu[$m_k][$menunames[1]];
                    }
                }
            }
        }
        //结束
        return $this->render('index', [
            'topMenu' => $topmenu_res,
            'leftMenu' => $array,
        ]);
    }

    /**
     * iframe里面首页
     */
    public function actionHome(){
        if (LoginForm::channel_redirect(Yii::$app->user->identity->role) == true){
            exit('<script>parent.location.href="' . Url::toRoute('/channel/channel-statistic-detail') . '";</script>');
        }
        $redisList = [
            ['key' => RedisQueue::LIST_CREDIT_USER_DETAIL_RECORD, 'name' => '#计算额度  @347', 'actionName' => 'credit-line/set-credit-line'],
            ['key' => UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX, 'name' => '#机审步骤1(收集数据)', 'actionName' => 'credit-check/get-data-source'],
            ['key' => UserCreditData::CREDIT_GET_DATA_SOURCE_DELAY, 'name' => '#收集数据延迟队列', 'actionName' => 'credit-check/order-delay-to-list'],
            ['key' => UserCreditData::CREDIT_GET_DATA_SOURCE_SIMPLE_PREFIX, 'name' => '#机审步骤1(缩水版收集数据)', 'actionName' => 'credit-check/get-data-source-simple'],
            ['key' => UserCreditData::CREDIT_GET_DATA_SOURCE_SIMPLE_DELAY, 'name' => '#收集数据延迟队列(缩水版)', 'actionName' => 'credit-check/order-delay-to-list'],
            ['key' => RedisQueue::LIST_CHECK_ORDER, 'name' => '#机审步骤2（执行决策）', 'actionName' => 'risk-control/auto-check'],
            ['key' => RedisQueue::LIST_USER_MOBILE_MESSAGES_UPLOAD, 'name' => '上报短信', 'actionName' => 'user/down-redis-contents 1'],
            ['key' => RedisQueue::LIST_USER_MOBILE_APPS_UPLOAD, 'name' => '上报app名字', 'actionName' => 'user/down-redis-contents 2'],
            ['key' => RedisQueue::LIST_USER_MOBILE_CONTACTS_UPLOAD, 'name' => '上报通讯录', 'actionName' => 'user/down-redis-contents 3'],
            ['key' => RedisQueue::LIST_ANALYSIS_ORDER, 'name' => '数据分析队列', 'actionName' => 'risk-control/auto-check'],
            ['key' => RedisQueue::LIST_USER_GET_PHONE_CAPTCHA, 'name' => '用户短信队列', 'actionName' => ''],
            ['key' => RedisQueue::LIST_APP_EVENT_MESSAGE, 'name' => '各种事件队列', 'actionName' => 'ygd-check/ygd-zc-cw-check'],
            ['key' => RedisQueue::LIST_USER_LOAN_LOG_MESSAGE, 'name' => '首页放款消息队列', 'actionName' => 'credit-app/index'],
            ['key' => RedisQueue::LIST_HOUSEFUND_TOKEN, 'name' => '公积金采集token队列', 'actionName' => 'house-fund/house-fund-list'],
            ['key' => RedisQueue::LIST_WEIXIN_USER_LOAN_INFO, 'name' => '微信推送借款成功队列', 'actionName' => 'weixin-msg/pay-status'],
            ['key' => RedisQueue::LIST_WEIXIN_USER_DEBIT_INFO, 'name' => '微信推送还款队列', 'actionName' => 'weixin-msg/loan-success'],
            ['key' => RedisQueue::LIST_CHANNEL_FEEDBACK, 'name' => '第三方渠道反馈事件', 'actionName' => 'channel-order/handle-event'],
        ];
        $redisIncr = [
            ['key' => sprintf('credit:order_count:%s', date('ymd')), 'name' => '当日普通用户放款量', 'actionName' => ''],
            ['key' => sprintf('credit:order_count_gjj:%s', date('ymd')), 'name' => '当日公积金用户放款量', 'actionName' => ''],
            ['key' => sprintf('credit:order_count_third:%s', date('ymd')), 'name' => '当日第三方用户放款量', 'actionName' => ''],
            ['key' => sprintf('credit:order_count_old_user:%s', date('ymd')), 'name' => '当日老用户放款量', 'actionName' => ''],
            ['key' => 'access_token', 'name' => '微信acc_token', 'actionName' => ''],
            ['key' => sprintf('credit:white_manual:%s', date('ymd')), 'name' => '白名单转人工量', 'actionName' => ''],
        ];
        foreach ($redisList as $key => $val) {
            $redisList[$key]['length'] = RedisQueue::getLength([$val['key']]);
        }
        foreach ($redisIncr as $k => $v) {
            $redisIncr[$k]['length'] = RedisQueue::get(['key' => $v['key']]);
        }

        return $this->render('home', [
            'redisList' => $redisList,
            'redisIncr' => $redisIncr,
        ]);
    }

    /**
     * @name 打印redis队列值
     */
    public function actionGetList()
    {
        $key = \Yii::$app->request->get('key');
        $page = Yii::$app->request->get('per-page',30);

        $pages = new Pagination(['totalCount' => $count = RedisQueue::getLength([$key])]);
        $pages->pageSize = 30;
        if($page){
            $pages->pageSize = $page;
        }
        $stop = $pages->offset + $pages->pageSize;
        $res = RedisQueue::getQueueList([$key, $pages->offset, $stop]);
        $page_all = (int)ceil($count / $pages->pageSize);
        return $this->render('home', [
            'List' => $res,
            'Page_all' => $page_all,
            'Count' => $count,
            'pages' => $pages,
            'Key' => $key
        ]);

    }

    /**
     * 退出
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(['login']);
    }

    /**
     * 刷新权限
     */
    public function actionResetRole()
    {
        // 把权限信息存到session中
        if (Yii::$app->user->identity->role && $roleModel = AdminUserRole::find()->andWhere("name in('" . implode("','", explode(',', Yii::$app->user->identity->role)) . "')")->all()) {
            $arr = array();
            foreach ($roleModel as $val) {
                if ($val->permissions)
                    $arr = array_unique(array_merge($arr, json_decode($val->permissions)));
            }
            Yii::$app->getSession()->set('permissions', json_encode($arr));
        }

        return $this->redirect(['main/index']);
    }
}
