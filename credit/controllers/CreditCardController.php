<?php
namespace credit\controllers;

use common\base\LogChannel;
use common\helpers\Lock;
use common\models\AccumulationFund;
use common\models\CreditFaceIdCard;
use common\models\ErrorMessage;
use common\models\UserDetail;
use common\models\UserProofMateria;
use common\models\UserQuotaMoreInfo;
use common\models\UserQuotaPersonInfo;
use common\models\UserQuotaWorkInfo;
use common\models\UserVerification;
use common\models\LoanPerson;
use common\models\UserContact;
use common\services\credit_line\CreditLineService;
use common\services\fundChannel\JshbService;
use common\services\JxlService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\helpers\Url;
use yii\web\UploadedFile;
use yii\validators\FileValidator;
use common\helpers\MessageHelper;
use common\soa\KoudaiSoa;
use common\helpers\StringHelper;
use common\exceptions\UserExceptionExt;
use common\models\CardInfo;
use common\models\UserCaptcha;
use common\models\UserLoanOrder;
use common\models\BankConfig;
use common\exceptions\CodeException;
use common\models\UserRealnameVerify;
use common\services\UserService;
use common\helpers\ToolsUtil;
use credit\components\ApiUrl;
use common\api\RedisQueue;
use common\services\AppEventService;
use common\helpers\Util;
use common\models\PopBox;
use common\models\UserCreditDetail;
use common\models\UserCreditData;
use common\models\BindCardInfo;

class CreditCardController extends BaseController {

    protected $userService;

