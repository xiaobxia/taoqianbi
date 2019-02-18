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
?>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;">&nbsp;
借款人类型：<?php echo Html::dropDownList('type', Yii::$app->getRequest()->get('type', ''), LoanPerson::$person_type,array('prompt' => '-所有类型-')); ?>&nbsp;
借款人名称：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;">&nbsp;
联系方式：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;">&nbsp;
借款人性别/行业：<input type="text" value="<?php echo Yii::$app->getRequest()->get('property', ''); ?>" name="property" class="txt" style="width:120px;">&nbsp;
按时间段：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
<input type="submit" name="search_submit" value="过滤" class="btn">
<?php $form = ActiveForm::end(); ?>
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>用户ID</th>
                <th>借款人类型</th>
<!--                <th>借款人来源</th>-->
                <th>姓名/公司名称</th>
                <th>联系方式</th>
                <th>生日/成立日期</th>
                <th>性别/行业</th>
                <th>紧急联系人</th>
                <th>紧急联系方式</th>
                <th>授信额度</th>
                <th>图片状态</th>
                <th>借款人状态</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($loan_person as $value): ?>
                <tr class="hover">
                    <td class="td25"><?php echo $value['id']; ?></td>
                    <td class="td25"><?php echo $value['uid']; ?></td>
                    <td class="td25"><?php echo empty($value['type'])?'--':LoanPerson::$person_type[$value['type']]; ?></td>
<!--                    <td class="td25">--><?php //echo LoanPerson::$person_source[$value['source_id']]; ?><!--</td>-->
                    <td class="td25"><?php echo $value['name']; ?></td>
                    <td class="td25"><?php echo $value['phone']; ?></td>
                    <td class="td25"><?php echo empty($value['birthday']) ? "" : date("Y-m-d" , $value['birthday']); ?></td>
                    <td class="td25"><?php echo $value['property']; ?></td>
                    <td class="td25"><?php echo $value['contact_username']; ?></td>
                    <td class="td25"><?php echo $value['contact_phone']; ?></td>
                    <td class="td25"><?php echo $value['credit_limit']; ?></td>
                    <td class="td25"><?php if(empty($value['attachment'])): ?>
                            待上传
                            <?php else: ?>
                            <a target="_blank" href="<?php echo Url::toRoute(['loan-secur/loan-person-pic', 'id' => $value['id']])?>">查看</a>
                        <?php endif;?>
                    </td>
                    <td class="td25"><?php echo isset(LoanPerson::$status[$value['status']])? LoanPerson::$status[$value['status']]:""; ?></td>
                    <td class="td25"><?php echo date("Y-m-d H:i",$value['created_at']); ?></td>
                    <td class="td25">
                        <a href="<?php echo Url::toRoute(['loan-period-secur/loan-record-backend-add', 'person_id' => $value['id'], 'user_id' => $value['uid'],'create_type'=>$create_type]); ?>">借款</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($loan_person)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>