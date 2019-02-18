<?php
use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'loan_person_house_fund');
$this->showsubmenu('敏感词管理', array(
    array('列表', Url::toRoute('sensitive-dict/show-list'), 1),
    array('添加', Url::toRoute('sensitive-dict/add'), 0),
));
?>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin([
    'id' => 'search_form',
    'method' => 'get',
    'action' => ['sensitive-dict/show-list'],
    'options' => ['style' => 'margin-top:5px;'],
]); ?>
ID：<input type="text" value="<?php echo Yii::$app->request->get('id', ''); ?>" name="id"
            class="txt" style="width:60px;">&nbsp;
敏感词：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name"
            class="txt" style="width:60px;">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<form name="listform" method="post">
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>ID</th>
            <th>敏感词</th>
            <th>操作</th>
        </tr>
        <?php foreach ($info as $value): ?>
            <tr class="hover">
                <td><?php echo $value['id']; ?></td>
                <td><?php echo $value['name']; ?></td>
                <td>
                    <a href="#" class="delete" value-id="<?php echo $value['id']; ?>">删除</a>
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
    $('.delete').click(function () {
        var id = $(this).attr('value-id');
        $.ajax({
            type: "POST",
            url: "<?php echo Url::toRoute(['/sensitive-dict/delete']); ?>",
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
