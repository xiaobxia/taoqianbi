<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\models\CreditZmop;
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
    借款人名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
    联系方式：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
   <input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>借款人类型</th>
                <th>姓名</th>
                <th>联系方式</th>
                <th>获取时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($loan_person as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <th><?php echo empty($value['type'])?'--':LoanPerson::$person_type[$value['type']]; ?></th>
                    <th><?php echo $value['name']; ?></th>
                    <th><?php echo $value['phone']; ?></th>
                    <th><?php echo empty($value['updated_at']) ? '--' : date('Y-m-d H:i:s',$value['updated_at']);?></th>
                    <td>
                            <a href="<?php echo Url::toRoute(['user-view', 'id' => $value['id']]); ?>">查看</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($loan_person)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    function sendZmMsm(id){
        var url = '<?php echo Url::toRoute(['zmop/batch-feedback']);?>';
        var params = {id:id};
        var ret = confirmMsg('确认发送');
        if(!ret){
            return false;
        }
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('发送成功');
            }else{
                alert(data.message);
            }
        },'json')
    }
</script>
