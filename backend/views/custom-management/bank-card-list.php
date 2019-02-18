<?php

use backend\components\widgets\LinkPager;
use common\models\CardInfo;
use yii\widgets\ActiveForm;
use common\models\User;
use yii\helpers\Html;
/**
 * @var backend\components\View $this
 */
$this->shownav('service', 'menu_card_list');
$this->showsubmenu('银行卡管理列表');
?>  
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['custom-management/card-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
借款人ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:70px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
<input type="submit" name="search_submit" value="搜索" class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>借款人ID</th>
                <th>持卡人姓名</th>
                <th>手机号码</th>
                <th>银行名称</th>
                <th>银行卡号</th>
                <th>开户行地址</th>
                <th>是否主卡</th>
                <th>卡类型</th>
                <th>状态</th>
                <th>添加时间</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['id']; ?></td>
                    <td class="td25"><?php echo $value['user_id']; ?></td>
                    <td class="td25"><?php echo $value['name'] ? $value['name'] : $value['loanPerson']['name']; ?></td>
                    <td class="td25"><?php echo $value['phone']; ?></td>
                    <td class="td25"><?php echo $value['bank_name']; ?></td>
                    <td class="td25"><?php echo $value['card_no']; ?></td>
                    <td class="td25"><?php echo $value['bank_address']; ?></td>
                    <td class="td25"><?php echo CardInfo::$mark[$value['main_card']]; ?></td>
                    <td class="td25"><?php echo CardInfo::$type[$value['type']]; ?></td>
                    <td class="td25"><?php echo empty($value['status']) ? "" : CardInfo::$status[$value['status']]; ?></td>
                    <td class="td25"><?php echo date("Y-m-d",$value['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>