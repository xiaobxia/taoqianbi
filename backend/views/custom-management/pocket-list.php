<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016-10-25
 * Time: 15:34
 */
use yii\helpers\Html;
use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use common\helpers\StringHelper;
use common\models\LoanRecordPeriod;
use common\models\LoanPerson;
use common\models\User;
use common\models\UserLoanOrder;
use common\services\loan_collection\UserFeedbackService;
//use mobile\components\ApiUrl;
//use newh5\components\ApiUrl;
$this->shownav('service', 'menu_user_loan_list');
$this->showsubmenu('借款列表', array(
    array('借款列表', Url::toRoute('custom-management/pocket-list'), 1),
));
?>
<link rel="stylesheet" href="//cdn.bootcss.com/bootstrap/3.3.0/css/bootstrap.min.css">
<!-- jQuery文件。务必在bootstrap.min.js 之前引入 -->
<script src="//cdn.bootcss.com/jquery/1.11.1/jquery.min.js"></script>

<!-- 最新的 Bootstrap 核心 JavaScript 文件 -->
<script src="//cdn.bootcss.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>

<style>
    .tb2 th{
        font-size: 12px;
    }
    .container{
        width: 100%;
    }
    #msg{
        color: red;
        float: left;
    }
    .panel{
        margin-bottom: 0;
        margin-left: 0;
    }
