<?php

use yii\helpers\Html;


?>
<div class="escape-rule-list">

    <?php echo $this->render('_escape-rule-form', ['searchModel' => $searchModel,'dataProvider' => $dataProvider,'model' => $model,'template_id' => $template_id]); ?>

</div>