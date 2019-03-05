<?php
use common\models\LoanPerson;
?>
<style type="text/css">
    #about_company_wraper{min-height:100%;background:#fff;}
    #about_company_wraper .column1{padding-top:2%;}
    #about_company_wraper .column1 p{padding-top:2%;font-size: 0.4em}
    #about_company_wraper .column2{margin-top:-2.75%;padding-bottom:10%;border-bottom:1px solid #e6e6e6;}
</style>
<div id="about_company_wraper">
    <div class="padding em__9" style="padding: 0 4.25%;">
            <h3 style="padding-top:4%" class="lh_em_2 _666">公司介绍</h3>
        <div class="lh_em_1_8 _8d8d8d">
            <p class="column1"><?php echo $app_name?>是基于移动互联网的网络借贷App。公司为解决城市年轻白领与蓝领人群资金周转的难题，提供短期借款服务。</p>
            <p class="column1"<?php echo $app_name?>研发团队实力雄厚，采用先进的大数据技术，分析不同人群的需求，提供最适合该类人群的借贷解决方案。在海量数据的基础上，团队致力于研发高效、精准的风控系统，采用全线上、无打扰自动审核程序，极大降低传统借贷的繁琐流程，提供无抵押、无担保地便捷借款服务。</p>
            <p class="column1">我们坚信未来社会的基石是信用，每个人的信用是他们无形的财富。因此，公司立志于建立和完善社区信用体系，帮助信用良好的用户享受更加便利和优质的生活。</p>
        </div>
    <div class="column2">
    <?php /*if($source == LoanPerson::PERSON_SOURCE_MOBILE_CREDIT):*/?><!--
    </div>
        <div class="column1" style="padding-top: 6%;">
            <span>客服 Q Q: <a id="online" style="color: #ff6462">立即咨询客服</a></span>
        </div>
        <div class="column1">
            <span>客服电话：</span><span style="color: #666666"><a onclick="callPhone('400-0805356')" style="color: #ff6462">400-080-5356</a></span>
        </div>
        <div class="column1">
            <span>服务时间：</span><span style="color: #666666">9:00~21:00 (周末及法定节假日9:00~18:00)</span>
        </div>
    </div>
    <?php /*endif;*/?>
    <?php /*if($source == LoanPerson::PERSON_SOURCE_WZD_LOAN):*/?>
    </div>
        <div class="column1" style="padding-top: 6%;">
            <span>客服 Q Q: <a id="online1" style="color: #ff6462">立即咨询客服</a></span>
        </div>
        <div class="column1">
            <span>客服电话：</span><span style="color: #666666"><a onclick="callPhone('021-80311201')" style="color: #ff6462">021-80311201</a></span>
        </div>
        <div class="column1">
            <span>服务时间：</span><span style="color: #666666">9:00~18:00</span>
        </div>
        </div>
    --><?php /*endif;*/?>
    <script>
        <?php
        if($type == 'ios'){
            echo 'var type = 1;';
        }else{
            echo 'var type = 2;';
        }
        ?>
        $("#online").click(function () {
            <?php if($app_version > '2.2.3'){?>
                return nativeMethod.returnNativeMethod('{"type":"12","is_help":"1"}');
            <?php }else{?>
                if(type == 1){//IOS
                    window.location.href="http://wpa.b.qq.com/cgi/wpa.php?ln=2&uin=";
                }else{
                    return nativeMethod.copyTextMethod('{"text":"'+10001+'","tip":"复制客服QQ成功!"}');
                }
            <?php }?>
        });
        $("#online1").click(function () {
                return nativeMethod.copyTextMethod('{"text":"'+10001+'","tip":"复制客服QQ成功!"}');
        });
        function callPhone(phone) {
            if(type == 1){
                window.location = "tel:" + phone;
            }else{
                window.nativeMethod.callPhoneMethod(phone);
            }
        }
    </script>
</div>
