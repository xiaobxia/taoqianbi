<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\LoanPerson;
use yii\helpers\Html;
/**
 * @var backend\components\View $this
 */
$rate = Yii::$app->request->get('from_st','0') ? 1.1 : 1;
$role=$role = Yii::$app->user->identity->role;
?>
<title>每日新用户数据</title>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-5*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<input type="hidden" name="from_st" value="<?php echo Yii::$app->request->get('from_st','0')?>">
更新时间：<?php echo date('Y-m-d H:i:s',$info[0]['updated_at']);?>
<?php ActiveForm::end(); ?>

    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>日期</th>
                <?php if($role!='superadmin'){?>
                <th>注册量</th>
                <?php }?>
                <th>运营商人数</th>
                <th>支付宝数</th>
                <th>芝麻信用人数</th>
                <th>工作信息人数</th>
                <th>实名人数</th>
                <th>绑卡人数</th>
                <th>全要素认证人数</th>
                <th>申请人数</th>
                <th>通过人数（放款人数）</th>
                <th>通过率</th>
            </tr>
            <?php foreach ($info as $key=> $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['date']; ?></td>
                    <?php if($role!='superadmin'){?>
                    <td class="td25"><?php echo floor($rate*$value['reg_num']); ?></td>
                    <?php }?>
                    <td class="td25"><?php echo floor($rate*$value['jxl_num']);  ?></td>
                    <td class="td25"><?php echo floor($rate*$value['alipay_num']);  ?></td>
                    <td class="td25"><?php echo floor($rate*$value['zmxy_num']);  ?></td>
                    <td class="td25"><?php echo floor($rate*$value['real_work_num']);  ?></td>
                    <td class="td25"><?php echo floor($rate*$value['realname_num']);  ?></td>
                    <td class="td25"><?php echo floor($rate*$value['bind_card_num']); ?></td>
                    <td class="td25"><?php echo floor($rate*$value['all_verif_num']); ?></td>
                    <td class="td25"><?php echo floor($rate*$value['apply_num']); ?></td>
                    <td class="td25"><?php echo floor($rate*$value['apply_success_num']); ?></td>
                    <td class="td25"><?php echo empty($value['apply_num'])?"--":sprintf("%0.2f",$value['apply_success_num']/$value['apply_num']*100)."%"; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php //echo LinkPager::widget(['pagination' => $pages]); ?>

<br>
<br>
<br>
<p>备注：</p>
<p>注册量：今日注册人数</p>
<p>运营商人数：今日注册人数中运营商认证人数</p>
<p>实名：今日注册人数中实名认证人数</p>
<p>申请人数：今日注册人数中申请借款人数</p>
<p>通过放款人数：今日注册人数中申请借款通过放款的人数</p>
<p>通过率：通过放款人数/申请人数</p>