    /**
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
    public function __construct($id, $module, UserService $userService, $config = []) {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,
                'except' => ['reg-get-code', 'reg-get-audio-code', 'register', 'login', 'logout', 'quick-login',
                    'reset-pwd-code', 'verify-reset-password', 'reset-password', 'state', 'captcha', 'yirute-tid',
                ],
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
     * 获取用户额度卡信息
     *
     * @name    获取用户额度卡信息 [creditCardGetCardInfo]
     * @uses    获取用户额度卡信息
     * @author  honglifeng
     */
    public function actionGetCardInfo() {
        try {
            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => [
                        'my_amount' => 1000,
                        'amount_list' => [
                            ['amount' => 2000, 'lock' => 1],
                            ['amount' => 3000, 'lock' => 1],
                            ['amount' => 4000, 'lock' => 1],
                            ['amount' => 5000, 'lock' => 1],
                        ],
                        'url' => 'http://www.hao123.com/?amount=',
                    ]
                ],
            ];
        } catch (\Exception $e) {
            return[
                'code' => $e->getCode(),
                'message' => $e->getMessage(),

            ];
        }
    }

    /**
     * Face++身份证校验
     * @name Face++身份证校验 [FacePlusIdcard]
     * @method post
     * @param  string $image_file 身份证二进制图片
     * @return array
     */
    public function actionFacePlusIdcard()
    {
        $person_id = Yii::$app->user->identity->getId();
        if ($person_id <= 0){
            return [
                'code'=>-2,
                'message'=>'用户未登录',
            ];
        }
        $file = UploadedFile::getInstanceByName('image_file');
        if(empty($file)){
            return [
                'code'=>-1,
                'message'=>'图片上传失败',
            ];
        }

        $validator = new FileValidator();
        $validator->extensions = ['jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG', 'gif', 'GIF'];
        $validator->checkExtensionByMimeType = false;
        if($file->size > (1024*4*1024)){
            return [
                'code'=>-1,
                'message'=>'图片过大',
            ];
        }
        $error = "";
        if (!$validator->validate($file, $error)) {
            return UserExceptionExt::throwCodeAndMsgExt("文件不符合要求，" . $error);
        };
        $service = Yii::$app->creditFacePlusService;
        $img_file = file_get_contents($file->tempName);
        $new_arr = $service->idCardCheck(null,$img_file);
        return $new_arr;
    }

    /**
     * 获取认证列表
     *
     * @name    获取认证列表 [creditCardGetVerificationInfo]
     * @uses    获取认证列表
     * @author  honglifeng
     */
    public function actionGetVerificationInfo()
    {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        $source = $this->getSource();
        $card1 = 'card/jshb';

        try {
            $ret = $this->userService->getVerifyInfo($user_id, false);

            //认证列表样式适配
            $verificationStyle = $this->t('verification');
            $verificationStyle['operator_color'] = $this->getColor();

            //身份证认证
            $id_card = [
                'title' => '个人信息',
                'title_mark' => '<font color="' . $verificationStyle['title_mark_must_color'] . '" size="3">(必填)</font>',
                'subtitle' => '请确保您的信息真实有效',
                'tag' => UserVerification::TAG_ID_CARD,
                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
                'logo' => $this->staticUrl('image/'.$card1.'/id_card_logo.png', 1),
				'logo2' => $this->staticUrl('image/'.$card1.'/id_card_logo_grey.png', 1),
                'status' => $ret['real_verify_status'],
                'type' => 1,
            ];

            if ($ret['real_verify_status'] && $this->userService->checkMemberVert($user_id)) {
                $id_card['operator'] = '已填写';
            }
            $list[] = $id_card;

            //联系人信息认证
            $contact_info = [
                'title' => '紧急联系人',
                'title_mark' => '<font color="' . $verificationStyle['title_mark_must_color'] . '" size="3">(必填)</font>',
                'subtitle' => '特殊情况可帮助我们联系到您',
                'tag' => UserVerification::TAG_CONTACT_INFO,
                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
                'logo' => $this->staticUrl('image/'.$card1.'/contact_info_logo.png', 1),
				'logo2' => $this->staticUrl('image/'.$card1.'/contact_info_logo2_grey.png', 1),
                'status' => $ret['real_contact_status'],
                'type' => 1,
            ];
            if ($ret['real_contact_status']) {
                $contact_info['operator'] = '已填写';
            }

            $list[] = $contact_info;
            //银行卡信息认证
            $bank_card_info = [
                'title' => '收款银行卡',
                'title_mark' => '<font color="' . $verificationStyle['title_mark_must_color'] . '" size="3">(必填)</font>',
                'subtitle' => '您的借款将打到这张卡上',
                'tag' => UserVerification::TAG_BANK_CARD_INFO,
                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
                'logo' => $this->staticUrl('image/'.$card1.'/bank_card_info_logo.png', 1),
				'logo2' => $this->staticUrl('image/'.$card1.'/bank_card_logo_grey.png', 1),
                'first_url' => ApiUrl::toRouteNewH5(['app-page/bank-card-info'], true),
                'status' => $ret['real_bind_bank_card_status'],
                'type' => 1,
            ];

            if ($ret['real_bind_bank_card_status']) {
                $card = $this->userService->getMainCardInfo($user_id);
                if (!$card) {
                    $message = '用户银行卡绑定信息异常，用户ID：' . $user_id;
                    if (YII_ENV_PROD) {
                        MessageHelper::sendSMS(NOTICE_MOBILE, $message);
                    }
                    \Yii::error($message);
                    $bank_card_info['operator'] = '已填写';
                } else {
                    $bank_card_info['operator'] = $card['bank_name'] . '(' . substr($card['card_no'], -4) . ')';
                }
                $bank_card_info['url'] = ApiUrl::toRouteNewH5(['app-page/bank-card-info'], true);
            }

            $list[] = $bank_card_info;

            //芝麻授信
//            $zmxy_info = [
//                'title' => '芝麻授信',
//                'title_mark' => '<font color="' . $verificationStyle['title_mark_must_color'] . '" size="3">(必填)</font>',
//                'subtitle' => '芝麻授权可以帮助您更快借款',
//                'tag' => UserVerification::TAG_ZMXY_INFO,
//                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
//                'logo' => $this->staticUrl('image/'.$card1.'/zmxy_info_logo.png', 1),
//                'first_url' => ApiUrl::toRouteMobile(['user/zm-authorize'], true),
//                'status' => $ret['real_zmxy_status'],
//                'type' => 1,
//            ];
//
//            if ($ret['real_zmxy_status']) {
//                $zmxy_info['operator'] = '已填写';
//            }
//            $list[] = $zmxy_info;

            //聚信立手机运营商信息认证
            $mobile_info = [
                'title' => '手机运营商',
                'title_mark' => '<font color="' . $verificationStyle['title_mark_must_color'] . '" size="3">(必填)</font>',
                'subtitle' => '手机运营商认证可以加速借款审核',
                'tag' => UserVerification::TAG_MOBILE_INFO,
                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
                'logo' => $this->staticUrl('image/'.$card1.'/mobile_info_logo.png', 1),
				'logo2' => $this->staticUrl('image/'.$card1.'/mobile_info_logo_grey.png', 1),
                'url' => Url::toRoute(['credit-web/verification-jxl'], true),
                'status' => $ret['real_jxl_status'],
                'type' => 1,
            ];

            if ($ret['real_jxl_status']) {
                $mobile_info['operator'] = '已填写';
            }
            $list[] = $mobile_info;

            //工作信息认证
            $work_info = [
                'title' => '工作信息',
                'title_mark' => '<font color="' . $verificationStyle['title_mark_option_color'] . '" size="3">(选填)</font>',
                'subtitle' => '填写真实工作信息，最高提额200',
                'tag' => UserVerification::TAG_WORK_INFO,
                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
                'logo' => $this->staticUrl('image/'.$card1.'/work_info_logo_2.png', 1),
				'logo2' => $this->staticUrl('image/'.$card1.'/work_info_logo_grey.png', 1),
                'status' => $ret['real_work_status'],
                'type' => 3,
            ];

            if ($ret['real_work_status']) {
                $work_info['operator'] = '已填写';
            }
            $list[] = $work_info;

            //公积金   0 为填写1待认证 2认证失败 3 已填写
//            $subtitle = '填写可帮助更快贷款，最高提额5000';
//            $Gjj_info = [
//                'title' => '公积金信息',
//                'title_mark' => '<font color="' . $verificationStyle['title_mark_must_color'] . '" size="3">(必填)</font>',
//                'subtitle' => $subtitle,
//                'tag' => UserVerification::TAG_ACCREDIT_FUND,
//                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
//                'logo' => $this->staticUrl('image/'.$card1.'/gong_ji_ji_log.png', 1),
//				'logo2' => $this->staticUrl('image/'.$card1.'/gong_ji_jin_logo_grey.png', 1),
//                'status' => 0,
//                'error_message'=> '',
//                'tip_message'=>$this->t('tip_message'),
//                'type' => 3,
//            ];
//
//            $accumulation_fund = AccumulationFund::findLatestOne(['user_id' => $user_id]);
//            if ($accumulation_fund) {
//                if ($ret['real_accumulation_fund'] && $accumulation_fund->status == AccumulationFund::STATUS_SUCCESS) {
//                    $Gjj_info['operator'] = '已填写';
//                    $Gjj_info['status'] = UserVerification::VERIFICATION_ACCUMULATION_SUCCESS;
//                } else {
//                    if($accumulation_fund->status == AccumulationFund::STATUS_INIT ||$accumulation_fund->status == AccumulationFund::STATUS_GET_TOKEN){
//                        $Gjj_info['operator'] = '待认证';
//                        $Gjj_info['status'] = UserVerification::VERIFICATION_ACCUMULATION_DOING;
//                    }
//                    if ($accumulation_fund->status == AccumulationFund::STATUS_FAILED){
//                        $Gjj_info['operator'] = '认证失败';
//                        $Gjj_info['status'] = UserVerification::VERIFICATION_ACCUMULATION_FILED;
//                    }
//                }
//                $Gjj_info['error_message'] = $accumulation_fund->message;
//            }
//
//            $list[] = $Gjj_info;

            //更多信息
            $more_info = [
                'title' => '更多信息',
                'title_mark' => '<font color="' . $verificationStyle['title_mark_option_color'] . '" size="3">(选填)</font>',
                'subtitle' => '补充可增加通过率，最高提额200',
                'tag' => UserVerification::TAG_MORE_INFO,
                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
                'url' => ApiUrl::toRouteNewH5(['app-page/more-user-info'], true),
                'logo' => $this->staticUrl('image/'.$card1.'/more_info_logo.png', 1),
				'logo2' => $this->staticUrl('image/'.$card1.'/more_info_logo_grey.png', 1),
                'status' => $ret['real_more_status'],
                'type' => 3,
            ];

            if ($ret['real_more_status']) {
                $more_info['operator'] = '已填写';
            }

            $list[] = $more_info;

			//微信认证
//            $weixin_info=[
//                'title' => '微信认证',
//                'title_mark' => '<font color="' . $verificationStyle['title_mark_option_color'] . '" size="3">(选填)</font>',
//                'subtitle' => '第一时间获取放款消息，最高提额200',
//                'tag' => UserVerification::TAG_WEIXIN_INFO,
//                'operator' => '<font color="' . $verificationStyle['operator_color'] . '" size="3">未完善</font>',
//                'url' => ApiUrl::toRouteNewH5(['app-page/wx-register'], true),
//                'logo' => $this->staticUrl('image/'.$card1.'/wechat_logo.png', 1),
//				'logo2' => $this->staticUrl('image/'.$card1.'/wechat_logo_grey.png', 1),
//                'status' => $ret['real_weixin_status'],
//                'type' => 3,
//
//            ];
//            if ($ret['real_weixin_status']) {
//                $weixin_info['operator'] = '已填写';
//            }
//            $list[]=$weixin_info;





            // 操作合作跳转 ++ 获取额度的接口也需要同样的数据结构 credit-info/user-credit-top
            $active_url = "";
            $active_title = "我的额度";

            $userService = Yii::$container->get('userService');
            $card_detail_info = $userService->getCreditDetail($user_id);

            // 消息提示
            // 恭喜您，额度授信成功！可以立刻申请借款啦！
            $message = "";
            $creditChannelService = \Yii::$app->creditChannelService;
            $user_credit_total = $creditChannelService->getCreditTotalByUserId($user_id);
            if (!$user_credit_total && $user_id) {
                $creditChannelService->initUserCreditTotal($user_id);
                $user_credit_total = $creditChannelService->getCreditTotalByUserId($user_id);
            }

            $ret_num = 4;
            if ($ret["authentication_pass"] == $ret_num && $card_detail_info) {
                if ($card_detail_info->credit_status == UserCreditDetail::STATUS_FINISH ||
                    ($card_detail_info->user_type == UserCreditDetail::USER_CREDIT_TYPE_ONE && $card_detail_info->credit_status == UserCreditDetail::STATUS_NORAML)) {
                    $amount = intval($user_credit_total->amount) >= 0 ? $user_credit_total->amount / 100 : "0";
                    $active_title = "更新额度";
                    $header = [
                        "status" => 3,
                        "title" => "您的信用额度超过" . ToolsUtil::getAmountApr($amount) . "%用户，完成加分认证可提额",
                        "data" => "¥" . (string) $amount,
                        "active_url" => $active_url,
                        "active_title" => $active_title,
                    ];

                    // 判断认证次数
                    // 新用户第一次 是开卡,
                    if ($card_detail_info->user_type == UserCreditDetail::USER_CREDIT_TYPE_ZERO && $card_detail_info->credit_total <= 1) {
                        $message = "恭喜您，额度授信成功！可以立刻申请借款啦！";
                    } else {
                        $message = sprintf("恭喜您，您的额度已提升至%d元，保持良好信用还能继续提额哦！", $amount);
                    }

                    // 升级发薪卡
                    if ($ret["real_verfy_senior"] >= 1) {
                        $footer = [
                            "title" => "升级发薪卡",
                            "status" => 2,
                            "card_type" => 2,
                        ];
                    } else {
                        $footer = [
                            "title" => "升级发薪卡",
                            "status" => 1,
                            "card_type" => 2,
                        ];
                    }

                    // 江湖救急的跳转链接
                    if ($amount == 0) {
                        $footer["status"] = 0;
                    }
                } elseif ($card_detail_info->credit_status == UserCreditDetail::STATUS_ING || $card_detail_info->credit_status == UserCreditDetail::STATUS_WAIT) {
                    $header = [
                        "status" => 2,
                        "title" => "额度计算中，预计需要1分钟，届时短信通知您",
                        "data" => "认证中",
                        "active_url" => $active_url,
                        "active_title" => $active_title,
                    ];
                    if (intval($card_detail_info->credit_total) > 1) {
                        $header["title"] = "额度计算中，预计需要1分钟，请等待";
                    }
                    // 不显示状态
                    $footer = [
                        "title" => "额度计算中...",
                        "status" => intval($card_detail_info->credit_total) > 1 ? 0 : 1,
                        "card_type" => 1,
                    ];
                } else {
                    $ret_data = number_format($ret["authentication_pass"] / $ret["authentication_total"] * 100);
                    $header = [
                        "status" => 1,
                        "title" => "认证越多，信用额度越高",
                        "data" => $ret_data,
                        "active_url" => $active_url,
                        "active_title" => $active_title,
                    ];
                    if (intval($ret_data) == 0) {
                        $header["title"] = "完成基础认证，即可享有专属额度";
                    } elseif (intval($ret_data) == 100) {
                        $header["title"] = "认证已完成，可查看借款额度";
                    }
                    //借款用户年龄
                    $loanPerson = LoanPerson::findOne($user_id);
                    if($loanPerson){
                        $age = Util::getAgeFromIdNumber($loanPerson->id_number);
                        if($age < 20){
                            //20岁以下
                            $header["title"] = "抱歉，由于您的年龄小于20岁不能申请借款";
                        }
                    }
                    // 可点击状态
                    $footer = [
                        "title" => "查看借款额度",
                        "status" => $ret_data == 100 ? 2 : 1,
                        "card_type" => 1,
                    ];
                }
            } else {
                $ret_data = number_format($ret["authentication_pass"] / $ret["authentication_total"] * 100);
                $header = [
                    "status" => 1,
                    "title" => "认证越多，信用额度越高",
                    "data" => $ret_data,
                    "active_url" => $active_url,
                    "active_title" => $active_title,
                ];

                if (intval($ret_data) == 0) {
                    $header["title"] = "完成基础认证，即可享有专属额度";
                }

                // 可点击状态
                $footer = [
                    "title" => "查看借款额度",
                    "status" => $ret_data == 100 ? 2 : 1,
                    "card_type" => 1,
                ];
            }
            // 白名单用户
            $user_list = [];

            if ($ret["real_verfy_base"] == 4 && $ret["real_verfy_senior"] >= 1) {
                if (in_array($card_detail_info->card_golden, UserCreditDetail::$card_pass)) {
                    // 如果首次开卡是发薪卡，处理header
                    $message = "恭喜您，由于信用良好，发薪卡开通成功！平台每天限量放出发薪卡额度，手慢无哦！";
                    if ($card_detail_info->user_type == 0 && $card_detail_info->credit_status == UserCreditDetail::STATUS_NORAML) {
                        $amount = intval($user_credit_total->amount) >= 0 ? $user_credit_total->amount / 100 : "0";
                        $active_title = "更新额度";
                        $header = [
                            "status" => 3,
                            "title" => "您的信用额度超过" . ToolsUtil::getAmountApr($amount) . "%用户，完成高级认证可提额",
                            "data" => "¥" . (string) $amount,
                            "active_url" => $active_url,
                            "active_title" => $active_title,
                        ];
                        $message = "恭喜您，由于信用良好，白卡、发薪卡都为您开通成功！可以立即申请借款啦！";
                    }
                    $footer = [
                        "title" => "升级发薪卡",
                        "status" => 0,
                        "card_type" => 2,
                    ];
                } elseif ($card_detail_info->card_golden == UserCreditDetail::CARD_GOLDEN_ING) {
                    $message = "";
                    $footer = [
                        "title" => "发薪卡开通中...",
                        "status" => 1,
                        "card_type" => 2,
                    ];
                } elseif ($card_detail_info->card_golden == UserCreditDetail::CARD_GOLDEN_MANUAL || $card_detail_info->card_golden == UserCreditDetail::CARD_GOLDEN_MANUAL_REJECT) {
                    $message = "亲，您距离激活发薪卡只差一点点的信用积累。建议使用白卡借款并保持良好信用，即有机会开通发薪卡！";
                    $footer = [
                        "title" => "升级发薪卡",
                        "status" => in_array($user_id, $user_list) ? 2 : 0,
                        "card_type" => 2,
                    ];
                } else {
                    if ($card_detail_info->credit_status == UserCreditDetail::STATUS_NORAML && $card_detail_info->user_type == UserCreditDetail::USER_CREDIT_TYPE_ZERO) {
                        $footer = [
                            "title" => "查看借款额度",
                            "status" => 2,
                            "card_type" => 1,
                        ];
                    } else {
                        $footer = [
                            "title" => "升级发薪卡",
                            "status" => 2,
                            "card_type" => 2,
                        ];
                    }
                }
            }

            // 处理金卡控制不显示逻辑
            if (Yii::$app->params['app_golden_card'] == FALSE) {
                // 处理发薪卡按钮不显示
                if ($footer["card_type"] == 2) {
                    $footer["status"] = 0;
                }
            }
            $list_name = [
                "1" => [
                    "title" => sprintf("基础认证 <font color='%s'>%s</font>", "#999999", "必填，完成后可查看借款额度"),
                    "sub_title_1" => "基础认证",
                    "sub_title_2" => "必填，完成后可查看借款额度",
                ],
                "3" => [
                    "title" => sprintf("加分认证 <font color='%s'>%s</font>", "#999999", "选填，可加速审核，可提额"),
                    "sub_title_1" => "加分认证",
                    "sub_title_2" => "选填，可加速审核，提高额度",
                ],
            ];


            $card_normal_info = [
                'act_logo' => $this->staticUrl('image/act_rebate.gif'),
                'act_url' => ApiUrl::toCredit(['credit-web/rebate', 'source_tag' => "yq9"]),
            ];

            // 处理处理首次弹窗逻辑
            if ($card_detail_info->golden_show == 0) {
                $message = "";
            }

            // 处理已开通金卡的用户不显示升级按钮
            if ($user_credit_total->card_type >= 2) {
                $footer["status"] = 0;
            }
            //处理加分认证的数量
            $count = 0;
            $true_count = 0;
            foreach ($list as $key=>$val){
                if(isset($val['type'])){
                    if($val['type'] == 3){
                        $count += 1;
                    }
                    if(($val['tag'] == 2 && $val['status'] == 1) || ($val['tag'] == 7 && $val['status'] == 1)){
                        $true_count += 1;
                    }else if($val['type'] == 3 && $val['status'] == 3){
                        $true_count += 1;
                    }
                }
            }
            $header['jia_fen']['all_count'] = $count;
            $header['jia_fen']['true_count'] = $true_count;
            $header['new_amount']['amount'] = 1000;
            $header['new_amount']['max_amount'] = 3000;
            $work_status = 0;
            if($ret['real_work_status']){
                $work_status = 1;
            }
            $credit_status = UserCreditDetail::find()
                ->where(['user_id'=>$user_id])
                ->select(['credit_status'])->one();//获取额度的状态
            $refresh_time = 0;
            if($credit_status->credit_status == 1){
                $refresh_time = 20;//20秒请求一次
            }

            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'item' => [
                        'work_info_status'=>$work_status,
                        'list' => $list,
                        'real_verify_status' => $ret['real_verify_status'],
                        'list_title' => '以下项目为选填信息，完善资料有助于提升额度',
                        "agreement" => [
                            "title" => '《信息授权和使用协议》',
                            "link" => Url::toRoute(['credit-web/safe-login-text'], true)
                        ],
                        'header' => $header,
                        'footer' => $footer,
                        'list_name' => $list_name,
                        "message_box" => $message,
                        // 添加广告
                        'act_info' => $card_normal_info,
                        'refresh_time'=>$refresh_time,
                    ]
                ],
            ];
        } catch (\Exception $e) {
            \Yii::error($e);
            return[
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    private function _checkSavePersonInfo() {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        $name = trim($this->request->post('name', ''));
        $id_number = trim($this->request->post('id_number', ''));
        $marriage = trim($this->request->post('marriage'));
        $degrees = trim($this->request->post('degrees'));
        $address = trim($this->request->post('address', ''));
        $address_distinct = trim($this->request->post('address_distinct', ''));
        $live_time_type = trim($this->request->post('live_time_type'));

        if (empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt('姓名不能为空');
        }
        if (empty($id_number)) {
            return UserExceptionExt::throwCodeAndMsgExt('身份证不能为空');
        }
        if (!isset(UserQuotaPersonInfo::$marriage[$marriage])) {
            //return UserExceptionExt::throwCodeAndMsgExt('请选择婚姻状况');
        }
        if(Util::getMarket()!='xybt_professional'){
            //非 专业版  需要这个检查项目
            if (!isset(UserQuotaPersonInfo::$degrees[$degrees])) {
                return UserExceptionExt::throwCodeAndMsgExt('请选择学历');
            }
        }

        if (empty($address)) {
            return UserExceptionExt::throwCodeAndMsgExt('现居住地址不能为空');
        }
        if (empty($address_distinct)) {
            return UserExceptionExt::throwCodeAndMsgExt('现居住地址区域不能为空');
        }
        if (!isset(UserQuotaPersonInfo::$live_time_type[$live_time_type])) {
            //return UserExceptionExt::throwCodeAndMsgExt('请选择居住时长');
        }
        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_Z);
        if (false == $user_proof_materia) {
            return UserExceptionExt::throwCodeAndMsgExt('请上传身份证正面照');
        }
        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_F);
        if (false == $user_proof_materia) {
            return UserExceptionExt::throwCodeAndMsgExt('请上传身份证反面照');
        }
        $user_proof_materia = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_FACE_RECOGNITION);
        if (false == $user_proof_materia) {
            return UserExceptionExt::throwCodeAndMsgExt('请进行人脸识别');
        }
        return false;
    }

    /**
     * 保存身份信息
     * @name    保存身份信息[creditCardSavePersonInfo]
     * @uses    保存身份信息
     * @method POST
     * @param   string $name 真实姓名
     * @param   string $id_number 身份证
     * @param   integer marriage 婚姻状况1、已婚，2、未婚，3、其它
     * @param   integer degrees 个人学历1、高中、中专及以下，2、本科或大专，3、硕士及以上
     * @param   string  address 现居住地址
     * @param   string  address_distinct  现居住地址区域
     * @param   string  longitude  地址经度
     * @param   string  latitude  地址维度
     * @param   integer live_time_type 居住时长1、半年以内，2、半年到一年，3、一年以上
     * @author  yuxuejin
     */
    public function actionSavePersonInfo() {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        $name = trim($this->request->post('name', ''));
        $id_number = trim($this->request->post('id_number', ''));
        if ($checkRet = $this->_checkSavePersonInfo()) {//检查输入数据
            return $checkRet;
        }

        $user_verification = UserVerification::findOne(['user_id' => $user_id]);
        if ($user_verification && ($user_verification->real_verify_status == UserVerification::VERIFICATION_VERIFY )) {
            return UserExceptionExt::throwCodeAndMsgExt('已经进行了实名认证，不能修改');
        }

        $source = $this->getSource();
//        $user_realname_verify = UserRealnameVerify::findOne([ //查询该渠道下是否已已有用户绑定了身份信息
//            'id_card' => $id_number,
//            'source_id' => $source,
//        ]);
        //排除自己（用户修改信息时）
        $user_realname_verify = UserRealnameVerify::find()->where([
            'id_card' => $id_number
        ])->andWhere(['<>','user_id',$user_id])->select('*')->one();
        if ($user_realname_verify) { //已有用户绑定了身份信息
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::ID_CARD_USED]);
        }

        //身份证图片识别
        /** @var CreditFacePlusService $face_service */
        $face_service = Yii::$app->creditFacePlusService;
        $user_proof_materia_front = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_Z);
        $user_proof_materia_back = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_F);
        if (empty($user_proof_materia_front) || (!$user_proof_materia_front->url) || empty($user_proof_materia_back) || (!$user_proof_materia_back->url)) {
            \yii::warning( sprintf('face_img_missing: %s', json_encode($user_proof_materia_front)), LogChannel::USER_ID_CARD );
            ErrorMessage::getMessage($user_id, 'face_img_missing:'.json_encode($user_proof_materia_front), ErrorMessage::SOURCE_IDCARD_CERT);
            return UserExceptionExt::throwCodeAndMsgExt('请先上传身份证照片');
        }
        $face_idcard_result = $face_service->idCardCheck($user_proof_materia_front->url);
        if ($face_idcard_result['code'] != UserProofMateria::STATUS_NORMAL
            || $face_idcard_result['data']['side'] != 'front'
            || empty($face_idcard_result['data']['id_card_number'])
        ) {
            \yii::warning( sprintf('face_img_not_front: %s', json_encode($face_idcard_result)), LogChannel::USER_ID_CARD );
            ErrorMessage::getMessage($user_id, 'face_img_not_front:'.json_encode($face_idcard_result), ErrorMessage::SOURCE_IDCARD_CERT);
            return UserExceptionExt::throwCodeAndMsgExt('身份证正面识别失败，请重新上传清晰图片');
        }

        if (stripos($face_idcard_result['data']['id_card_number'], '*') !== false
            || mb_stripos($face_idcard_result['data']['id_card_number'], '*') !== false
        ) {
            \yii::warning( sprintf('id_card_number_error: %s', $face_idcard_result['data']['id_card_number']), LogChannel::USER_ID_CARD );
            ErrorMessage::getMessage($user_id, 'id_card_number_error: '.$face_idcard_result['data']['id_card_number'], ErrorMessage::SOURCE_IDCARD_CERT);
            return UserExceptionExt::throwCodeAndMsgExt('身份证正面识别失败，请重新上传清晰图片');
        }

        if (strtoupper($face_idcard_result['data']['id_card_number']) != strtoupper($id_number) || $face_idcard_result['data']['name'] != $name) {
            $message = \sprintf("facepp_val_failed_1091 id:%s 填写号码：%s 识别号码：%s 填写姓名：%s 识别姓名：%s", $user_id, $id_number, $face_idcard_result['data']['id_card_number'], $name, $face_idcard_result['data']['name']);
            Yii::warning($message, LogChannel::USER_ID_CARD);
            ErrorMessage::getMessage($user_id, $message, ErrorMessage::SOURCE_IDCARD_CERT);
            return UserExceptionExt::throwCodeAndMsgExt('身份证正面识别失败：信息不一致');
        }

        $face_idcard_back_result = $face_service->idCardCheck($user_proof_materia_back->url);

        if(YII_ENV_PROD){
            //身份证合法性检验结果
            if (isset($face_idcard_result['data']['legality']['ID Photo'])
                && isset($face_idcard_result['data']['legality']['Photocopy'])
                && isset($face_idcard_result['data']['legality']['Edited'])
                && $face_idcard_result['data']['legality']['ID Photo'] < 0.75
                && $face_idcard_result['data']['legality']['Photocopy'] < 0.9
                && $face_idcard_result['data']['legality']['Edited'] < 0.9) {
                $message="身份证合法性检验结果：".json_encode($face_idcard_result);
                ErrorMessage::getMessage($user_id, $message, ErrorMessage::SOURCE_IDCARD_CERT);
                return UserExceptionExt::throwCodeAndMsgExt('请上传清晰的正式身份证照片');
            }

            if ($face_idcard_back_result['code'] != UserProofMateria::STATUS_NORMAL || !isset($face_idcard_back_result['data']['legality']['ID Photo']) || $face_idcard_back_result['data']['legality']['ID Photo'] < 0.75) {
                \yii::warning( sprintf('face_img_not_back: %s', json_encode($face_idcard_back_result)), LogChannel::USER_ID_CARD );
                ErrorMessage::getMessage($user_id, 'face_img_not_back: '.json_encode($face_idcard_back_result), ErrorMessage::SOURCE_IDCARD_CERT);
                return UserExceptionExt::throwCodeAndMsgExt('身份证反面识别失败，请重新上传清晰图片');
            }
        }

        $face_id_card_front = new CreditFaceIdCard();
        $face_id_card_front->user_id = $user_id;
        $face_id_card_front->type = CreditFaceIdCard::TYPE_FRONT;
        $face_id_card_front->data = json_encode($face_idcard_result, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

        $face_id_card_back = new CreditFaceIdCard();
        $face_id_card_back->user_id = $user_id;
        $face_id_card_back->type = CreditFaceIdCard::TYPE_BACK;
        $face_id_card_back->data = json_encode($face_idcard_back_result, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);

        if (!$face_id_card_front->save() || !$face_id_card_back->save()) {
            UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_INFO);
            ErrorMessage::getMessage($user_id, '身份证保存到credit_face_id_card失败：', ErrorMessage::SOURCE_IDCARD_CERT);
            return UserExceptionExt::throwCodeAndMsgExt('身份证识别失败，请重试');
        }
        //查看实名认证表数据，是否存在，如果存在，也不需要实名
        $user_realname_verify = UserRealnameVerify::find()
            ->where([
                'realname'=>$name,
                'id_card'=>$id_number,
                'id'=>$user_id,
            ])
            ->select('id')
            ->asArray()->one();
        if ($user_realname_verify) {
            if ($user_realname_verify->user_id != $user_id) {
                return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::ID_CARD_USED]);
            }

            $transaction = Yii::$app->db_kdkj->beginTransaction();
            $user = $curUser; //存在更新认证表即可
            $user->name = $name;
            $user->id_number = $id_number;
            $user->property = isset(LoanPerson::$sexes[$user_realname_verify->sex]) ? LoanPerson::$sexes[$user_realname_verify->sex] : "";
            $user->birthday = strtotime($user_realname_verify->birthday);
            $user->is_verify = UserVerification::VERIFICATION_VERIFY;
            if (!$user->save()) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_INFO);
                ErrorMessage::getMessage($user_id, '实名认证失败：保存loan_person数据失败', ErrorMessage::SOURCE_REALNAME);
                return UserExceptionExt::throwCodeAndMsgExt('实名认证失败');
            }
            $res = \common\models\LoanPersonHashInfo::addUserhash($user_id,$user->phone,$id_number);//融360需要添加电话 加身份证的hash
            if($res == false){
                ErrorMessage::getMessage($user_id, "LoanPersonHashInfo保存失败user_id:{$user_id}, card:{$id_number}, phone:{$user->phone}", ErrorMessage::SOURCE_REALNAME);
                \Yii::error("LoanPersonHashInfo保存失败user_id:{$user_id}, card:{$id_number}, phone:{$user->phone}", 'xybt.channelorder.main.hash');
            }
            $verification = UserVerification::saveUserVerificationInfo([
                'user_id' => $user_id,
                'real_verify_status' => UserVerification::VERIFICATION_VERIFY,
                'operator_name' => $user_id,
            ]);
            if (!$verification) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_INFO);
                ErrorMessage::getMessage($user_id, '实名认证失败：user_verification已存在', ErrorMessage::SOURCE_REALNAME);
                return UserExceptionExt::throwCodeAndMsgExt('实名认证失败');
            }

            $params = $this->request->post();
            $params['user_id'] = $user_id;
            $quotaPersonInfo = UserQuotaPersonInfo::saveUserQuotaPersonInfo($params);
            if (!$quotaPersonInfo) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_INFO);
                ErrorMessage::getMessage($user_id, '实名认证失败：保存user_quota_person_info失败', ErrorMessage::SOURCE_REALNAME);
                return UserExceptionExt::throwCodeAndMsgExt('实名认证失败');
            }
            $transaction->commit();
        }
        else {
            if (YII_ENV_DEV) {
                $ret = [
                    'code' => 0,
                    'data' => [
                        'sex' => LoanPerson::SEX_MALE,
                        'realname' => $name,
                        'idcard' => $id_number,
                        'birthday' => date('Y-m-d',ToolsUtil::idCard_to_birthday($id_number)),
                    ]
                ];
            }
            else {
//                $ret = KoudaiSoa::instance('User')->realnameAuth($name, $id_number);
                $ret = JshbService::realnameAuth($name, $id_number);
            }
            if (!isset($ret['code']) || 0 != $ret['code']) {
                \yii::error("save_person_info_1091：" . json_encode($ret), LogChannel::USER_REGISTER);
                ErrorMessage::getMessage($user_id, "姓名：{$name}，身份证号：{$id_number},实名认证失败：" . json_encode($ret), ErrorMessage::SOURCE_REALNAME);
                return UserExceptionExt::throwCodeAndMsgExt('实名认证失败code:001');
            }

            $sex = "";
            if (LoanPerson::SEX_MALE == $ret['data']['sex']) {
                $sex = "男";
            } else if (LoanPerson::SEX_FEMALE == $ret['data']['sex']) {
                $sex = "女";
            }

            $type='';
            if(isset($ret['data']['type'])){
                $type=$ret['data']['type'];
            }
            $data = array(
                'name' => $ret['data']['realname'],
                'id_number' => $ret['data']['idcard'],
                'property' => $sex,
                'birthday' => strtotime($ret['data']['birthday']),
                'type'     => $type
            );
            $this->userService->afterRealVerify($data, false, $source); //更新认证表
            $user_realname_verify = UserRealnameVerify::findOne([
                'id_card' => $data['id_number'],
                'source_id' => $source,
            ]);
            if (!$user_realname_verify) {
                ErrorMessage::getMessage($user_id, 'user_realname_verify已存在id_card', ErrorMessage::SOURCE_REALNAME);
                return UserExceptionExt::throwCodeAndMsgExt('实名认证失败code:002');
            }

            if ($user_realname_verify->user_id != $user_id) {
                ErrorMessage::getMessage($user_id, CodeException::$code[CodeException::ID_CARD_USED], ErrorMessage::SOURCE_REALNAME);
                return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::ID_CARD_USED]);
            }

            $params = $this->request->post();
            $params['user_id'] = $user_id;
            $quotaPersonInfo = UserQuotaPersonInfo::saveUserQuotaPersonInfo($params);
            if (!$quotaPersonInfo) {
                ErrorMessage::getMessage($user_id, '保存到user_quota_person_info失败', ErrorMessage::SOURCE_REALNAME);
                return UserExceptionExt::throwCodeAndMsgExt('实名认证失败code:003');
            }
            UserService::delAuthStatus($user_id,UserService::USER_AUTH_TYPE_INFO);
        }
        return [
            'code' => 0,
            'message' => '保存身份信息成功',
        ];
    }

    /**
     * 获取身份信息
     * @name    获取身份信息 [creditCardGetPersonInfo]
     * @uses    获取身份信息
     */
    public function actionGetPersonInfo() {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();
        try {
            $loan_person = LoanPerson::find()->where(['id' => $user_id])->one();
            if (false == $loan_person) {
                return UserExceptionExt::throwCodeAndMsgExt('该用户不存在');
            }

            $user_verification = UserVerification::findOne(['user_id' => $user_id]);
            $real_verify_status = $user_verification ? $user_verification->real_verify_status : 0;

            $extra_info = UserQuotaPersonInfo::find()->where(['user_id' => $user_id])->one();
            if (empty($extra_info)) {
                $extra_data = [
                    'degrees' => "",
                    'marriage' => "",
                    'address' => "",
                    'address_period' => "",
                    'address_distinct' => "",
                    'longitude' => '',
                    'latitude' => '',
                ];
            } else {
                $extra_data = [
                    'degrees' => $extra_info['degrees'],
                    'marriage' => $extra_info['marriage'],
                    'address' => $extra_info['address'],
                    'address_period' => $extra_info['live_time_type'],
                    'address_distinct' => $extra_info['address_distinct'],
                    'longitude' => $extra_info['longitude'],
                    'latitude' => $extra_info['latitude'],
                ];
            }
            $picture = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_FACE_RECOGNITION);
            $picture_face = "";
            $picture_face_url = "";
            if ($picture) {
                $picture_face = parse_url($picture['url']);
                if ($picture_face['scheme'] == 'http') {
                    $picture_face['scheme'] = 'https://';
                    $picture_face_url = $picture_face['scheme'] . $picture_face['host'] . $picture_face['path'];
                } else {
                    $picture_face_url = $picture['url'];
                }
            }

            $picture = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_Z);
            $id_pic_z = "";
            $id_pic_z_url = "";
            if ($picture) {
                $id_pic_z = parse_url($picture['url']);
                if ($id_pic_z['scheme'] == 'http') {
                    $id_pic_z['scheme'] = 'https://';
                    $id_pic_z_url = $id_pic_z['scheme'] . $id_pic_z['host'] . $id_pic_z['path'];
                } else {
                    $id_pic_z_url = $picture['url'];
                }
            }

            $picture = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_ID_CAR_F);
            $id_pic_f = "";
            $id_pic_f_url = "";
            if ($picture) {
                $id_pic_f = parse_url($picture['url']);
                if ($id_pic_f['scheme'] == 'http') {
                    $id_pic_f['scheme'] = 'https://';
                    $id_pic_f_url = $id_pic_f['scheme'] . $id_pic_f['host'] . $id_pic_f['path'];
                } else {
                    $id_pic_f_url = $picture['url'];
                }
            }

            $live_time_type_all = UserQuotaPersonInfo::getLiveTimeDict();
            $degrees_all = UserQuotaPersonInfo::getDegreeDict();
            $marriage_all = UserQuotaPersonInfo::getMarriageDict();

            $data = [
                'id' => $loan_person['id'],
                'name' => $loan_person['name'],
                'id_number' => $loan_person['id_number'],
                'degrees' => $extra_data['degrees'],
                'marriage' => $extra_data['marriage'],
                'address' => $extra_data['address'],
                'live_period' => $extra_data['address_period'],
                'address_distinct' => $extra_data['address_distinct'],
                'longitude' => $extra_data['longitude'],
                'latitude' => $extra_data['latitude'],
                'real_verify_status' => $real_verify_status,
                'face_recognition_picture' => $picture_face_url,
                'id_number_z_picture' => $id_pic_z_url,
                'id_number_f_picture' => $id_pic_f_url,
                'degrees_all' => $degrees_all,
                'marriage_all' => $marriage_all,
                'live_time_type_all' => $live_time_type_all
            ];

            return [
                'code' => 0,
                'message' => '成功获取身份信息',
                'data' => ['item' => $data],
            ];
        } catch (\Exception $e) {
            \Yii::error($e);
            return[
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 下发用户完善信息的配置 学历 居住时长  婚姻状况
     * @return array
     */
    public function actionGetPersonExtendInfo(){
        $live_time_type_all = UserQuotaPersonInfo::getLiveTimeDict();
        $degrees_all = UserQuotaPersonInfo::getDegreeDict();
        $marriage_all = UserQuotaPersonInfo::getMarriageDict();
        $data = [
            [
                'degrees_all' => $degrees_all,
                'type'=>'select'
            ],
            [
                'marriage_all' => $marriage_all,
                'type'=>'select'
            ],
            [
                'live_time_type_all' => $live_time_type_all,
                'type'=>'select'
            ],
        ];
        return [
            'code' => 0,
            'message' => '',
            'data' => $data,
        ];
    }

    /**
     * 保存紧急联系人
     * @name    保存紧急联系人 [creditCardSaveContacts]
     * @uses    保存紧急联系人
     * @method  post
     * @param   string $type 联系人关系,1、父亲，2、配偶，3、母亲，9、儿子，10、女儿
     * @param   string $mobile 手机号
     * @param   string $name 姓名
     * @param   string $relation_spare 联系人关系备用,5、同学，6、朋友，7、同事，8、亲戚，100、其他
     * @param   string $mobile_spare 手机号备用
     * @param   string $name_spare 姓名备用
     * @author  yuxuejin
     */
    public function actionSaveContacts() {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $contacts_type = trim($this->request->post('type', 0));
        $contacts_name = trim($this->request->post('name', 0));
        $contacts_mobile = trim($this->request->post('mobile', 0));
        $relation_spare = trim($this->request->post('relation_spare', 0));
        $name_spare = trim($this->request->post('name_spare', ""));
        $mobile_spare = trim($this->request->post('mobile_spare', ""));
        if (!isset(UserContact::$relation_types[$contacts_type])) {
            return UserExceptionExt::throwCodeAndMsgExt('请重新选择直系亲属与本人关系');
        }
        if (empty($contacts_mobile)) {
            return UserExceptionExt::throwCodeAndMsgExt('直系亲属手机号不能为空');
        }
        if (empty($relation_spare) || empty($name_spare) || empty($mobile_spare)) {
            return UserExceptionExt::throwCodeAndMsgExt('其他联系人信息不能为空');
        }
        if (!isset(UserContact::$relation_two[$relation_spare])) {
            return UserExceptionExt::throwCodeAndMsgExt('请选择其他联系人关系');
        }
        $user_contact = UserContact::findOne(['user_id' => $curUser->getId()]);
        if (empty($user_contact)) {
            $user_contact = new UserContact();
            $user_contact->user_id = $curUser->getId();
        }
        $user_id = $curUser->getId();
        $user_contact->relation = $contacts_type;
        $user_contact->name = $contacts_name;
        $user_contact->mobile = $contacts_mobile;
        $user_contact->status = UserContact::STATUS_NORMAL;
        $user_contact->updated_at = time();
        $user_contact->source = UserContact::SOURCE_UPLOAD;
        $user_contact->relation_spare = $relation_spare;
        $user_contact->name_spare = $name_spare;
        $user_contact->mobile_spare = $mobile_spare;
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        if ($user_contact->save()) {
            //更新认证表
            $user_verification = UserVerification::findOne(['user_id' => $curUser->getId()]);
            if (false === $user_verification) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_LXR);
                return UserExceptionExt::throwCodeAndMsgExt('获取数据失败,请稍后再试');
            }
            if (empty($user_verification)) {
                $user_verification = new UserVerification();
                $user_verification->user_id = $curUser->getId();
                $user_verification->updated_at = time();
                $user_verification->created_at = time();
            }
            $user_verification->real_contact_status = UserVerification::VERIFICATION_CONTACT;
            $user_verification->operator_name = $curUser->getId();
            if (!$user_verification->save()) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_LXR);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            // 如果当前用户有状态等于 M版认证中的用户，更新一下用户订单状态
            $condition = sprintf("user_id=%s AND status=%d ", $curUser->getId(), UserLoanOrder::STATUS_WAIT_FOR_CONTACTS);
            $order_special = UserLoanOrder::find()->where($condition)->limit(1)->one();
            if ($order_special) {
                $order_special->status = UserLoanOrder::STATUS_CHECK;
                $order_special->save();
                RedisQueue::push([UserCreditData::CREDIT_GET_DATA_SOURCE_PREFIX, $order_special->id]);
            }
            $transaction->commit();
            UserService::delAuthStatus($user_id,UserService::USER_AUTH_TYPE_LXR);
            return [
                'code' => 0,
                'message' => '成功保存紧急联系人',
            ];
        } else {
            $transaction->rollBack();
            UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_LXR);
            return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
        }
    }

    /**
     * 获取紧急联系人
     * @name    获取紧急联系人 [creditCardGetContacts]
     * @uses    获取紧急联系人
     * @author  yuxuejin
     */
    public function actionGetContacts() {
        try {
            $curUser = Yii::$app->user->identity;
            if (empty($curUser)) {
                return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
            }
            $user_detail = UserContact::find()->where(['user_id' => $curUser->getId()])->one();
            if (false === $user_detail) {
                return UserExceptionExt::throwCodeAndMsgExt('获取数据失败,请稍后再试');
            }
            $client = Yii::$app->getRequest()->getClient();
            $appVersion = $client->appVersion;
            $special = 0;
            $url = '';
            if ($this->isFromXjk() && version_compare($appVersion, '1.3.2') > 0) {
                $user_special = UserVerification::find()->where(['user_id' => $curUser->getId()])->limit(1)->asArray()->one();
                $order_special = UserLoanOrder::find()->where(['user_id' => $curUser->getId()])->limit(1)->asArray()->one();
                if ($user_special && $order_special) {
                    if ($user_special['real_contact_status'] == 0 && $order_special['id'] != null) {
                        $special = 1;
                        $url = ApiUrl::toRouteMobile(['loan/loan-detail', 'id' => $order_special['id']]);
                    } else {
                        $special = 0;
                        $url = '';
                    }
                } else {
                    $special = 0;
                    $url = '';
                }
            }
            $urgent = array();
            $ret = UserContact::$relation_one;
            foreach ($ret as $key => $item) {
                $urgent[] = [
                    'type' => $key,
                    'name' => $item,
                ];
            }
            $spare = [];
            $ret = UserContact::$relation_two;
            foreach ($ret as $key => $item) {
                $spare[] = [
                    'type' => $key,
                    'name' => $item,
                ];
            }
            $data = [
                'lineal_relation' => $user_detail['relation'] ? $user_detail['relation'] : '',
                'lineal_name' => $user_detail['name'] ? $user_detail['name'] : '',
                'lineal_mobile' => $user_detail['mobile'] ? $user_detail['mobile'] : '',
                'other_relation' => $user_detail['relation_spare'] ? $user_detail['relation_spare'] : '',
                'other_name' => $user_detail['name_spare'] ? $user_detail['name_spare'] : '',
                'other_mobile' => $user_detail['mobile_spare'] ? $user_detail['mobile_spare'] : '',
                'lineal_list' => $urgent,
                'other_list' => $spare,
                'special' => $special,
                'url' => $url,
            ];
            return [
                'code' => 0,
                'message' => '成功获取紧急联系人',
                'data' => [
                    'item' => $data
                ],
            ];
        } catch (\Exception $e) {
            return[
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 保存工作信息
     * @name    保存工作信息 [creditCardSaveWorkInfo]
     * @uses    保存工作信息
     * @method POST
     * @param   string company_name 单位名称
     * @param   string company_address_distinct 单位所在地
     * @param   string company_address 单位地址
     * @param   string company_phone 单位电话
     * @param   string company_worktype 工作类型
     * @param   string company_period 工作时长1、一年以内，2、一到三年，3、三到五年，4、五年以上
     * @param   string longitude 单位地址经度
     * @param   string latitude  单位地址维度
     * @param   string company_payday  发薪日
     * @author  yuxuejin
     */
    public function actionSaveWorkInfo() {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();
        $company_name = trim($this->request->post('company_name', ''));
        $company_phone = trim($this->request->post('company_phone', ''));
        $company_address = trim($this->request->post('company_address', ''));
        $company_worktype = trim($this->request->post('company_worktype', ''));
        $company_payday = trim($this->request->post('company_payday', ''));

        // 添加字段信息
        if ($company_worktype && $company_worktype == UserQuotaWorkInfo::WORK_TYPE_STUDENT) {
            $params['work_type'] = $company_worktype;
            $params['user_id'] = $user_id;
            $user_quota_work_info = UserQuotaWorkInfo::saveUserQuotaWorkInfo($params);
            if (!$user_quota_work_info) {
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            $user_verification = UserVerification::saveUserVerificationInfo([
                'user_id' => $user_id,
                'real_work_status' => UserVerification::VERIFICATION_WORK,
                'operator_name' => $user_id,
            ]);
            if (!$user_verification) {
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            return [
                'code' => 0,
                'message' => '保存工作信息成功',
            ];
        }

        // 添加字段信息
        if ($company_worktype && $company_worktype == UserQuotaWorkInfo::WORK_TYPE_NO_WORK) {
            $params['work_type'] = $company_worktype;
            $params['user_id'] = $user_id;
            $user_quota_work_info = UserQuotaWorkInfo::saveUserQuotaWorkInfo($params);
            if (!$user_quota_work_info) {
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            $user_verification = UserVerification::saveUserVerificationInfo([
                'user_id' => $user_id,
                'real_work_status' => UserVerification::VERIFICATION_WORK,
                'operator_name' => $user_id,
            ]);
            if (!$user_verification) {
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            UserService::delAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
            return [
                'code' => 0,
                'message' => '保存工作信息成功',
            ];
        }

        if (empty($company_name)) {
            return UserExceptionExt::throwCodeAndMsgExt('单位名称不能为空');
        }

        if (empty($company_address)) {
            return UserExceptionExt::throwCodeAndMsgExt('单位所在地不能为空');
        }
        $work_address_distinct = trim($this->request->post('company_address_distinct', ''));
        if (empty($work_address_distinct)) {
            return UserExceptionExt::throwCodeAndMsgExt('请填写工作地址区域');
        }
        if (!ToolsUtil::checkTel($company_phone)) {
            if (!ToolsUtil::checkTelMobile($company_phone)) {
                return UserExceptionExt::throwCodeAndMsgExt('单位电话格式不合法');
            }
        }

        $transaction = UserQuotaWorkInfo::getDb()->beginTransaction();
        try {
            $params = $this->request->post();
            $params['user_id'] = $curUser->getId();
            $user_detail = UserDetail::saveUserDetail($params); //保存用户信息表
            if (!$user_detail) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            $params['work_address_distinct'] = $work_address_distinct;
            $params['work_address'] = $company_address;
            if (isset($params['company_period'])) {
                $params['entry_time_type'] = $params['company_period'];
            }
            if ($company_worktype) {
                $params['work_type'] = $params['company_worktype'];
            }
            $user_quota_work_info = UserQuotaWorkInfo::saveUserQuotaWorkInfo($params);
            if (!$user_quota_work_info) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            $user_verification = UserVerification::saveUserVerificationInfo([
                'user_id' => $user_id,
                'real_work_status' => UserVerification::VERIFICATION_WORK,
                'operator_name' => $user_id,
            ]);
            if (!$user_verification) {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
                return UserExceptionExt::throwCodeAndMsgExt('保存数据失败,请稍后再试');
            }
            $transaction->commit();
            UserService::delAuthStatus($user_id,UserService::USER_AUTH_TYPE_WORK);
            return [
                'code' => 0,
                'message' => '保存工作信息成功',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            \Yii::error($e);
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 获取工作信息
     * @name    获取工作信息 [creditCardGetWorkInfo]
     * @uses    获取工作信息
     *
     * @author  yuxuejin
     */
    public function actionGetWorkInfo() {
        try {
            $curUser = Yii::$app->user->identity;
            if (empty($curUser)) {
                return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
            }
            $user_id = $curUser->getId();
            $work_info = UserQuotaWorkInfo::find()->where(['user_id' => $user_id])->one();

            if (empty($work_info)) {
                $work_data = [
                    'company_post' => "",
                    'company_salary' => "",
                    'company_period' => "",
                    'company_address_distinct' => "",
                    'company_longitude' => "",
                    'company_latitude' => "",
                    'company_worktype' => "1",
                    'company_payday' => "",
                ];
            } else {
                $work_type_str = 1;
                if (!empty($work_info['work_type'])) {
                    $work_type_str = $work_info['work_type'];
                }
                $work_data = [
                    'company_post' => $work_info['post'],
                    'company_salary' => $work_info['salary_type'],
                    'company_period' => $work_info['entry_time_type'],
                    'company_address_distinct' => $work_info['work_address_distinct'],
                    'company_longitude' => $work_info['longitude'],
                    'company_latitude' => $work_info['latitude'],
                    'company_worktype' => $work_type_str,
                    'company_payday' => $work_info['pay_day'],
                ];
            }
            $user_detail = UserDetail::find()->where(['user_id' => $user_id])->one();
            $picture = UserProofMateria::findOneByType($user_id, UserProofMateria::TYPE_WORK_CARD);

            $entry_time_type_all = [];
            $all = UserQuotaWorkInfo::$entry_time_type;
            foreach ($all as $key => $item) {
                $entry_time_type_all[] = [
                    'entry_time_type' => $key,
                    'name' => $item,
                ];
            }

            $salary_type_all = [];
            $all = UserQuotaWorkInfo::$salary_type;
            foreach ($all as $key => $item) {
                $salary_type_all[] = [
                    'salary_type' => $key,
                    'name' => $item,
                ];
            }

            $work_type = [];
            $all = UserQuotaWorkInfo::$work_app_type;
            foreach ($all as $key => $item) {
                $work_type[] = [
                    'work_type_id' => $key,
                    'work_type' => $key,
                    'name' => $item,
                ];
            }

            // 处理发薪日
            $work_pay_day = [];
            $all_pay_day = [1, 31];
            for ($i = $all_pay_day[0]; $i <= $all_pay_day[1]; $i++) {
                array_push($work_pay_day, sprintf("%d号", $i));
            }

            // 处理
            $data = [
                'company_name' => empty($user_detail['company_name']) ? "" : $user_detail['company_name'],
                'company_post' => $work_data['company_post'],
                'company_address' => empty($user_detail['company_address']) ? "" : $user_detail['company_address'],
                'company_longitude' => empty($user_detail['company_longitude']) ? "" : $user_detail['company_longitude'],
                'company_latitude' => empty($user_detail['company_latitude']) ? "" : $user_detail['company_latitude'],
                'company_address_distinct' => $work_data['company_address_distinct'],
                'company_phone' => empty($user_detail['company_phone']) ? "" : $user_detail['company_phone'],
                'company_period' => $work_data['company_period'],
                //'company_salary' => $work_data['company_salary'],
                'company_picture' => $picture ? 1 : 0,
                //'company_salary_list' => $salary_type_all,
                'company_period_list' => $entry_time_type_all,
                'company_worktype_id' => $work_data['company_worktype'],
                'company_worktype' => $work_data['company_worktype'],
                'company_worktype_list' => $work_type,
                'company_payday' => empty($work_data['company_payday']) ? "" : $work_data['company_payday'],
                'company_payday_list' => $work_pay_day,
            ];

            return [
                'code' => 0,
                'message' => '成功获取工作信息',
                'data' => ['item' => $data],
            ];
        } catch (\Exception $e) {
            return[
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     *
     * @name    获取验证码 [creditCardGetCode]
     * @uses    用户绑定银行卡拉取验证码
     * @method  post
     * @param   string $phone 手机号
     * @author  yuxuejin
     */
    public function actionGetCode() {
        $source = $this->getSource();
        $currentUser = Yii::$app->user->identity;
        $user_id = $currentUser->getId();
        if(empty($user_id)){
            return [
                'code' => -1,
                'message' => '获取用户登录信息失败',
                'data' => []
            ];
        }

        $id_number = $currentUser->id_number;
        $name = $currentUser->name;
        if (empty($id_number) || empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        $view = UserVerification::find()->where(['user_id' => $user_id])->one();
        if (0 == $view->real_verify_status) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        $card_no = $this->request->post('card_no');
        $bank_id = $this->request->post('bank_id');
        //获得$card_no对应的卡的id
        $card_id=0;$card_phone='';
        if(!empty($card_no) && $card_no!=''){
            $card_no=trim($card_no);
            $card_no = StringHelper::trimBankCard($card_no); //消除输入的银行卡中的空格
            //有可能用户信用卡跟借记卡绑定同一张卡
            $card_info=CardInfo::find()->where(['user_id' => $user_id,'card_no'=>$card_no,'type'=>CardInfo::TYPE_DEBIT_CARD])->one();
            if($card_info){
                $card_id=$card_info->id;
                $bank_id=$card_info->bank_id;
                $card_phone=$card_info->phone;
            }
        }

        try {
            $phone = trim($this->request->post('phone'));
            $type = trim($this->request->post('type'));
            $service = Yii::$container->get('userService');
            $deviceId = isset($_REQUEST['deviceId']) ? trim($_REQUEST['deviceId']) : '';

            $ip = \common\helpers\ToolsUtil::getIp();
            if(!YII_ENV_DEV && !YII_ENV_TEST){
                $rule = ['phone' => $phone, 'ip' => $ip];
                if(!isset($type) && $type != 1 && isset($deviceId)){
                    $rule = ['phone' => $phone, 'deviceId' => $deviceId, 'ip' => $ip];
                }
                if (!Lock::lockCode(Lock::LOCK_CREDIT_CARD_GET_CODE,$rule)) {
                    \yii::warning( sprintf('device_locked [%s][%s][%s].', $ip, $phone, $deviceId), "credit.card.get.code" );
                    ErrorMessage::getMessage(0, '手机号：'.$phone.'，用户绑定银行卡拉取验证码请求过于频繁', ErrorMessage::SOURCE_BINDCARDCODE);
                    return [
                        'code' => -1,
                       'message' => '验证码请求过于频繁，请稍后再试',
                        'data' => []
                    ];
                }
            }

            //获得当前发送短信支付渠道
            $channel_id=CardInfo::HELIPAY;

            //判断当前卡号是否绑定过
            $pay_channel=CardInfo::$channel_abbreviation[$channel_id];
            if(!empty($pay_channel) && $card_id>0){
                $BindCardInfo=BindCardInfo::find()->where(['user_id'=>$user_id,'card_id'=>$card_id,'pay_channel'=>$pay_channel,'customer_status'=>BindCardInfo::CARDBIND])->one();
                if($BindCardInfo && $card_phone==$phone){
                    return [
                        'code' => -1,
                        'message' => '抱歉，该银行卡已经在合利宝绑定过',
                        'data' => []
                    ];
                }
            }

            //判断是否支持银行
            if (!empty($bank_id)){
                if(!array_key_exists($bank_id,CardInfo::$bankInfo)){
                    return [
                        'code' => -1,
                        'message' => '抱歉，该银行卡暂不支持绑定'
                    ];
                }
            }

            //判断用户的银行卡是否有效
            $card_info = JshbService::cardBin($card_no,$bank_id);
            if (isset($card_info['code']) && (0 != $card_info['code'])) {
                ErrorMessage::getMessage($user_id, '银行卡验证失败[卡号是否正确]，bank_id：'.$bank_id.'，卡号：'.$card_no."，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
                $message = "请检查银行卡号是否正确";
                return [
                    'code' => -1,
                    'message' => $message,
                    'data' => []
                ];
            }

            if(isset($card_info['data']['card_type']) && $card_info['data']['card_type'] != 1){
                $message = "请检查银行卡是否为储蓄卡";
                \yii::warning( sprintf('uid_bind_credit_card %s, %s, %s',$user_id, json_encode($card_no), json_encode($card_info)), LogChannel::USER_CARD);
                ErrorMessage::getMessage($user_id, '银行卡验证失败[卡片类型]，bank_id：'.$bank_id.'，卡号：'.$card_no."，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
                return [
                    'code' => -1,
                    'message' => $message,
                    'data' => []
                ];
            }

            if ($bank_id != $card_info['data']['bank_id']) {
                ErrorMessage::getMessage($user_id, '银行卡验证失败[bank_id有误：输入的'.$bank_id.'，返回的'.$card_info['data']['bank_id'].']，卡号：'.$card_no."，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
                return [
                    'code' => -1,
                    'message' => '请选择正确的银行',
                    'data' => []
                ];
            }

            $sms_tips=HELIPAYTIPS;
            //第三方支付来发短信验证码
            $data = [
                // 业务参数
                'name'         => (string)$name,
                'phone'        => (string)$phone,
                'id_card_no'   => (string)$id_number,
                'bank_card_no' => (string)$card_no,
                'bank_id'      => (string)$bank_id,
                'channel_id'   => (string)$channel_id
            ];
            $service = Yii::$container->get('JshbService');
            $sms_result=$service->payAuthSmsCode($data);
            if(!$sms_result || $sms_result['code'] == '0'){
                //redis数量加1
                $key="band_card_channel_{$user_id}";
                //过期时间
                $expire=strtotime(date("Ymd")) + 3600*24 - time();
                RedisQueue::set(['expire'=>$expire,'key'=>$key,'value'=>json_encode([$channel_id,1])]);
                $is_send_sms=true;
            }else{
                $message='抱歉，获取短信验证码失败';
                if(isset($sms_result['message'])){
                    $message=$sms_result['message'];
                }
                return [
                    'code' => -1,
                    'message' => $message
                ];
            }

            if ($is_send_sms) {
                $bank_list = CardInfo::getCardConfigList();
                return [
                    'code' => 0,
                    'message' => '成功获取验证码',
                    'data' => ['item' => []],
                    'bank_list' => $bank_list,
                    'sms_tips'  => $sms_tips
                ];
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('发送验证码失败，请稍后再试');
            }
        } catch (\Exception $e) {
            ErrorMessage::getMessage(0, '手机号：'.$phone.'，用户绑定银行卡拉取验证码异常：'.$e->getMessage(), ErrorMessage::SOURCE_BINDCARDCODE);
            return UserExceptionExt::throwCodeAndMsgExt($e->getMessage());
        }
    }

    /**
     * 添加银行卡信息
     *
     * @name 添加银行卡信息 [creditCardAddBankCard]
     * @method post
     * @param string $card_no 还款卡号
     * @param string $bank_id 银行id
     * @param string $phone 手机号
     * @param string $code 验证码
     * @author  yuxuejin
     */
    public function actionAddBankCard() {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        //绑定注册类型时间
        $verification = UserVerification::saveUserVerificationInfo([
            'user_id' => $user_id,
            'real_verify_status' => UserVerification::VERIFICATION_VERIFY,
            'operator_name' => $user_id,
        ]);

        $user_id = $curUser->getId();
        $id_number = $curUser->id_number;
        $name = $curUser->name;
        if (empty($id_number) || empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        $view = UserVerification::find()->where(['user_id' => $user_id])->one();
        if (0 == $view->real_verify_status) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        $bank_id = intval($this->request->post('bank_id'));
        if (empty($bank_id)) {
            return UserExceptionExt::throwCodeAndMsgExt("缺少 bank_id 参数");
        }

        $card_no = trim($this->request->post('card_no'));
        $card_no = StringHelper::trimBankCard($card_no); //消除输入的银行卡中的空格
//        $card_info = KoudaiSoa::instance('BankCard')->cardBin($card_no);
//        $card_info = [
//            'code' => 0,
//            'data' => [
//                'card_type' => 1,
//                'bank_id' => $bank_id
//            ]
//        ];
        $card_info = JshbService::cardBin($card_no,$bank_id);

        // --------------------
        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            ErrorMessage::getMessage($user_id, '银行卡验证失败[卡号是否正确]，bank_id：'.$bank_id.'，卡号：'.$card_no."，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
            $message = "请检查银行卡号是否正确";
            return UserExceptionExt::throwCodeAndMsgExt($message);
        }

        if(isset($card_info['data']['card_type']) && $card_info['data']['card_type'] != 1){
            $message = "卡片类型错误";
            \yii::warning( sprintf('uid_bind_credit_card %s, %s, %s',
                $curUser->id, json_encode($card_no), json_encode($card_info)
            ), LogChannel::USER_CARD );
            ErrorMessage::getMessage($user_id, '银行卡验证失败[卡片类型]，bank_id：'.$bank_id.'，卡号：'.$card_no."，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
            return UserExceptionExt::throwCodeAndMsgExt($message);
        }

        if ($bank_id != $card_info['data']['bank_id']) {
            ErrorMessage::getMessage($user_id, '银行卡验证失败[bank_id有误：输入的'.$bank_id.'，返回的'.$card_info['data']['bank_id'].']，卡号：'.$card_no."，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
            return UserExceptionExt::throwCodeAndMsgExt("请选择正确的银行");
        }

        $phone = trim($this->request->post('phone'));
        if (empty($phone)) {
            return UserExceptionExt::throwCodeAndMsgExt("请填写手机号码");
        }
        $code = trim($this->request->post('code'));
        $source = $this->getSource();
        //判断绑卡鉴权验证码谁来发发送
        $key="band_card_channel_{$user_id}";
        $channel=RedisQueue::get(['key'=>$key]);
        $channel_id='';
        if(!empty($channel)){
            $channel=json_decode($channel,true);
            $channel_id=$channel[0];
        }
        if(empty($channel_id) || $channel_id==''){
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        }

        if(!array_key_exists($bank_id,CardInfo::$bankInfo)){
            return UserExceptionExt::throwCodeAndMsgExt("请选择正确的银行");
        }

        $check = CardInfo::checkCardIsUsed($card_no, $source);
        if ($check) {
            if (count($check) == 1 && $check[0] == $user_id) {
                $cardService = Yii::$container->get('cardService');
                $card_record = CardInfo::findOne([
                    'user_id' => $user_id,
                    'card_no' => $card_no,
                    'type' => CardInfo::TYPE_DEBIT_CARD,
                ]);
                if ($card_record) {
                    return $cardService->switchCard($curUser, $card_record, $card_record, [], $phone, $source);
                }
            }

            return UserExceptionExt::throwCodeAndMsgExt("对不起，该银行卡已被绑定过");
        }

        $find = $this->userService->getMainCardInfo($user_id);
        if ($find) {
            return UserExceptionExt::throwCodeAndMsgExt("对不起，您只能绑定一张借记卡");
        }

        //验证银行卡
//        $ip = $this->request->getUserIP();
//        $card_info = KoudaiSoa::instance('BankCard')->cardVerify($card_no, $phone, $id_number, $name, ['client_ip' => $ip]);
        $card_info = JshbService::cardQuickVerify($card_no, $phone, $id_number, $name ,$bank_id ,$channel_id ,$code);
        //----------
        if (false == $card_info) {
            ErrorMessage::getMessage($user_id, "银行卡四要素鉴权失败，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
            return UserExceptionExt::throwCodeAndMsgExt("数据有误，验证银行卡类型失败");
        }

        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            ErrorMessage::getMessage($user_id, "银行卡四要素鉴权失败，返回：".json_encode($card_info), ErrorMessage::SOURCE_CHECKCARD);
            $message = str_replace("口袋", "手机信用卡", $card_info['message']);
            $message = str_replace("口袋理财", "手机信用卡", $message);
            return UserExceptionExt::throwCodeAndMsgExt($message);
        }

//        //签约绑卡，不需要之前银生宝支付需要绑卡签约，不用银行卡四要素鉴权
//        $data = [
//            // 业务参数
//            'name'         => (string)$name,
//            'phone'        => (string)$phone,
//            'id_card_no'   => (string)$id_number,
//            'bank_card_no' => (string)$card_no,
//            'bank_id'      => (string)$bank_id,
//        ];
//        $service = Yii::$container->get('JshbService');
//        $service->preSignNew($data);

        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $info = new CardInfo();
            $info->user_id = $curUser->id;
            $info->bank_id = $bank_id;
            $info->bank_name = BankConfig::$bankInfo[$bank_id];
            $info->card_no = $card_no;
            $info->type = 2;
            $info->phone = $phone;
            $info->status = CardInfo::STATUS_SUCCESS;
            //现在只能绑卡一次，默认设置主卡
            $info->main_card = CardInfo::MAIN_CARD;
            $info->source_id = $source;
            $verify_info = UserVerification::find()->where(['user_id' => $curUser->id])->one();
            if (empty($verify_info)) {
                $verify_info = new UserVerification();
                $verify_info->user_id = $curUser->id;
                $verify_info->real_bind_bank_card_status = UserVerification::VERIFICATION_BIND_BANK_CARD;
            } else {
                $verify_info->real_bind_bank_card_status = UserVerification::VERIFICATION_BIND_BANK_CARD;
            }

            $bind_card_info = new BindCardInfo();
            $bind_card_info->user_id = $user_id;
            $bind_card_info->result = 1;
            $bind_card_info->pay_channel = CardInfo::$channel_abbreviation[$channel_id];
            $bind_card_info->customer_status = 'bind';
            $bind_card_info->payment_sign_status = '01';
            $bind_card_info->bank_sign_status = '00';
            $bind_card_info->created_at=time();
            $bind_card_info->updated_at=time();

            if ($info->validate() && $verify_info->validate()) {
                if ($info->save() && $verify_info->save()) {
                    $bind_card_info->card_id = $info->id;
                    if(!$bind_card_info->save()){
                        $transaction->rollBack();
                        UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_CARD);
                        return UserExceptionExt::throwCodeAndMsgExt("银行卡状态保存失败");
                    }
                    $transaction->commit();

                    //删除绑卡redis中缓存
                    $key="band_card_channel_{$user_id}";
                    RedisQueue::del(['key'=>$key]);

                    UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_BIND_BANK_CARD]);
                    $data = [];
                    $baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
                    $data[] = [
                        'card_id' => $info->id,
                        'url' => $baseUrl . "/image/bank/bank_" . $bank_id . ".png",
                        'bank_info' => BankConfig::$bankInfo[$bank_id] . CardInfo::$type[$info->type] . " 尾号" . substr($card_no, -4),
                        'main_card' => $info->main_card,
                    ];

                    //事件处理队列    绑卡成功
                    RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                        'event_name' => AppEventService::EVENT_SUCCESS_BIND_CARD,
                        'params' => ['user_id' => $user_id, 'from_app' => Util::t('from_app')],
                    ])]);
                    UserService::delAuthStatus($user_id,UserService::USER_AUTH_TYPE_CARD);
                    return [
                        'code' => 0,
                        'message' => '绑定银行卡成功',
                        'data' => [
                            'item' => $data
                        ]
                    ];
                }
            } else {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_CARD);
                return UserExceptionExt::throwCodeAndMsgExt("银行卡状态保存失败");
            }
        } catch (\Exception $e) {
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 添加银行卡信息
     *
     * @name 添加信用卡银行卡信息 [creditCardAddBankCard]
     * @method post
     * @param string $card_no 还款卡号
     * @param string $bank_id 银行id
     * @param string $phone 手机号
     * @param string $code 验证码
     * @author  yuxuejin
     */
    public function actionAddCreditBankCard() {
        $curUser = Yii::$app->user->identity;
        $user_id = $curUser->getId();
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        //绑定注册类型时间
        $verification = UserVerification::saveUserVerificationInfo([
            'user_id' => $user_id,
            'real_verify_status' => UserVerification::VERIFICATION_VERIFY,
            'operator_name' => $user_id,
        ]);

        $user_id = $curUser->getId();
        $id_number = $curUser->id_number;
        $name = $curUser->name;
        if (empty($id_number) || empty($name)) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        $view = UserVerification::find()->where(['user_id' => $user_id])->one();
        if (0 == $view->real_verify_status) {
            return UserExceptionExt::throwCodeAndMsgExt("您还没有实名认证");
        }

        $bank_id = intval($this->request->post('bank_id'));
        if (empty($bank_id)) {
            return UserExceptionExt::throwCodeAndMsgExt("缺少 bank_id 参数");
        }

        $card_no = trim($this->request->post('card_no'));
        $card_no = StringHelper::trimBankCard($card_no); //消除输入的银行卡中的空格
        // TODO:alexding 临时关闭绑卡
        //$card_info = KoudaiSoa::instance('BankCard')->cardBin($card_no);
        $card_info = [
            'code' => 0,
            'data' => [
                'card_type' => 1,
                'bank_id' => $bank_id
            ]
        ];
        // --------------------
        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            $message = "请检查银行卡号是否正确";
            return UserExceptionExt::throwCodeAndMsgExt($message);
        }

        if(isset($card_info['data']['card_type']) && $card_info['data']['card_type'] != 1){
            $message = "卡片类型错误";
            \yii::warning( sprintf('uid_bind_credit_card %s, %s, %s',
                $curUser->id, json_encode($card_no), json_encode($card_info)
            ), LogChannel::USER_CARD );
            return UserExceptionExt::throwCodeAndMsgExt($message);
        }

        if ($bank_id != $card_info['data']['bank_id']) {
            return UserExceptionExt::throwCodeAndMsgExt("请选择正确的银行");
        }

        $phone = trim($this->request->post('phone'));
        if (empty($phone)) {
            return UserExceptionExt::throwCodeAndMsgExt("请填写手机号码");
        }
        $code = trim($this->request->post('code'));
        $source = $this->getSource();
        if (!UserCaptcha::validateCaptcha($phone, $code, UserCaptcha::TYPE_BIND_BANK_CARD, $source)) {
            return UserExceptionExt::throwCodeAndMsgExt('验证码错误或已过期');
        }

        $check = CardInfo::checkCardIsUsed($card_no, $source);
        if ($check) {
            if (count($check) == 1 && $check[0] == $user_id) {
                $cardService = Yii::$container->get('cardService');
                $card_record = CardInfo::findOne([
                    'user_id' => $user_id,
                    'card_no' => $card_no,
                    'type' => CardInfo::TYPE_CREDIT_CARD,
                ]);
                if ($card_record) {
                    return $cardService->switchCard($curUser, $card_record, $card_record, [], $phone, $source);
                }
            }

            return UserExceptionExt::throwCodeAndMsgExt("对不起，该银行卡已被绑定过");
        }

        $find = $this->userService->getMainCreditCardInfo($user_id);
        if ($find) {
            return UserExceptionExt::throwCodeAndMsgExt("对不起，您只能绑定一张信用卡");
        }

        //验证银行卡
        $ip = $this->request->getUserIP();
        // TODO:alexding 临时关闭绑卡
        // $card_info = KoudaiSoa::instance('BankCard')->cardVerify($card_no, $phone, $id_number, $name, ['client_ip' => $ip]);
        $card_info = [
            'code' => 0
        ];
        //----------
        if (false == $card_info) {
            return UserExceptionExt::throwCodeAndMsgExt("数据有误，验证银行卡类型失败");
        }

        if (isset($card_info['code']) && (0 != $card_info['code'])) {
            $message = str_replace("口袋", "手机信用卡", $card_info['message']);
            $message = str_replace("口袋理财", "手机信用卡", $message);
            return UserExceptionExt::throwCodeAndMsgExt($message);
        }
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        try {
            $info = new CardInfo();
            $info->user_id = $curUser->id;
            $info->bank_id = $bank_id;
            $info->bank_name = BankConfig::$bankInfo[$bank_id];
            $info->card_no = $card_no;
            $info->type = 1;
            $info->phone = $phone;
            $info->status = CardInfo::STATUS_SUCCESS;
            //现在只能绑卡一次，默认设置主卡
            $info->main_card = CardInfo::MAIN_CARD_NO;
            $info->source_id = $source;
            if ($info->validate()) {
                $result=false;
                //判断是否有过信用卡
                $res = $this->userService->getCreditCardInfo($user_id);
                if(!empty($res)){
                    $result=CardInfo::updateAll(['bank_id' => $bank_id,'bank_name'=>BankConfig::$bankInfo[$bank_id],'card_no'=>$card_no,'phone'=>$phone,'updated_at'=>time()], 'user_id=' . (int)$user_id . ' and type=1 and source_id= ' . $source);
                }else{
                    $result=$info->save();
                }
                if ($result) {
                    $transaction->commit();
                    UserCaptcha::deleteAll(['phone' => $phone, 'type' => UserCaptcha::TYPE_BIND_BANK_CARD]);
                    $data = [];
                    $baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
                    $data[] = [
                        'card_id' => $info->id,
                        'url' => $baseUrl . "/image/bank/bank_" . $bank_id . ".png",
                        'bank_info' => BankConfig::$bankInfo[$bank_id] . CardInfo::$type[$info->type] . " 尾号" . substr($card_no, -4),
                        'main_card' => $info->main_card,
                    ];

                    //事件处理队列    绑卡成功
                    RedisQueue::push([RedisQueue::LIST_APP_EVENT_MESSAGE, json_encode([
                        'event_name' => AppEventService::EVENT_SUCCESS_BIND_CARD,
                        'params' => ['user_id' => $user_id, 'from_app' => Util::t('from_app')],
                    ])]);
                    UserService::delAuthStatus($user_id,UserService::USER_AUTH_TYPE_CARD);
                    return [
                        'code' => 0,
                        'message' => '绑定银行卡成功',
                        'data' => [
                            'item' => $data
                        ]
                    ];
                }
            } else {
                $transaction->rollBack();
                UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_CARD);
                return UserExceptionExt::throwCodeAndMsgExt("银行卡状态保存失败");
            }
        } catch (\Exception $e) {
            return [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取用户银行卡信息
     *
     * @name 获取用户银行卡信息 [creditCardGetBankCard]
     * @method post
     * @author yuxuejin
     */
    public function actionGetBankCard() {
        $user_id = Yii::$app->user->identity->id;
        $res = $this->userService->getCardInfo($user_id);
        return [
            'code' => 0,
            'message' => '成功获取银行卡信息',
            'data' => [
                'item' => $res
            ]
        ];
    }

    /**
     * 获取用户信用卡银行卡信息
     *
     * @name 获取用户信用卡银行卡信息 [creditCardGetBankCard]
     * @method post
     * @author yuxuejin
     */
    public function actionGetCreditBankCard() {
        $baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
        //获得信用卡（银行卡）
        $user_id = Yii::$app->user->identity->id;
        $res = $this->userService->getCreditCardInfo($user_id);
        $data=array();
        if(!empty($res)){
            $res=$res[0];
            $res['card_type']=CardInfo::TYPE_CREDIT_CARD;
            $url=$baseUrl . "/image/bank/bank_" . $res['bank_id'] . ".png";
            $res['url']=$url;
        }else{
            $res['card_type']=CardInfo::TYPE_CREDIT_CARD;
            $res['card_id'] = null;
            $res['bank_id'] = null;
            $res['bank_name'] = null;
            $res['card_no'] = null;
            $res['main_card'] = null;
            $res['phone'] = null;
            $res['card_no_end'] = null;
            $res['url']=null;
        }
        $data[]=$res;

        //获得借记卡（储蓄卡）
        $res = $this->userService->getCardInfo($user_id,1);
        if(!empty($res)){
            $res=$res[0];
            $res['card_type']=CardInfo::TYPE_DEBIT_CARD;
            $url=$baseUrl . "/image/bank/bank_" . $res['bank_id'] . ".png";
            $res['url']=$url;
        }else{
            $res['card_type']=CardInfo::TYPE_DEBIT_CARD;
            $res['card_id'] = null;
            $res['bank_id'] = null;
            $res['bank_name'] = null;
            $res['card_no'] = null;
            $res['main_card'] = null;
            $res['phone'] = null;
            $res['card_no_end'] = null;
            $res['url']=null;
        }
        $data[]=$res;
        return [
            'code' => 0,
            'message' => '成功获取银行卡信息',
            'data' => [
                'item' => $data
            ]
        ];
    }

    /**
     * 获取银行卡列表信息
     *
     * @name 获取银行卡列表信息 [creditCardBankCardInfo]
     * @method post
     * @author yuxuejin
     */
    public function actionBankCardInfo() {
        try {
            $info = CardInfo::getCardConfigList();
            return [
                'code' => 0,
                'message' => '成功获取',
                'data' => [
                    'item' => $info,
                    'tips' => "由于邮政储蓄银行不支持还款代扣，建议优先选择其他银行卡。",
                    'smstips' => HELIPAYTIPS
                ]
            ];
        } catch (\Exception $e) {
            return[
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 保存我的更多页面信息
     *
     * @name 保存我的更多页面信息 [creditCardSaveMoreInfo]
     * @method post
     * @param string $taobao 淘宝账号
     * @param string $mail 常用邮箱
     * @param string $qq QQ账号
     * @param string $wx 微信账号
     * @author yuxuejin
     */
    public function actionSaveMoreInfo() {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }
        $user_id = $curUser->getId();
        $taobao = trim($this->request->post('taobao'));
        $mail = trim($this->request->post('mail'));
        if ($mail != "") {
            if (!preg_match("/^[0-9a-zA-Z]+@(([0-9a-zA-Z]+)[.])+[a-z]{2,4}$/i", $mail)) {
                return UserExceptionExt::throwCodeAndMsgExt("邮箱不合法");
            }
        }
        $qq = trim($this->request->post('qq'));
        if ($qq != "") {
            if (!ToolsUtil::checkNum($qq)) {
                return UserExceptionExt::throwCodeAndMsgExt('QQ账号不合法');
            }
        }
        $wx = trim($this->request->post('wx'));
        if (!isset($taobao) && !isset($mail) && !isset($qq) && !isset($wx)) {
            return UserExceptionExt::throwCodeAndMsgExt("不能保存空信息");
        }
        $transaction = Yii::$app->db_kdkj->beginTransaction();
        $user_quota_more_info = UserQuotaMoreInfo::find()->where(['user_id' => $user_id])->one();
        if (empty($user_quota_more_info)) {
            $user_quota_more_info = new UserQuotaMoreInfo();
            $user_quota_more_info->user_id = $user_id;
        }
        $user_quota_more_info->taobao = $taobao;
        $user_quota_more_info->mail = $mail;
        $user_quota_more_info->qq = $qq;
        $user_quota_more_info->wechat = $wx;
        if (!$user_quota_more_info->save()) {
            $transaction->rollBack();
            UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_MORE);
            return [
                'code' => -1,
                'message' => '保存信息失败',
            ];
        }
        $user_verification = UserVerification::find()->where(['user_id' => $user_id])->one();
        if (empty($user_verification)) {
            $user_verification = new UserVerification();
            $user_verification->updated_at = time();
            $user_verification->created_at = time();
            $user_verification->user_id = $user_id;
            $user_verification->real_more_status = 1;
        } else {
            $user_verification->real_more_status = 1;
        }
        if (!$user_verification->save()) {
            $transaction->rollBack();
            UserService::saveAuthStatus($user_id,UserService::USER_AUTH_TYPE_MORE);
            return [
                'code' => -1,
                'message' => '保存信息失败',
            ];
        }

        $transaction->commit();
        UserService::delAuthStatus($user_id,UserService::USER_AUTH_TYPE_MORE);
        return [
            'code' => 0,
            'message' => '保存信息成功',
        ];
    }

    /**
     * 获取我的更多页面信息
     *
     * @name 获取我的更多页面信息 [creditCardGetMoreInfo]
     * @method post
     * @author yuxuejin
     */
    public function actionGetMoreInfo() {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return UserExceptionExt::throwCodeAndMsgExt(CodeException::$code[CodeException::LOGIN_DISABLED], ['code' => CodeException::LOGIN_DISABLED]);
        }

        $user_id = $curUser->getId();
        $user_quota_more_info = UserQuotaMoreInfo::findOne(['user_id' => $user_id]);
        if (empty($user_quota_more_info)) {
            return UserExceptionExt::throwCodeAndMsgExt("获取信息失败");
        }

        return [
            'code' => 0,
            'message' => '获取信息成功',
            'data' => [
                'user_id' => $user_id,
                'qq' => empty($user_quota_more_info->qq) ? '' : $user_quota_more_info->qq,
                'wx' => empty($user_quota_more_info->wechat) ? '' : $user_quota_more_info->wechat,
                'taobao' => empty($user_quota_more_info->taobao) ? '' : $user_quota_more_info->taobao,
                'mail' => empty($user_quota_more_info->mail) ? '' : $user_quota_more_info->mail,
            ],
        ];
    }

    /**
     * [开发调试]获取公积金登录方式列表
     */
    public function actionHouseFundLoginMethodsDev() {
        if (YII_ENV_PROD) {
            throw new NotFoundHttpException('not found');
        }

        \yii::$app->response->format = Response::FORMAT_JSON;

        $service = \Yii::$app->jxlService;
        $methods = $service->getCitysLoginMethods();
        if ($methods) {
            return [
                'code' => 0,
                'message' => '获取信息成功',
                'data' => [
                    'city_info' => $methods,
                ],
            ];
        }
        return [
            'code' => -1,
            'message' => '获取信息失败',
        ];
    }

    /**
     * 获取公积金登录方式列表
     */
    public function actionHouseFundLoginMethods() {
        \yii::$app->response->format = Response::FORMAT_JSON;

        $service = \Yii::$app->jxlService;
        $methods = $service->getHouseFundMethods();
        if ($methods) {
            return [
                'code' => 0,
                'message' => '获取信息成功',
                'data' => [
                    'city_info' => $methods,
                    'user_name' => \yii::$app->user->identity->name,
                    'id_card_num' => \yii::$app->user->identity->id_number,
                ],
            ];
        }

        return [
            'code' => -1,
            'message' => '获取信息失败',
        ];
    }

    /**@name 获取认证状态
     * @return array
     */
    public function actionGetVerification(){
        $data['type_list'] = '';
        $id = Yii::$app->user->identity;
        $baseUrl = $this->request->getHostInfo() ;
        $data['url'] = $baseUrl.Url::to(['credit-web/verification-jxl']);
        $user_verification = UserVerification::findOne(['user_id'=>$id->getId()]);
        $str = '';
        if($user_verification){
            if($user_verification->real_verify_status == 0){$str .= UserVerification::TYPE_PERSON_INFO_STATUS.",";}
            if($user_verification->real_contact_status == 0){$str .= UserVerification::TYPE_PERSON_CONTACT_STATUS.",";}
            if($user_verification->real_jxl_status == 0){$str .= UserVerification::TYPE_PERSON_JXL_STATUS.",";}
            if($user_verification->real_zmxy_status == 0){$str .= UserVerification::TYPE_PERSON_ZML_STATUS.",";}
            if($user_verification->real_bind_bank_card_status == 0){$str .= UserVerification::TYPE_PERSON_CARD_STATUS;}
            $data['type_list'] = $str;
        }
        return [
            'code' => 0,
            'message' => '认证流程',
            'data'=>$data,
        ];
    }


    private function UserCreditTop()
    {
        $curUser = Yii::$app->user->identity;
        if (empty($curUser)) {
            return [
                "code" => CodeException::LOGIN_DISABLED,
                "message" => CodeException::$code[CodeException::LOGIN_DISABLED]
            ];
        }

        $user_id = $curUser->getId();

        $card_type = trim($this->request->post('type'));
        if ($card_type == 1) {
            Yii::warning(\sprintf("%s : %s", $user_id, json_encode($this->getRequest()->get())), 'golden_show');
            $card_type = 0;
        }

        $footer = [
            "title" => "额度计算中...",
            "status" => 1,
            "card_type" => $card_type == 1 ? 2 : 1,
        ];

        // 加认证锁
        if (UserCreditDetail::lockUserCreditRecord($user_id)) {
            $userService = Yii::$container->get('userService');
            $card_detail_info = $userService->getCreditDetail($user_id);

            if ($card_type == 1) { // 1. 金卡(目前不用)
                $card_detail_info->card_golden = UserCreditDetail::CARD_GOLDEN_ING;
                $card_detail_info->golden_show = 1;
                $card_detail_info->save();
                // 添加
                CardQualificationService::checkUserCardQualification($user_id, 0);
                $footer = [
                    "title" => "发薪卡开通中...",
                    "status" => 1,
                    "card_type" => 2,
                ];

                // RedisQueue::push([RedisQueue::LIST_CHECK_CARD_QUALIFICATION, json_encode([ 'user_id' => $user_id, 'type' => 0 ])]);
            }
            else { // 2. 白卡
                if (UserCreditDetail::STATUS_ING != $card_detail_info->credit_status) {
                    $card_detail_info->credit_status = UserCreditDetail::STATUS_ING;
                    $card_detail_info->credit_total += 1;
                    $card_detail_info->save();
                    CreditLineService::checkUserCreditLines($user_id);
                }
            }
        }

        $header = [
            "status" => 2,
            "title" => "额度计算中，预计需要1分钟，请耐心等待",
            "data" => "认证中",
            "active_url" => "",
            "active_title" => "我的额度",
        ];

        // 处理区分 开卡 ，提额 ，升级发薪卡
        $message = "";
        // 升级发薪卡
        if ($card_type == 1) {
            $message = "亲，正在努力为您开通发薪卡！此过程预计需要1分钟，请您耐心等待哦！";
            $data = [
                "message" => $message,
                "footer" => $footer
            ]; // 提额认证
        }
        elseif ($card_type == 2) {
            $data = [
                "message" => $message,
                "header" => $header,
            ]; // 开卡
        }
        else {
            $message = "亲，正在努力为您计算额度！此过程预计需要1分钟，请您耐心等待哦！";
            $data = [
                "message" => $message,
                "header" => $header,
                "footer" => $footer
            ];
        }

        return [
            "code" => 0,
            "message" => $message,
            "data" => $data
        ];
    }
    /**
     * 获取认证流程
     * @Author    Captain.D.Y
     * @DateTime  2017-09-12
     * @return    [type]       [description]
     */
    public function actionGetCerificationProcess(){

        $loginUid = Yii::$app->user->identity->getId();

        $select = [
            "real_verify_status", "real_work_status", "real_contact_status", "real_bind_bank_card_status",
            "real_credit_card_status", "real_jxl_status", "real_zmxy_status", "real_credit_status"
        ];
        $userVerify = UserVerification::find()
            ->select($select)
            ->where(["user_id" => $loginUid])
            ->asArray()
            ->one();
        //var_dump($userVerify);die;
        foreach ( $select as $key ){
            if(//失败下返回1
                (isset($userVerify[$key]) && $userVerify[$key] == 1 && $key == 'real_verify_status' && UserService::getAuthStatus($loginUid,UserService::USER_AUTH_TYPE_INFO) != 1 )||
                (isset($userVerify[$key]) && $userVerify[$key] == 1 && $key == 'real_contact_status' && UserService::getAuthStatus($loginUid,UserService::USER_AUTH_TYPE_LXR) != 1 )||
                (isset($userVerify[$key])  && $key == 'real_jxl_status' && (JxlService::getJxlQueryStatus($loginUid) == 1||JxlService::getJxlQueryStatus($loginUid) == 2 )) ||
                (isset($userVerify[$key]) && $userVerify[$key] == 1 && $key == 'real_zmxy_status' && UserService::getAuthStatus($loginUid,UserService::USER_AUTH_TYPE_ZM) != 1 )||
                (isset($userVerify[$key]) && $userVerify[$key] == 1 && $key == 'real_bind_bank_card_status' && UserService::getAuthStatus($loginUid,UserService::USER_AUTH_TYPE_CARD) != 1)
            )
            {
                $userVerify[$key] = 1;
            }else{
                $userVerify[$key] = isset( $userVerify[$key] ) ? intval($userVerify[$key]) : 0;
            }
        }
        # 身份证正反面
        $user_img_font = UserProofMateria::find()->select(['url'])->where(['user_id'=>$loginUid,'type'=>UserProofMateria::TYPE_ID_CAR_Z])->orderBy('id desc')->one();
        $user_img_back = UserProofMateria::find()->select(['url'])->where(['user_id'=>$loginUid,'type'=>UserProofMateria::TYPE_ID_CAR_F])->orderBy('id desc')->one();

        $userVerify["idcard_verify_status"] = (empty( $user_img_font ) || empty($user_img_back)) ? 0 : 1;

        $data = [
            "status"    => $userVerify,
            "url"       => Yii::$app->request->getHostInfo() . Url::to(['credit-web/verification-jxl'])
        ];


        return [
            "code"      => 0,
            "message"   => "认证流程",
            "data"      => $data
        ];
    }
}
