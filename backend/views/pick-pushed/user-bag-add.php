<?php
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\helpers\Url;
use yii\helpers\Html;
?>
<style>
    td.label {
        width: 170px;
        text-align: right;
        font-weight: 700;
    }
    .txt{ width: 100px;}

    .tb2 .txt, .tb2 .txtnobd {
        width: 200px;
        margin-right: 10px;
    }
</style>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>

<?php $form = ActiveForm::begin(['method' => 'post'])?>
<input type="hidden" name="_id" value="<?php echo isset($info['_id']) ? $info['_id'] : 0;?>"></input>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">生成用户包</th></tr>
    <?php if (!empty($info) && isset($info['_id'])) : ?>
    	<tr>
            <td class="label"><font color="red">*</font>是否申请用户：</td>
            <td >
            	<?php if ($info['type'] == 1) { echo '是'; } else { echo '否'; } ?>
            </td>
        </tr>
        <tr>
            <td class="label"><font color="red">*</font>用户包名称：</td>
            <td >
            	<?php echo $info['name'];?>
            </td>
        </tr>
            <tr>
            <td class="label"><font color="red">*</font>用户包唯一标识：</td>
            <td >
            	<?php echo $info['code'];?>英文+数字 例如：test01
            </td>
        </tr>
        <tr>
            <td class="label"><font color="red">*</font>注册时间段：</td>
            <td >
            	<?php echo date('Y-m-d H:i:s', $info['reg_begin_time']) . '-' . date('Y-m-d H:i:s', $info['reg_end_time']);?>
            </td>
        </tr>
    <?php else : ?>
        <tr>
            <td class="label"><font color="red">*</font>是否申请用户：</td>
            <td >
            	<?php
                	echo Html::radioList('is_apply_user', isset($info['type'])?$info['type']:0, [
                	    '1' => '是',
                	    '0' => '否',
                	]);
            	?>
            </td>
        </tr>
        <tr>
            <td class="label"><font color="red">*</font>用户包名称：</td>
            <td >
            	<?php echo Html::textInput('name', isset($info['name'])?$info['name']:'');?>
            </td>
        </tr>
            <tr>
            <td class="label"><font color="red">*</font>用户包唯一标识：</td>
            <td >
            	<?php echo Html::textInput('code', isset($info['code'])?$info['code']:'');?>英文+数字 例如：test01
            </td>
        </tr>
        <tr>
            <td class="label"><font color="red">*</font>注册时间段：</td>
            <td >
            	<input type="text" value="<?php echo isset($info['reg_begin_time'])? $info['reg_begin_time'] : ''; ?>" name="reg_begin_time" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
            	-
            	<input type="text" value="<?php echo isset($info['reg_end_time']) ? $info['reg_end_time'] : ''; ?>"  name="reg_end_time" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
            </td>
        </tr>
    <?php endif;?>
    <tr>
        <td class="label">渠道：</td>
        <td >
        	<textarea rows="8" cols="50" name="app_market"><?php echo isset($info['custom']['app_market'])? $info['custom']['app_market'] : '';?></textarea>&nbsp;&nbsp;&nbsp;&nbsp;多选，一行一个
        </td>
    </tr>
    <tr>
        <td class="label">年龄段选择：</td>
        <td >
        	<input type="text" value="<?php echo isset($info['custom']['begin_age'])? $info['custom']['begin_age'] : ''; ?>" name="begin_age" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
            	-
            	<input type="text" value="<?php echo isset($info['custom']['end_age']) ? $info['custom']['end_age'] : ''; ?>"  name="end_age" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:%s',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:false})">
        </td>
    </tr>
    <tr>
        <td class="label">现居地：</td>
        <td >
        	<input type="text" name="address" value="<?php echo isset($info['custom']['address']) ? $info['custom']['address'] : ''; ?>"></input>
        </td>
    </tr>
    <tr class="no_apply">
    	<td class="label">未申请|</td>
    	<td></td>
    </tr>
    <tr class="no_apply">
        <td class="label">是否实名：</td>
        <td >
        	<?php
            	echo Html::radioList('is_real_name', isset($info['custom']['is_real_name'])?$info['custom']['is_real_name']:1, [
            	    '1' => '是',
            	    '0' => '否',
            	]);
        	?>
        </td>
    </tr>
    <tr class="no_apply">
        <td class="label">是否绑卡：</td>
        <td >
        	<?php
            	echo Html::radioList('is_bind_card', isset($info['custom']['is_bind_card'])?$info['custom']['is_bind_card']:1, [
            	    '1' => '是',
            	    '0' => '否',
            	]);
        	?>
        </td>
    </tr>
    <tr class="no_apply">
        <td class="label">支付宝授权：</td>
        <td >
        	<?php
            	echo Html::radioList('is_alipay_passed', isset($info['custom']['is_alipay_passed'])?$info['custom']['is_alipay_passed']:1, [
            	    '1' => '是',
            	    '0' => '否',
            	]);
        	?>
        </td>
    </tr>
    <tr class="yes_apply">
    	<td class="label">已申请|</td>
    	<td></td>
    </tr>
    <tr class="yes_apply">
        <td class="label">目前额度：</td>
        <td >
        	<select name="now_credit">
        		<option value="0" <?php echo isset($info['custom']['now_credit']) && $info['custom']['now_credit'] == 0 ? 'selected' : ''; ?>>0</option>
        		<option value="200" <?php echo isset($info['custom']['now_credit']) && $info['custom']['now_credit'] == 200 ? 'selected' : ''; ?>>200</option>
        		<option value="500" <?php echo isset($info['custom']['now_credit']) && $info['custom']['now_credit'] == 500 ? 'selected' : ''; ?>>500</option>
        		<option value="1000" <?php echo isset($info['custom']['now_credit']) && $info['custom']['now_credit'] == 1000 ? 'selected' : ''; ?>>1000</option>
        		<option value="2000" <?php echo isset($info['custom']['now_credit']) && $info['custom']['now_credit'] == 2000 ? 'selected' : ''; ?>>2000</option>
        		<option value="3000" <?php echo isset($info['custom']['now_credit']) && $info['custom']['now_credit'] == 3000 ? 'selected' : ''; ?>>3000</option>
        	</select>
        </td>
    </tr>
    <tr class="yes_apply">
        <td class="label">续借次数：</td>
        <td >
			<select name="delay_num">
				<option value="0" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 0 ? 'selected' : ''; ?>>0</option>
        		<option value="1" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 1 ? 'selected' : ''; ?>>1</option>
        		<option value="2" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 2 ? 'selected' : ''; ?>>2</option>
        		<option value="3" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 3 ? 'selected' : ''; ?>>3</option>
        		<option value="4" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 4 ? 'selected' : ''; ?>>4</option>
        		<option value="5" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 5 ? 'selected' : ''; ?>>5</option>
        		<option value="6" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 6 ? 'selected' : ''; ?>>6</option>
        		<option value="7" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 7 ? 'selected' : ''; ?>>7</option>
        		<option value="8" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 8 ? 'selected' : ''; ?>>8</option>
        		<option value="9" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 9 ? 'selected' : ''; ?>>9</option>
        		<option value="10" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 10 ? 'selected' : ''; ?>>10</option>
        		<option value="11" <?php echo isset($info['custom']['delay_num']) && $info['custom']['delay_num'] == 11 ? 'selected' : ''; ?>>>10</option>
        	</select>
        </td>
    </tr>
    <tr class="yes_apply">
        <td class="label">首次放款额度：</td>
        <td >
        	<select name="first_pocket_credit">
        		<option value="0" <?php echo isset($info['custom']['first_pocket_credit']) && $info['custom']['first_pocket_credit'] == 0 ? 'selected' : ''; ?>>0</option>
        		<option value="200" <?php echo isset($info['custom']['first_pocket_credit']) && $info['custom']['first_pocket_credit'] == 200 ? 'selected' : ''; ?>>200</option>
        		<option value="500" <?php echo isset($info['custom']['first_pocket_credit']) && $info['custom']['first_pocket_credit'] == 500 ? 'selected' : ''; ?>>500</option>
        		<option value="1000" <?php echo isset($info['custom']['first_pocket_credit']) && $info['custom']['first_pocket_credit'] == 1000 ? 'selected' : ''; ?>>1000</option>
        		<option value="2000" <?php echo isset($info['custom']['first_pocket_credit']) && $info['custom']['first_pocket_credit'] == 2000 ? 'selected' : ''; ?>>2000</option>
        		<option value="3000" <?php echo isset($info['custom']['first_pocket_credit']) && $info['custom']['first_pocket_credit'] == 3000 ? 'selected' : ''; ?>>3000</option>
        	</select>
        </td>
    </tr>
    <tr class="yes_apply">
        <td class="label">是否逾期过：</td>
        <td >
        	<?php
            	echo Html::radioList('is_delayed', isset($info['custom']['is_delayed'])?$info['custom']['is_delayed']:1, [
            	    '1' => '是',
            	    '0' => '否',
            	]);
        	?>
        </td>
    </tr>
    <tr class="yes_apply">
        <td class="label">距离上次还款：</td>
        <td >
        	<select name="leave_repay">
        		<option value="0" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 0 ? 'selected' : ''; ?>>0</option>
        		<option value="1" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 1 ? 'selected' : ''; ?>>1</option>
        		<option value="2" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 2 ? 'selected' : ''; ?>>2</option>
        		<option value="3" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 3 ? 'selected' : ''; ?>>3</option>
        		<option value="4" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 4 ? 'selected' : ''; ?>>4</option>
        		<option value="5" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 5 ? 'selected' : ''; ?>>5</option>
        		<option value="6" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 6 ? 'selected' : ''; ?>>6</option>
        		<option value="7" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 7 ? 'selected' : ''; ?>>7</option>
        		<option value="8" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 8 ? 'selected' : ''; ?>>8</option>
        		<option value="9" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 9 ? 'selected' : ''; ?>>9</option>
        		<option value="10" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 10 ? 'selected' : ''; ?>>10</option>
        		<option value="11" <?php echo isset($info['custom']['leave_repay']) && $info['custom']['leave_repay'] == 11 ? 'selected' : ''; ?>>>10</option>
        	</select>天
        </td>
    </tr>
    <tr>
        <td></td>
        <td colspan="15">
            <input type="submit" value="提交" name="submit_btn" class="btn">
        </td>
    </tr>
</table>
<?php $form = ActiveForm::end();?>

<script>
	var is_apply = <?php echo isset($info['type'])?$info['type']:0; ?>;
	if (is_apply == 1) {
		$('.yes_apply').show();
		$('.no_apply').hide();
	} else {
		$('.yes_apply').hide();
	}
    $(document).ready(function(){
        $("input[name='is_apply_user']").change(function(){
        	var is_apply_user = $("input[name='is_apply_user']:checked").val();
			if (is_apply_user == 1) {
				$('.yes_apply').show();
				$('.no_apply').hide();
			} else if (is_apply_user == 0){
				$('.no_apply').show();
				$('.yes_apply').hide();
			}
        });

    });
</script>
<script>
   $('#uid').parent().css({display : "none"});
   $('#open_id').parent().css({display : "none"});
</script>