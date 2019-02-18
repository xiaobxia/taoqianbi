<?php

use yii\helpers\Html;


?>
<div class="escape-template-list">

    <?php echo $this->render('_escape-template-form', ['searchModel' => $searchModel,'dataProvider' => $dataProvider,'model' => $model]); ?>

</div>