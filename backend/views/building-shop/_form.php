<?php
use common\helpers\Url;
use backend\components\widgets\ActiveForm;
use common\models\Shop;
?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'intergration-form']); ?>
	<?php //$this->showtips('基本配置（根据不同类别，会出现不同额外配置项）：'); ?>
	<table class="tb tb2 fixpadding">
		<tr><th class="partition" colspan="15">基本信息</th></tr>
		<tr class="">
	    	<td class="td27" width="10%">所属借款项目：</td>
	        <td class="vtop rowform" width="50%"><?php echo $form->field($model, 'loan_project_id')->dropDownList($loans,['prompt' => '--请选择--']); ?></td>
	        <td colspan="15"></td>
	    </tr>
	    <tr class="">
	    	<td class="td27">对接人：</td>
	        <td class="vtop rowform"><?php echo $form->field($model, 'broker')->textInput(['placeholder'=>'对接人姓名']);?></td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">授信额度：</td>
	        <td class="vtop rowform"><?php echo $form->field($model, 'credit_line')->textInput(['placeholder'=>'授信额度（为数值）']);?></td>
	        <td colspan="15"></td>
	    </tr>
	    <tr><th class="partition" colspan="15">商户信息</th></tr>
	    <tr class="">
	        <td class="td27">省：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'province_id')->dropDownList($provinces); ?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">市：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'city_id')->dropDownList(['prompt'=>'--请选择--']); ?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">县/区：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'area_id')->dropDownList(['prompt'=>'--请选择--']); ?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">店名：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shop_name')->textInput(['placeholder'=>'店名']);?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">营业执照等证件资料：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shop_licence')->textarea(['placeholder'=>'填写图片路径，中间用英文逗号隔开，格式：url_pic1 , url_pic2...']);?>
	        </td>
	        <td colspan="15">
	        	<a style="color:#7f63fe;font-weight:600;" target="_blank" href="<?php echo Url::toRoute(['main/index', 'action' => 'attachment/add']) ?>">上传附件 - 资产管理商户资料</a>
				（<font color="green">请填写图片路径，中间用 [ 英文逗号 ] 隔开，格式：url_pic1 , url_pic2 , url_pic3 ...</font>）
	        </td>
	    </tr>
	    <tr>
	    	<td class="td27">商户实景图资料：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shop_img')->textarea(['placeholder'=>'填写图片路径，中间用英文逗号隔开，格式：url_pic1 , url_pic2...']);?>
	        </td>
	        <td colspan="15">
	        	<a style="color:#7f63fe;font-weight:600;" target="_blank" href="<?php echo Url::toRoute(['main/index', 'action' => 'attachment/add']) ?>">上传附件 - 资产管理商户资料</a>
				（<font color="green">请填写图片路径，中间用 [ 英文逗号 ] 隔开，格式：url_pic1 , url_pic2 , url_pic3 ...</font>）
	        </td>
	    </tr>
	    <tr><th class="partition" colspan="15">店主信息</th></tr>
	    <tr>
	    	<td class="td27">店主UID（可为空）：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shopkeeper_id')->textInput(['placeholder'=>'店主UID（可为空）']);?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">店主姓名：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shopkeeper_name')->textInput(['placeholder'=>'店主姓名','maxlength'=>10]);?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">店主手机号码：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shopkeeper_phone')->textInput(['placeholder'=>'店主手机号码','maxlength'=>11]);?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">店主身份证号：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shopkeeper_card_id')->textInput(['placeholder'=>'店主身份证号','maxlength'=>18]);?>
	        </td>
	        <td colspan="15"></td>
	    </tr>
	    <tr>
	    	<td class="td27">店主身份信息资料：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'shopkeeper_card_pic')->textarea(['placeholder'=>'填写图片路径，中间用英文逗号隔开，格式：url_pic1 , url_pic2...']);?>
	        </td>
	        <td colspan="15">
	        	<a style="color:#7f63fe;font-weight:600;" target="_blank" href="<?php echo Url::toRoute(['main/index', 'action' => 'attachment/add']) ?>">上传附件 - 资产管理商户资料</a>
				（<font color="green">请填写图片路径，中间用 [ 英文逗号 ] 隔开，格式：url_pic1 , url_pic2 , url_pic3 ...</font>）
	        </td>
	    </tr>
		<tr><th class="partition" colspan="15">对接人信息</th></tr>
		<tr>
			<td class="td27">姓名：</td>
			<td class="vtop rowform">
				<?php echo $form->field($model, 'linker_name')->textInput(['placeholder'=>'对接人姓名','maxlength'=>10]);?>
			</td>
			<td colspan="15"></td>
		</tr>
		<tr>
			<td class="td27">手机号码：</td>
			<td class="vtop rowform">
				<?php echo $form->field($model, 'linker_phone')->textInput(['placeholder'=>'对接人手机号','maxlength'=>11]);?>
			</td>
			<td colspan="15"></td>
		</tr>
	    <tr><th class="partition" colspan="15">补充信息</th></tr>
	    <tr>
	    	<td class="td27">其他资料：</td>
	        <td class="vtop rowform">
	        	<?php echo $form->field($model, 'other_info')->textarea(['placeholder'=>'填写图片路径，中间用英文逗号隔开，格式：url_pic1 , url_pic2...']);?>
	        </td>
	        <td colspan="15">
	        	<a style="color:#7f63fe;font-weight:600;" target="_blank" href="<?php echo Url::toRoute(['main/index', 'action' => 'attachment/add']) ?>">上传附件 - 资产管理商户资料</a>
				（<font color="green">请填写图片路径，中间用 [ 英文逗号 ] 隔开，格式：url_pic1 , url_pic2 , url_pic3 ...</font>）
	        </td>
	    </tr>
	    <tr>
	    	<td class="td27">备注：</td>
	        <td class="vtop rowform"><?php echo $form->field($model, 'remark')->textInput();?></td>
	        <td colspan="15"></td>
	    </tr>
		<tr>
			<td class="td27">商户介绍：</td>
			<td class="vtop rowform"><?php echo $form->field($model, 'shop_description')->textarea(['placeholder'=>'填写商户介绍,限制1000字...','maxlength'=>1000]);?></td>
			<td colspan="15">可输入<label id="description_remain">1000</label>字</td>
		</tr>
	    <tr class="">
	        <td class="td27"><input type="submit" value="提交" name="submit_btn" class="btn"></td>
	        <td colspan="15"></td>
	    </tr>
	    <tr><td colspan="15"></td></tr><tr><td colspan="15"></td></tr>
	</table>
