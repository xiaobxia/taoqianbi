<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\Shop;
use common\models\LoanProject;

/**
 * @var backend\components\View $this
 */
$this->shownav('asset', 'menu_shop_list');
$this->showsubmenu($status_do);
?>
<style type="text/css">
    .show-style{
        position: absolute;
        top: 50px;
        right: 10%;
        padding: 10px;
        border: 2px dashed #0099CC;
    }
</style>
<?php $form = ActiveForm::begin(['id' => 'intergration-form']); ?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">注：单击图片为查看，双击图片为隐藏</th></tr>
    <tr>
        <td class="td24">商户状态：</td>
        <td colspan="3"><font color="red"><?php echo Shop::$shop_status[$model->status]; ?></font></td>
    </tr>
    <tr>
        <td class="td24">ID：</td>
        <td width="200"><?php echo $model->id; ?></td>
        <td class="td24">所属借款项目：</td>
        <td><?php echo $loan_project->loan_project_name; ?></td>
    </tr>
    <tr>
        <td class="td24">店名：</td>
        <td><?php echo $model->shop_name; ?></td>
        <td class="td24">所在地区：</td>
        <td><?php echo $model->province.$model->city.$model->area; ?></td>
    </tr>
    <tr>
        <td class="td24">店主姓名：</td>
        <td><?php echo $model->shopkeeper_name; ?></td>
        <td class="td24">店主UID：</td>
        <td><?php echo $model->shopkeeper_id; ?></td>
    </tr>
    <tr>
        <td class="td24">店主手机号：</td>
        <td><?php echo $model->shopkeeper_phone; ?></td>
        <td class="td24">店主身份证号：</td>
        <td><?php echo $model->shopkeeper_card_id; ?></td>
    </tr>
    <tr>
        <td class="td24">店主方对接人：</td>
        <td><?php echo $model->linker_name; ?></td>
        <td class="td24">店主方对接人手机号：</td>
        <td><?php echo $model->linker_phone; ?></td>
    </tr>
    <tr>
        <td class="td24">对接人：</td>
        <td><?php echo $model->broker; ?></td>
        <td class="td24">授信额度：</td>
        <td><?php echo $model->credit_line; ?></td>
    </tr>
    <tr>
        <td class="td24">创建人：</td>
        <td><?php echo $model->creater; ?></td>
        <td class="td24">创建时间：</td>
        <td><?php echo date('Y-m-d H:i:s',$model->created_at); ?></td>
    </tr>
    <tr>
        <td class="td24">店主身份信息资料：</td>
        <td colspan="3">
            <?php foreach(json_decode($model->shopkeeper_card_pic,true) as $v):?>
                <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php endforeach;?>
        </td>
    </tr>
    <tr>
        <td class="td24">营业执照等证件资料：</td>
        <td colspan="3">
            <?php foreach(json_decode($model->shop_licence,true) as $v):?>
                <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php endforeach;?>
        </td>
    </tr>
    <tr>
        <td class="td24">商户实景图资料：</td>
        <td colspan="3">
            <?php if($model->shop_img){?>
            <?php foreach(json_decode($model->shop_img,true) as $v):?>
                <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php endforeach;?>
            <?php }?>
        </td>
    </tr>
    <tr>
        <td class="td24">其他资料：</td>
        <td colspan="3">
            <?php if(!empty($model->other_info) && json_decode($model->other_info,true)){?>
            <?php foreach(json_decode($model->other_info,true) as $v):?>
                <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php endforeach;?>
            <?php }?>
        </td>
    </tr>
    <tr>
        <td class="td24">商户介绍：</td>
        <td colspan="3">
            <?php echo $model->shop_description; ?>
        </td>
    </tr>
    <?php if($status == Shop::SHOP_DRATF):?>
        <tr>
            <td class="td24">初审状态：</td>
            <td colspan="3">
                <input type="radio" value="<?php echo Shop::SHOP_TRAIL;?>" name="Shop[status]" />初审通过
                <input type="radio" value="<?php echo Shop::SHOP_TRAIL_NO;?>" name="Shop[status]" />初审作废
            </td>
        </tr>
        <tr>
            <td class="td24">初审备注：</td>
            <td colspan="3">
                <textarea class="form-control" name="Shop[trial_remark]" style="width:600px;height:100px;"></textarea>
            </td>
        </tr>
    <?php elseif($status == Shop::SHOP_TRAIL):?>
        <tr>
            <td class="td24">初审：</td>
            <td colspan="3">
                （初审人）<?php echo $model->trial;?>
                <br/><br/>
                （初审意见）<?php echo $model->trial_remark;?>
            </td>
        </tr>
        <tr>
            <td class="td24">复审状态：</td>
            <td colspan="3">
                <input type="radio" value="<?php echo Shop::SHOP_REVIEW;?>" name="Shop[status]" />复审通过
                <input type="radio" value="<?php echo Shop::SHOP_REVIEW_NO;?>" name="Shop[status]" />复审作废
            </td>
        </tr>
        <tr>
            <td class="td24">复审备注：</td>
            <td colspan="3">
                <textarea class="form-control" name="Shop[review_remark]" style="width:600px;height:100px;"></textarea>
            </td>
        </tr>
    <?php elseif($status == Shop::SHOP_REVIEW):?>
        <tr>
            <td class="td24">初审：</td>
            <td colspan="3">
                （初审人）<?php echo $model->trial;?>
                <br/><br/>
                （初审意见）<?php echo $model->trial_remark;?>
            </td>
        </tr>
        <tr>
            <td class="td24">复审：</td>
            <td colspan="3">
                （复审人）<?php echo $model->review;?>
                <br/><br/>
                （复审意见）<?php echo $model->review_remark;?>
            </td>
        </tr>
        <tr>
            <td class="td24">发布状态：</td>
            <td colspan="3">
                <input type="radio" value="<?php echo Shop::SHOP_ACTIVE;?>" name="Shop[status]" />发布通过
                <input type="radio" value="<?php echo Shop::SHOP_DELETE;?>" name="Shop[status]" />发布作废
            </td>
        </tr>
        <tr>
            <td class="td24">发布备注：</td>
            <td colspan="3">
                <textarea class="form-control" name="Shop[audit_remark]" style="width:600px;height:100px;"></textarea>
            </td>
        </tr>
    <?php endif;?>
    <tr>
        <td class="td24"><input type="submit" value="提交" name="submit_btn" class="btn"></td>
        <td colspan="3"></td>
    </tr>
    <tr>
        <td colspan="4"></td>
    </tr>
    <tr>
        <td colspan="4"></td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<div class="show-pic"></div>
<script type="text/javascript">
    $(function(){
        $('.td24').css('font-weight','600');
    });
    $('img').css('cursor','pointer').click(function(){
        var img = '<img src="'+$(this).attr('src')+'" />';
        $('.show-pic').addClass('show-style').html(img);
    });
    $('img').css('cursor','pointer').dblclick(function(){
        $('.show-pic').removeClass('show-style').html('');
    });
</script>