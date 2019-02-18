<?php
use yii\helpers\Url;
use yii\helpers\Html;
use common\helpers\GlobalHelper;
$baseUrl=Yii::$app->getRequest()->getAbsoluteBaseUrl();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $this->title ? $this->title : '口袋理财'; ?></title>
    <script type="text/javascript" src="<?php echo Yii::$app->getRequest()->getAbsoluteBaseUrl(); ?>/js/flexable.js" ></script>

	<link rel="stylesheet" href="<?php echo $baseUrl;?>/css/building/register.css">
	<style type="text/css">
		/*.delete {*/
			/*position: absolute;*/
			/*bottom: 0;*/
			/*right: 0;*/
			/*width: 34px;*/
			/*height: 36px;*/
			/*background:url('../../web/image/building/del.png') no-repeat center; */
			/*background-size: 34px 36px;*/
		/*}*/
		.wrap {
			position: relative;
			display: inline-block;
			width: 2rem;
			height: 2rem;
			background:url('<?php echo $baseUrl;?>/image/building/loading.gif') no-repeat center;
			background-size: 2rem;
		}
	</style>
</head>
<body>
	<div class="upload-css">
		<header>请提供房产证照片（单张照片不超过2M），最多不超过5张</header>
		<div class="upload-file">
			<div id="localImag"></div>
			<div class="button">
				<input type="file" name="attach" id="attach" >
				<div class="button_bg"></div>
			</div>
		</div>
		<footer>
			<?php if(empty($order_id)): ?>
	        <button id="btn" ><span id="word">保存资料</span></button>
			<?php else: ?>
			<button id="btn" ><span id="word">一键评估</span></button>
			<?php endif; ?>
	    </footer>
	</div>
