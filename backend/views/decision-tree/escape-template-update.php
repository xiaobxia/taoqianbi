<?php

use yii\helpers\Html;


?>
<div class="escape-template-update">

    <?php echo $this->render('_escape-template-form', ['model'=>$model,'searchModel' => $searchModel,'dataProvider' => $dataProvider,'add_map'=>$add_map]); ?>

</div>