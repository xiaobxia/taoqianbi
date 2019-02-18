<?php

use yii\helpers\Url;
?>
<form action="<?= Url::to(['/loan/upload-document'], true) ?>" method="post">
    <input type="hidden" name="fuck" value="<?= $id ?>"> 
    <input height="200" width="200"  type="file" name="photo" value="上传还款凭证"/>
    <button height="200" width="200" >确认上传</button> 
</form>