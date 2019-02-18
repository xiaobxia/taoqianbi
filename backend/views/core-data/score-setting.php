<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/9/26
 * Time: 11:41
 */
use common\helpers\Url;
use yii\helpers\Html;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
?>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<link rel="Stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/loginDialog.css'); ?>?v=201610311550" />
<h3 style="color: #3325ff;font-size: 14px">评分统计设置</h3>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['core-data/score-setting'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    评分树：<?php echo Html::dropDownList('tree_id', Yii::$app->request->get('tree_id', ''), \common\models\ScoreSetting::$score_name); ?>&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->request->get('status', ''), \common\models\ScoreSetting::$status); ?>&nbsp;

    <input type="submit" name="search_submit" value="过滤" class="btn">&nbsp;
    <input type="button" name="search_submit" style="float: right" onclick="addNewStandard();" value="新建" class="btn">&nbsp;
<?php ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding" style="border: 1px solid">

        <tr class="header">
            <th style="border: 1px solid;text-align: center">ID</th>
            <th style="border: 1px solid;text-align: center">评分树ID</th>
            <th style="border: 1px solid;text-align: center">评分树名称</th>
            <th style="border: 1px solid;text-align: center">标准线</th>
            <th style="border: 1px solid;text-align: center">状态</th>
            <th style="border: 1px solid;text-align: center">操作</th>

        </tr>

        <?php foreach($data as $k=> $item):?>
            <tr class="hover">
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $item['id']; ?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php  echo $item['tree_id']; ?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php if($item['tree_id']==1) echo '信用评分树'; else echo '欺诈评分树'; ?></td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php echo $item['standard_line']?>%</td>
                <td style="width: 12%;border: 1px solid;text-align: center" class="td25"><?php if($item['status']==0)  echo '启用';
                    else echo '停用'?></td>
                <td style="width:20%;border: 1px solid;text-align: center" class="td25">
                    <?php if($item['status'] == 0){?>
                <input type="button" value="停用" onclick="changeStatus('<?php echo $item['id']; ?>');" class="btn" id ='stop'>
                    <?php }else{ ?>
                    <input type="button" value="启用" onclick="changeStatus('<?php echo $item['id']; ?>');" class="btn" id="start">
                    <?php }?>
                    <input type="button" value="删除" onclick="deleteStandardLine('<?php echo $item['id']; ?>');" class="btn">
                    <a href="<?php echo Url::toRoute(['core-data/get-user-score','id'=>$item['id']])?>" <input type="button" class="btn" value="查看统计" onclick="viewStatistics('<?php echo $item['id']; ?>')">查看统计</a>
                       </td>
            </tr>
        <?php endforeach?>
    </table>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
    <?php if (empty($data)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>

<div id="LoginBox" class="login-box" style="display:none">

    <div class="row1">
        新建评分设置<a href="javascript:void(0)" title="关闭窗口" class="close_btn" id="closeBtn">×</a>
    </div>

    <div>
        <div style="text-align: center;font-size: 14px;">评分树：<select id="tree_type" style="width:100px;height: 25px;margin-left: 10px">
            <option value="1" >信用评分树</option>
            <option value="2" >欺诈评分树</option>
        </select>


        <div style="text-align:center;font-size:14px;margin: 20px 0 20px 20px ;">标准线：<input type="text" id="standard" style="width:100px;height: 20px;margin-left: 10px">%</div>
       <div> <input type="button" value="提交" onclick="newStandardLine();"  class="btn"></div>
        </div>
    </div>

</div>

<script type="text/javascript">
    function changeStatus(id) {

        var id = id;
        var url = "<?php echo Url::toRoute(['core-data/change-status']) ?>"
        $.ajax({
            type: 'POST',
            url: url,
            data: {id: id,_csrf:"<?php echo Yii::$app->request->csrfToken ?>"},
            dataType: "json",
            success: function (data) {
                if (data == 1) {
                    alert('状态更改失败')
                    location.reload()
                } else {
                    alert('状态更改成功');
                    location.reload()
                }
            }
        })
    }
    function deleteStandardLine(id){
        var id = id;
        var url = "<?php echo Url::toRoute(['core-data/delete-standard-line']) ?>"
        $.ajax({
            type: 'POST',
            url: url,
            data: {id: id,_csrf:"<?php echo Yii::$app->request->csrfToken ?>"},
            dataType: "json",
            success: function (data) {
                if (data == 1) {
                    alert('删除失败')
                    location.reload()
                } else {
                    alert('删除成功');
                    location.reload()
                }
            }
        })
    }
    function addNewStandard(){
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
    function newStandardLine(){
        var url = "<?php echo Url::toRoute(['core-data/new-standard-line']) ?>"
        var type = $("#tree_type").val();
        var standard = $("#standard").val();
        $.ajax({
            type:'post',
            url:url,
            data:{tree_id:type,_csrf:"<?php echo Yii::$app->request->csrfToken ?>",
                standard_line:standard,_csrf:"<?php echo Yii::$app->request->csrfToken ?>"
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
    function viewStatistics(id){
        var id = id;
        var url = "<?php echo Url::toRoute(['core-data/get-user-score']) ?>";
        $.ajax({
            type: 'POST',
            url: url,
            data: {id: id,_csrf:"<?php echo Yii::$app->request->csrfToken ?>"},
            dataType: "json",
            success: function (data) {
            }
        })
    }
</script>