</style>
<div class="panel panel-body" style="font-size: 12px;">
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
订单号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id', ''); ?>" name="id" class="txt" style="width:120px;"/>&nbsp;
用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('uid', ''); ?>" name="uid" class="txt" style="width:120px;"/>&nbsp;
姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:120px;"/>&nbsp;
手机号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('phone', ''); ?>" name="phone" class="txt" style="width:120px;"/>&nbsp;
<input type="submit" name="search_submit" value="搜索" class="btn">
&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" <?php if (Yii::$app->getRequest()->get('cache')==1): ?> checked <?php endif;?> class="btn">去除缓存
<?php $form = ActiveForm::end(); ?>
</div>

        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>订单号</th>
                <th>用户ID</th>
                <th>姓名</th>
                <th>手机号</th>
                <th>是否是老用户</th>
                <th>借款金额(元)</th>
                <th>借款项目</th>
                <th>借款期限</th>
                <th>公司名称</th>
                <th>申请来源</th>
                <th>申请时间</th>
                <th>子类型</th>
                <th>来源</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
            <?php foreach ($data_list as $value): ?>
                <tr class="hover">
                    <td><?php echo $value['id']; ?></td>
                    <td><?php echo $value['user_id']; ?></td>
                    <td><?php echo $value['name']; ?></td>
                    <th><?php echo $value['phone']; ?></th>
                    <th><?php echo isset(LoanPerson::$cunstomer_type[$value['customer_type']])?LoanPerson::$cunstomer_type[$value['customer_type']]:""; ?></th>
                    <th><?php echo sprintf("%0.2f",$value['money_amount']/100); ?></th>
                    <th><?php echo isset(UserLoanOrder::$loan_type[$value['order_type']])?UserLoanOrder::$loan_type[$value['order_type']]:""; ?></th>
                    <th><?php echo isset(UserLoanOrder::$loan_method[$value['loan_method']])?$value['loan_term'] .UserLoanOrder::$loan_method[$value['loan_method']]:$value['loan_term']; ?></th>
                    <th><?php echo $value['company_name'] ?></th>
                    <th><?php echo "---" ?></th>
                    <th><?php echo date('Y-m-d H:i:s',$value['order_time']); ?></th>
                    <th><?php echo UserLoanOrder::$sub_order_type[$value['sub_order_type']].'('.\common\models\BaseUserCreditTotalChannel::$card_types[$value['card_type']].')'; ?></th>
                    <th><?php echo LoanPerson::$person_source[$value['source_id']] ?? APP_NAMES; ?></th>
                    <th><?php echo isset($status_data[$value['id']])?$status_data[$value['id']]:""; ?></th>
                    <th>
                        <a href="<?php echo Url::toRoute(['pocket/pocket-detail', 'id' => $value['id']]);?>">查看</a>&nbsp;&nbsp;
                        <a style="color:orangered;" href="javascript:;" onclick="popUp(this)">反馈</a>&nbsp;&nbsp;
                        <a style="color:orangered;" href="javascript:;" onclick="chancelUp(this)">取消借款</a>&nbsp;&nbsp;
                        <?php if($value['status'] == UserLoanOrder::STATUS_CHECK && $value['auto_risk_check_status']!=1){?>
                            <a onclick="if(confirmMsg('确定要跳过机审吗？')){return true;}else{return false;}" href="<?php echo Url::toRoute(['check-status', 'id' => $value['id']]);?>">跳过机审</a>&nbsp;&nbsp;
                        <?php }?>
                        <?php
                            $id = $value['id'];
                            $encrypt_id = StringHelper::auto_encrypt("{$id}");
                            if(YII_ENV_PROD){
                                $url = 'http://'.SITE_DOMAIN.'/credit/web/credit-web';
                            }else if(YII_ENV_TEST){
                                $url = 'http://test-www.xybaitiao.com/credit/web/credit-web';
                            }else{
                                $url = "http://192.168.8.101/zhoushan/php_dev/credit/web/credit-web";//"";http://qb.wzdai.com/credit/web/credit-web/license-agreement?id=900521
                            }
                            $downloan_url_1 = "{$url}/loan-issued2?id={$encrypt_id}";
                            $downloan_url_2 = "{$url}/license-explode?id={$encrypt_id}";
                        ?>
                        <a href="<?php echo $downloan_url_1;?>" target="_blank">《借款协议》</a>&nbsp;
                        <a href="<?php echo $downloan_url_2;?>" target="_blank">《授权扣款委托书》</a>
                    </th>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($data_list)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<div class="modal fade" id="myModal" aria-hidden="true" style="display: none;">
    <div class="modal-dialog" style="width: 630px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">
                        ×
                    </span>
                    <span class="sr-only">
                        Close
                    </span>
                </button>
                <h4 class="modal-title">
                    客服人员问题反馈：
                </h4>
            </div>
            <div class="modal-body">
                <form class="form-inline" role="form">
                    订单ID：<input type="text" class="form-control" id="order_id" name="order_id" value="" size="10" readonly>
                    用户姓名：<input type="text" id="userName" value="" readonly size="10" class="form-control">
                    是否加急：
                    <select id="is_urgent" class="form-control" name="is_urgent">
                        <option value="-1">-请选择-</option>
                        <option value="0" selected="selected">否</option>
                        <option value="1">是</option>
                    </select>
                </form>
            </div>
            <div class="modal-body">
                <p>请填写问题详情：</p>
                <textarea name="remark" id="remark" class="form-control" rows="4" cols="20"></textarea>
            </div>
            <div class="modal-footer">
                <span id="msg">一天只有五次加急处理机会哦...!</span>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    取消
                </button>
                <button type="button" id="btn_submit" class="btn btn-primary">
                    提交
                </button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<div class="modal fade" id="myModal2" aria-hidden="true" style="display: none;">
    <div class="modal-dialog" style="width: 630px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">
                        ×
                    </span>
                    <span class="sr-only">
                        Close
                    </span>
                </button>
                <h4 class="modal-title">
                    借款订单手动取消：
                </h4>
            </div>
            <div class="modal-body">
                <form class="form-inline" role="form">
                    订单ID：<input type="text" class="form-control" id="chancel-order_id" name="order_id" value="" size="10" readonly>
                    用户姓名：<input type="text" id="chancel-userName" value="" readonly size="10" class="form-control">
                </form>
            </div>
            <div class="modal-body">
                <p>取消理由：</p>
                <textarea name="remark" id="chancel-remark" class="form-control" rows="4" cols="20"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    取消
                </button>
                <button type="button" id="chancel_submit" class="btn btn-primary">
                    提交
                </button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<script type="text/javascript">
    function popUp(obj) {
        order_id        = $(obj).parent().parent().children("td").eq(0).text(); //获取当前行的订单ID
        user_name   = $(obj).parent().parent().children('td').eq(2).text(); //获取当前行的催收人姓名
        $('#myModal').find(".modal_warning").css('display', 'none');
        $('#myModal').find("#order_id").val(order_id);
        $('#myModal').find("#userName").val(user_name);
        $('#myModal').modal();
    }
    function chancelUp(obj) {
        order_id        = $(obj).parent().parent().children("td").eq(0).text(); //获取当前行的订单ID
        user_name   = $(obj).parent().parent().children('td').eq(2).text(); //获取当前行的催收人姓名
        $('#myModal2').find(".modal_warning").css('display', 'none');
        $('#myModal2').find("#chancel-order_id").val(order_id);
        $('#myModal2').find("#chancel-userName").val(user_name);
        $('#myModal2').modal();
    }
    $("#btn_submit").click(function(){
        //$(this).attr('disabled',true);
        if ($.trim($('#remark').val()) == '') {
            $('.modal_warning').css('display', 'block');
            return;
        }
        var url = '<?php echo Url::toRoute(['custom-management/custom-problem-feedback']);?>';
        var params = {
            remark: $('#remark').val(),
            order_id: $('#order_id').val(),
            create_user_id: $('#create_user_id').val(),
            is_urgent : $("#is_urgent").val(),
        };
        $.get(url, params, function(data) {
            if (data.code == 0) {
                alert(data.msg);
                location.reload(true);
            } else {
                alert(data.msg);
            }
        });
    })
    $("#chancel_submit").click(function(){
        if ($.trim($('#chancel-remark').val()) == '') {
            alert('备注不能为空');
            return;
        }
        var url = '<?php echo Url::toRoute(['custom-management/manual-chancel-order']);?>';
        var params = {
            remark: $('#chancel-remark').val(),
            order_id: $('#chancel-order_id').val(),
        };
        $.get(url, params, function(data) {
            if (data.code == 0) {
                alert(data.msg);
                location.reload(true);
            } else {
                alert(data.msg);
            }
        });
    })
</script>
