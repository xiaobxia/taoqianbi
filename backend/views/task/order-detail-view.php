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
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<style>

    .person {
        border:1px solid darkgray;
        background: #f5f5f5 none repeat scroll 0 0;
        font-weight: bold;
    }
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
        <tr><th class="partition" colspan="10">任务详情</th></tr>
        <tr>
            <th width="110px;" class="person">任务</th>
            <td style="padding: 2px;margin-bottom: 1px; border:1px solid darkgray;">
                <table style="margin-bottom: 0px" class="table">
                    <tr>
                        <th >任务ID：</th>
                        <td><?php echo $data['id'];?></td>
                        <th>任务标题：</th>
                        <td><?php echo $data['title'];?></td>
                        <th >任务类型：</th>
                        <td><?php echo $data['type'];?></td>
                        <th>任务状态：</th>
                        <td><?php echo $data['status'];?></td>
                    </tr>
                    <tr>
                        <th >任务执行时间：</th>
                        <td><?php echo $data['excute_start'];?></td>
                        <th >任务结束时间：</th>
                        <td><?php echo $data['excute_end'];?></td>
                        <th>任务创建时间：</th>
                        <td><?php echo $data['created_at'];?></td>
                        <th>任务更新时间：</th>
                        <td><?php echo $data['updated_at'];?></td>
                    </tr>
                    <tr>
                        <th >任务创建人：</th>
                        <td><?php echo $data['created_by'];?></td>
                        <th >任务操作人：</th>
                        <td><?php echo $data['operator_name'];?></td>
                    </tr>
                    </tr>

                </table>
            </td>
        </tr>

        <tr>
            <th width="110px;" class="person">任务详情</th>
            <td><?php echo $data['remark'];?></td>
        </tr>
        <tr>
            <th width="110px;" class="person">任务条件</th>
            <td><?php echo $data['excute_task'];?></td>
        </tr>
        <tr>
            <th width="110px;" class="person">任务备注</th>
            <td><?php echo $data['remark'];?></td>
        </tr>

    </table>










