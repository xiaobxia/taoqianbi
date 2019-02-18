<?php
if(empty($pic_arr)){
    echo "不存在此类型图片记录！";
    return;
}

?>
<?php foreach($pic_arr as $k => $v):
    $k++;
    ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.rotate.min.js'); ?>"></script>
    <div style="text-align: center;margin: 0 auto; margin-top: 10px;">
         <img id="img1" src="http://res.kdqugou.com/loan_record/<?php echo $loan_record_id ?>/<?php echo $v;?>">
        <div style="text-align: center; margin-top: 5px; font-size: 14px; color: red;font-weight: bold;">
            <?php if($handle == \common\models\LoanRecordPeriod::HANDLE_TRIAL):?>
                <?php echo \common\models\LoanTrial::$column_desc[$type][$column]['title']."(".$k.")"?>
            <?php elseif($handle == \common\models\LoanRecordPeriod::HANDLE_REVIEW):?>
                <?php echo \common\models\LoanReview::$column_desc[$type][$column]['title']."(".$k.")"?>
            <?php endif;?>
<!--            <a href="--><?php //echo \yii\helpers\Url::toRoute(['loan-period/delete-pic', 'handle' => $handle, 'type' => $type, 'column' => $column, 'pic_url' => $v,'loan_record_period_id' => $loan_record_id])?><!--">删除</a>-->
        </div>
        <hr style=" height: 5px; background-color: yellow;">
    </div>
<?php endforeach;?>
<script>
    var value = 0;
    $("img").rotate({
        bind: {
            click: function () {
                value += 90;
                $(this).rotate({animateTo: value})
            }
        }
    })
</script>
