<?php

use backend\components\widgets\LinkPager;
use common\models\AccumulationFund;
use common\models\LoanBlackList;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'loan_black_list');
$this->showsubmenu(APP_NAMES.'黑名单', array(
    array('列表', Url::toRoute('loan-black-list/show-list'), 1),
    array('添加', Url::toRoute('loan-black-list/add-user'), 1),
));
?>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form', 'method' => 'get', 'action' => ['loan-black-list/show-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id"
            class="txt" style="width:60px;">&nbsp;
手机号:<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt"
           maxlength="20" style="width:120px;">&nbsp;
备注:<input type="text" value="<?php echo Yii::$app->getRequest()->get('remark', ''); ?>" name="remark" class="txt"
          maxlength="20" style="width:120px;">&nbsp;
创建者:<input type="text" value="<?php echo Yii::$app->getRequest()->get('admin_user', ''); ?>" name="admin_user"
           class="txt" maxlength="20" style="width:120px;">&nbsp;
是否有效：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), LoanBlackList::$status_list, ['prompt' => '所有类型']); ?>&nbsp;
添加日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_start', ''); ?>" name="add_start"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_end', ''); ?>" name="add_end"
         onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>用户ID</th>
            <th>姓名</th>
            <th>电话</th>
            <th>身份证号</th>
            <th>备注</th>
            <th>创建者</th>
            <th>添加时间</th>
            <th>是否有效</th>
            <th>操作</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td><?php echo $value['phone']; ?></td>
                <td><?php echo $value['id_number']; ?></td>
                <td><?php echo $value['black_remark']; ?></td>
                <td><?php echo $value['black_admin_user']; ?></td>
                <td><?php echo date("Y-m-d H:i:s", $value['created_at']); ?></td>
                <td><?php echo LoanBlackList::$status_list[$value['black_status']]; ?></td>
                <td>
                    <?php if ($value['black_status'] == LoanBlackList::STATUS_NO) : ?>
                        <a href="#" class="change_status" value-id="<?php echo $value['id']; ?>">置为有效</a>
                        <?php else : ?>
                        <a href="#" class="change_status" value-id="<?php echo $value['id']; ?>">置为无效</a>
                    <?php endif; ?>
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
    $('.change_status').click(function () {
        var id = $(this).attr('value-id');
        $.ajax({
            type: "POST",
            url: "<?php echo Url::toRoute(['/loan-black-list/change-status']); ?>",
            data: {_csrf: "<?php echo Yii::$app->request->csrfToken ?>", id: id},
            dataType: "json",
            success: function (o) {
                console.log(o)
                if (o.code == 0) {
                    alert('状态更新成功');
                    location.reload()
                } else {
                    alert(o.message);
                }
            }
        })
    })
</script>
