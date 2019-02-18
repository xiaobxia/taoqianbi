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
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <script type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" xmlns="http://www.w3.org/1999/html"></script>
    <link rel="Stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/loginDialog.css'); ?>?v=201610311550" />
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    <h3 style="color: #3325ff;font-size: 14px">错误信息</h3>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['zmop/error-message-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    用户ID：<input type="text" value="<?php echo Yii::$app->request->get('user_id'); ?>"  name="user_id" />&nbsp;
    类型：<?php echo Html::dropDownList('error_source', Yii::$app->request->get('error_source', ''), \common\models\ErrorMessage::$source); ?>&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->request->get('status', ''), \common\models\ErrorMessage::$status_remark); ?>&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
    <input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding" style="border: 1px solid">

        <tr class="header">
            <th style="border: 1px solid;text-align: center">ID</th>
            <th style="border: 1px solid;text-align: center">用户ID</th>
            <th style="border: 1px solid;text-align: center">错误信息</th>
            <th style="border: 1px solid;text-align: center">错误类型</th>
            <th style="border: 1px solid;text-align: center">错误时间</th>
            <th style="border: 1px solid;text-align: center">状态</th>
            <th style="border: 1px solid;text-align: center">操作</th>
        </tr>
        <?php foreach($message as $k=> $item):?>
            <tr class="hover">
                <td style="width:8%;border: 1px solid;text-align: center" class="td25"><?php echo $item['id']; ?></td>
                <td style="width:8%;border: 1px solid;text-align: center" class="td25"><?php  echo $item['user_id']; ?></td>
                <td class="td25" style="word-wrap: break-word; word-break: normal;word-break:break-all;max-width:400px;border: 1px solid;text-align: center"><div style="max-height:45px;overflow:hidden;text-align:left;" onclick="showmore(this);"><?php echo $item['message']?></div></td>
                <td style="width:12%;border: 1px solid;text-align: center" class="td25"><?php echo isset(\common\models\ErrorMessage::$source[$item['error_source']]) ? \common\models\ErrorMessage::$source[$item['error_source']] : "--"; ?></td>
                <td style="width:15%;border: 1px solid;text-align: center" class="td25"><?php echo date('Y-m-d H:i:s',$item['error_time'])?></td>
                <td style="width: 8%;border: 1px solid;text-align: center" class="td25" id="status-name-<?php echo $item['id']; ?>"><?php echo isset(\common\models\ErrorMessage::$status_remark[$item['status']]) ? \common\models\ErrorMessage::$status_remark[$item['status']] : "--"; ?></td>
                <td style="width: 10%;border: 1px solid;text-align: center" class="td25"><?php echo $item['status']==\common\models\ErrorMessage::STATUS_SUCCESS?'<font style="color:#ccc;">暂无</font>':'<span style="cursor:pointer;color:#3325ff;" onclick="errorsuccess('.$item['id'].',this);">已成功</span>' ?></td>
            </tr>
        <?php endforeach?>
    </table>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<?php if (empty($message)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<script type="text/javascript">
    function showmore(obj){
        $('.td25 .hot').removeClass('hot').css({'max-height':'45px'});
        if(!$(obj).hasClass('hot')){
            $(obj).addClass('hot').css({'max-height':'none'});
        }
    }

    function errorsuccess(id,obj){
        if(confirm('您确定要将该异常信息标记为处理成功吗，点击确认后不可撤销，是否继续？')){
            var csrfToken = $('meta[name="csrf-token"]').attr("content");
            var url='<?php echo Url::toRoute(['zmop/error-message-list']); ?>';
            $.post(url,{id:id.toString(),_csrf:csrfToken},function(data){
                if(data!=null){
                    if(data.result==1){
                        alert('恭喜您，该异常已标记为已处理！');
                        $(obj).html('<font style="color:#ccc;">暂无</font>');
                        $('#status-name-'+id.toString()).html('已成功');
                    }else{
                        alert('抱歉，操作失败！');
                    }
                }
            },'json');
        }
    }
</script>
