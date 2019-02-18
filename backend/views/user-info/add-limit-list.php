<?php

use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
use common\models\LimitApply;

/**
 * @var backend\components\View $this
 */
$this->shownav('loan', 'menu_credit_add_limit_list');
$this->showsubmenu('提额申请列表');

?>
    <style type="text/css">
        th {border-right: 1px dotted #deeffb;}
    </style>

<?php $form = ActiveForm::begin(['id' => 'searchform', 'method' => "get", 'options' => ['style' => 'margin-bottom:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
    手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), LimitApply::$status, ['prompt' => '所有状态']); ?>&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
    &nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php ActiveForm::end(); ?>


    <table class="tb tb2 fixpadding" style="text-align: center;">
        <tr class="header" style="text-align: center;">
            <th>用户ID</th>
            <th>用户姓名</th>
            <th>手机号码</th>
            <th>总额度</th>
            <th>成功还款次数</th>
            <th>正常还款次数</th>
            <th>成功还款金额</th>
            <th>正常还款金额</th>
            <th>总提额额度</th>
            <th>上次提额时间</th>
            <th>创建时间</th>
            <th>更新时间</th>
            <th>状态</th>
            <th>操作人</th>
            <th>操作</th>
        </tr>
        <?php foreach ($limit_apply_list as $value): ?>
            <tr class="hover" style="text-align: center;">
                <td><?php echo $value['user_id']; ?></td>
                <td><?php echo $value['loanPerson']['name']; ?></td>
                <td><?php echo $value['loanPerson']['phone']; ?></td>
                <td><?php echo $value['userCreditTotal']['amount']/100; ?></td>
                <td><?php echo $value['total_times']; ?></td>
                <td><?php echo $value['nurmal_num']; ?></td>
                <td><?php echo sprintf('%.2f', $value['repay_money'] / 100); ?></td>
                <td><?php echo sprintf('%.2f', $value['nurmal_money'] / 100); ?></td>
                <td><?php echo isset($value['userCreditTotal']['repayment_credit_add'])?$value['userCreditTotal']['repayment_credit_add'] /100:''; ?></td>
                <td><?php echo empty($value['userCreditTotal']['increase_time'])?'':date('Y-m-d H:i:s',$value['userCreditTotal']['increase_time']); ?></td>
                <td><?php echo date('Y-m-d H:i:s',$value['created_at']); ?></td>
                <td><?php echo date('Y-m-d H:i:s',$value['updated_at']); ?></td>
                <td><?php echo LimitApply::$status[$value['status']]; ?></td>
                <td><?php echo $value['operator_name']; ?></td>
                <td>
                    <?php if($value['status']==LimitApply::STATUS_TRIAL){?>
                    <a href="<?php echo Url::toRoute(['user-info/add-limit-trail', 'id' => $value['id'],'user_id'=>$value['user_id'],'status'=>$value['status']]);?>">审核</a>
                    <?php }else{?>
                    <a href="<?php echo Url::toRoute(['user-info/credit-modify-log','user_id'=>$value['user_id']]);?>">查看</a>
                    <?php } ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php if (empty($limit_apply_list)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>