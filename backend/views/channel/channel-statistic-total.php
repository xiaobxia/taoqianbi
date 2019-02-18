
<?php

use common\helpers\Url;
use backend\components\widgets\LinkPager;
use backend\components\widgets\ActiveForm;
use yii\helpers\Html;
use common\models\LoanPerson;
use common\api\RedisQueue;

$this->shownav('system', 'menu_channel_statistic_total');

$this->showsubmenu('渠道推广汇总<p>统计为实时更新</p>');
?>
<style type="text/css">
    .itemtitle p{ display:inline;font-weight:normal;color:#999;padding-left:10px;}
</style>
<style>.tb2 th{ font-size: 12px;}</style>
<?php $form = ActiveForm::begin(['method' => "get",'options' => ['style' => 'margin-top: 10px;margin-bottom:10px;'] ]); ?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
渠道名称：<?php echo Html::dropDownList('channel', Yii::$app->getRequest()->get('channel', ''), $channel, ['prompt' => '所有渠道']); ?>
申请时间：<input type="text" value="<?php echo Yii::$app->getRequest()->get('begintime', ''); ?>" name="begintime" onfocus="WdatePicker({startDate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
至<input type="text" value="<?php echo Yii::$app->getRequest()->get('endtime', ''); ?>"  name="endtime" onfocus="WdatePicker({startDcreated_atate:'%y-%M-%d %H:%m:00',dateFmt:'yyyy-MM-dd HH:mm:00',alwaysUseStartDate:true,readOnly:true})">
<input type="submit" name="search_submit" value="过滤" class="btn"><?php
$key="channel-check";
$channeltime=RedisQueue::get(['key'=>$key]);
if($channeltime!=''&&!empty($channeltime)){
    echo '&nbsp;&nbsp;&nbsp;&nbsp;<p style="color:#7447f7;display:inline;">最近一次更新时间：'.date('Y-m-d H:i:s',$channeltime).'</p>';
}
?>
<!--&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="cache" value="1" --><?php //if (Yii::$app->getRequest()->get('cache')==1): ?><!-- checked --><?php //endif;?><!-- class="btn">去除缓存-->
<?php $form = ActiveForm::end(); ?>

<table class="tb tb2 fixpadding">
    <tr class="header">
        <th>序列号</th>
        <th>渠道名称</th>
        <th>渠道英文名称</th>
        <th>统计日期</th>
        <th>注册总数</th>
        <!--        <th>注册数(扣量后)</th>-->
        <!--        <th>扣量率</th>-->
        <!--        <th>扣量数</th>-->
        <th>申请数</th>
        <th>借款数</th>
        <th>还款数</th>
        <th>还款率</th>
        <th>最后一次更新时间</th>
    </tr>
    <?php
    $pre_pv = array_sum(array_column($info, 'pre_pv'));
    $pv = array_sum(array_column($info, 'pv'));
    $apply_all = array_sum(array_column($info, 'apply_all'));
    $loan_all = array_sum(array_column($info, 'loan_all'));
    $repayment_all = array_sum(array_column($info, 'repayment_all'));
    $rate = ($loan_all>0)?sprintf("%0.2f",($repayment_all*100/$loan_all))."%":"-";
    $rate_total = ($loan_all_total>0)?sprintf("%0.2f",($repayment_all_total*100/$loan_all_total))."%":"-";
    //截至日期
    $end_date=date("Y-m-d",strtotime("-8 day"));
    if (isset($_GET['channel']) && !empty($_GET['channel'])){
        $text = "    <tr>
        <th style='color:#5f10ff;font-weight:bold;'>汇总信息</th>
        <th colspan='3' style='color:#5f10ff;font-weight:bold;'>截至到：".$end_date."（总借款<font style='color:red;'>".$loan_all_total."</font>笔，总还款<font style='color:red;'>".$repayment_all_total."</font>笔，还款率：<font style='color:red;'>".$rate_total."</font>）</th>
        <th style='color:#5f10ff;font-weight:bold;'>".number_format($pre_pv)."</th>
        <th style='color:#5f10ff;font-weight:bold;'>".number_format($apply_all)."</th>
        <th style='color:#5f10ff;font-weight:bold;'>".number_format($loan_all)."</th>
        <th style='color:#5f10ff;font-weight:bold;'>".number_format($repayment_all)."</th>
        <th style='color:#5f10ff;font-weight:bold;'>".$rate."</th>
        <th></th>
    </tr>";
        echo $text;
    } ?>
    <?php
    $page=1;
    if(isset($_GET['page'])){
        $page=intval(trim($_GET['page']));
    }
    $per_page=15;
    if(isset($_GET['per-page'])){
        $per_page=intval(trim($_GET['per-page']));
    }
    $i=1+($page-1)*$per_page;
    foreach ($info as $value): ?>
        <tr class="hover">
            <td><?php echo $i; ?></td>
            <td><?php echo isset($value['name'])?$value['name']:""; ?></td>
            <td><?php echo isset($value['appMarket'])?$value['appMarket']:""; ?></td>
            <td><?php echo isset($value['time'])?date('Y-m-d',$value['created_at']):""; ?></td>
            <td class="edit-cell-input text-view" data-id="<?php echo $value['id']; ?>"><?php echo isset($value['pv'])?$value['pv']:0; ?></td>
<!--            <td>--><?php //echo isset($value['withhold_pv'])?$value['withhold_pv']:0; ?><!--</td>-->
            <td><?php echo isset($value['apply_all'])?$value['apply_all']:0; ?></td>
            <td><?php echo isset($value['loan_all'])?$value['loan_all']:0; ?></td>
            <td><?php echo isset($value['repayment_all'])?$value['repayment_all']:0; ?></td>
            <td><?php echo ($value['loan_all']>0)?sprintf("%0.2f",($value['repayment_all']*100/$value['loan_all']))."%":"-"; ?></td>
            <td><?php echo isset($value['time'])?date('Y-m-d H:i:s',$value['updated_at']):""; ?></td>
        </tr>
        <?php
        ++$i;
    endforeach; ?>
</table>
<?php if (empty($info)): ?>
    <div class="no-result">暂无记录</div>
<?php endif; ?>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>
<script>
    //标记值是否修改过
    var old_value='';
    $('.edit-cell').on('click', function () {
        if ($(this).hasClass('text-view')) {
            var text = $(this).text();
            var inputId = (Math.random() * 10000000).toString(16).substr(0, 4) + '-' + (new Date()).getTime() + '-' + Math.random().toString().substr(2, 5);
            var inputIdStr = "'" + inputId + "'";
            old_value = $.trim($(this).html());
            var optionHtml = '<option value = "-">-</option>'
            for (var i = 1; i < 10; i++) {
                optionHtml += '<option value = "' + i + '">' + i + '0%</option>'
            }
            $(this).html('<select style="width:60px;" id="' + inputId + '" onblur="onChangeHandler(' + inputIdStr + ')">' +
                optionHtml +
                '</select>');
            //$(this).html('<input type="text" value="'+text+'" style="width:60px;" id="'+inputId+'" onblur="onBlurHandler('+inputIdStr+')" />');
            $(this).removeClass('text-view').addClass('input-view');
            //默认-
            //$('#' + inputId).val('-')
        }
    });

    function onChangeHandler(id) {
        var $cell = $('#'+id).parent('.edit-cell');
        var rawId = $cell.attr('data-id');
        var value = $('#'+id).val();
        if(value.toString() === '-'){
            //不修改请求ajax进行修改值
            onChangeSuccess();
            return;
        }
        if(old_value.toString()==(value.toString() + '0%')){
            //不修改请求ajax进行修改值
            onChangeSuccess();
            return;
        }
        if(['1','2','3','4','5','6','7','8','9'].indexOf(value.toString()) === -1){
            //不修改请求ajax进行修改值
            onChangeSuccess();
            return;
        }
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        var url='<?php echo Url::toRoute(['channel/channel-statistic-edit']); ?>';
        $.post(url,{id:rawId.toString(),pv_rate:value.toString(),_csrf:csrfToken,rnd:Math.random()},function(data){
            if(data!=null){
                if(data.result){
                    onChangeSuccess();
                    alert(data.message);
                }else{
                    alert(data.message);
                }
            }
        },'json');
        //TODO 请求接口修改
        //成功回调
        function onChangeSuccess() {
            if (value ==='-') {
                $cell.html(value)
            } else {
                $cell.html(value + '0%')
            }
            $cell.removeClass('input-view').addClass('text-view');
            // $cell.prev().html(value)
        }
    }

    var old_value_input='';
    $('.edit-cell-input').on('click', function () {
        if ( $(this).hasClass('text-view')) {
            var text = $(this).text();
            var inputId = (Math.random()*10000000).toString(16).substr(0,4)+'-'+(new Date()).getTime()+'-'+Math.random().toString().substr(2,5);
            var inputIdStr = "'"+ inputId + "'";
            old_value_input = $.trim($(this).html());
            $(this).html('<input type="text" value="'+text+'" style="width:60px;" id="'+inputId+'" onblur="onBlurHandler('+inputIdStr+')" />');
            $(this).removeClass('text-view').addClass('input-view');
        }
    });
    function onBlurHandler(id) {
        var $cell = $('#'+id).parent('.edit-cell-input');
        var rawId = $cell.attr('data-id');
        var value = $('#'+id).val();
        if(old_value_input.toString()==value.toString()){
            //不修改请求ajax进行修改值
            onChangeSuccess();
            return;
        }
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        var url='<?php echo Url::toRoute(['channel/channel-statistic-edit']); ?>';
        $.post(url,{id:rawId.toString(),pv:value.toString(),_csrf:csrfToken,rnd:Math.random()},function(data){
            if(data!=null){
                if(data.result){
                    onChangeSuccess();
                    alert(data.message);
                }else{
                    alert(data.message);
                }
            }
        },'json');
        //TODO 请求接口修改
        //成功回调
        function onChangeSuccess() {
            $cell.html(value)
            $cell.removeClass('input-view').addClass('text-view');
            // $cell.next().html(value)
        }
    }

    // $('.edit-cell input').on('blur', function () {
    //     var $cell = $(this).parent('.edit-cell');
    //     var rawId = $cell.attr('data-id');
    //     var value = $(this).val();
    //     //TODO 请求接口修改
    //     //成功回调
    //     function onChangeSuccess() {
    //         $cell.html(value)
    //     }
    //     onChangeSuccess();
    // })
</script>