</body>
<script type="text/javascript">
$(function(){
	var index=0;
	//下面用于图片上传预览功能
	function previewImage(avalue) {
		var docObj = document.getElementById("attach"),
			oImg = document.getElementsByClassName("wrap").length,
			oBtn = document.getElementsByClassName("button")[0],
			images = "";
		if(docObj.files && docObj.files.length>0){
			if (oImg==4) {
				oBtn.style.display = "none";
			}
			for (var i = 0; i < docObj.files.length; i ++) {
				index+=1;
				if(index > (5 - <?php echo $count ?>)){
					dialog("上传图片超过规定数量，不能继续上传");
					return false;
				}
                images += "<div class='wrap'><img style='display:none;' onclick='imgZoom(this);' class='preview"+ index +"' src= "+ window.URL.createObjectURL(docObj.files[i]) +" ></div> ";
            }
            $("#localImag").append(images);
		}else{
			//IE下，使用滤镜
			docObj.select();
			var imgSrc = document.selection.createRange().text;
			var localImagId = document.getElementById("localImag");
			//必须设置初始大小
			localImagId.style.width = "2rem";
			localImagId.style.height = "2rem";
			//图片异常的捕捉，防止用户修改后缀来伪造图片
			try{
				localImagId.style.filter="progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale)";
				localImagId.filters.item("DXImageTransform.Microsoft.AlphaImageLoader").src = imgSrc;
			}catch(e){
				alert("您上传的图片格式不正确，请重新选择!");
				return false;
			}
			imgObjPreview.style.display = 'none';
			document.selection.empty();
		}
		ajaxFileUpload();
		$('#btn').attr('disabled',"true");
		// createBtn();
		return true;
	}

	$('#attach').on("change",previewImage);

	// 压缩
	function compress(img) {
		var canvas = document.createElement("canvas");
		var ctx = canvas.getContext('2d');

		var tCanvas = document.createElement("canvas");
		var tctx = tCanvas.getContext("2d");

		var initSize = img.src.length;
		var width = img.width;
		var height = img.height;

		//如果图片大于四百万像素，计算压缩比并将大小压至400万以下
		var ratio;
		if ((ratio = width * height / 4000000)>1) {
			ratio = Math.sqrt(ratio);
			width /= ratio;
			height /= ratio;
		}else {
			ratio = 1;
		}

		canvas.width = width;
		canvas.height = height;

		//        铺底色
		ctx.fillStyle = "#fff";
		ctx.fillRect(0, 0, canvas.width, canvas.height);

		//如果图片像素大于100万则使用瓦片绘制
		var count;
		if ((count = width * height / 1000000) > 1) {
			count = ~~(Math.sqrt(count)+1); //计算要分成多少块瓦片

			//            计算每块瓦片的宽和高
			var nw = ~~(width / count);
			var nh = ~~(height / count);

			tCanvas.width = nw;
			tCanvas.height = nh;

			for (var i = 0; i < count; i++) {
				for (var j = 0; j < count; j++) {
					tctx.drawImage(img, i * nw * ratio, j * nh * ratio, nw * ratio, nh * ratio, 0, 0, nw, nh);

					ctx.drawImage(tCanvas, i * nw, j * nh, nw, nh);
				}
			}
		} else {
			ctx.drawImage(img, 0, 0, width, height);
		}

		//进行最小压缩
		var ndata = canvas.toDataURL("image/jpeg", 0.5);
		tCanvas.width = tCanvas.height = canvas.width = canvas.height = 0;

		return ndata;
	}

	// 提交图片
	function ajaxFileUpload() {
		var file = document.getElementById("attach").files[0];
		var reader = new FileReader();
        reader.onload = function(event) {
			var img = new Image();
			img.src = event.target.result;
			img.onload = function() {
				var dataImg = compress(img);
				$.post("<?php echo Url::toRoute(['picture/upload-image'],true); ?>", {'attach': [dataImg]}, function (data) {
					if (data.code === 0) {
						$('#btn').removeAttr("disabled");
						$('.preview' + index).show();
						$(".wrap").css("background", "white");
					} else {
						$('#btn').attr('disabled', "true");
						$('#attach').attr("disabled", "true");
					}
				})
			}
		};
		reader.readAsDataURL(file);
    }
    function save () {
    	var oImg = document.getElementsByClassName("wrap").length;
    	if(oImg==0){
    		dialog("请提供房产证照片");
    		return;
    	}
    	document.domain='<?php echo GlobalHelper::getDomain(); ?>';
		<?php if(empty($distinct)): ?>
    	KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd/order','clientType'=>'h5'], true); ?>","",function(data){
           if(data.code == 0) {
                window.location.href = "<?php echo Url::toRoute(['building/result'],true);?>";
            } else {
                dialog(data.message);
            }
        });
		<?php else : ?>
		$("#btn").attr("onclick","");
		var order_id = "<?php echo $order_id ?>";
		var parms = {
			distinct:'<?php echo $distinct; ?>',
			address:'<?php echo $address; ?>',
			house_address:'<?php echo $house_address; ?>',
			area:'<?php echo $area; ?>',
			other_area:'<?php echo $other_area; ?>',
			year:'<?php echo $year; ?>',
			house_type:'<?php echo $house_type; ?>',
			totalfloor:'<?php echo $totalfloor; ?>',
			towards:'<?php echo $towards; ?>'
		};

		var $e=$('<div id="loading"></div>');
		$e.appendTo("body");
		$.get("<?php echo Url::toRoute(['building/create-hfd-order'], true); ?>",parms,function(data) {
			if(data.code == 0){
				order_id = data.order_id;
				$.get("<?php echo Url::toRoute(['building/upload'],true); ?>",{order_id:order_id},function(data){
					if(data.code == 0) {
						var param = {
							address:'<?php echo $address; ?>',
							projectid:'<?php echo $projectid; ?>',
							project_name:'<?php echo $project_name; ?>',
							unitid:'<?php echo $unitid; ?>',
							unitno:'<?php echo $unitno; ?>',
							roomno:'<?php echo $roomno; ?>',
							year:'<?php echo $year; ?>',
							floor:'<?php echo $floor; ?>',
							totalfloor:'<?php echo $totalfloor; ?>',
							towards:'<?php echo $towards; ?>',
							area:'<?php echo $area; ?>',
							other_area:'<?php echo $other_area; ?>',
							city_code:'<?php echo $city_code; ?>',
							city_area_code:'<?php echo $city_area_code; ?>',
							house_type:'<?php echo $house_type; ?>',
							cl_house_type:'<?php echo $cl_house_type; ?>',
							order_id:order_id
						};
						KD.util.post("<?php echo \mobile\components\ApiUrl::toRoute(['hfd-evaluate/get-all-evaluate'], true); ?>",param,function(data) {
							$e.remove();
							if(data.code == 0){
								var param1={order_id:order_id,type:1};
								$.get("<?php echo Url::toRoute(['building/update-type'], true); ?>",param1,function(data) {});
								dialog(data.message,function(){
								window.location.href = "<?php echo Url::toRoute(['building/machine-trial'],true);?>" +'?price='+data.data.item.price+'&order_id='+order_id;
								});
							} else if(data.code == 100){
								var param2= {order_id:order_id,type:2};
								$.get("<?php echo Url::toRoute(['building/update-type'], true); ?>",param2,function(data) {});
								dialog(data.message,function(){
								window.location.href = "<?php echo Url::toRoute(['building/person-trial'],true);?>"+'?order_id='+order_id;
								});
							} else if(data.code == 200) {
								var param3={order_id:order_id,type:3};
								$.get("<?php echo Url::toRoute(['building/update-type'], true); ?>",param3,function(data) {});
								dialog(data.message,function(){
								window.location.href = "<?php echo Url::toRoute(['building/machine-trial'],true);?>" +'?price='+data.data.item.price+'&order_id='+order_id;
								});
							} else {
								console.log(3);
								dialog(data.message);
								$("#btn").attr("onclick","save()");
							}
						},function(){
							console.log(21);
						});
					} else {
						console.log(2);
						dialog(data.message);
						$("#btn").attr("onclick","save()");
					}
				});
			}else {
				console.log(1);
				dialog(data.message);
				$("#btn").attr("onclick","save()");
			}
		});
		<?php endif; ?>
    }
	$('#btn').click(save);
    // 消息弹窗
    function dialog(g,f){
			var $e=$('<div class="pop-box"><div class="pop-con"><p>'+g+"</p><button>确认</button></div></div>");
        $e.appendTo("body");
        $e.find("button").on("click",function(a){
            a.preventDefault();
            $e.remove();
            if(typeof (f) == "function"){
                f();
            }
        });
    }

	function imgZoom(obj){
		var src = $(obj).attr('src');
		var html = '';
		html += '<div id="mask" onclick="hideExDialog();" style="background: #000 url('+src+') no-repeat center center;background-size:100%;opacity: 1;filter: alpha(opacity=100);-moz-opacity: 1;-khtml-opacity: 1;"></div>';
		$(".kdlc_mobile_wraper > div").append(html);
	}

})
</script>
</html>