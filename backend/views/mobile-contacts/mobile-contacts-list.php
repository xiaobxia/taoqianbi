<?php

use common\helpers\Url;
use common\helpers\StringHelper;
use yii\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
use yii\helpers\Html;
use common\models\LoanProject;
use common\models\LoanRecordPeriod;
use common\models\LoanRecord;
use common\models\LoanRepayment;


$this->shownav('user', 'loan_person_message_log');
$this->showsubmenu('用户通讯录', array(
    array('列表', Url::toRoute('mobile-contacts/mobile-contacts-list'), 1)
));
?>

<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.js'); ?>"></script>



<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => Url::toRoute(['mobile-contacts/mobile-contacts-list']), 'options' => ['style' => 'margin-top:5px;']]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
联系人手机：<input type="text" value="<?php echo Yii::$app->getRequest()->get('mobile', ''); ?>" name="mobile" class="txt" style="width:60px;">&nbsp;
联系人姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:60px;">&nbsp;
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php if(!empty(Yii::$app->request->get('user_id'))): ?>
    <input style="display: none" type="button" name="search_submit" value="导出到CVS" onclick="exportmobile(<?php echo Yii::$app->request->get('user_id'); ?>);" class="btn">
<?php endif;?>
<?php ActiveForm::end(); ?>
<form name="listform" method="get">
<table class="tb fixpadding">
    <tr class="header">
        <th>选择</th>
        <!-- <th>用户ID</th> -->
        <th>用户姓名/手机</th>
        <th>联系人</th>
        <th>联系人手机</th>
        <th>上传时间</th>
    </tr>
    <?php foreach ($loan_mobile_contacts_list as $value): ?>
        <tr class="hover">
            <td class="td25"><input type="checkbox" name="contactphone" value="<?php echo $value['mobile']; ?>"></td>
           <!--  <td class="td25"><?php //echo $value['loanPerson']['id']; ?></td> -->
                <td class="td25"><?php echo $value['user_id']; ?></td>
                <td class="td25"><?php echo $value['name']; ?></td>
                <td class="td25"><?php echo $value['mobile']; ?></td>
                <td class="td25"><?php echo $value['created_at'] > 1000000 ? date('Y-m-d H:i:s',$value['created_at']) : $value['created_at']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>
    <input type="checkbox" name="all_check">全选&nbsp;&nbsp;&nbsp;&nbsp;
<!--     <button id="send_button">发送短息</button><br> -->
    <?php if (empty($loan_mobile_contacts_list)): ?>
        <div class="no-result">暂无记录</div>
<?php endif; ?>
<!-- </form> -->
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script type="text/javascript">
    // $('#send_button').click(function(){
    //     var ids = [];
    //     $('input[name=ids]').each(function(){
    //         if ($(this).prop('checked')) {
    //             ids.push($(this).val());
    //         }
    //         if (ids.length ==0) {
    //             alert('请选择后再操作！');
    //             return false;
    //         }else{
    //             ids = ids.join();
    //             var url = <?php echo '"', urldecode(Url::toRoute(['collection/loan-collection-outside-edit', 'id' => 0, 'ids' => '" + ids'])); ?>;
    //             window.location = url;
    //         }
    //     });
    // });
    $('input[name=all_check]').click(function(){
    if ($(this).prop('checked')) {
        $('input[name=contactphone]').each(function(){
            $(this).prop('checked',true);
        });
        }else{
        	$('input[name=contactphone]').each(function(){
            $(this).prop('checked',false);
        });
    };
});

function exportmobile(user_id){
    user_id = user_id.toString();
    var url = <?php echo '"', urldecode(Url::toRoute(['mobile-contacts/mobile-contacts-export', 'user_id' => '" + user_id'])); ?>;
    window.location = url;
}
</script>
