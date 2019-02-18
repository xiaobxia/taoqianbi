<?php
use yii\helpers\Html;
use common\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\UserCreditOnoff;
use backend\assets\AppAsset;

$this->shownav('credit', 'menu_credit_user_onoff');
$this->showsubmenu($operate_name.'用户征信开关');
if($operate_name == '修改')
{
    $formUrl = Url::toRoute(['do-update-user-credit-onoff']);
}
else
{
    $formUrl = Url::toRoute(['do-add-user-credit-onoff']);
}

?>
<html>
<?php $this->beginPage() ?>
<head>
<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>" type="text/javascript"></script>
<style>
.tb2 th{
        font-size: 12px;
    }
.btn {
    display: inline-block;
    padding: 3px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: normal;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    border: 1px solid transparent;
    border-radius: 4px;
}

.btn-success {
    color: #fff;
    background-color: #5cb85c;
    border-color: #4cae4c;
}

.form-control {
    width: 100%;
    padding: 6px 12px;
    font-size: 14px;
    line-height: 1.42857143;
    color: #555;
    background-color: #fff;
    background-image: none;
    border: 1px solid #ccc;
    border-radius: 4px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
    -webkit-transition: border-color ease-in-out .15s, -webkit-box-shadow ease-in-out .15s;
    -o-transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
    transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
}

</style>
<?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div style="width: 100%;height: 5px;"></div>
<div class="characteristics-form">

<?php $form = ActiveForm::begin(['action' => $formUrl]); ?>
    <?php
        if($operate_name == '修改')
            echo $form->field($model, 'id')->hiddenInput()->label(false);
     ?>
    <?php echo $form->field($model, 'name')->textInput(['autofocus' => true,'class'=>'form-control','style'=>'width:50%'])->label('征信名'); ?>
    <?php echo $form->field($model, 'type')->textInput(['class'=>'form-control','style'=>'width:50%'])->label('类型'); ?>
    <?php echo $form->field($model, 'status')->dropDownList(UserCreditOnoff::$user_credit_status,['prompt'=>'' , 'class'=>'form-control','style'=>'width:50%'])->label('状态'); ?>
    <?php echo $form->field($model, 'overdue_days')->textInput(['class'=>'form-control','style'=>'width:50%'])->label('过期天数'); ?>
    <?php echo $form->field($model, 'refresh_count')->textInput(['class'=>'form-control','style'=>'width:50%'])->label('刷新笔数'); ?>

    <div class="form-group form-buttons">
    <?php echo  Html::submitButton('保存', ['class' => 'btn btn-success', 'name' => 'submit', '']) ?>
    <?php echo  html::a('取消',['user-credit-onoff'],['class'=>'btn']); ?>
    </div>
<?php ActiveForm::end(); ?>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>