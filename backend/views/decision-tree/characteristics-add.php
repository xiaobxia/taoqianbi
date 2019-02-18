<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\Coupon */

$this->title = '新建特征';
?>
<div class="characteristics-add">

    <h1><?php echo Html::encode($this->title) ?></h1>
    <?php echo $this->render('_characteristics-form', ['ruleModel'=>$ruleModel,'searchModel' => $searchModel,'dataProvider' => $dataProvider,'extendModel'=>$extendModel,'escapetemplate'=>$escapetemplate]); ?>

</div>