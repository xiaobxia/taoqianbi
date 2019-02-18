<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/8/8
 * Time: 16:58
 */
use backend\components\widgets\ActiveForm;
use common\helpers\StringHelper;
use common\models\HfdOrder;
use common\models\HfdHouse;
use common\models\HfdNotarization;
use common\models\LoanHfdOrder;
use common\helpers\Url;
use yii\helpers\Html;

?>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.js'); ?>"></script>
<script type="text/javascript" src="<?php echo Url::toStatic('/jquery-photo-gallery/jquery.photo.gallery.js'); ?>"></script>
<style>
    .td25 {
        font-weight: bold;
        width: 6%;
    }
</style>
<table class="tb tb2 fixpadding">
<?php if(!empty($hfd_house)): ?>
    <tr>
        <td>
            <table class="tb tb2 fixpadding">
                <?php foreach($hfd_house as $key => $item) :?>
                    <tr><th class="partition" colspan="15">
                            <?php if($item['house_attribute'] == HfdHouse::HOUSE_SPARE) {
                                echo "第".$key."套备用房";
                            }else{
                                echo "抵押房";
                            }?>的房产信息</th></tr>
                    <tr>
                        <th   class="td25">房产证号：</th>
                        <td><?php echo $item['house_id'];?></td>
                        <th   class="td25">订单号：</th>
                        <td><?php echo $item['order_id'];?></td>
                        <th  class="td25">房子属性：</th>
                        <td><?php echo empty($item['house_attribute']) ? "--" : HfdHouse::$house_attribute[$item['house_attribute']];?></td>
                        <th  class="td25">小区地址：</th>
                        <td><?php echo $item['estate_province'].$item['estate_city'].$item['estate_area'].$item['estate_address'];?></td>
                    </tr>
                    <tr>
                        <th  class="td25">房产类型：</th>
                        <td><?php echo empty($item['house_type']) ? "--" : HfdHouse::$house_type[$item['house_type']];?></td>
                        <th  class="td25" >房产面积：</th>
                        <td><?php echo ($item['house_area'] / 100)."平方米";?></td>
                        <th  class="td25" >房型：</th>
                        <td><?php echo $item['house_layout'];?></td>
                        <th  class="td25">房子装修：</th>
                        <td><?php echo empty($item['house_renovation']) ? "--" : HfdHouse::$house_renovation[$item['house_renovation']];?></td>
                    </tr>
                    <tr>
                        <th  class="td25">是否被租赁：</th>
                        <td><?php if($item['house_lease'] == 1){echo "是";}else{ echo "否";}?></td>
                        <th  class="td25" >房龄：</th>
                        <td><?php echo date("Y-m-d",$item['house_year']);?></td>
                        <th  class="td25">权利人：</th>
                        <td><?php echo $item['obligee'];?></td>
                        <th  class="td25">双方关系：</th>
                        <td><?php echo empty($item['relation']) ? "--" : HfdHouse::$relation[$item['relation']];?></td>
                    </tr>
                    <tr>
                        <th  class="td25">是否有小孩：</th>
                        <td><?php if($item['elderly_and_child'] == 1){echo "有";}else{ echo "没有";}?></td>
                        <th   class="td25">房产证上人数：</th>
                        <td><?php echo $item['registered_residence_num']."个";?></td>
                        <th  class="td25">产权来源：</th>
                        <td><?php echo empty($item['property_right_source']) ? "--" : HfdHouse::$property_type[$item['property_right_source']];?></td>
                        <th   class="td25">银行贷款：</th>
                        <td><?php echo sprintf("%.2f",$item['bank_loans'] / 1000000)."万元";?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
<?php endif; ?>
<?php echo $this->render('/public/review_info', [
    'review_log' => $review_log,
]); ?>

<tr>
    <td>
        <table class="tb tb2 fixpadding">
            <tr>
                <th colspan="15" class="partition">房产材料</th>
            </tr>
            <tr>
                <td class="td24">渠道来源:</td>
                <td><?php echo $shop['shop_name']; ?></td>
                <td class="td24">业务员:</td>
                <td><?php echo $ywy_person['name']; ?></td>
                <td class="td24">业务员手机:</td>
                <td><?php echo $ywy_person['phone']; ?></td>
            </tr>
            <tr>
                <td class="td24">实际公证时间:</td>
                <td><?php echo empty($hfd_notarization['notarization_true_time'])?"--:--":date('Y-m-d H:i:s',$hfd_notarization['notarization_true_time']); ?></td>
                <th class="td24">公证材料：</th>
                <td class="gallerys">
                    <?php foreach($picture as $list): ?>
                        <img class="gallery-pic" height="100" src="<?php echo $list;?>"/>
                    <?php endforeach;?>
                </td>
                <th class="td24">房产证材料：</th>
                <td class="gallerys">
                    <?php foreach($house_picture as $value): ?>
                        <img class="gallery-pic" height="100" src="<?php echo $value;?>"/>
                    <?php endforeach;?>
                </td>
            </tr>
            <tr>
                <td class="td24">提单授信额度:</td>
                <td><?php echo sprintf("%0.2f",$loan_hfd_order['loan_money']/1000000).'万元'; ?></td>
                <td class="td24">风控授信额度:</td>
                <td><?php echo sprintf("%0.2f",$loan_hfd_order['true_loan_money']/1000000).'万元'; ?></td>
                <td class="td24">风控授信利率:</td>
                <td><?php echo $loan_hfd_order['true_loan_apr'].'%'; ?></td>
            </tr>
        </table>
    </td>
