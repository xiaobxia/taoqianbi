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
        <th>自营</th>
        <th>创建时间</th>
        <th>操作</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($user_collection as $value): ?>
        <tr class="hover"  >
            <td><?php echo $value['id']; ?></td>
            <td><?php echo $value['title']; ?></td>
            <td><?php echo $value['system'] == true ? '是' : '否';?></td>
            <td><?php echo date('Y-m-d H:i:s',$value['add_time']);?></td>
            <td>
                <?php if (!$value['system']): ?>
                    <a href="<?php echo Url::toRoute(['user-company/company-edit','id'=>$value['id']])?>" class="btn_edit">编辑</a>&nbsp;&nbsp;
                    <a href="javascript:;" class="btn_del" data-company="<?php echo $value['id']?>" style="color:red;">删除</a>
                <?php endif;?>
            </td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if (empty($user_collection)): ?>
    <div class="no-result">暂无记录</div>
<?php else:?>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>

<a href="<?php echo Url::toRoute(['user-company/company-add','tip'=>0]);?>">新增公司</a>
<script>
    $(function(){
        $('.btn_del').click(function(){
            if(!confirmMsg('确认要删除吗?')){
                return false;
            }
            company_id = $(this).attr('data-company');
            console.log(company_id);
            del_company(company_id);
        });
    });

    //删除指定公司：
    function del_company(company){

        var params = {company_id:company};
        $.ajax({
            url:'<?php echo Url::toRoute(["user-company/del-company"])?>',
            async:false,
            data:params,
            dataType:'json',
            success:function(data){
                if (data.code == 0) {
                    alert('删除成功');
                }else{
                    alert(data.msg);
                }
                window.location.reload(true);
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
