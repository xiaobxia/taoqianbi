<?php

use backend\components\widgets\LinkPager;
use common\models\CardInfo;
use yii\widgets\ActiveForm;
use common\models\User;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'menu_bankcard_list');
$this->showsubmenu('银行卡管理列表', array(
    array('银行卡管理列表', Url::toRoute('bank-card/card-list'), 1),
    array('银行卡添加', Url::toRoute('bank-card/card-add'), 0),
));
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['bank-card/card-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
借款人ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:70px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
银行卡号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('card_no', ''); ?>" name="card_no" class="txt" style="width:100px;">&nbsp;
状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), CardInfo::$status, ['prompt' => '所有类型']); ?>&nbsp;
添加日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_start', ''); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_end', ''); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
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
                <th>操作</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['id']; ?></td>
                    <td class="td25"><?php echo $value['user_id']; ?></td>
                    <td class="td25"><?php echo $value['name'] ? $value['name'] : $value['loanPerson']['name']; ?></td>
                    <td class="td25 click-phone"  data-phoneraw="<?php echo $value['phone']; ?>">--</td>
                    <td class="td25"><?php echo $value['bank_name']; ?></td>
                    <td class="td25"><?php echo $value['card_no']; ?></td>
                    <td class="td25"><?php echo $value['bank_address']; ?></td>
                    <td class="td25"><?php echo CardInfo::$mark[$value['main_card']]; ?></td>
                    <td class="td25"><?php echo CardInfo::$type[$value['type']]; ?></td>
                    <td class="td25"><?php echo empty($value['status']) ? "" : CardInfo::$status[$value['status']]; ?></td>
                    <td class="td25"><?php echo date("Y-m-d",$value['created_at']); ?></td>
                    <td class="td25">
                    <?php if(isset($value['main_card']) && $value['main_card']==0 && $value['type']==CardInfo::TYPE_DEBIT_CARD){?>
                    <a onclick="if(confirmMsg('确定要切换主卡吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['change-card', 'id' => $value['id']]);?>">切换主卡</a> <?php }?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    /**
     * 电话显示*，点击后正常显示
     */
    (function initClickPhoneCol() {
        $('.click-phone').each(function () {
            var $item = $(this);
            var phone = $item.attr('data-phoneraw');
            if (phone && phone.length>5) {
                var phoneshow = phone.substr(0, 3) + '****' + phone.substr(phone.length - 2, 2);
                $item.attr('data-phoneshow', phoneshow);
                $item.text(phoneshow);
            } else {
                $item.attr('data-phoneshow', phone);
                $item.text(phone);
            }
        });
        $('.click-phone').one('click', function () {
            $(this).text($(this).attr('data-phoneraw'));
        })
    })();
</script>
