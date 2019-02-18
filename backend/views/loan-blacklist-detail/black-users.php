<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:21
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
use backend\components\widgets\LinkPager;
$this->shownav('loan', 'loan_blacklist_list');
$this->showsubmenu('黑名单规则列表', array(
    array('黑名单规则列表', Url::toRoute('loan-blacklist-detail/list'), 0),
    array('黑名单规则添加', Url::toRoute('loan-blacklist-detail/add'),0),
    array('黑名单用户列表', Url::toRoute('loan-blacklist-detail/black-users'),1)
));
?>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;&nbsp;
<button class="btn reset">重置</button>&nbsp;&nbsp;
<input type="submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
        <tr class="header">
            <th>用户ID</th>
            <th>用户姓名</th>
            <th>用户手机</th>
            <th>初次拉黑时间</th>
            <th>拉黑总次数</th>
            <th>最后拉黑时间</th>
            <th>操作人</th>
            <th>备注</th>
            <th>操作</th>
        </tr>
    <?php foreach ($list as $value): ?>
        <tr class="hover">
            <td><?php echo $value['user_id']; ?></td>
            <td><?php echo empty($value['name'])?'未知用户':$value['name']; ?></td>
            <td><?php echo $value['phone']; ?></td>
            <td><?php echo date('Y-m-d H:i:s',$value['created_at']) ?></td>
            <td><?php echo $value['black_count']; ?></td>
            <td><?php echo date('Y-m-d H:i:s',$value['updated_at']) ?></td>
            <td><?php echo $value['black_admin_user'] ?></td>
            <td><?php echo empty($value['black_remark'])?'暂无备注':$value['black_remark']; ?></td>
            <td>
                <a href="Javascript:;" onclick="del(<?php echo $value['id'];?>)">删除</a>
            </td>
        </tr>
    <?php endforeach; ?>
        </table>
    <?php if (empty($list)): ?>
        <div class="no-result">暂无记录</div>
    <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    $('.reset').click(function(){
        $('.txt').val('');
        return false;
    })
    function del(id){
        if(!confirmMsg('是否取消黑名')){
            return false;
        }
        $param = {
            id:id
        };
        var url = '<?php echo Url::toRoute(['loan-black-list/del'])?>';
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