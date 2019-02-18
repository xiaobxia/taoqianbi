<?php
/**
 * Created by phpDesigner
 * User: user
 * Date: 2016/10/21
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;

?>

<style>
.tb2 th{ font-size: 12px;}
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
    月份：
    <input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_created_at', ''); ?>" name="begin_created_at" onfocus="WdatePicker({startDate:'%y-%M ',dateFmt:'yyyy-MM',alwaysUseStartDate:true,readOnly:true})"/>
       至<input type="text" value="<?php echo Yii::$app->getRequest()->get('end_created_at', ''); ?>" name="end_created_at" onfocus="WdatePicker({startDate:'%y-%M',dateFmt:'yyyy-MM',alwaysUseStartDate:true,readOnly:true})"/>
	<input type="submit" name="search_submit" value="过滤" class="btn"/>
<?php $form = ActiveForm::end(); ?>
    <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>月份</th>
                <th>申请人数</th>
                <th>放款人数</th>
                <th>申请笔数</th>
                <th>放款笔数</th>
                <th>放款金额</th>
                <th>续借笔数</th>
                <th>续借人数</th>
                <th>续借金额</th>
            </tr>
            <?php if(!empty($info)):?>
                <?php foreach ($info as $value): ?>
                    <tr>
                        <td><?php echo empty($value['month']) ? '--' : $value['month'];?></td>
                        <td><?php echo empty($value['apply_person_num'])?'--':$value['apply_person_num'];?></td>
                        <td><?php echo empty($value['pock_person_num'])?'--':$value['pock_person_num'];?></td>
                        <td><?php echo empty($value['apply_num'])?'--':$value['apply_num'];?></td>
                        <td><?php echo empty($value['pock_num'])?'--':$value['pock_num'];?></td>
                        <td><?php echo empty($value['pock_money'])?'--':sprintf("%0.2f",$value['pock_money']/100);?></td>
                        <td><?php echo empty($value['delay_num'])?'--':$value['delay_num'];?></td>
                        <td><?php echo empty($value['delay_person_num'])?'--':$value['delay_person_num'];?></td>
                        <td><?php echo empty($value['delay_money'])?'--':sprintf("%0.2f",$value['delay_money']/100);?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif;?>
    </table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>


