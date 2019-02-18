<?php

use yii\helpers\Html;

$this->title = '测试特征结果';

?>
<div class="characteristics-test">

    <h1><?php echo Html::encode($this->title) ?></h1>
    <?php echo $this->render('_characteristics-form', ['ruleModel'=>$ruleModel,'searchModel' => $searchModel,'dataProvider' => $dataProvider,'extendModel' =>$extendModel,'escapetemplate'=>$escapetemplate,'test'=>true]); ?>

</div>