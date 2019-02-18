<?php
namespace newh5\controllers;

use common\models\DiscoverActivity;
use common\models\DiscoverBanner;
use Yii;
use yii\helpers\Url;
use yii\helpers\Html;
use common\helpers\Util;
use yii\filters\AccessControl;
use common\services\DiscoverColleagueBannerService;
use newh5\components\ApiUrl;
use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\WeixinUser;
use common\models\UserQuotaMoreInfo;
use common\models\DiscoverColleague;
use common\models\DiscoverColleagueBanner;
use common\models\DiscoverNonColleague;


class AppPageController extends BaseController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['about-company','find-other','show-pay','alipay-repayment','loan-procedure'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionShowPay(){
        return $this->render('show-repayment-method',[
        ]);
    }

    public function actionAlipayRepayment(){
        return $this->render('alipay-repayment',[
        ]);
    }

    public function actionLoanProcedure(){
        return $this->render('loan-procedure',[
        ]);
    }

    public function actionRepaymentResults(){
		$id = intval($this->request->get('id'));
        return $this->redirect(ApiUrl::toRouteMobile(['loan/loan-detail', 'id' => $id],true));
        // $this->view->title = '还款结果';
        // return $this->render('repayment-results');
    }
	public function actionMoreUserInfo(){
        $is_login = Yii::$app->user->getIsGuest() ? 0 : 1; //是否登录
        $data = [
            'qq' => '',
            'wx' => '',
            'taobao' => '',
            'mail' => '',
        ];
        if($is_login) {
            $curUser = Yii::$app->user->identity;
            $user_id = $curUser->id;
            $query = UserQuotaMoreInfo::find()->where(['user_id' => $user_id])->asArray()->one();
            if ($query) {
                isset($query['qq']) && $data['qq'] = $query['qq'];
                isset($query['wechat']) && $data['wx'] = $query['wechat'];
                isset($query['taobao']) && $data['taobao'] = $query['taobao'];
                isset($query['mail']) && $data['mail'] = $query['mail'];
            }
        }
        $source = $this->getSource();
        $view = 'more-user-info';
        //$color = '#1782e0';
        if($source == LoanPerson::PERSON_SOURCE_HBJB){
            $view = 'more-user-info-hbqb';
        }
        $color = $this->getColor();
        $this->view->title = '更多信息';
        return $this->render($view,[
            'data' => $data,
            'color' => $color,
        ]);
    }
    public function actionBankCardInfo(){
        $this->view->title = '已绑银行卡';
        $user_id = Yii::$app->user->identity->id;
        $cards = Yii::$container->get('userService')->getCardInfo($user_id, 1);
        $source = $this->getSource();
        if(!$cards){
            return $this->redirect(Url::toRoute(['app-page/bank-card-action','source'=>$source],true));
//            return $this->render('bank-card-action', [
//                'card_info' => $cards[0],
//                'source'=>$source,
//            ]);
        }
        $img = 'hsm.png';
        if(Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){
            $img = 'pro.png';
        }
        $color = $this->getColor();
        $view = 'bank-card-info';
        if($source == LoanPerson::PERSON_SOURCE_HBJB){
            $view = 'bank-card-info-hbqb';
        }
        return $this->render($view, [
            'color'=>$color,
            'card_info' => $cards[0],
            'source'=>$source,
            'img'=>$img
        ]);
    }
    public function actionBankCardAction(){
        $name = Yii::$app->user->identity->name;
        $this->view->title = '绑定银行卡';
        $source = $this->getSource();

        $color = $this->getColor();
        if(empty($color)){
            $color = '1782e0';
        }
      /*  $header = \Yii::$app->request->headers;
        $appMarket = $header->get('appmarket');
        $clientType = $header->get('clientType');*/
        $img = 'hsm.png';
        if(Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){
            $img = 'pro.png';
        }
        return $this->render('bank-card-action', [
            'img'=>$img,
            'name' => $name,
            'card_list' => CardInfo::getCardConfigList(),
            'color'=>$color,
            'source'=>$source,
          /*  'appMarket'=>$appMarket,
            'clientType'=>$clientType*/
        ]);
    }
    public function actionAboutCompany(){
        $from = Html::encode($this->request->get('from'));
        $this->view->title = '关于我们';
        $clientType = \yii::$app->request->getClient()->clientType;
        $source = $this->getSource();
//        $app_name = LoanPerson::$person_source[$source];
        $company = $this->getCompany();
        $app_name = $this->getAppName();

        /*$company = '德清正恒网络科技有限公司';
        switch ($source){
            case LoanPerson::PERSON_SOURCE_MOBILE_CREDIT:
                $company = '德清正恒网络科技有限公司';
                break;
            case LoanPerson::PERSON_SOURCE_WZD_LOAN:
                $company = '德清正恒网络科技有限公司';
                break;
            case LoanPerson::PERSON_SOURCE_HBJB:
                $company = '淮北汇邦小额贷款股份有限公司';
                break;
        }*/
        $app_version = \Yii::$app->request->get('app_version');
        if(empty($app_version)){
            $app_version = '2.2.4';
        }
        return $this->render('about-company',[
            'from' => $from,
            'type'=>$clientType,
            'source'=>$source,
            'company'=>$company,
            'app_name'=>$app_name,
            'app_version'=>$app_version,
        ]);
    }
    /**
     * 微信APP认证页面
     */
    public function actionWxRegister(){
        $this->view->title = '微信认证';
        $user_id = Yii::$app->user->identity->id;
        $weixin = WeixinUser::findOne(['uid'=>$user_id]);
        $color = $this->getColor();
        $image = 'wx-auth-beijin.png';
        if(Util::getMarket() == LoanPerson::APPMARKET_XJBT_PRO){
            $image = 'weixin-back.png';
        }
        if($weixin){//已完成认证
            return $this->render('complete-wx-auth',[
                'data'=>$weixin,
                'color'=>$color,
                'image'=>$image,
            ]);
        }else{
            return $this->render('wx-auth',[
                'color'=>$color
            ]);
        }
    }


    /**
     * 发现H5页面
     */
    public function actionFindOther(){
        $this->view->title = '发现';
        //$pageSize = yii::$app->request->get('pageSize', 14);
        $list = DiscoverColleague::find()
            ->select('id, colleague_name AS title, icon, slogan, cate_id, tag, day_rate, credit_limit')
            ->where(['status' => 1, 'is_del' => 0])
            ->andWhere(['in','cate_id',DiscoverColleagueBannerService::$show_list])
            ->orderBy('position ASC')
            ->asArray()->all();
        //$listCount = DiscoverColleague::find()->where(['status' => 1, 'is_del' => 0])->count();
        foreach ($list as &$item) {
            if(!empty($item['tag'])){
                $item['tags'] = explode(',',$item['tag']);
            }
            $item['link'] = $this->replaceUrl(Yii::$app->getRequest()->getAbsoluteBaseUrl().'/discover/colleague?id=' . $item['id'], 'newh5');
            unset($item['id']);

        }
        //$data['data']['max_page'] = ceil($listCount / $pageSize);
        $activityList = DiscoverActivity::find()
            ->where(['status' => 1])
            ->select('content, link, image_url AS image, created_at AS date')
            ->orderBy('position ASC')
            ->limit(5)->asArray()->all();
        foreach ($activityList as &$activity) {
            $activity['date'] = date('Y-m-d', $activity['date']);
        }
        $activity_link = '';
        if ($activityList) {
            $activity_link = $this->replaceUrl(Yii::$app->request->getAbsoluteBaseUrl(). '/discover/activity-list', 'newh5');
        }
        //活动banner
        $bannerList = DiscoverBanner::find()
            ->where(['status' => 1])
            ->select('link_url AS link, image_url AS image')
            ->orderBy('position ASC')->asArray()->all();
        return $this->render('find-other',[
            'bannerList'=>$bannerList,
            'activityList'=>$activityList,
            'activity_link'=>$activity_link,
            'list'=>$list,
        ]);
    }

    /**
     * @author guoxiaoyong
     * 将cretid 转换成其他模块子域
     * @param string $url
     * @param string $modulName
     */
    protected function replaceUrl($url, $moduleName)
    {
        $oldUrl = Url::to($url);
        $newUrl = \str_replace([
            'credit/', 'api.', 'qbapi.'
        ], [
            $moduleName . '/', 'credit.', 'qbcredit.'
        ], $oldUrl); //兼容多种环境和绝对路径


        return $newUrl;
    }

}
