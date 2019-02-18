<?php
/**
 * Created by phpdesigner.
 * User: user
 * Author：chengyunbo
 * Date: 2016/11/4
 * Time: 11:00
 */
use backend\components\widgets\ActiveForm;
use common\models\LoanCollection;
use common\models\UserOperateApplication;
use common\helpers\Url;
use yii\helpers\Html;
?>
<style>
    td.label {
        width: 170px;
        text-align: right;
        font-weight: 700;
    }
    .txt{ width: 100px;}

    .tb2 .txt, .tb2 .txtnobd {
        width: 200px;
        margin-right: 10px;
    }
</style>

<?php $form = ActiveForm::begin(['method' => "post",'id' => 'get-mobile-list-request']); ?>
<table class="tb tb2 fixpadding">
    <tr>
    <td class="label" >操作方式</td>
    <td>
        <?php echo Html::dropDownList('operate', Yii::$app->getRequest()->get('operate', 0), UserOperateApplication::$type,array('prompt' => '-所有操作-','onchange'=>"showNotice(this)")); ?>
    </td>
    </tr>
    <tr>
        <td class="label" >操作内容</td>
        <td>
            <?php echo Html::textarea('remark','',['style' => 'background-color:white', 'rows' => 6, 'cols' => 50]); ?>
        </td>
    </tr>
    <tr>
        <td class="label"></td>
        <td >
            <input type="submit" value="提交申请" name="submit_btn" class="btn"/>
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
<script type="text/javascript">
function showNotice(obj){
    if(obj.value==<?php echo UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE?>){
        $('textarea[name=remark]').val('修改手机号码为:189XXX;');
        $('textarea[name=remark]').css('color','red');
    }else{
        $('textarea[name=remark]').val('');
    }
}
$(document).ready(function() {
    //清空文本域内容
    $('textarea[name=remark]').focus(function(){
        //$(this).val('');
        $(this).css('color','black');
    });
    //提交校验
    $('input[name=submit_btn]').click(
        function(){
            remark = $('textarea[name=remark]').val();
            remark = remark.trim();
            if(remark==''){
                alert('操作内容不能为空！');
                return false;
            }
            if($('select[name=operate]').val()==<?php echo UserOperateApplication::OPERATE_UPDATE_PERSON_PHONE ?>)
            {
                var arr_info = new Array();
                if(remark.indexOf('；')>-1){//判断分号的中英文格式；
                    arr_info = remark.split('；');
                }else{
                    arr_info = remark.split(';');
                }
                var length = arr_info.length;
                if(length>1){
                    arr_info.pop();  //移除最后一个空元素
                }
                for(var i=0;i<arr_info.length;i++){
                    if(arr_info[i].indexOf('：')>-1){
                        info_str = arr_info[i].substr(arr_info[i].indexOf('：')+1);
                    }else{
                        info_str = arr_info[i].substr(arr_info[i].indexOf(':')+1);
                    }
                    if(i==0){//验证手机号码格式
                        var preg_str=/^1[0-9]{10}$/;
                        var tag = preg_str.test(info_str);
                        if(!tag){
                            alert('操作内容格式有误,请重新输入！');
                            return false;
                        }
                    }
                }
            }
        }
    );
 });
</script>
