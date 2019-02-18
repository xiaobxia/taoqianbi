<?php
use yii\helpers\Html;
use yii\helpers\Url;


/* @var $this \yii\web\View view component instance */
/* @var $message \yii\mail\BaseMessage instance of newly created mail message */

?>
<style type="text/css">
	table{
		width:500px;
		margin-left:16px;
	}
</style>
<p>您好：感谢你使用员工帮<br/>
<table border="1" cellpadding="0" cellspacing="0">
	<tr>
		<td>验证码：<?php if(!empty($context['code'])){ echo $context['code'];}?></td>
	</tr>
</table>
<br>