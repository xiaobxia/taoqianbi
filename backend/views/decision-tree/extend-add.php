<?php

use yii\helpers\Html;

$this->title = '编辑特征';

?>
<div class="characteristics-update">

    <h1><?php echo Html::encode($this->title) ?></h1>
    <?php echo $this->render('_characteristics-form', ['ruleModel'=>$ruleModel,'searchModel' => $searchModel,'dataProvider' => $dataProvider,'extendModel' =>$extendModel,'escapetemplate'=>$escapetemplate,'add_map'=>$add_map]); ?>

</div>