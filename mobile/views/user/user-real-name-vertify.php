<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->absBaseUrl; ?>/css/style.css?v=20150601101">
<script type="text/javascript" src="<?php echo $this->absBaseUrl; ?>/js/data.js?v=2015101601" ></script>
<div id="real_name_wraper">
    <div class="padding _666 em__9" id="tips">为保障您的资金安全，投资前请先认证您的真实姓名和身份证号</div>
    <input class="padding em_1" id="realname" type="text" placeholder="请输入真实姓名"/>
    <input class="padding em_1" id="identity_number" type="text" maxlength="18" placeholder="请输入身份证号"/>
    <div class="padding fd5457 em__8" id="msg">&nbsp;&nbsp;</div>
    <div class="padding">
        <div class="fff em_1 a_center _cursor _b_radius" id="btn">下一步</div>
        <?php if(Yii::$app->request->get('fund') !='fund' && (Yii::$app->request->get('fund') !='loan')){ ?>
        <p class="lh_em_2_5 _666 em__9">完成实名后可获得<span class="fd5457">100</span>元礼包</p>
        <?php }?>

    </div>
</div>
<script type="text/javascript">
    $(document).ready(function(){
        Initialization();
    });
    $(window).resize(function(){
        Initialization();
    });
    function Initialization(){
        fontSize();
        isOneScreen();
    }
    function real_vertify(){
        var identity_number = $("#identity_number").val();
        if(!valid_identity(identity_number)){
            $("#msg").html("身份证格式有误");
            return false;
        }
    }
    $("#real_name_wraper #btn").click(function(){
        var realname = $("#realname").val();
        var identity_number = $("#identity_number").val();
        var preg_text = /^(\d{14}|\d{17})(\d|[xX])$/;
        if( realname == '' ) {
            $("#msg").html("请输入真实姓名");
            return false;
        } else if( identity_number == '' ){
            $("#msg").html("请输入身份证号");
            return false;
        } else if( !preg_text.test(identity_number) ){
            $("#msg").html("身份证格式有误");
            return false;
        }
        var url = "<?php echo ApiUrl::toRoute("user/real-verify",true); ?>";
        fund =  "<?php echo Yii::$app->request->get('fund');?>";
        if (fund == 'fund') {
            var url_redirect = "<?php echo Url::toRoute('fund/fund-openaccount', true);?>";
        } else if (fund == 'loan') {
            <?php
            $url = Url::to(['page/loan-project-detail', 'id' => Yii::$app->request->get('loan_project_id')],true);
            $url = str_replace(
                ['mobile/', 'm.kdqugou.com'],
                ['frontend/', 'api.kdqugou.com'],
                $url );
            ?>
            var url_redirect = "<?php echo $url;?>"
        } else {
            var url_redirect = "<?php echo Url::toRoute(['user/bind-card'],true);?>";
        }
        userRealNameVertify(url,realname,identity_number,url_redirect);
    });
</script>