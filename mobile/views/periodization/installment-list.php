<?php
use yii\helpers\Url;
use common\models\IndianaOrder;
use common\models\IndianaUserWish;

//if( $this->isFromApp() ){
//    $title = '0息分期买iPhone啦';
//    $desc = '口袋分期，0首付，0利息，0服务费，线上申请，极速审核发货';
//    $award_config = '';
//    $activity_id = 1;
//    $url = Url::toRoute(['indiana/installment-list'],true);
//}
?>
<style type="text/css">
<!--
#installment_list_wraper{
    background: #F2F2F2;
}
#installment_list_wraper .all_list td{
    position: relative;
    border-top: 1px solid #F2F2F2;
    background: #FFF;
}
#installment_list_wraper .all_list td + td{
    border-left: 1px solid #F2F2F2;
}
#installment_list_wraper .all_list ._margin{
    margin: auto 12.5%;
}
#installment_list_wraper .line{
    padding: .3em;
}
-->
</style>
<div id="installment_list_wraper">
<!--    <img src="--><?php //echo $this->absBaseUrl;?><!--/image/indiana/banner_fenqi.png" width="100%">-->
    <div class="bg_fff padding lh_em_3 p_relative">
<!--        <i class="_969696" style="padding-left:6%;background:url('--><?php //echo $this->absBaseUrl;?>/*/*/image/indiana/icon_edit.png') no-repeat left center;background-size:8%;">填写心愿单，想要啥就有啥</i>*/*/
<!--        <span class="f_right a_right icon_arrow">&nbsp;<img class="v_center" src="--><?php //echo $this->absBaseUrl;?><!--/image/indiana/icon-arrow.png" alt="" width="50%"></span>-->
        <p class="clear"></p>
<!--        <a class="indie" href="--><?//=Url::toRoute(['user/wish','source'=>IndianaUserWish::SOUCE_FENQI],true)?><!--"></a>-->
    </div>
    <p class="line"></p>

    <table class="all_list" id="dataTable" width="100%" cellspacing="0" cellpadding="0">
        <?php foreach($data as $key => $val){ 
                if($key%2==0) echo "<tr>";
        ?>
            <td class="a_center v_bottom" width="50%">
                <div class="_margin">
                    <div style="height:5em;margin:5% 0;background:url(<?=$val['img_url']?>) no-repeat center center;background-size:60%;"></div>
                    <p style="max-width:140px;" class="a_left lh_em_1_5 _000 o_hidden _ellipsis"><?=$val['title']?></p>
                    <p class="a_left lh_em_2 em__9 _999">总价：<i class="_999"><?=$val['installment_price']/100?></i></p>
<!--                    <p class="a_left lh_em_2 em__9 _999">月费率：<i class="_999 t_line_through">--><?php //echo IndianaOrder::FEE_RATE;?><!--%</i>&nbsp;<span class="bg_fd5353 fff _b_radius">&nbsp;0%&nbsp;</span></p>-->
                    <p class="a_left lh_em_2 em__9 _999">月供：<i class="fd5353">&yen;<?=$val['month_pay'];?>&times;<?= $val['max_month'];?></i></p>
                    <p class="a_left lh_em_2 em__9 _999">分期人数：<i class="_999"><?=$val['installment_buy_count'];?></i></p>
                    <a class="indie" href="<?=Url::toRoute(['indiana/shop-installment','indiana_id'=>$val['id']],true)?>"></a>
                </div>
            </td>
            <?php 
                $count = count($data);
                if($count%2!=0 && $key==($count-1) ):
            ?>
            <td class="a_center" width="50%">
                <p class="_999">敬请期待...</p>
            </td>
            <?php endif;?>
        <?php }?>
    </table>
	<?php if($data){ ?>
    <p class="bg_fff lh_em_2_5 a_center" id="searchMore"><a class="fd5353" href="javascript:morePage();">查看更多</a></p>
    <?php }else{ ?>
    <p class="bg_fff lh_em_2_5 a_center fd5353">暂无数据</p>
    <?php } ?>
    <p class="line"></p>
    <div class="bg_fff padding column lh_em_3 p_relative">
<!--        <span class="_999"><img class="v_center" src="--><?php //echo $this->absBaseUrl;?><!--/image/indiana/icon_share.png" alt="" width="5%"><i class="v_center">&nbsp;&nbsp;分享给好友</i></span>-->
<!--        <i class="f_right a_right">&nbsp;<img class="v_center" src="--><?php //echo $this->absBaseUrl;?><!--/image/indiana/right.png" alt="" width="40%"></i>-->
        <p class="clear"></p>
        <a class="indie" href="javascript:sharePacket()"></a>
    </div>
    <?php 
//        echo \qugou\components\Footer::widget();
    ?>
</div>
<script type="text/javascript">
    var p = 2;
    function morePage(){
        var url = "<?php echo Url::toRoute(['indiana/installment-list'],true);?>";
        var url1 = "<?=Url::toRoute(['indiana/shop-installment'],true);?>";
        $.post(url+'?page='+p,'', function(resp){
            var len = resp.data.length;
            if(len <= 0){
                return $('#searchMore').html('<a class="fd5353" href="javascript:;">没有更多啦</a>');
            }
            var html = '';
            $.each(resp.data,function(index,value){
                if(index%2 == 0){
                    html += '<tr>';
                }
                html += '<td class="a_center v_bottom" width="50%">';
                html += '<div class="_margin"><div style="height:5em;margin:5% 0;background:url('+value['img_url']+') no-repeat center center;background-size:60%;"></div>';
                html += '<p style="max-width:140px;" class="a_left lh_em_1_5 _000 o_hidden _ellipsis">'+value['title']+'</p>';
                html += '<p class="a_left lh_em_2 em__9 _999">总价：<i class="_999">'+value['installment_price']/100+'</i></p>';
                html += '<p class="a_left lh_em_2 em__9 _999">月供：<i class="fd5353">&yen;'+value['month_pay']+'&times;'+value['max_month']+'</i></p>';
                html += '<a class="indie" href="'+url1+'?indiana_id='+value['id']+'"></a></div>';
                html += '</td>';
                if( len%2 != 0 && index == len-1 ){
                   html += '<td class="a_center" width="50%">';
                   html += '<p class="_999">敬请期待...</p>';
                   html += '</td>';
                }
            });
            $('#dataTable').append(html);
            p++;
        });
    }
<!--    --><?php //if($this->isFromApp()): ?>
//    //APP分享
//    function sharePacket() {
//        return jumpTo('wap2app://app.launch/params?title=<?php //echo $title;?>//&desc=<?php //echo $desc;?>//&url=<?php //echo urlencode($url);?>//&share_type=<?php //echo \common\models\ActivitySuccessUp::SHARE_SPACIAL_ACTIVITY;?>//&activity_id=<?php //echo $activity_id;?>//&award_config=<?php //echo $award_config;?>//');
//    }
//    <?php //else: ?>
//    function sharePacket() {
//        return showExDialog('请先使用APP，分享页面给好友','我知道了');
//    }
//    <?php //endif;?>
</script>