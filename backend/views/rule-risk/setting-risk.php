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
<h3 style="color: #3325ff;font-size: 14px">规则任务设置</h3>

    <input type="button" name="search_submit" style="float: right" onclick="addNewStandard();" value="新建规则任务" class="btn">&nbsp;

    <table class="tb tb2 fixpadding">

        <tr>
           <th style="border: 1px solid;text-align: center">ID</th>
            <th style="border: 1px solid;overflow: hidden">规则id</th>
            <th style="border: 1px solid;text-align: center">创建时间</th>

            <th style="border: 1px solid;text-align: center">操作</th>

        </tr>

        <?php foreach($data as $k=> $item):?>
            <tr class="hover">
                <td style="border: 1px solid;text-align: center" class="td25"><?php echo $item['id']; ?></td>
                <td style="border: 1px solid;overflow: hidden" class="td25"><?php  echo $item['rule_ids']; ?></td>
                <td style="border: 1px solid;text-align: center" class="td25"><?php  echo date('Y-m-d H:i:s',$item['created_at']); ?></td>
                <td style="border: 1px solid;text-align: center" class="td25">
                    <?php if($item['status'] == 0){?>
                        <input type="button" value="待处理" onclick="changeStatus('<?php echo $item['id']; ?>');" class="btn" id ='stop'>
                    <?php }else{ ?>
                       <a href="<?php echo Url::toRoute(['rule-risk/download','id'=>$item['id']])?>">下载</a>
                    <?php }?>

                </td>
            </tr>
        <?php endforeach?>
    </table>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
    <?php if (empty($data)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>

<div id="LoginBox" class="login-box" style="display:none;height: 385px">
    <form action="<?php echo Url::toRoute(['rule-risk/get-rule-value']) ?>" method="post" enctype="multipart/form-data">
        <div class="row1">
            新建任务<a href="javascript:void(0)" title="关闭窗口" class="close_btn" id="closeBtn">×</a>
        </div>

        <div>
            <div style="text-align: center;font-size: 14px;">规则id：<input type="text" id="rule_ids" value="" name="rule_ids" style="width: 270px;height: 20px;margin-left: 10px;margin-top: 30px;margin-right: 30px">
                <p style="color: red;font-size: 12px">(*多个rule_id用','间隔!)</p>

                <div style="text-align:left;font-size:14px;margin: 40px 0 20px 20px ;margin-left: 45px"> 文件：<?php echo Html::fileInput('attach'); ?>
                    <p style="color: red;font-size: 12px">(*请上传内容为user_id的文件,user_id之间用","间隔!)</span>只支持txt格式的文件</p></div>
                <div style="text-align:left;font-size:14px;margin: 40px 0 20px 20px ;margin-left: 45px"> 文件：<?php echo Html::fileInput('order'); ?>
                    <p style="color: red;font-size: 12px">(*请上传内容为order_id的文件,order_id之间用","间隔!)</span>只支持txt格式的文件</p></div>
                <div> <input type="submit" value="提交"  class="btn"></div>
            </div>
        </div>
        <input type="hidden" name="_csrf" value="<?php echo Yii::$app->request->csrfToken?>">
    </form>



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
//    function newStandardLine(){
//        var url = "<?php //echo Url::toRoute(['rule-risk/get-rule-value']) ?>//"
//        var formData = new FormData();
//        var rules_id = $("#rule_ids").val();
//        formData.append("file",$("#file")[0].files[0]);
//      alert(formData);
//        $.ajax({
//            type: 'POST',
//            url: url,
//            data: {rules_id: rules_id,_csrf:"<?//= Yii::$app->request->csrfToken ?>//"},
//            dataType: "json",
//            success: function (data) {
//                console.log(data);
//                if (data == 1) {
//                    alert('删除失败');
//
//                } else {
//                    alert('删除成功');
//
//                }
//            }
//        })
//            }

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
