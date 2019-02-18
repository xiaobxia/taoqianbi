<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <title><?php echo $this->title ? $this->title : '借贷分期'; ?></title>
    <!--标准mui.css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/mui/mui.css" rel="stylesheet"/>
    <!--App自定义的css-->
    <link href="<?php echo $this->absBaseUrl; ?>/css/train-period/mystyle.css?v=2016033102" rel="stylesheet"/>
	<style>
		#pop_dialog{width:100%;position:fixed;top:50%;-webkit-transform:translateY(-50%);transform:translateY(-50%);max-width:480px;display:none;z-index:10000;}
		#pop_dialog .pop_dialog{background:#fff;width:80%;padding-bottom:10px;border-radius:5px;margin:0 auto;}
		#pop_dialog .message{text-align:center;padding:28px;}
		#pop_dialog .convert_btn{display:block;width:80%;margin:0 auto;padding:10px;background:#FD5353;text-align:center;color:#fff;border-radius:5px;}
		#mask{position:fixed;background-color:black;max-width:480px;top:0;width:100%;opacity:.65;z-index:9999;}
		#pop_dialog .no_confirm{float:left;display:block;width:38%;padding:8px;background:#CCC;text-align:center;color:#fff;border-radius:3px;margin-left:10px;}
		#pop_dialog .yes_confirm{float:right;display:block;width:38%;padding:8px;background:#FD5353;text-align:center;color:#fff;border-radius:3px;margin-right:10px;}
		.clear{ clear:both; }
	</style>
</head>
<body>
	<!-- <header class="mui-bar mui-bar-nav bgcolor1">
	    <h1 class="mui-title font-color1">证件上传</h1>
	</header> -->
	<div class="mui-content">
		<!-- <form class="mui-input-group" action="<?php // echo Url::toRoute(['train-period/upload-picture'])?>" method="post" enctype="multipart/form-data"> -->
		<form class="mui-input-group">
		    <ul class="mui-table-view mui-grid-view my-table-view">
		        <li class="mui-table-view-cell mui-media mui-col-xs-6">
		            <a class="my-table-view-cell" id="cell_img1">
		            	<input type="file" name="head-img1" id="the_img1" accept="image/*" class="my-file-img" onchange="javascript:setImagePreview('the_img1','head_img1','cell_img1');">
		            	<img class="mui-media-object mui-action-preview" id="head_img1" <?php if (empty($image_one)): ?>src="<?php echo $this->absBaseUrl; ?>/image/train-period/head-img1.png"<?php else: ?>src="<?php echo $image_one; ?>"<?php endif; ?> >
		            </a>
		            <p class="tl">1.身份证正面照</p>
		        </li>
		        <li class="mui-table-view-cell mui-media mui-col-xs-6">
		            <a class="my-table-view-cell" id="cell_img2">
		            	<input type="file" name="head-img2" id="the_img2" accept="image/*" class="my-file-img" onchange="javascript:setImagePreview('the_img2','head_img2','cell_img2');">
		                <img class="mui-media-object mui-action-preview" id="head_img2"  <?php if (empty($image_two)): ?> src="<?php echo $this->absBaseUrl; ?>/image/train-period/head-img2.png"<?php else: ?>src="<?php echo $image_two; ?>"<?php endif; ?> >
		            </a>
		            <p class="tl">2.身份证反面照</p>
		        </li>
		        <li class="mui-table-view-cell mui-media mui-col-xs-6">
		            <a class="my-table-view-cell" id="cell_img3">
		            	<input type="file" name="head-img3" id="the_img3" accept="image/*" class="my-file-img" onchange="javascript:setImagePreview('the_img3','head_img3','cell_img3');">
		            	<img class="mui-media-object mui-action-preview" id="head_img3"  <?php if (empty($image_three)): ?>src="<?php echo $this->absBaseUrl; ?>/image/train-period/head-img3.png"<?php else: ?>src="<?php echo $image_three; ?>"<?php endif; ?> >
		            </a>
		            <p class="my-p tl">3.本人审核照，证明自己是自己，请保证背景为纯色，五官清晰无遮挡</p>
		        </li>
		        <li class="mui-table-view-cell mui-media mui-col-xs-6">
		            <a class="my-table-view-cell" id="cell_img4">
		            	<input type="file" name="head-img4" id="the_img4" accept="image/*" class="my-file-img" onchange="javascript:setImagePreview('the_img4','head_img4','cell_img4');">
		                <img class="mui-media-object mui-action-preview" id="head_img4"  <?php if (empty($image_four)): ?>src="<?php echo $this->absBaseUrl; ?>/image/train-period/head-img4.png"<?php else: ?>src="<?php echo $image_four; ?>"<?php endif; ?> >
		            </a>
		            <p class="my-p tl">4.工作证明需拍摄一张。可为盖有公章的工作证明、劳动合同（甲乙方签字盖章页）、工牌、有效期内工作证、职员证。</p>
		        </li>
		    </ul>
		    <!-- <div class="mui-content-padded align-center">
		    	<input type="button" id="my-btn" value="保存">
			</div> --> 
		</form>
		<div class="padding _666 em__9 fd5457 em__9" id="msg">&nbsp;&nbsp;</div>
		<div class="mui-content-padded">
			<button class="mui-btn mui-btn-block bgcolor1 font-color1" type="button">保存</button>
		</div>
	</div>
	<!-- 半透明遮掩 -->
	<div id="mask" ></div>
	<!-- 用户确认框 -->
	<div id="pop_dialog" align="center">
		<div class="pop_dialog">
			<p class="message"></p>
			<p class="confirm"><a class="no_confirm" onclick="hideDialog()">取消</a><a class="yes_confirm" onclick="">确定</a><div class="clear"></div></p>
			<span class="convert_btn" onclick="hideDialog()">朕知道了</span>
		</div>
	</div>
