<?php

use backend\components\widgets\LinkPager;
use common\models\AccumulationFund;
use common\models\IcekreditAlipay;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'loan_person_alipay');
$this->showsubmenu('公积金支付宝认证列表', array(
    array('支付宝认证列表', Url::toRoute('icekredit/alipay-list'), 1),
));
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['icekredit/alipay-list'], 'options' => ['style' => 'margin-top:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
    手机号:<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" maxlength="20" style="width:120px;">&nbsp;
    身份证号:<input type="text" value="<?php echo Yii::$app->getRequest()->get('id_card', ''); ?>" name="id_card" class="txt" maxlength="20" style="width:120px;">&nbsp;
    状态：<?php echo Html::dropDownList('status', Yii::$app->getRequest()->get('status', ''), IcekreditAlipay::$status_desc, ['prompt' => '所有类型']); ?>&nbsp;
    添加日期：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_start', ''); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    至：<input type="text" value="<?php echo Yii::$app->getRequest()->get('add_end', ''); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>ID</th>
                <th>用户ID</th>
                <th>姓名</th>
                <th>电话</th>
                <th>身份证号</th>
                <th>状态</th>
                <th>添加时间</th>
                <th>操作</th>
            </tr>
            <?php foreach ($info as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['loanPerson']['name']; ?></td>
                    <td><?php echo $value['loanPerson']['phone']; ?></td>
                    <td><?php echo $value['loanPerson']['id_number']; ?></td>
                    <td><?php echo IcekreditAlipay::$status_desc[$value['status']]; ?></td>
                    <td><?php echo date("Y-m-d H:i:s",$value['created_at']); ?></td>
                    <td><a href="<?php echo Url::toRoute(['icekredit/alipay-view', 'id' => $value['user_id']]);?>">查看支付宝信息</a>
                        <a href="<?php echo Url::toRoute(['icekredit/report-view', 'id' => $value['user_id']]);?>">查看冰鉴评分报告</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>