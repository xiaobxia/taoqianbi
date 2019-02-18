<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/26
 * Time: 11:41
 */
use common\helpers\Url;
use yii\helpers\Html;
use common\models\LoanPersonChannelRebate;
use backend\components\widgets\ActiveForm;
?>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<link rel="Stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/loginDialog.css'); ?>?v=201610311550" />
<style>
    .chart-div {
        margin-top: 30px;
        width: 1780px;
        height: 600px;
        float: left;
    }
</style>

<script src="<?php echo Url::toStatic('/js/lodash.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/echarts3.min.js'); ?>" type='text/javascript'></script>
<script src="<?php echo Url::toStatic('/js/common_chart3.js'); ?>" type='text/javascript'></script>


<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['core-data/get-user-score'], 'options' => ['style' => 'margin-top:5px;']]); ?>
日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
<input type="hidden" name="id" value="<?php echo $set['id']?>">
<input type="submit" name="search_submit" value="过滤" class="btn">
<input type="button" name="search_submit"  <?php if($set['supplement_status']) echo "disabled".'='."disabled";?> style="float: right" onclick="addNewdata();"  value="<?php if($set['supplement_status'] ==1){
    echo "补充数据中";
} else{
    echo "补充数据";
}?>" class="btn">
    <div>
        <h3 style="text-align: center;color:#3325ff" ><?php if($set['tree_id']==1){
                echo '信用评分树'.$set['standard_line'].'%标准线统计';
            }  else {echo '欺诈评分树'.$set['standard_line'].'%标准线统计';}?></h3>
        <div id="score_standard" class="chart-div"></div>
        <div id="rate" class="chart-div"></div>
    </div>


<div id="LoginBox" class="login-box" style="display:none;position: center" >
    <div class="row1">
        补充数据<a href="javascript:void(0)" title="关闭窗口" class="close_btn" id="closeBtn">×</a>
        <div>

    </div>
        <div style="text-align:center;">
            <div style="font-size: 12px">开始日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  id="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;</div>
            <div style="font-size: 12px;margin-left:50px;">天数：<input type="text" id="days" style="margin-left: 25px;"><span>10天以内</span></div>
            <div style="float: none"> <input type="button" value="提交" onclick="supplementaryData();"  class="btn"></div>
            <input type="hidden" id="tree_id" value="<?php echo $set['tree_id']?>">
            <input type="hidden" id="set_id" value="<?php echo $set['id']?>">
            <input type="hidden" id="standard_line" value="<?php echo $set['standard_line']?>">

        </div>
    </div>

</div>

<script type="text/javascript">
    setBarChart1('<?php echo "标准分";?>', "score_standard",
        <?php echo json_encode($data);?>
    );
    setBarChart2('所占百分比', "rate",<?php echo json_encode($date);?>,<?php echo json_encode($big);?>,<?php echo json_encode($small)?>
    );
</script>
<script type="text/javascript">
    function addNewdata(){
        $("body").append("<div id='mask'></div>");
        $("#mask").addClass("mask").fadeIn("slow");
        $("#LoginBox").fadeIn("slow");
    }
    $("#loginbtn").hover(function () {
        $(this).stop().animate({
            opacity: '1'
        }, 600);
    }, function () {
        $(this).stop().animate({
            opacity: '0.8'
        }, 1000);
    });
    $(".close_btn").hover(function () { $(this).css({ color: 'black' }) }, function () { $(this).css({ color: '#999' }) }).on('click', function () {
        $(".login-box").fadeOut("fast");
        $("#mask").css({ display: 'none' });
    });
    function supplementaryData(){
        var url = "<?php echo Url::toRoute(['core-data/supplementary-data']) ?>"
        var tree_id = $('#tree_id').val();
        var set_id = $('#set_id').val();
        var date = $('#add_start').val();
        var days = $('#days').val();
        var rate = $('#standard_line').val();
        $.ajax({
            type:'post',
            url:url,
            data:{tree_id:tree_id,_csrf:"<?php echo Yii::$app->request->csrfToken ?>",
                set_id:set_id,
                date:date,
                days:days,
                rate:rate,
            },
            dataType:"json",
            success:function(data){
                if (data.status == 1) {
                    alert('设置失败')
                    location.reload()
                } else {
                    alert('设置成功');
                    location.reload()
                }
            }
        })
    }
</script>