</body>
<script src="<?php echo $this->absBaseUrl; ?>/js/jquery.min.js" type="text/javascript"></script>
<script src="<?php echo $this->absBaseUrl; ?>/js/ajaxfileupload.js" type="text/javascript"></script>
<script>
	var up_url = "<?php echo Url::toRoute(['train-period/upload-picture'])?>";
	$(function () {
        $(":button").click(function () {
            ajaxFileUpload();
        })
    })
	// 给用户提示的弹框
	function showDialog()
	{
		$("#mask").height(window.innerHeight);
		$("#mask").show();
	}
	function showPopDialog(message,btn){
		$('#pop_dialog div p').html(message);
		$('#pop_dialog div span').html(btn);
		$('#pop_dialog').show();
		showDialog();
	}
	// 隐藏兑换完毕弹框
	function hideDialog()
	{
		$("#mask").hide();
		$("#pop_dialog").hide();
		window.location.href="";
	}

    function ajaxFileUpload() {
    	// alert(1);
        $.ajaxFileUpload
        (
            {
                url: up_url, //用于文件上传的服务器端请求地址
                secureuri: false, //是否需要安全协议，一般设置为false
                fileElementId:  ['the_img1','the_img2','the_img3','the_img4'], //文件上传域的ID
                dataType: 'json', //返回值类型 一般设置为json
                success: function (data)  //服务器成功响应处理函数
                {
					if(0 == data.code){
//						$('.yes_confirm').attr('onclick',"toUrl('"+data.url+"')");
//						showPopDialog('提交资料成功',确定)
						window.location.href = data.url;

					}else{
						showPopDialog(data.message,'确认');
					}

                },
                error: function (data, status, e)//服务器响应失败处理函数
                {
                    console.log(e);
                }
            }
        )
        return false;
    }

	//下面用于图片上传预览功能
	function setImagePreview(docObj_id,imgObjPreview_id,localImagId_id) {
		var docObj = document.getElementById(docObj_id);
		var imgObjPreview = document.getElementById(imgObjPreview_id);
		if(docObj.files && docObj.files[0])
		{
			// 火狐7以上版本不能用上面的getAsDataURL()方式获取，需要一下方式
			imgObjPreview.src = window.URL.createObjectURL(docObj.files[0]);
		}
		else
		{
			// IE下，使用滤镜
			docObj.select();
			var imgSrc = document.selection.createRange().text;
			var localImagId = document.getElementById(localImagId_id);
			//图片异常的捕捉，防止用户修改后缀来伪造图片
			try{
				localImagId.style.filter="progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale)";
				localImagId.filters.item("DXImageTransform.Microsoft.AlphaImageLoader").src = imgSrc;
			}
			catch(e)
			{
				alert("您上传的图片格式不正确，请重新选择!");
				return false;
			}
			imgObjPreview.style.display = 'none';
			document.selection.empty();
		}
		return true;
	}
</script>
</html>