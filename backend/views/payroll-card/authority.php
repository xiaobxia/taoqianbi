<?php
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
    <title><?php echo APP_NAMES;?></title>
    <meta name="format-detection" content="telephone=no">
    <script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery-1.7.2.min.js'); ?>"></script>
    <link rel="stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/style.css'); ?>?v=2016121214" />

</head>
<style>

.container{
    padding: 0 0 0;

}
.authority{
    width: 90%;
    margin-left: 5%;
    margin-right: 5%;
    font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
    font-size: 1.6vh;
    margin-top: 5%;
    color: #666;
    line-height: 3vh;
}
.title{
    font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
    font-size: 2vh;
    color: #666;
    width: 30%;
    margin: 0 auto;
    text-align: center;
    margin-top: 5%;
}

    .button{width: 80%;
        height: 6vh;
        margin: 0 auto;
        background-color: #1782e0;
        color: #fff;
        line-height: 6vh;
        border-radius: 3vh;
        font-size: 2.5vh;
        font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
        text-align: center;
        margin-top: 5vh;
        margin-bottom: 5vh }
</style>

<body style="background-color:#fff">
<div class="flag"></div>
<div class="title">
        <p>授权书</p>
        <p>(个人征信业务)</p>
</div>
<div class="authority">
    重要提示：<br/>
    尊敬的客户：为了维护您的权益，请在您点击确认本授权书之前，仔细阅读本授权书各条款（特别是黑体字条款），关注您在授权书中的权利、义务。如您接受本授权书，请勾选“接受授权书”。您使用被授权方公司产品服务的行为，默认为您已经认真阅读并充分理解本授权书的全部内容。若有任何疑问，可向客服人员咨询。<br/>授权方：（姓名：          身份证号码：             ）</br>
    被授权方:<?php echo COMPANY_NAME;?><br/>
    第一条 采集授权<br/>
    1.1本人同意并不可撤销的授权被授权方（包括被授权方分支机构）按照国家相关规定采集、保存本人与被授权方业务往来中提供或产生的个人信息，并向金融信用信息基础数据库或与被授权方具有合作关系的合作第三方、以及其他依法设立的征信机构（以下简称合作第三方）提供符合相关规定的本人个人信息和包括借贷信息在内的信用信息（包括本人的不良信用信息）。<br/>
    1.2本人特别授权被授权方在向合作第三方提供本人的信息前无需另行通知本人。<br/>
    1.3本人已经知悉在信息安全保证制度不完善的情况下，本人仍然同意授权被授权方向合作第三方提供前述财产性信息。<br/>
    第二条 授权信息范围<br/>
    如本人选择或接受被授权方的相关产品及服务时，本人不可撤销同意及授权被授权方通过网银、邮箱登录等方式向合作第三方提供本人个人信息，其中包括但不限于以下信息：<br/>
    （1）本人基本信息、网银账号密码信息、邮箱账号密码信息以及通过网银邮箱账号密码登录获取的信用卡及储蓄卡交易等本人原始数据信息（包括但不限于年收入、主要财产、网银交易、信用报告）；<br/>
    （2）本人选择的产品项目或服务的基本信息（包括但不限于项目或服务名称、类型、主要内容、审批文件、还款来源、借款用途、借款金额、借款期限、还款方式及利率）；<br/>
    （3）被授权方及/或合作第三方根据本人基本信息、征信信息、财产状况等综合评定的信用评级或信用评分；<br/>
    （4）在产品或服务履行期限内本人经营状况及财务状况、还款能力变化情况等；<br/>
    （5）其他因服务需要被授权方合法获取的信息、数据等。<br/>
    第三条 使用授权<br/>
    3.1本人同意并不可撤销的授权，被授权方在保证本人信息安全的前提下可以根据国家相关规定保存、整理、加工本授权书中所述的、通过合法途径采集的符合相关规定的本人个人信息和包括信贷信息在内的信用信息，并通过合作第三方查询、核实、打印、保存、整理、加工本授权书中所述的、通过合法途径采集的符合相关规定的本人个人信息和包括信贷信息在内的信用信息，用于评价本人信用情况或核实本人信息的真实性。<br/>
    3.2本人同意并不可撤销的授权被授权方向合作第三方提供被授权方保存、整理、加工的本授权书中所述的、通过合法途径采集的本人个人信息和包括信贷信息在内的信用信息。本人因与被授权方及合作第三方处申请相关业务或存在某种服务关系，合作第三方可使用、保存、整理、加工被授权方向其提供的本人征信及本授权书约定之信用信息，并可自行通过征信机构或其他拥有合法资质的合作第三方查询或核实本人信息，并将该等信息与被授权方进行回传及共享。本人同意合作第三方与被授权方在同等授权范围内进行上述信息采集、使用、加工、披露行为且该行为已经征得本人同意，无须另行书面授权。<br/>
    3.3本人同意授权被授权方及合作第三方将合法授权的本人信息披露或运用于为本人提供的下述业务中，包括：<br/>
    （1）对本人或配偶提出的贷款、信用卡申请、租赁、赊购、投保、理赔、担保、投资等业务的事前、事中、事后情况进行审查申请的；<br/>
    （2）对本人担任法定代表人、负责人或出资人的法人或其他组织的借款、担保申请需要查询本人信用状况的；<br/>
    （3）对本人提出的异议申请进行核查的；<br/>
    （4）向本人推荐产品及服务的；<br/>
    （5）依法或经有权部门要求的（包括但不限于公检法机关需要、会计师事务所审计、信息安全测评认证机构进行测评认证等）；<br/>
    （6）其他经过本人同意的合法用途。<br/>
    本人已经详细阅读并充分理解授权方的全部内容，同意按照本授权书约定执行。本人保证本人提供的所有信息为本人所有，均为真实合法有效，否则由本人承担相关责任。本人承诺本授权书授权事项为不可撤销之授权，本授权书自本人在线点击确认之日起具有法律效力。<br/>
</div>
<!--<a href="--><?php //echo Url::toRoute(['payroll-card/index']);?><!--"><div class="button" id="button">-->
<!--        我已阅读并同意-->
<!--    </div></a>-->



</body>
