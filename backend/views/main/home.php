
<?php use backend\components\widgets\LinkPager;

$this->shownav('index', 'menu_home'); ?>
<style>
table {
    border-collapse:separate;
    border:solid gray 1px;
    border-radius:6px;
    -moz-border-radius:6px;
}

td, th {
    border-left:solid black 1px;
    border-top:solid black 1px;
}

th {
    background-color: blue;
    border-top: none;
}

td:first-child, th:first-child {
     border-left: none;
}
</style>

Welcome, <?php echo date('y-m-d H:i:s'); ?>

<table>
<?php if (! \yii::$app->request->get('key')) : ?>
    <tr>
        <td>名称</td>
        <td>脚本名称</td>
        <td>控制名称</td>
        <td>数量</td>
        <td>操作</td>
    </tr>
    <?php foreach($redisList as $val): ?>
    <tr>
        <td><?php echo $val['key'] ?></td>
        <td><?php echo $val['name'] ?></td>
        <td><?php echo $val['actionName'] ?></td>
        <td><?php echo $val['length'] ?></td>
        <td><a href="<?php echo \yii\helpers\Url::toRoute(['main/get-list','key'=>$val['key']])?>">查看</a></td>
    </tr>
    <?php endforeach; ?>

    <?php foreach($redisIncr as $val): ?>
        <tr>
            <td><?php echo $val['key'] ?></td>
            <td><?php echo $val['name'] ?></td>
            <td><?php echo $val['actionName'] ?></td>
            <td><?php echo $val['length'] ?></td>
            <td></td>
        </tr>
    <?php endforeach; ?>
<?php else : ?>
    <?php if ($Key == 'list:user:get:phone:captcha') :?>
        <tr>
            <td>电话</td>
            <td>内容</td>
            <td>类型</td>
            <td>是否记录</td>
        </tr>
    <?php else :?>
        <tr><td>内容</td></tr>
    <?php endif; ?>
    <?php foreach($List as $key1 => $val) :?>
    <tr>
        <?php
        if ($Key == 'list:user:get:phone:captcha') {
            $res = json_decode($val,true);
            echo "<td>{$res['phone']}</td>";
            if ($res['content']) {
                echo '<td>'.urldecode($res['content']).'</td>';
            }
            echo "<td>{$res['type']}</td>";
            echo "<td>{$res['db_log']}</td>";
        }else {
            echo "<td>{$val}</td>";
        }
        ?>
    </tr>
    <?php endforeach;?>
    <?php echo LinkPager::widget(['pagination' => $pages]); ?> 一共<?php echo $Count?>条
<?php endif; ?>
</table>
