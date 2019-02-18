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
use common\models\LoanBlacklistDetail;
use common\models\LoanPerson;
use common\models\User;
use common\models\UserLoanOrder;
$this->shownav('user', 'loan_blacklist_list');
$this->showsubmenu('黑名单规则列表', array(
    array('黑名单规则列表', Url::toRoute('loan-blacklist-detail/list'), 1),
    array('黑名单规则添加', Url::toRoute('loan-blacklist-detail/add'),0),
    array('黑名单用户列表', Url::toRoute('loan-blacklist-detail/black-users'),0)
));
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
规则类型：<?php echo Html::dropDownList('type',Yii::$app->getRequest()->get('type',''),LoanBlacklistDetail::$type_list,['prompt'=>'全部'])?>
录入方式：<?php echo Html::dropDownList('source',Yii::$app->getRequest()->get('source',''),LoanBlacklistDetail::$source_list,['prompt'=>'全部'])?>
规则内容：<input type="text" value="<?php echo Yii::$app->getRequest()->get('content', ''); ?>" name="content" class="txt" style="width:120px;">&nbsp;
修改时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>关联用户ID</th>
                <th>规则类型</th>
                <th>规则内容</th>
                <th>录入方式</th>
                <th>录入人</th>
                <th>录入时间</th>
                <th>修改时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($list as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo LoanBlacklistDetail::$type_list[$value['type']]; ?></td>
                    <th><?php echo $value['content']; ?></th>
                    <th><?php echo LoanBlacklistDetail::$source_list[$value['source']]; ?></th>
                    <th><?php echo $value['admin_username'] ?></th>
                    <th><?php echo date('Y-m-d H:i:s',$value['created_at']) ?></th>
                    <th><?php echo date('Y-m-d H:i:s',$value['updated_at']) ?></th>
                    <th>
                        <a href="<?php echo Url::toRoute(['edit', 'id' => $value['id']]);?>">编辑</a>
                        <a href="Javascript:;" onclick="del(<?php echo $value['id'];?>)">删除</a>
                    </th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    function del(id){
        if(!confirmMsg('是否删除')){
            return false;
        }
        $param = {
            id:id
        };
        var url = '<?php echo Url::toRoute(['del'])?>';
        $.get(url,$param,function(data){
            if(data.code == 0){
                alert('删除成功');
                window.location.reload(true);
            }else{
                alert(data.message);
                window.location.reload(true);
            }
        });

    }
</script>
