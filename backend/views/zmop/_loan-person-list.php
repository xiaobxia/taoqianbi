<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\CreditZmop;
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
    借款人名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    联系方式：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
   <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>借款人类型</th>
                <th>姓名</th>
                <th>联系方式</th>
                <th>操作</th>
            </tr>
            <?php foreach ($loan_person as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <th><?php echo empty($value['type'])?'--':LoanPerson::$person_type[$value['type']]; ?></th>
                    <th><?php echo $value['name']; ?></th>
                    <th class="click-phone" data-phoneraw="<?php echo $value['phone']; ?>">--</th>
                    <td>
<!--                        <a href="--><?php //echo Url::toRoute(['user-zmop-view', 'id' => $value['id']]); ?><!--">芝麻信用</a>-->
                        <a href="<?php echo Url::toRoute(['jxl/user-view', 'id' => $value['id']]); ?>">蜜罐</a>
<!--                        <a href="--><?php //echo Url::toRoute(['fkb/user-view', 'id' => $value['id']]); ?><!--">face++</a>-->
                        <a href="<?php echo Url::toRoute(['td/user-view', 'id' => $value['id']]); ?>">同盾</a>
                        <a href="<?php echo Url::toRoute(['jxl/user-report-view', 'id' => $value['id']]); ?>">聚信立</a>
                        <a href="<?php echo Url::toRoute(['bqs/view', 'id' => $value['id']]); ?>">白骑士</a>
<!--                        <a href="--><?php //echo Url::toRoute(['hulu/user-view','id'=>$value['id']]);?><!--">葫芦</a>-->
                        <a href="<?php echo Url::toRoute(['br/view', 'id' => $value['id']]); ?>">百融</a>
<!--                        <a href="--><?php //echo Url::toRoute(['hd/view', 'id' => $value['id']]); ?><!--">华道征信</a>-->
<!--                        <a href="--><?php //echo Url::toRoute(['yxzc/view', 'id' => $value['id']]); ?><!--">宜信至诚</a>-->
<!--                        <a href="--><?php //echo Url::toRoute(['zzc/view', 'id' => $value['id']]); ?><!--">中智诚</a>-->
<!--                        <a href="--><?php //echo Url::toRoute(['yd/view', 'id' => $value['id']]); ?><!--">有盾</a>-->
<!--                        <a href="--><?php //echo Url::toRoute(['zs/view', 'id' => $value['id']]); ?><!--">甄视科技</a>-->
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($loan_person)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
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