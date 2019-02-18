<?php
/**
 * Created by Sublime Text.
 * User: 李振国
 * Date: 2016/10/26
 * Time: 19:46
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\models\loan\LoanCollection;
?>
<style>.tb2 th{ font-size: 12px;}</style>

<table class="tb tb2 fixpadding">
    <thead>
    <tr class="header">
        <th>ID</th>
        <th>公司名称</th>
        <th>用户组</th>
        <th>当前人数</th>
        <th>每天每人最大接单数</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($user_collection as $value): ?>
        <tr class="hover"  data-company="<?php echo $value['company_id']?>" data-group="<?php echo $value['group_id']?>" >
            <td><?php echo $value['id']; ?></td>
            <td><?php echo $value['company_title']; ?></td>
            <td><?php echo $value['group_title']; ?></td>
            <td><?php echo $value['group_amount']; ?></td>
            <td class="max_amount">
                <?php if($value['system']):?>
                    <span>自营团队，无上限</span>
                <?php else:?>
                <input value="<?php echo $value['max_amount']; ?>"/>
                <?php endif;?>
            </td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (empty($user_collection)): ?>
    <div class="no-result">暂无记录</div>
<?php else:?>
<button class="btn btn-success" name="btn_update_user_schedule">更新</button><br/>

<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

<script>
    $(function(){
        $('[name=btn_update_user_schedule').click(function(){
            $('tbody tr').each(function(index, el){
                update_user_schedule($(el).attr('data-company'), $(el).attr('data-group'), $(el).children('td.max_amount').children('input').val());
            });
            alert('更新完毕');
        });
    });

    //更新每组每天最大接单量：
    function update_user_schedule(company, group, amount){

        var params = {company_id:company, group_id:group, max_amount:amount};
        $.ajax({
            url:'<?php echo Url::toRoute(["user-company/schedule-update"])?>',
            async:false,
            data:params,
            dataType:'json',
            success:function(data){

                // console.log(data.code);
            },
            error:function(){
                alert('ajax error');
            }
        });

    }


    function updatedisable(id){
        if(!confirmMsg('确认要禁用吗?')){
            return false;
        }
        var url = '<?php echo Url::toRoute(["user-collection/update"]);?>';
        var params = {id:id,status:-1};
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('操作成功');
                window.location.reload(true);
            }else{
                alert(data.message);
            }
        })
    }
    function updateable(id){
        if(!confirmMsg('确认要启用吗?')){
            return false;
        }
        var url = '<?php echo Url::toRoute(["user-collection/update"]);?>';
        var params = {id:id,status:2};
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('操作成功');
                window.location.reload(true);
            }else{
                alert(data.message);
            }
        })
    }

    function delBlackList(id){
        if(!confirmMsg('确认取消黑名单')){
            return false;
        }
        var url = '<?php echo Url::toRoute(["loan-black-list/del"]);?>';
        var params = {id:id};
        $.get(url,params,function(data){
            if(data.code == 0){
                alert('取消成功');
                window.location.reload(true);
            }else{
                alert(data.message);
            }
        })
    }
</script>