</tr>

<tr>
    <td>
        <table class="tb tb2 fixpadding">
            <tr>
                <th colspan="15" class="partition">借款信息</th>
            </tr>
            <tr>
                <td class="td24">用户ID:</td>
                <td><?php echo $loan_person['id']; ?></td>
                <td class="td24">手机:</td>
                <td><?php echo $loan_person['phone']; ?></td>
                <td class="td24">姓名:</td>
                <td><?php echo $loan_person['name']; ?></td>
                <td class="td24">身份证:</td>
                <td><?php echo $loan_person['id_number']; ?></td>
            </tr>
            <tr>
                <td class="td24">业务订单ID:</td>
                <td><?php echo $loan_hfd_order['order_id']; ?></td>
                <td class="td24">借款金额:</td>
                <td><?php echo sprintf("%0.2f",$loan_hfd_order['true_loan_money']/1000000).'万元'; ?></td>
                <td class="td24">借款利率:</td>
                <td><?php echo $loan_hfd_order['true_loan_apr']."%"; ?></td>
                <td class="td24">借款期限:</td>
                <td><?php echo $loan_hfd_order['loan_peroid']."个月"; ?></td>
            </tr>
            <tr>
                <td class="td24">服务费:</td>
                <td><?php echo sprintf("%0.2f",$loan_hfd_order['fee_amount']/100).'元'; ?></td>
                <td class="td24">加急费:</td>
                <td><?php echo sprintf("%0.2f",$loan_hfd_order['urgent_amount']/100).'元'; ?></td>
                <td class="td24">保证金:</td>
                <td><?php echo sprintf("%0.2f",$loan_hfd_order['deposit']/100).'元'; ?></td>
            </tr>
            <tr>
                <th colspan="15" class="partition">打款信息</th>
            </tr>
            <tr>
                <td class="td24">业务订单ID:</td>
                <td><?php echo $hfd_financial_record['order_id']; ?></td>
                <td class="td24">收款人姓名:</td>
                <td><?php echo $hfd_financial_record['payee_name']; ?></td>
                <td class="td24">打款金额:</td>
                <td style="color: red;font-weight: bold"><?php echo sprintf("%0.2f",$hfd_financial_record['money']/1000000).'万元'; ?></td>
                <td class="td24">期望财务打款时间:</td>
                <td style="color: red;font-weight: bold"><?php echo date("Y-m-d",$hfd_financial_record['plan_pay_money_time']); ?></td>
            </tr>
            <tr>
                <td class="td24">收款人银行:</td>
                <td><?php echo $hfd_financial_record['payee_card_name']; ?></td>
                <td class="td24">收款人银行支行:</td>
                <td><?php echo $hfd_financial_record['payee_card_name_branch']; ?></td>
                <td class="td24">收款人银行卡号:</td>
                <td><?php echo $hfd_financial_record['payee_card_num']; ?></td>
                <td class="td24">备注:</td>
                <td><?php echo $hfd_financial_record['remark']; ?></td>
            </tr>
            <?php $find = HfdNotarization::find()->select(['pawner_id','capital_id'])->where(['order_id'=>Yii::$app->request->get("order_id")])->asArray()->one(); ?>
            <tr>
                <td class="td24">资方:</td>
                <td><?php echo \backend\models\Pawner::getnamebyid($find['pawner_id']);?></td>
                <td class="td24">抵押人:</td>
                <td><?php echo \backend\models\Capital::getnamebyid($find['capital_id']);?></td>
            </tr>
            <?php if(isset($is_child)&&!empty($is_child)): ?>
            <tr>
                <td class="td24">打款类型:</td>
                <td style="color: red;font-weight: bold"><?php echo $is_child; ?></td>
                <td class="td24">放款条件:</td>
                <td style="color: red;font-weight: bold"><?php echo empty($loan_hfd_order['loan_condition']) ? "-- " : LoanHfdOrder::$loan[$loan_hfd_order['loan_condition']]; ?></td>
            </tr>
            <?php endif; ?>
            <?php if(!empty($hfd_financial_record['true_money'])): ?>
            <tr>
                <td class="td24">实际打款金额:</td>
                <td><?php echo sprintf("%0.2f",$hfd_financial_record['true_money']/1000000).'万元'; ?></td>
                <td class="td24">实际打款时间:</td>
                <td><?php echo $hfd_financial_record['true_pay_money_time']; ?></td>
            </tr>
            <?php endif; ?>
            <?php if(!empty($pay_picture)) :?>
            <tr>
                <td class="td24">房产证材料：</td>
                <td class="gallerys">
                    <?php foreach($pay_picture as $list1): ?>
                        <img class="gallery-pic" height="100" src="<?php echo $list1;?>"/>
                    <?php endforeach;?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </td>
</tr>
</table>
<script>
    $('img').click(function(){
        $.openPhotoGallery(this);
    });
    $(window).resize(function(){
        $('#J_pg').height($(window).height());
        $('#J_pg').width($(window).width());
    });
</script>