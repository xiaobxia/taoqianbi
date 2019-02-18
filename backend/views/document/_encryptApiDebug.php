<?php
use yii\helpers\Html;
use common\helpers\Url;
?>

<script src="<?php echo Url::toStatic('/js/jsbeautify.js'); ?>" type="text/javascript"></script>

<div class="container-fluid" style="padding:0;">
    <div class="row">
        <div class="col-md-4">
		<h3>路由：<?php echo Html::encode($route); ?></h3>
		<form role="form">
		  <?php if ($model->params): ?>
		  <?php foreach ($model->params as $param): ?>
		  <div class="form-group">
		    <label>
		    	<?php echo $param['name'].' '.$param['desc']; ?>
		    	<?php if ($param['name'] == "\$sign"): ?>
		    	<a href="javascript:void(0);" id="get-sign"> 点击获取签名</a>
                        <?php elseif ($param['name'] == "\$time"): ?>
                        <a href="javascript:void(0);" id="get-time"> 点击获取时间戳</a>
		    	<?php endif; ?>
		    </label>
		    <input type="text" class="form-control" name="<?php echo trim($param['name'], '$'); ?>" value="<?php echo $model->getParamDefaultValue(trim($param['name'], '$')); ?>">
                    <?php if ($param['name'] == "\$sign"): ?>
                    <div class="hint-block">
                        默认使用\common\models\Order::getSign($params),自定义签名方法请在对应的控制器中添加public static getTestSign($params){return $sign;}方法
                    </div>
                    <?php endif; ?>
		  </div>
		  <?php endforeach; ?>
		  <?php else: ?>
		  <div class="form-group">无参数</div>
		  <?php endif; ?>
		  <button id="submit-btn" type="button" class="btn btn-primary" data-loading-text="提交中..." autocomplete="off">提交</button>
		</form>
	</div>
        <!-- <div class="col-md-8" role="main" style="position:fixed;top:10px;right:0"> -->
        <div class="col-md-8" role="main">
                <h3>请求时间：</h3>
                <pre id="request_time"></pre>
                <h3>响应时间：</h3>
                <pre id="response_time"></pre>
		<h3>请求返回:</h3>
		<pre id="response">Empty.</pre>
	</div>
    </div>
</div>
<script type="text/javascript">
function getDate() {
    var date = new Date();
    return date.getFullYear()+'-'+(date.getMonth()+1)+'-'+(date.getDate())+' '+(date.getHours())+':'+(date.getMinutes())+':'+(date.getSeconds());
}
$(function(){
	$('#submit-btn').click(function(){
                if($(this).hasClass('loading')) {
                    alert('之前的请求还正在进行，请稍等或刷新页面重试');
                    return false;
                }
		var btn = $(this).addClass('loading').html('请求中');
                $('#request_time').html(getDate());
		var data = {};
		$('.form-control').each(function(){
			if ($(this).val() != '') {
				data[$(this).attr('name')] = $(this).val();
			}
		});
                $.ajax({
			url: '<?php echo $encryptUrl; ?>',
			type: 'post',
			data: data,
                        complete:function() {

                        },
			success: function(retData) {
                            if(retData.code!=='OK') {
                                btn.removeClass('loading').html('提交');
                                alert("加密接口错误："+retData.message);
                                return false;
                            }
                            $.ajax({
                                    url: '<?php echo $debugUrl; ?>',
                                    type: '<?php echo $model->method; ?>',
                                    data: retData.data,
                                    complete:function() {
                                        $('#response_time').html(getDate());
                                        btn.removeClass('loading').html('提交');
                                    },
                                    success: function(retData) {
                                        if (typeof retData === 'string' && retData.indexOf('content="text/html;') != -1) {
                                                var url = '<?php echo $debugUrl; ?>?';
                                                for (key in data) {
                                                        url += key + '=' + data[key] + '&';
                                                }
                                                window.open(url);
                                                $('#response').html('该接口是返回html页面，请允许浏览器弹出新页面或自行在浏览器调试');
                                        } else {
                                                var formatText = js_beautify(JSON.stringify(retData), 4, ' ');
                                                $('#response').html(formatText);
                                        }
                                    },
                                    error: function(retData) {
                                        alert('请求接口错误');
                                    }
                            });
			},
			error: function(retData) {
				btn.removeClass('loading').html('提交');
				alert('获取加密数据时发生错误');
			}
		});
	});

	$('#get-sign').click(function(){
		var data = {};
		$('.form-control').each(function(){
			if ($(this).val() != '') {
				data[$(this).attr('name')] = $(this).val();
			}
		});
		data['<?php echo Yii::$app->getRequest()->csrfParam; ?>'] = '<?php echo Yii::$app->getRequest()->getCsrfToken(); ?>';
		$.ajax({
			url: '<?php echo Url::toRoute(['document/get-sign', 'route'=>$route]); ?>',
			type: 'post',
			data: data,
			success: function(retData) {
				$('input[name=sign]').val(retData);
			},
			error: function(retData) {
				alert('发生错误');
			}
		});
	});

        $('#get-time').click(function(){
            $('input[name=time]').val(Date.parse(new Date())/1000);
	});
});
</script>