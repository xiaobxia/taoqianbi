<?php
use yii\helpers\Html;
use common\helpers\Url;
use common\models\UserCreditOnoff;
use backend\components\widgets\LinkPager;

$this->shownav('credit', 'menu_credit_user_onoff');
$this->showsubmenu('用户征信开关');


?>
<script type="text/javascript">
    function deleteOption(id)
    {
        if(confirm('确定要删除吗？'))
        {
            location.href = '<?php echo Url::toRoute(["user-credit-onoff-delete"]); ?>&id=' + id;
        }
    }

</script>
<style>
.tb2 th{
        font-size: 12px;
    }
.btn {
    display: inline-block;
    padding: 1px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    border: 1px solid transparent;
    border-radius: 4px;
}

.btn-success {
    color: #fff;
    background-color: #5cb85c;
    border-color: #4cae4c;
}

</style>
        <div style="width: 100%;height: 5px;"></div>
        <p>
            <a href="<?php echo Url::toRoute(['user-credit-onoff-add']); ?>" class='btn btn-success'>添加</a>
        </p>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>征信名</th>
                <th>类型</th>
                <th>状态</th>
                <th>过期天数</th>
                <th>刷新笔数</th>
                <th>操作</th>
            </tr>
            <?php if (is_array($userCreditOnoff) && count($userCreditOnoff) > 0):
               foreach ($userCreditOnoff as $value):
            ?>
            <tr class="hover">
                <td><?php echo $value['id'] ?></td>
                <th><?php echo $value['name'] ?></th>
                <th><?php echo $value['type'] ?></th>
                <th><?php echo UserCreditOnoff::$user_credit_status[$value['status']] ?></th>
                <th><?php echo $value['overdue_days'] ?></th>
                <th><?php echo $value['refresh_count'] ?></th>
                <td>
                    <a href="<?php echo Url::toRoute(['user-credit-onoff-update', 'id' => $value['id']]); ?>">修改</a>
                    <a href="javascript:deleteOption(<?php echo $value['id']; ?>)">删除</a>
                </td>
            </tr>
            <?php endforeach ?>
            <?php else: ?>
                <tr class="hover">
                <td colspan="5">暂无记录</td>
            </tr>
            <?php endif ?>
        </table>

        <?php echo LinkPager::widget(['pagination' => $pages]); ?>



