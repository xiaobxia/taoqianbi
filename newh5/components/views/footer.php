<?php
use yii\helpers\Url;
?>
<?php if(\Yii::$app->controller->layout == 'pc-main'):?>
<!-- PC -->
<?php else:?>
<!-- M版 -->
<style type="text/css">
#footer{
    width:100%;
    bottom:0;
    z-index:1;
}
#footer .tab_list{
    border-collapse: collapse;
}
#footer .tab_list td{
    padding-top: .4em;
    padding-bottom: .4em;
    border-top: 1px solid #d3dee5;
}
#footer .tab_list td img{
    margin-bottom: .3em;
}
</style>
<div class="p_fixed bg_fff" id="footer">
    <table class="tab_list" width="100%">
        <tr>
            <td class="p_relative a_center" width="34%">
                <img class="_hidden" width="30%" src="<?php echo $this->absBaseUrl;?>/image/m-site/tab_icon_1.png"/>
                <img width="32%" src="<?php echo $this->absBaseUrl;?>/image/m-site/tab_icon_11.png"/>
                <p class="em_1 _61cae4">借款</p>
                <a class="indie _333 em__9" href="<?php echo Url::toRoute(['m-site/index'],true);?>"></a>
            </td>
            <td class="p_relative a_center" width="33%">
                <img width="32%" src="<?php echo $this->absBaseUrl;?>/image/m-site/tab_icon_2.png"/>
                <img class="_hidden" width="30%" src="<?php echo $this->absBaseUrl;?>/image/m-site/tab_icon_22.png"/>
                <p class="em_1 _8d8d8d">还款</p>
                <a class="indie _333 em__9" href="<?php echo Url::toRoute(['app-page/bank-card-action'],true);?>"></a>
            </td>
            <td class="p_relative a_center" width="34%">
                <img width="32%" src="<?php echo $this->absBaseUrl;?>/image/m-site/tab_icon_3.png"/>
                <img class="_hidden" width="30%" src="<?php echo $this->absBaseUrl;?>/image/m-site/tab_icon_33.png"/>
                <p class="em_1 _8d8d8d">我的</p>
                <a class="indie _333 em__9" href="<?php echo Url::toRoute(['app-page/bank-card-action'],true);?>"></a>
            </td>
        </tr>
    </table>
</div>
<?php endif;?>