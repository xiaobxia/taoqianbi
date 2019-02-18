<?php

use yii\helpers\Url;
?>
<form action="<?= Url::to(['/loan/leaving-message'], true) ?>" method="post">
    <input type="hidden" name="fuck" value="<?= $id ?>"> 
    <p>留言内容: <input type="text" name="fname" value='' /></p>

    <input type="submit" value="Submit" />
</form>