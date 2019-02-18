<!DOCTYPE html>
<html>
    <?php

    use yii\helpers\Url;
    use yii\helpers\Html;

$baseUrl = Yii::$app->getRequest()->getAbsoluteBaseUrl();
    ?>
    <head>
        <meta charset="UTF-8">
        <title>邀请好友活动</title>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/flexible.js"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/jquery.js?v=2016112901"></script>
        <script src="<?php echo $this->absBaseUrl; ?>/credit/js/common.js?=2016120903"></script>
        <link href="<?= $this->staticUrl('css/act/style.css?v=2016112803'); ?>" rel="stylesheet" />
    </head>
    <body>
        <style type="text/css">
            body {
                background-color: #fbca04;
            }
        </style>
        <div class="rebate-rule-body">
            <h3>活动规则</h3>
            <div class="content">
                <p>1、本次活动福利仅针对2016年12月1日00：00-2017年1月5日23：59：59建立邀请关系的用户。</p>
                <p>2、被邀请人在建立邀请关系后的30天内首次申请借款即可获得奖励；超过30天申请，奖金作废。</p>
                <p>3、<?php echo APP_NAMES;?>注册用户点击“马上邀请”按钮发起邀请，通过邀请链接成功邀请好友即可获得丰厚奖励。</p>
                <p>4、关于奖励：<br> 
                    邀请人奖励 <br>a、被邀请人申请借款，邀请人即可获得3元现金，多邀多得，上不封顶。 <br>b、被邀请人首次放款成功，邀请人即可随机获得最高100元的现金奖励 <br>
                    <span>保底至少可获得现金奖励 = 被邀请人放款金额*1%</span>
                    <span>最高可获得100元的现金奖励</span>
                    被邀请人奖励<br>
                    通过邀请链接注册的新用户可获得一张10元还款抵用券，首单还款时抵扣，券有效期20天，过期无效。
                </p>
                <p>5、关于提现：<br>
                    •  每周（每个自然周）可申请一次现金提现；<br> 
                    •  累计满30元才可以提现，且只能全额提取；<br>
                    •  处于逾期状态中的用户需先还款才可以提现<br>
                    •  奖金将在提现申请提交3个工作日内发放到您绑定的银行卡上，
                    请及时绑定您的银行卡。</p>
                <p>6、如果邀请人在<?php echo APP_NAMES;?>平台出于逾期状态，需先还款才有资格参与，逾期超过31天，则取消活动资格。</p>
                <p>7、关于查看奖励：<br>
                    邀请人可通过登录app进入活动链接" 我的奖金 "中查看，也可至" 我的 "->"我的奖金"中查看<br>
                    被邀请人获得的还款抵用券可至 "我的" -> "我的优惠" 中查看。</p>
                <p>8、用户通过欺骗、造假等非法手段参与活动的，将取消活动资格，并追究相应的法律责任。</p>
                <p>9、活动解释权归<?php echo APP_NAMES;?>平台所有，客服电话：<a href="javascript:callPhoneMehtod('400-681-2016')" >400-681-2016</a></p>
                <p>10、首次申请借款的新用户还享受<?php echo APP_NAMES;?>全国首创的 "拒就陪"活动；申请借款成功通过审核放款20分钟到账，如果审核被拒绝，还将获得最高50元现金赔偿。<a href="https://qbcredit.wzdai.com/credit-web/event-details-page" >拒就赔活动详情点击</a></p>
            </div>
        </div>
    </body>

</html>
