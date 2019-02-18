<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:34
 */
use backend\components\widgets\ActiveForm;
use common\models\DebitErrorLog;
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use common\models\BankConfig;
?>
<style>
    .remark{
        width:20px;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.modal.min.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get',  'options' => ['style' => 'margin-top:5px;']]); ?>
    ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:60px;" >&nbsp;
    银行卡号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('card_no', ''); ?>" name="card_no" class="txt" style="width:120px;">&nbsp;
    用户ID: <input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:120px;">&nbsp;
    类型: <?php echo Html::dropDownList('type',Yii::$app->getRequest()->get('type', ''),[0=>'全部']+DebitErrorLog::$ERROR_TYPE)?>&nbsp;
    手机号: <input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
    时间：<input type="text" value="<?php echo \yii::$app->request->get('created_at_begin', ''); ?>" name="created_at_begin" onfocus="WdatePicker({startDate:'%y-%M-%d 00:00:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    至<input type="text" value="<?php echo \yii::$app->request->get('created_at_end', ''); ?>"  name="created_at_end" onfocus="WdatePicker({startDate:'%y-%M-%d 00:00:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
    <input type="submit" name="search_submit" value="过滤" class="btn">
    <input style="display: none" type="submit" name="submitcsv" value="导出csv" onclick="$(this).val('exportcsv');return true;" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th >ID</th>
                <th >用户ID</th>
                <th >所属行</th>
                <th >卡号</th>
                <th >手机号</th>
                <th >渠道</th>
                <th>错误信息</th>
                <th >类型</th>
                <th >备注</th>
                <th >状态</th>
                <th >管理员ID</th>
                <th >创建时间</th>
                <th >修改时间</th>
                <th >操作</th>
            </tr>
            <?php foreach ($debitLogList as $item): ?>
                <tr>
                    <td><?php echo $item['id']?></td>
                    <td><?php echo $item['user_id']?></td>
                    <td><?php echo $item['bank_name']?></td>
                    <td><?php echo $item['card_no']?></td>
                    <td><?php echo $item['phone']?></td>
                    <td><?php echo isset(BankConfig::$platform[$item['platform']]) ? BankConfig::$platform[$item['platform']] : ''?></td>
                    <td><?php echo $item['error_msg']?></td>
                    <td><?php echo DebitErrorLog::$ERROR_TYPE[$item['type']]?></td>
                    <td><?php echo $item['remark']?></td>
                    <td><?php echo DebitErrorLog::$ERROR_STATUS[$item['status']]?></td>
                    <td><?php echo $item['admin_id']?></td>
                    <td><?php echo date('Y-m-d H:i:s',$item['created_at'])?></td>
                    <td><?php echo date('Y-m-d H:i:s',$item['updated_at'])?></td>
                    <td><a href="<?php echo Url::toRoute(['error-view', 'id' => $item['id']]);?>">查看</a></td>
                </tr>
            <?php endforeach;?>
        </table>
        <?php if (empty($debitLogList)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
