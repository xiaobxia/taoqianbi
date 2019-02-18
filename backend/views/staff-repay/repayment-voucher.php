<?php

use yii\helpers\Html;
use common\helpers\Url;

$this->showsubmenu('零钱包贷后管理', array(
        array('还款列表', Url::toRoute('staff-repay/pocket-repay-list'), 0),
    ));
?>
<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>凭证名称</th>
        <th>凭证内容</th>
        <th>图片链接（图片无法打开时可以复制链接去浏览器查看）</th>
        <th>上传时间</th>
    </tr>
        <tr class="hover">
            <td><?php echo isset($sql['url'])?'还款凭证图片':''; ?></td>
            <td><img src="<?php echo $sql['url']; ?>" /></td>
            <td><?php echo $sql['url']; ?></td>
            <td><?php echo date('Y-m-d H:i:s',$sql['created_at']); ?></td>
        </tr>
</table>
<?php if (empty($sql)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
