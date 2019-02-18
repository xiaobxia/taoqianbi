<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2017/5/26
 * Time: 18:27
 */
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->showsubmenu('管理员管理', array(
    array('角色列表', Url::toRoute('back-end-admin-user/role-list'), 1),
));
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>序号</th>
                <th>用户ID</th>
                <th>用户名</th>
                <th>备注名</th>
                <th>手机号</th>
                <th>添加时间</th>
            </tr>
            <?php foreach ($result as $k=>$value): ?>
                <tr>
                    <td><?php echo $k+1; ?></td>
                    <td><?php echo $value['id']?></td>
                    <td><?php echo $value['username']; ?></td>
                    <td><?php echo $value['mark']; ?></td>
                    <td><?php echo $value['phone']; ?></td>
                    <td><?php echo date('Y-m-d H:i:s',$value['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($result)): ?>
            <div class="no-result" style="color:red;font-size: 18px">该角色下暂无成员</div>
         <?php else: ?>
            <div class="no-result" style="color:red;font-size: 18px"><?php echo '该角色下有'.$count.'个成员'?></div>
        <?php endif; ?>
