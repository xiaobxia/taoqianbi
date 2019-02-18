<?php

use yii\helpers\Html;


?>
<div class="escape-rule-update">

    <?php echo $this->render('_escape-rule-form', ['searchModel' => $searchModel,'dataProvider' => $dataProvider,'model' => $model,'template_id' => $template_id,'add_map' => $add_map]); ?>

</div>