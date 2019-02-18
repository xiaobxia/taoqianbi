<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\Shop;
use common\models\LoanProject;

/**
 * @var backend\components\View $this
 */
$this->shownav('asset', 'menu_shop_review');
$this->showsubmenu('商户复审');
?>
<?php $form = ActiveForm::begin(['id' => 'searchform','method'=>'get', 'options' => ['style' => 'margin-bottom:5px;']]); ?>
所属项目：<?php echo Html::dropDownList('project', Yii::$app->getRequest()->get('project', ''), $projects, array('prompt' => '-所有项目-')); ?>&nbsp;
商户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:60px;">&nbsp;
商户名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shop_name', ''); ?>" name="shop_name" class="txt" >&nbsp;
    机构代码：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shop_code', ''); ?>" name="shop_code" class="txt" >&nbsp;
商户负责人：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shopkeeper_name', ''); ?>" name="shopkeeper_name" class="txt" >&nbsp;
负责人UID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shopkeeper_id', ''); ?>" name="shopkeeper_id" class="txt" style="width:60px;">&nbsp;
负责人手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('shopkeeper_phone', ''); ?>" name="shopkeeper_phone" class="txt" >&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
<?php // $this->showtips('商品排序调整，请注意：排序值越小，商品显示越在前'); ?>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th width="3%">ID</th>
        <th width="8%">商户名称</th>
        <th width="8%">机构代码</th>
        <th width="6%">所属项目</th>
        <th width="12%">商户所在地区</th>
        <th width="5%">商户负责人</th>
        <th width="8%">手机号</th>
        <th width="10%">身份证号</th>
        <th width="5%">授信额度</th>
        <th width="5%">创建人</th>
        <th width="10%">创建时间</th>
        <th width="5%">状态</th>
        <th>操作</th>
    </tr>
    <?php foreach ($shops as $v): ?>
    <tr class="hover">
        <td><?php echo $v['id'];?></td>
        <td><?php echo $v['shop_name'];?></td>
        <td><?php echo $v['shop_code'];?></td>
        <td>
            <?php $loan_project = LoanProject::find()->where(['id'=>$v['loan_project_id']])->one(Yii::$app->db_kdkj);?>
            <?php echo isset($loan_project['loan_project_name']) ? $loan_project['loan_project_name'] : '';?>
        </td>
        <td><?php echo $v['province'].$v['city'].$v['area'];?></td>
        <td><?php echo $v['shopkeeper_name'];?></td>
        <td><?php echo $v['shopkeeper_phone'];?></td>
        <td><?php echo $v['shopkeeper_card_id'];?></td>
        <td><?php echo $v['credit_line'];?></td>
        <td><?php echo $v['creater'];?></td>
        <td><?php echo date('Y-m-d H:i:s',$v['created_at']);?></td>
        <td><font color="green"><?php echo Shop::$shop_status[$v['status']];?></font></td>
        <td>
            <a href="<?php echo Url::toRoute(['audit', 'id' => $v->id]); ?>">复审</a>
            <a href="<?php echo Url::toRoute(['details', 'id' => $v->id]); ?>">详情</a>
            <a href="<?php echo Url::toRoute(['edit', 'id' => $v->id]); ?>">编辑</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($shops)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>

<?php echo LinkPager::widget(['pagination' => $pages]); ?>