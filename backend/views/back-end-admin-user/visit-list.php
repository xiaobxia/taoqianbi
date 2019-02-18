<?php
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\models\AdminUser;
use yii\helpers\Html;
use backend\components\widgets\ActiveForm;

/**
 * @var backend\components\View $this
 */
$this->shownav('system', 'menu_adminuser_visit_list');
$this->showsubmenu('管理员管理', array(
    array('列表', Url::toRoute('admin-user/visit-list'), 1),
));
?>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo Url::toStatic('/css/daterangepicker.css'); ?>" />
<!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
<script src="<?php echo Url::toStatic('/js/jquery.min.js'); ?>"></script>

<script type="text/javascript" src="<?php echo Url::toStatic('/js/moment.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/js/daterangepicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<style>
    th { text-align: center;  }
    td {text-align: center;}
</style>
<?php $form = ActiveForm::begin([
    'id' => 'searchform',
    'method'=>'get',
    'options' => ['style' => 'margin-bottom:5px;'],
]); ?>
用户ID：<input type="text" value="<?php echo Yii::$app->request->get('admin_user_id', ''); ?>" name="admin_user_id" style="width: 100px"/>&nbsp;
用户名：<input type="text" value="<?php echo Yii::$app->request->get('username', ''); ?>" name="username" class="txt" placeholder="请输入关键字"/>&nbsp;
访问URL：<input type="text" value="<?php echo Yii::$app->request->get('url', ''); ?>" name="url" class="txt" placeholder="请输入关键字" />&nbsp;
请求参数：<input type="text" value="<?php echo Yii::$app->request->get('request_param', ''); ?>" name="request_param" class="txt" placeholder="请输入关键字" />&nbsp;
访问时间：<input type="text" value="<?php echo Yii::$app->request->get('visit-start-time', ''); ?>" name="visit-start-time"
            onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:true})" />
至<input type="text" value="<?php echo Yii::$app->request->get('visit-end-time', ''); ?>"  name="visit-end-time"
        onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:ss',alwaysUseStartDate:true,readOnly:true})" />&nbsp;&nbsp;
<input type="submit" name="search_submit" value="筛选" class="btn" />
<?php ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>序号</th>
        <th>用户ID</th>
        <th>用户名</th>
        <th>类型</th>
        <th>请求URL</th>
        <th>请求参数</th>
        <th>客户端IP</th>
        <th>请求时间</th>
    </tr>
    <?php foreach ($visitLogs as $value): ?>
        <tr class="hover">
            <td class="td25" ><?php echo $value->id; ?></td>
            <td><?php echo $value->admin_user_id; ?></td>
            <td><?php echo $value->admin_user_name; ?></td>
            <td><?php echo $value->request; ?></td>
            <td> <textarea style="width:400px;height:40px;background-color: white" readonly="readonly"><?php echo $value->route; ?></textarea></td>
            <?php if($value->request_params != '[]'):?>
                <td> <textarea style="width:500px;height:60px;background-color: white" readonly="readonly"><?php echo $value->request_params; ?></textarea></td>
            <?php else:?>
                <td class="params"><?php echo $value->request_params; ?></td>
            <?php endif;?>
            <td><?php echo $value->ip ?></td>
            <td><?php echo date('Y-m-d H:i:s', $value->created_at); ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<?php echo LinkPager::widget(['pagination' => $pages, 'firstPageLabel' => "首页", 'lastPageLabel' => "尾页"]); ?>
<?php if(!empty($visitLogs)):?>
    <div style="color:#3325ff;font-size: 14px;font-weight:bold;" >每页&nbsp;<?php echo Html::dropDownList('page_size', Yii::$app->getRequest()->get('page_size', 15), \common\models\loan\LoanCollectionRecord::$page_size); ?>&nbsp;条</div>
    <script type="text/javascript">
        $('select[name=page_size]').change(function(){
            var pages_size = $(this).val();
            $('#searchform').append("<input type='hidden' name='page_size' value="+ pages_size+">");
            $('#searchform').append('<input type="hidden" name="search_submit" value="筛选">');
            $('#searchform').submit();
        });
    </script>
<?php endif;?>
