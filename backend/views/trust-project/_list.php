<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use backend\components\widgets\ActiveForm;
use common\models\LoanProject;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
?>

<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'options' => ['style' => 'margin-top:5px;']]); ?>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:60px;" >&nbsp;
    项目名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('loan_project_name', ''); ?>" name="loan_project_name" class="txt" style="width:120px;">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th width="2%">ID</th>
                <th width="5%">项目名称</th>
                <th width="6%">项目类型</th>
                <th width="8%">金额范围（元）</th>
                <th width="6%">期限范围（月）</th>
                <th width="6%">年龄范围（岁）</th>
                <th width="4%">地域</th>
                <th width="20%">项目简介</th>
                <th width="20%">规则说明</th>
                <th width="5%">成功人数</th>
                <th width="5%">状态</th>
                <th>操作</th>
            </tr>
            <?php foreach ($loan_project_list as $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['id']; ?></td>
                    <td><?php echo $value['loan_project_name']; ?></td>
                    <td><?php echo LoanProject::$type_list[$value['type']]; ?></td>
                    <td><?php echo sprintf('%d - %d', $value['amount_min'] / 100, $value['amount_max'] / 100); ?></td>
                    <td>&nbsp;&nbsp;<?php echo sprintf('%d - %d', $value['period_min'], $value['period_max']); ?></td>
                    <td>&nbsp;&nbsp;<?php echo sprintf('%d - %d', $value['age_min'], $value['age_max']); ?></td>
                    <td><?php echo $value['region']; ?></td>
                    <td><?php echo $value['description']; ?></td>
                    <td><?php echo $value['rule_description']; ?></td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $value['success_number']; ?></td>
                    <td><?php echo LoanProject::$status[$value['status']]; ?></td>
                    <td>
                        <a href="<?php echo Url::toRoute(['view', 'id' => $value['id']]); ?>">查看</a>
                        <?php if('list' == $action):?>
                            <a href="<?php echo Url::toRoute(['edit', 'id' => $value['id']]); ?>">编辑</a>
                            <a onclick="confirmRedirect('确认作废', '<?php echo Url::toRoute(['del', 'id' => $value['id']]); ?>')" href="javascript:void(0);">作废</a>
                        <?php elseif('trial' == $action): ?>
                            <a onclick="confirmRedirect('确认通过', '<?php echo Url::toRoute(['trial-pass', 'id' => $value['id']]); ?>')" href="javascript:void(0);">通过</a>
                            <a onclick="confirmRedirect('确认驳回', '<?php echo Url::toRoute(['trial-reject', 'id' => $value['id']]); ?>')" href="javascript:void(0);">驳回</a>
                        <?php elseif('review' == $action): ?>
                            <a onclick="confirmRedirect('确认通过', '<?php echo Url::toRoute(['review-pass', 'id' => $value['id']]); ?>')" href="javascript:void(0);">通过</a>
                            <a onclick="confirmRedirect('确认驳回', '<?php echo Url::toRoute(['review-reject', 'id' => $value['id']]); ?>')" href="javascript:void(0);">驳回</a>
                        <?php elseif('active' == $action): ?>
                            <a onclick="confirmRedirect('确认通过？', '<?php echo Url::toRoute(['active-pass', 'id' => $value['id']]); ?>')" href="javascript:void(0);">通过</a>
                            <a onclick="confirmRedirect('确认驳回？', '<?php echo Url::toRoute(['active-reject', 'id' => $value['id']]); ?>')" href="javascript:void(0);">驳回</a>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($loan_project_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>