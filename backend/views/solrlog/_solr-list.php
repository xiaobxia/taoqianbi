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
use common\models\LoanRecordPeriod;
use common\models\LoanPerson;
use common\models\User;
use common\models\UserLoanOrder;
use common\models\SolrUpdateLog;
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
    维护结果：<?php echo Html::dropDownList('flag', Yii::$app->getRequest()->get('flag',''),[0=>'全部',1=>'完成',2=>'中止',3=>'超时']); ?>&nbsp;
<?php if(Yii::$app->getRequest()->get('date','')<1): ?>
    维护时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begin_at', ''); ?>" name="begin_at" onfocus="WdatePicker({startDate:'%y-%M-%d 00:00:00',dateFmt:'yyyy-MM-dd 00:00:00',alwaysUseStartDate:true,readOnly:true})">
    至<input type="text" value="<?php echo Yii::$app->getRequest()->get('finish_at', ''); ?>"  name="finish_at" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d 23:59:59',dateFmt:'yyyy-MM-dd 23:59:59',alwaysUseStartDate:true,readOnly:true})">
<?php endif; ?>
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
echo
    <?php if((Yii::$app->getRequest()->get('num_id',''))<1): ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <?php if(Yii::$app->getRequest()->get('date','')<1): ?>
                    <th>编号</th>
                    <?php else: ?>
                    <th>离散编号</th>
                <?php endif; ?>
                <th>维护日期</th>
                <th>维护结果</th>
                <?php if(Yii::$app->getRequest()->get('date','')<1): ?>
                    <th>维护总数</th>
                <?php endif; ?>
                <th>成功总数</th>
                <th>失败总数</th>
                <th>开始时间</th>
                <th>结束时间</th>
                <th>维护用时(秒)</th>
                <th>操作</th>
            </tr>
            <?php $i = 1;?>
            <?php foreach ($data_list as $value): ?>
                <tr class="hover">
                    <td>
                        <?php
                            if(Yii::$app->getRequest()->get('date','')<1){
                                echo $i++;
                            }else{
                                echo $value['num'];
                            }
                        ?>
                    </td>
                    <td><?php echo mb_substr($value['update_date'],0,4).'年'.mb_substr($value['update_date'],4,2).'月'.mb_substr($value['update_date'],6,2).'日'; ?></td>
                    <?php if(Yii::$app->getRequest()->get('date','')<1):?>
                        <td>
                            <?php echo $value['total']; ?>
                        </td>
                    <?php endif;?>
                    <th>
                        <?php
                            if($value['flag']==1){echo '完成';}elseif($value['flag']){echo '中止';}else{echo '超时';}
                        ?>
                    </th>
                    <th><?php echo $value['success']; ?></th>
                    <th><?php echo $value['fail']; ?></th>
                    <th><?php echo date('H:i:s',$value['begin_at']); ?></th>
                    <th>
                        <?php
                            if($value['finish_at']!=0){
                                echo date('H:i:s',$value['finish_at']);
                            }else{
                                echo '';
                            }
                        ?>
                    </th>
                    <th>
                        <?php
                            if($value['finish_at']!=0){
                                echo ($value['finish_at']-$value['begin_at']);
                            }else{
                                echo '';
                            }
                        ?>
                    </th>
                    <?php if(Yii::$app->getRequest()->get('date','')<1): ?>
                        <th>
                            <?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
                            <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
                                <input type="hidden" name="date" value="<?php echo $value['update_date'] ?>">
                                <input type="submit" name="search_submit" value="查看" class="btn">
                            <?php $form = ActiveForm::end(); ?>
                        </th>
                    <?php elseif($value['fail_id']!=null):?>
                        <th>
                            <?php $form = ActiveForm::begin(['action' => Url::toRoute('/'), 'method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
                            <input type="hidden" name="r" value="<?php echo Yii::$app->getRequest()->get('r',''); ?>">
                            <input type="hidden" name="num_id" value="<?php echo $value['id']?>">
                            <input type="submit" name="search_submit" value="查看" class="btn">
                            <?php $form = ActiveForm::end(); ?>
                        </th>
                    <?php endif;?>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    <?else: ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>id</th>
            </tr>
            <?php $arr = explode(',',$data_list[0]['fail_id']);?>
            <?php foreach ($arr as $key=>$value): ?>
                <tr>
                    <th><?php $value; ?></th>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
<?php if(Yii::$app->getRequest()->get('date','')<1||Yii::$app->getRequest()->get('num_id','')>1): ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<?php endif;?>