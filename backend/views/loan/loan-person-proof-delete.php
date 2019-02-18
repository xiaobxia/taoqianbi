<?php
use common\helpers\Url;
use common\models\UserQuotaPersonInfo;
use common\models\UserContact;
use common\models\CardInfo;
use common\models\UserProofMateria;
use common\models\loanPerson;
?>

<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.photo.gallery.js'); ?>"></script>
<style>

    .table {
        max-width: 100%;
        width: 100%;
        border:1px solid #ddd;
    }
    .table th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .table td{
        border:1px solid darkgray;
    }
    .tb2 th{
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
        width:100px
    }
    .tb2 td{
        border:1px solid darkgray;
    }
    .tb2 {
        border:1px solid darkgray;
    }
    .mark {
        font-weight: bold;
        /*background-color:indianred;*/
        color:red;
    }
</style>
<table class="tb tb2 fixpadding">
<tr><th class="partition" colspan="15">删除用户证明/照片</th></tr>
<?php foreach($loanPersonProofType as $type_key=> $type_item):?>
<tr>
    <th><?php echo $type_item['title'] ?></th>
    <td>
    <?php foreach($loanPersonProofInfo as $key=> $info):?>
    <?php if($type_key==$info['type']):?>
    <div class="del_proof gallerys" id="proof_<?php echo $info['id'];?>" style="display:inline;background: #ddd;">
        <img class="gallery-pic" height="100" src="<?php echo $info['url'];?>"/>
        <input type="button" id="delete_submit" value="X" onclick="delete_proof_info(<?php echo $info['id'];?>,<?php echo $info['user_id']?>,<?php echo $info['type']?>,<?php echo $approve_application_id?>);"/>
    </div>
    <?php endif?>
    <?php endforeach?>
   <!-- <div>
        <a style="">点此出当前分类信息全部删除</a>
    </div>
    -->
    </td>
</tr>
<?php endforeach?>
</table>
<script type="text/javascript">
    function delete_proof_info(proof_id,user_id,photo_type,approve_application_id)
    {

        $.ajax({
            <?php
            if($list_type=='custom'){//根据菜单类型执行不同的操作；
                $string='custom-management/loan-person-proof-delete-operate';
            }else{
                $string='loan/loan-person-proof-delete-operate';
            }
            ?>
            url:"<?php echo Url::toRoute([$string])?>",
            type : 'GET',
            data : {proof_id:proof_id,user_id:user_id,photo_type:photo_type,approve_application_id:approve_application_id
                },
            dataType : 'text',
            contentType : 'application/x-www-form-urlencoded',
            async : false,
            success : function(mydata) {
//                alert('删除成功');
                $('#proof_'+proof_id).remove();
            },
            error : function() {
                alert("申请已受理，无法修改相片");
            }
        });
    }
    $(function(){
        $('.gallery-pic').click(function(){
            $.openPhotoGallery(this);
        });
    });
</script>
