<?php
use yii\bootstrap\Tabs;
use common\helpers\Url;
use backend\assets\AppAsset;
use backend\components\widgets\SideNavWidget;
use yii\helpers\Html;
$this->title = $classDoc['name'];

AppAsset::register($this);
?>

<?php $this->beginPage(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="x-ua-compatible" content="ie=7" />
	<?php $this->head() ?>
	<link href="<?php echo Url::toStatic('/css/document.css'); ?>" rel="stylesheet" type="text/css" />
	<script src="<?php echo Yii::$app->getRequest()->getBaseUrl();?>/js/jquery-1.7.2.min.js" type="text/javascript"></script>
	<title><?php echo $this->title?></title>
        <style>
            a.list-group-item.active {
                background-color: #3325ff;
                border-color: #3325ff;
                color: #fff;
                z-index: 2;
            }
        </style>
</head>
<body>
<div id="cpcontainer" style="margin: 60px 30px 0;">
	<?php $this->beginBody(); ?>
	<nav role="navigation" class="navbar-inverse navbar-fixed-top navbar" id="w13767">
		<div class="navbar-header">
			<a href="<?php echo Url::toRoute(['document/partner-api',
                            'partner'=>$partner
                        ]); ?>" class="navbar-brand"><?php echo $this->title?></a>
		</div>
	</nav>

        <div class="row">
            <div class="col-md-3">
	        <?php echo SideNavWidget::widget([
	            'id' => 'navigation',
	            'items' => $navItems,
	            'view' => $this,
	        ]); ?>
	    </div>
            <div class="col-md-9" style="padding-top:20px;">
                <div class="row hidden api-doc" id="desc" >
                    <?php echo $classDoc['desc']?>
                </div>
                    <?php foreach($actions as $model) { ?>
                    <div class="row hidden api-doc" id="<?php echo substr($model->getRoute(), strrpos($model->getRoute(), '/')+1)?>">
                        <table class="table  table-bordered">
                            <tr>
                                <td>
                                    请求接口
                                </td>
                                <td>
                                    <?php echo $model->getTitle();?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    请求地址
                                </td>
                                <td>
                                    <?php echo substr($model->getRoute(), strrpos($model->getRoute(), '/')+1);?>
                                </td>
                            </tr>
                            <tr>
                                <td>请求方式</td>
                                <td><?php echo $model->getMethod();?></td>
                            </tr>
                            <tr>
                                <td>请求参数</td>
                                <td><?php if ($model->params): ?>
                                    <?php foreach ($model->params as $param): ?>
                                    <div class="form-group">
                                        <?php echo substr($param['name'],1).' '.$param['type'].' '.$param['desc']; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <div class="form-group">无参数</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>返回结果</td>
                                <td><?php echo $model->getReturn();?></td>
                            </tr>
                            <tr>
                                <td>测试地址</td>
                                <td>
                                    <a href="<?php echo Url::toRoute([$encrypt ? 'encrypt-api-debug' : 'api-debug', 'route'=>$model->getRoute()])?>" target="_blank"><?php echo Url::toRoute([ $encrypt ? 'encrypt-api-debug' : 'api-debug', 'route'=>$model->getRoute()],true)?></a>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php }?>
            </div>
        </div>
	<?php $this->endBody(); ?>
</div>
<script type="text/javascript">
$(function(){
    var locationHash = window.location.hash;
    window.location.hash;

    window.onhashchange=function(){
        var key=window.location.hash.substring(1);//substring(1)用来减去地址栏的地址中的#号
        $('.api-doc').addClass('hidden');
        $('#'+key).removeClass('hidden');

        $('.list-group-item').removeClass('active');
        $("a[href='#"+key+"']").addClass('active');
        $(window).scrollTop(0);
    };

    if(!window.location.hash) {
        window.location.hash = '<?php echo $navItems[0]['url']?>';
    } else {
        window.onhashchange();
    }
});
</script>
</body>
</html>
<?php $this->endPage(); ?>
