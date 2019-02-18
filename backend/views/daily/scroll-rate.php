<?php

use callcenter\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\User;
use common\models\loan\LoanCollection;
use common\models\loan\LoanCollectionOrder;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var callcenter\components\View $this
 */

$this->shownav('data_analysis', 'menu_daily_scroll_rate');

?>
<style type="text/css">
    .div_tips{
        /*position:relative;*/
        display: none;
        border: 1px solid red;
        background-color: yellow;
        width: auto;
        top:10px;
        left: -200%;
        z-index: 999;
    }
</style>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th style="text-align:center;">放款日\还款日</th>
            <?php foreach ($time_array as $v):?>
            <?php $fk_date = date("n-j",  strtotime($v)); ?>
            <th style="text-align:center;"><?php echo $fk_date ;?></th>
            <?php endforeach;?>
        </tr>

        <?php foreach ($time_data as $key=>$val): ?>
            <tr class="hover">
            <?php $hk_date = date("n-j",  strtotime($key)); ?>
                <td class="td25" style="text-align: center"><?php echo $hk_date; ?></td>
            <?php foreach ($val as $key2=>$val2): ?>
                <?php $fk_date = date("n-j",  strtotime($key2)); ?>
                <?php if(empty($val2['yes_count'])):?>
                <td class="td25" style="text-align: center"></td>
                <?php else:?>
                <td class="td25" style="text-align: center" onmouseenter="_mouseenter(this);" onmouseleave="_mouseleave(this);">
                    <?php $today_yes_count = sprintf("%.2f", ($val2['yes_count'] / $val2['all_count'])*100).'%';?>
                    <?php  if(!empty($val2['today_all_yes_count'])):?>
                        <span>
                        <?php $today_all_yes_count = sprintf("%.2f", ($val2['today_all_yes_count'] / $val2['all_count'])*100).'%'; ?>
                        <?php echo $today_all_yes_count; ?>
                        </span>
                        <span style="color: blue">
                        <?php echo  "({$today_yes_count})"; ?>
                        </span>
                        <?php $tips = "{$hk_date}放款<br/>在{$fk_date}还款率为{$today_all_yes_count}<br/>(当日新增{$today_yes_count})";?>
                    <?php else:?>
                        <?php echo $today_yes_count;?>
                        <?php $tips = "{$hk_date}放款<br/>在{$fk_date}还款率为{$today_yes_count}";?>
                    <?php endif;?>
                    <div class="div_tips">
                        <?php echo $tips; ?>
                    </div>
                </td>
                <?php endif;?>
            <?php endforeach;?>
            </tr>
        <?php endforeach;?>
    </table>
</form>
<script>
    //鼠标移入
    function _mouseenter($obj) {
        $this = $($obj);
        $this.find('div[class="div_tips"]').show();
    }

    //鼠标移出
    function _mouseleave($obj) {
        $this = $($obj);
        $this.find('div[class="div_tips"]').hide();
    }
</script>

