<?php
/**
 * Created by PhpStorm.
 * User: guoxiaoyong
 * Date: 2017/7/13
 * Time: 下午3:11
 */

namespace common\services\wechat;


use Yii;
use yii\base\Component;
use common\helpers\Curl;
use common\helpers\Util;
use common\models\LoanPerson;
use common\models\mongo\wechat\MsgTemplateRetMongo;
use common\models\UserCouponInfo;
use common\models\UserCreditTotal;
use common\models\UserLoanOrderRepayment;
use common\models\WeixinUser;

class SendService extends Component
{
    protected $openid;

    protected $templateParams;

    protected $user_id;

    /**
     * 通过手机号码发送信息内容给微信用户
     * @param $mobile
     * @param $content
     * @return void
     */
    public function Send($mobile,$post_msg ='')
    {

        $userInfo = LoanPerson::find()->select('wx.openid, wx.uid')->from(LoanPerson::tableName() . ' AS p')
                                    ->leftJoin(WeixinUser::tableName() . ' AS wx', 'p.id = wx.uid')
                                    ->where('p.phone = ' . $mobile)
                                    ->andWhere('p.source_id = ' . LoanPerson::PERSON_SOURCE_MOBILE_CREDIT)
                                    ->asArray()
                                    ->one();
        if(isset($userInfo['openid']) && !empty($userInfo['openid']))
        {

            //发送微信模板
            $this->openid = $userInfo['openid'];
            $this->user_id = $userInfo['uid'];

            //查询有几张券
            $count = UserCouponInfo::find()->where(['user_id' => $this->user_id, 'is_use' => UserCouponInfo::STATUS_FALSE ])
                                            ->andWhere(['>=', 'end_time', time()])
                                            ->count();
            //查询用户最后一条借款记录
            $info = UserLoanOrderRepayment::find()->where(['user_id' => $this->user_id ])
                                                ->select('plan_fee_time,principal,status,total_money')
                                                ->orderBy('id desc')->limit(1)->one();
            if (empty($info)) {
                $data['keyword1'] = '无';
                $data['keyword2'] = '无';
            } elseif ($info->status == UserLoanOrderRepayment::STATUS_REPAY_COMPLETE) {
                $data['keyword1'] = '无';
                $data['keyword2'] = '无';
            } else {
                $data['keyword1'] = sprintf("%0.2f", $info->total_money / 100);
                $data['keyword2'] = date('Y.m.d', $info->plan_fee_time);
            }
            //用户额度
            $money = UserCreditTotal::find()
                ->where(['user_id' => $this->user_id])
                ->select('amount,used_amount,locked_amount')->one();
            $amount = $money->amount / 100;
            $user_amount = $money->used_amount / 100;
            $locked_amount = $money->locked_amount / 100;
            $no_user_amount = $amount - $locked_amount - $user_amount;
            $data['keyword3'] = $amount . '.00';
            $data['keyword4'] = $no_user_amount . '.00';
            $data['keyword5'] = $count;
            $msg = $this->endMsg($this->user_id);
            $data['remark'] = $msg['msg'];
            if(!empty($post_msg)){
                $data['remark'] = $post_msg;
            }
            $data['url'] = $msg['url'];
            $data['openid'] = $this->openid;

            $res = $this->TemplateOne($data);
            if($res->errcode!=0){
                Yii::error(sprintf('微信模板推送失败：%s,%s', $userInfo['uid'], json_encode($res)));
            }
        }


    }


    /**
     * @param array $data
     * @return mixed
     */
    private function template($data)
    {
        $weixinService = Yii::$app->weixinService;
        $accessToken = $weixinService->get_access_token();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$accessToken;
        $json_data = json_encode($data,JSON_UNESCAPED_UNICODE);//解决utf-8中文传输问题
        $ret = $this->postData($url,$json_data);
        return json_decode($ret);

    }


    /**
     *模板 用户查询结果
     */
    //待还款金额：{{keyword1.DATA}}
    //还款日期：{{keyword2.DATA}}
    //账户额度：{{keyword3.DATA}}
    //剩余可用额度：{{keyword4.DATA}}
    //可用券：{{keyword5.DATA}}
    public function TemplateOne($data){
        $template_id = 'cRu9pdO5D8qL493EpMqwXAYS9ZETch_i0WyOr7ExSs0';
        $temp_res['touser'] = $data['openid'];
        $temp_res['template_id'] = $template_id;
        $temp_res['url'] = $data['url'];
        $temp_res['data']['first']['value'] = '您好，截止目前您账户信息如下:';//开头
        $temp_res['data']['keyword1']['value'] = $data['keyword1'];
        $temp_res['data']['keyword2']['value'] = $data['keyword2'];
        $temp_res['data']['keyword3']['value'] = $data['keyword3'];
        $temp_res['data']['keyword4']['value'] = $data['keyword4'];
        $temp_res['data']['keyword5']['value'] = $data['keyword5'];
        $temp_res['data']['remark']['value'] = $data['remark'];//结尾
        $temp_res['data']['remark']['color'] = '#FF0000';//结尾
        return $this->template($temp_res);
    }


    public function endMsg($uid)
    {
        //查询用户是否有优惠券
        $url='http://mp.weixin.qq.com/s/0ZN6Hf_6PWHhx1poEiMpMg';
        $info_count = UserCouponInfo::find()->where(['user_id'=>$uid,'is_use'=>0])->andWhere(['>','end_time',time()])->count();
        $msg = "温馨提示：按时还款可提高额度";
        if($info_count){
            $msg = "恭喜您获得了一张还款抵扣券，按时还款可用，过期失效，按时还款可提高额度";
        }
        $msg1['msg'] = $msg;
        $msg1['url'] = $url;
        return $msg1;
    }



    /**
     * CURL获取
     * @param $url
     * @return mixed
     */
    public static function postData($url , $data = null){
        $timeout = 1000;
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在


        if (!is_null($data)){
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)'); // 模拟用户使用的浏览器
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包x
            curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        }
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环

        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            echo 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }


}