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
$this->shownav('user', 'loan_out_del_list');
$this->showsubmenu('注销记录', array(
));
?>
<script language="javascript" type="text/javascript"
        src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>注销ID</th>
            <th>姓名</th>
            <th>电话</th>
            <th>备注</th>
            <th>创建人</th>
            <th>创建时间</th>
            <th>是否有效</th>
            <th>更新时间</th>
            <th>操作</th>
        </tr>
        <?php foreach ($loan_person as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['name']; ?></td>
                <td><?php echo $value['phone']; ?></td>
                <td><?php echo $value['remark']; ?></td>
                <td>
                    <?php echo $value['admin_user'] ?>
                </td>
                <td><?php echo date("Y-m-d H:i:s", $value['created_at']); ?></td>
                <td>
                    <?php echo $value['status'] != -2 ?'已恢复':'删除'; ?>
                </td>
                <td><?php echo date("Y-m-d H:i:s", $value['updated_at']);  ?></td>
                <td>
                    <?php if ($value['status'] == -2) : ?>
                        <a href="#" class="change_status" value-id="<?php echo $value['id']; ?>">恢复注销</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($loan_person)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
</form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

<script>
    $('.change_status').click(function () {
        var id = $(this).attr('value-id');
        $.ajax({
            type: "POST",
            url: "<?php echo Url::toRoute(['/loan/change-status']); ?>",
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
    $('.delete').click(function () {
        var id = $(this).attr('value-id');
        $.ajax({
            type: "POST",
            url: "<?php echo Url::toRoute(['/loan/delete-out']); ?>",
            data: {_csrf: "<?php echo Yii::$app->request->csrfToken ?>", id: id},
            dataType: "json",
            success: function (o) {
                console.log(o)
                if (o.code == 0) {
                    alert('删除成功');
                    location.reload()
                } else {
                    alert(o.message);
                }
            }
        })
    })
</script>
