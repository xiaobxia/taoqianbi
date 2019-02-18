<?php

namespace credit\controllers;

use common\models\LoanPerson;
use Yii;
use common\models\PopBox;
use common\models\UserLoanOrder;
use common\models\UserRegisterInfo;
use common\models\LoanSearchPublicList;
use yii\web\Response;

/**
 * NoticeAllController
 * @use 用于用户接受消息通知等信息的控制器 （包括：App推送、短信、App弹框、App红点消息等）
 */
class NoticeController extends BaseController {

    /**
     * 启动弹框
     *
     * @name 启动弹框 [noticePopBox]
     * @method get
     * @param integer $box_id 客户端存储的弹框记录ID
     */
    public function actionPopBox() {
        //2017-06-16取消启动弹框
        return [
            'code' => -1,
            'message' => '当前没有满足条件的启动弹框！'
        ];
        // 如果存在慢就赔红包优先显示慢就赔
//        $coupon = $this->isExistCoupon();
//        if ($coupon["code"] == "0") {
//            return $coupon;
//        }

        // 如果存在红包优先显示红包
//        $red_packet = $this->isExistedRedPacket();
//        if ($red_packet["code"] == "0") {
//            return $red_packet;
//        }
        $box_id = intval($this->request->get('box_id', ''));
        $time = time();
        $project_type = '';
        $pop_box = PopBox::find()->select('*')->where(['status' => PopBox::STATUS_SUCC, 'show_site' => PopBox::SHOW_SITE_ONE])->andWhere(' expect_time <= ' . $time . ' and expire_time > ' . $time . ' ')->orderBy('id desc')->limit(1)->one();

        if ($pop_box && (!$box_id || ($box_id && $pop_box->id > $box_id))) {
            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'id' => $pop_box->id, //弹框id
                    'img_url' => $pop_box->img_url, // 图片链接
                    'action_type' => $pop_box->action_type, // 跳转类型 0-无触发动作 1-跳转原生页面 2-跳转H5页面
                    'action_url' => $pop_box->action_url // 跳转url
                ]
            ];
        }
        return [
            'code' => -1,
            'message' => '当前没有满足条件的启动弹框！'
        ];
    }

    /**
     * 启动首页弹窗
     * @name 弹窗广告列表
     */
    public function actionPopBoxList() {
        $now = time();
        $condition = sprintf(" status=%s 
            AND show_site=%d 
            AND expect_time <= %s 
            AND expire_time > %s ", PopBox::STATUS_SUCC, PopBox::SHOW_SITE_ONE, $now, $now);
        $data = PopBox::find()->select(["img_url", "id AS pop_id", "action_type", "action_url"])
            ->where($condition)
            ->orderBy('top desc,id desc')
            ->limit(10)->asArray()->all();

        if ($data) { // 添加新用户首屏逻辑
            $curUser = Yii::$app->user->identity;
            if ($curUser) {
                $user_id = $curUser->getId();

                $userRegInfo = UserRegisterInfo::find()->where(["user_id" => $user_id])->orderBy('id DESC')->limit(1)->one();
                if ($userRegInfo) {
                    $app_market = $userRegInfo->appMarket;

                    if (strpos($app_market,"H5-SDZJ") >= 0) {
                        $p_condition = sprintf(" status=%s AND show_site=%d AND expect_time <= %s AND expire_time > %s ", PopBox::STATUS_SUCC, PopBox::SHOW_SITE_SIX, $now, $now);

                        $pop_data = PopBox::find()->select(["img_url", "id AS pop_id", "action_type", "action_url"])->where($p_condition)->orderBy('top desc,id desc')->limit(1)->asArray()->all();
                        if ($pop_data) {
                            array_unshift($data, $pop_data[0]);
                        }
                    }else{
                        // 判断当前用户是否新用户
                        $order = UserLoanOrder::find()->where(['user_id' => $user_id])->orderBy('id DESC')->limit(1)->one();
                        if (empty($order) || ($order && $order->is_first == 1)) {
                            // 置顶显示注册拉新活动
                            $p_condition = sprintf(" status=%s AND show_site=%d AND expect_time <= %s AND expire_time > %s ", PopBox::STATUS_SUCC, PopBox::SHOW_SITE_FIVE, $now, $now);
                            $pop_data = PopBox::find()->select(["img_url", "id AS pop_id", "action_type", "action_url"])->where($p_condition)->orderBy('top desc,id desc')->limit(1)->asArray()->all();
                            if ($pop_data) {
                                array_unshift($data, $pop_data[0]);
                            }
                        }
                    }
                }
            }
            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    "pop_list" => $data,
                ]
            ];
        }
        return [
            'code' => -1,
            'message' => '当前没有满足条件的启动弹框！'
        ];
    }

    /**
     * 优惠券预留
     * @return array
     */
    public function actionIsCoupon() {
        return [
            'code' => 0,
            'message' => 'success！'
        ];
    }

    /**
     * @name 启动页广告
     */
    public function actionStartPopAd() {
        \yii::$app->response->format = Response::FORMAT_JSON;

        $pop_list = [];
        $source = $this->getSource();

        $now = time();
        $user_id = \yii::$app->user->identity ? \yii::$app->user->identity->id : 0;
        $condition = sprintf(" status=%s AND show_site=%d AND expect_time <= %s AND expire_time > %s AND source_id = %s",
            PopBox::STATUS_SUCC, PopBox::SHOW_SITE_TWO, $now, $now, $source);

        $pop_lists = PopBox::find()->select(["img_url", "action_url", "loan_search_public_list_id"])
            ->where($condition)
            ->orderBy('id desc')
            ->asArray()
            ->all();
        if ($pop_lists) {
            foreach ($pop_lists as $val) {
                if ($val['loan_search_public_list_id'] == 0) { // 无关联集合ID，所有人可见
                    $pop_list = $val;
                    break;
                }
                else { // 关联了某个集合，判断是否可见
                    if ($user_id) { // 未登录不显示
                        /* @var $redis \yii\redis\Connection */
                        $redis = Yii::$app->redis;
                        $record = LoanSearchPublicList::findOne($val['loan_search_public_list_id']);
                        if ($record && $redis->sismember($record->key, \yii::$app->user->identity->phone)) { // 判断用户是否在可显示列表里
                            $pop_list = $val;
                            break;
                        }
                    }
                }
            }
        }

        $clientType = strtolower( \yii::$app->request->getClient()->clientType );
        $pop_list = ($clientType == 'ios') ? $pop_list : [$pop_list]; # android, ios 响应结构有差异
        return [
            'code' => 0,
            'message' => 'success',
            'data' => [
                "pop_list" => $pop_list,
            ]
        ];
    }

    /**
     * 活动弹窗
     */
    public function actionShowActivity(){
        $data = Yii::$app->request->post();
        if (empty($data)) {
            return [
                "code" => 0,
                'message' => "暂无数据",
                'data' => (object)[],
            ];
        }

        if ($data['uid'] != 0 && empty($data['uid'])) {
            return [
                "code" => 0,
                'message' => "暂无数据",
                'data' => (object)[],
            ];
        }
        $source = $this->getSource();
        $huodong = [];//DiscoverHuodong::find()->where(['status'=>1])->orderBy('id desc')->limit(1)->one();
        if ($data['uid'] == $huodong['id'] || empty($huodong) || $source != LoanPerson::PERSON_SOURCE_MOBILE_CREDIT) {
            return [
                "code" => 0,
                'message' => "暂无数据",
                'data' =>  (object)[],
            ];
        }

        $return_data['uid'] = $huodong['id'];
        $return_data['type'] = '1';
        $return_data['sub_type'] = '0';
        $return_data['link_url'] = $huodong['link'];
        $return_data['image_url'] = $huodong['image_url'];
        return [
            "code" => 0,
            'message' => "有数据",
            'data' => $return_data,
        ];
    }


}
