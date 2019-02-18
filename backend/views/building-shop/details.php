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
$this->showsubmenu('商户详情');
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.rotate.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.mousewheel.min.js'); ?>" ></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.iviewer.js'); ?>" ></script>
<link rel="stylesheet" href="<?php echo Url::toStatic('/css/jquery.iviewer.css'); ?>" />
<style type="text/css">

</style>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">注：单击缩略图为查看，双击缩略图或单击展开图为隐藏</th></tr>
    <tr>
        <td class="td24">ID：</td>
        <td width="200"><?php echo $model->id; ?></td>
        <td class="td24">所属借款项目：</td>
        <td colspan="15"><?php echo $loan_project->loan_project_name; ?></td>
    </tr>
    <tr>
        <td class="td24">店名：</td>
        <td width="200"><?php echo $model->shop_name; ?></td>
        <td class="td24">所在地区：</td>
        <td colspan="15"><?php echo $model->province.$model->city.$model->area; ?></td>
    </tr>
    <tr>
        <td class="td24">店主姓名：</td>
        <td width="200"><?php echo $model->shopkeeper_name; ?></td>
        <td class="td24">店主UID：</td>
        <td colspan="15"><?php echo $model->shopkeeper_id; ?></td>
    </tr>
    <tr>
        <td class="td24">店主手机号：</td>
        <td width="200"><?php echo $model->shopkeeper_phone; ?></td>
        <td class="td24">店主身份证号：</td>
        <td colspan="15"><?php echo $model->shopkeeper_card_id; ?></td>
    </tr>
    <tr>
        <td class="td24">店主方对接人：</td>
        <td><?php echo $model->linker_name; ?></td>
        <td class="td24">店主方对接人手机号：</td>
        <td><?php echo $model->linker_phone; ?></td>
    </tr>
    <tr>
        <td class="td24">对接人：</td>
        <td width="200"><?php echo $model->broker; ?></td>
        <td class="td24">授信额度：</td>
        <td colspan="15"><?php echo $model->credit_line; ?></td>
    </tr>
    <tr>
        <td class="td24">创建人：</td>
        <td width="200"><?php echo $model->creater; ?></td>
        <td class="td24">创建时间：</td>
        <td colspan="15"><?php echo date('Y-m-d H:i:s',$model->created_at); ?></td>
    </tr>
    <tr>
        <td class="td24">商户状态：</td>
        <td width="200"><font color="green"><?php echo Shop::$shop_status[$model->status]; ?></font></td>
        <td class="td24">备注：</td>
        <td colspan="15"><?php echo $model->remark; ?></td>
    </tr>
    <tr>
        <td class="td24">初审人：</td>
        <td width="200"><?php echo $model->trial; ?></td>
        <td class="td24">初审备注：</td>
        <td colspan="15"><?php echo $model->trial_remark; ?></td>
    </tr>
    <tr>
        <td class="td24">复审人：</td>
        <td width="200"><?php echo $model->review; ?></td>
        <td class="td24">复审备注：</td>
        <td colspan="15"><?php echo $model->review_remark; ?></td>
    </tr>
    <tr>
        <td class="td24">发布人：</td>
        <td width="200"><?php echo $model->audit; ?></td>
        <td class="td24">发布备注：</td>
        <td colspan="15"><?php echo $model->audit_remark; ?></td>
    </tr>
    <tr>
        <td class="td24">店主身份信息资料：</td>
        <td colspan="15">
            <?php foreach(json_decode($model->shopkeeper_card_pic,true) as $v):?>
                <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php endforeach;?>
        </td>
    </tr>
    <tr>
        <td class="td24">营业执照等证件资料：</td>
        <td colspan="15">
            <?php foreach(json_decode($model->shop_licence,true) as $v):?>
                <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php endforeach;?>
        </td>
    </tr>
    <tr>
        <td class="td24">商户实景图资料：</td>
        <td colspan="15">
            <?php if($model->shop_img){?>
            <?php foreach(json_decode($model->shop_img,true) as $v):?>
            <script type="text/javascript">
                    var $ = jQuery;
                    $(document).ready(function() {

                    });
                </script>
            <div class="wrapper">
               <div id="viewer">
                   <img  src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
               </div>
            </div>
            <?php endforeach;?>
            <?php }?>
        </td>
    </tr>
    <tr>
        <td class="td24">其他资料：</td>
        <td colspan="15">
            <?php if($model->other_info && json_decode($model->other_info,true)){?>
            <?php foreach(json_decode($model->other_info,true) as $v):?>
                <img src="<?php echo $v; ?>" height="100" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <?php endforeach;?>
            <?php }?>
        </td>
    </tr>
    <tr>
        <td class="td24">商户介绍：</td>
        <td colspan="15"><?php echo $model->shop_description ?></td>
    </tr>
</table>
<div id="bg"></div>
<div class="show-pic">
</div>
<script type="text/javascript">
    var iviewer = {};
    $(function(){
        $('.td24').css('font-weight','600');
        $("#bg").iviewer(
            {
                src: '',
                initCallback: function () {
                    iviewer = this;
                }
            });
    });
    $('img').css('cursor','pointer').click(function(){
        iviewer.loadImage(this.src);
        $('#bg').show();
    });
    $('.iviewer_delete').click(function(){
        $('#bg').hide();
    });
</script>