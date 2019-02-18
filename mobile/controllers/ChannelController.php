<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/21
 * Time: 11:53
 */
namespace mobile\controllers;

use yii\base\UserException;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use Yii;
use yii\filters\AccessControl;
use common\services\channel\WubaService;
use common\models\LoanOrderSource;

class ChannelController extends BaseController
{

    public $layout = 'loan';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => ['channel-repay'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * 渠道还款
     */
    public function actionChannelRepay()
    {
        $channel_id = Yii::$app->request->get('channel_id', '');
        if (!$channel_id || !array_key_exists($channel_id, LoanOrderSource::$source_list)) {
            throw new UserException("非法请求");
        }
        $channel_name = LoanOrderSource::$source_list[(int)$channel_id];
        $channel_service_name = '\common\services\channel\\' . ucfirst($channel_name) . 'Service';
        $channel_service = new $channel_service_name;
        if (!method_exists($channel_service, 'getData')) {
            throw new UserException("获取数据方法不存在");
        }
        $data = $channel_service->getData();
        if (empty($data['source_order_id']) || empty($data['return_url'])) {
            throw new UserException("还款参数缺失");
        }
        /*
        $data = [
            'source_order_id' => 'R201702131055048114',
            'return_url' => 'http://apitest.jiedianqian.com/xjbk/repayCallback.do'
        ];
        */
        $source_order_id = $data['source_order_id'];
        $url = $data['return_url'];

        $source_order = LoanOrderSource::find()->where([
            'source_order_id' => (string)$source_order_id
        ])->limit(1)->one();
        if (!$source_order || $source_order->source != (int)$channel_id) {
            Yii::error($channel_name . '-' . "订单不存在，订单号为：{$source_order_id}");
            return $this->render('error', [
                'msg' => '订单不存在',
                'result_url' => $url,
            ]);
        }
        $order_id = $source_order->order_id;
        //$order_id = 15330;
        //$url = 'https://www.baidu.com';
        if (!($order = UserLoanOrder::findOne((int)$order_id)) || $order->sub_order_type != LoanOrderSource::$source_to_subOrderType_list[(int)$channel_id]) {
            Yii::error($channel_name . '-' . "源订单不存在，订单号为：{$order_id}");
            return $this->render('error', [
                'msg' => '订单不存在',
                'result_url' => $url,
            ]);
        }

        $repayment = UserLoanOrderRepayment::findOne(['order_id' => $order_id]);

        if (!$repayment || $repayment->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
            Yii::error($channel_name . '-' . "订单不支持还款申请'，订单号为：{$order_id}");
            return $this->render('error', [
                'msg' => '当前订单不支持还款申请',
                'result_url' => $url,
            ]);
        }

        //跳转用户还款页面
        if (!Yii::$app->user->isGuest && Yii::$app->user->id != $order->user_id) {
            Yii::$app->user->logout();
        }
        $user = LoanPerson::findOne($order->user_id);
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->login($user);
        }

        $this->view->title = '请选择还款方式';
        $infos = $this->_getOrderInfos($order_id);
        /*
        $order = UserLoanOrder::findOne($order_id);
        $user = LoanPerson::findOne($order->user_id);
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->login($order->loanPerson);
        }
        */
        $order = $infos['order'];
        if (!$infos['repayment']) {
            Yii::error($channel_name . '-' . "订单还未打款，暂不支持还款，订单号为：{$order_id}");
            return $this->render('error', [
                'msg' => '该订单还未打款，暂不支持还款',
                'result_url' => $url,
            ]);
        }
        if (!$infos['card_info']) {
            Yii::error($channel_name . '-' . "银行卡不存在，订单号为：{$order_id}");
            return $this->render('error', [
                'msg' => '银行卡不存在',
                'result_url' => $url,
            ]);
        }
        Yii::info($channel_name . '-' . '请求还款订单号为：' . $order_id);
        return $this->render('channel_repayment', [
            'order' => $order,
            'result_url' => $url,
        ]);
    }

    private function _getOrderInfos($id)
    {
        $user_id = Yii::$app->user->identity->id;    // 测试改这里的代码
        return UserLoanOrder::getOrderRepaymentCard($id, $user_id);
    }
}