<?php ActiveForm::end(); ?>
<script type="text/javascript">
	$(function(){
		$('#description_remain').html(parseInt($('#shop-shop_description').attr("maxlength"), 10) - $('#shop-shop_description').val().length);
		$('#shop-shop_description').keyup(function() {
			var area = $(this);
			var max = parseInt(area.attr("maxlength"), 10);
			if (max > 0) {
				var remain = max - area.val().length;
				if(remain < 0) remain = 0;
				$('#description_remain').html(remain);
			}
		});
		$('#shop-shop_description').blur(function() {
			var area = $(this);
			var max = parseInt(area.attr("maxlength"), 10);
			if (max > 0) {
				if(remain < 0) remain = 0;
				var remain = max - area.val().length;
				$('#description_remain').html(remain);
			}
		})
	});



	$(function(){
		$('.tarea').css('width','100%');
		getCity($('#shop-province_id').val());
	});

	$('#shop-province_id').change(function(){
		getCity($(this).val());
	});

	function getCity(province_id){
		var city_option = '';
		<?php foreach($cities as $k => $v):?>
			if(<?php echo $k;?> == province_id){
				<?php foreach($v as $cid => $city){?>
					city_option += '<option value="<?php echo $cid;?>" <?php if($model->city_id == $cid){echo "selected";}?>><?php echo $city;?></option>';
				<?php }?>
			}
		<?php endforeach;?>
		$('#shop-city_id').html(city_option);
		getArea($('#shop-city_id').val());
	}

	$('#shop-city_id').change(function(){
		getArea($(this).val());
	});
	function getArea(city_id){
		var area_option = '';
		<?php foreach($areas as $k => $v):?>
			if(<?php echo $k;?> == city_id){
				<?php foreach($v as $aid => $area){?>
					area_option += '<option value="<?php echo $aid;?>" <?php if($model->area_id == $aid){echo "selected";}?>><?php echo $area;?></option>';
				<?php }?>
			}
		<?php endforeach;?>
		$('#shop-area_id').html(area_option);
	}
</script>
