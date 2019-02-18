<?php
/**
 * User: gaokuankuan
 * Date: 2016/10/25
 */
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
$this->shownav('loan', 'menu_ygb_zc_lqd_al');
$this->showsubmenu('审核员列表');
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
  <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>用户名</th>
                <th>角色</th>
                <th>待审数量</th>
                <th>能够接入</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['username']; ?></td>
                    <th><?php echo $value['role'] == 0 ?"初审审核员":"复审审核员"; ?></th>
                    <th><?php echo $value['total']; ?></th>
                    <th><?php echo $value['acceptance']?"是":"否"; ?></th>
                    <th><?php echo $value['status'] ?"审核中":"休息中"; ?></th>
                    <th>
                        <a href="<?php echo Url::toRoute(['pocket/update-acceptance', 'id' => $value['id'],'acceptance' => $value['acceptance']]);?>"><?php echo $value['acceptance']?"停止接入":"允许接入"; ?></a>
                        <a href="<?php echo Url::toRoute(['pocket/pending-to-empty', 'id' => $value['id']]);?>">待审清空</a>
                    </th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php $form = ActiveForm::end(); ?>
