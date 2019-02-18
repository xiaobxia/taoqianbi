<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/7/29
 * Time: 15:45
 */
use backend\components\widgets\ActiveForm;
use common\helpers\StringHelper;
use common\models\LoanHfdOrder;
use common\models\LoanPersonHfdOperate;
use common\models\LoanHfdRiskPerson;
use common\models\LoanHfdRiskHouse;
use common\models\LoanHfdRiskLastTime;
use common\helpers\Url;
use yii\helpers\Html;

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.js'); ?>"></script>
<style>
    .td24{
        width: 6%;
        font-weight: bold;
    }
    .td25{
        width: 7%;
        font-weight: bold;
    }
    .td28{
        width: 10%;
        font-weight: bold;
    }
</style>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">风控管理的详情页</th></tr>
    <?php if(!empty($risk_person)) :?>
    <tr>
        <td >
            <table class="tb tb2 fixpadding">
                <tr><th class="partition" colspan="15">借款人信息</th></tr>
                <tr>
                    <td class="td24">姓名：</td>
                    <td  width="200"><?php echo $risk_person['loan_name'];?></td>
                    <td class="td24">身份证号：</td>
                    <td width="290"><?php echo $risk_person['loan_id_number'];?></td>
                    <td class="td24">户籍类型：</td>
                    <td width="340"><?php echo empty($risk_person['loan_registry_type']) ? "--" : LoanHfdRiskPerson::$household[$risk_person['loan_registry_type']];?></td>
                    <td class="td24">学历：</td>
                    <td><?php echo empty($risk_person['loan_education']) ? "--" : LoanHfdRiskPerson::$education[$risk_person['loan_education']];?></td>
                </tr>
                <tr>
                   <th  class="td28">借款人历史最高逾期期数：</th>
                    <td><?php echo $risk_person['loan_now_overdue_past_max_times']."期";?></td>
                    <th  class="td28">借款人当前逾期期数：</th>
                    <td><?php echo $risk_person['loan_now_overdue_times']."期";?></td>
                    <th  class="td28">借款人当前逾期金额：</th>
                    <td><?php echo sprintf("%.2f",$risk_person['loan_now_overdue_money'] / 100)."元";?></td>
                </tr>
                <tr>
                    <td class="td24">婚姻状况：</td>
                    <td><?php echo empty($risk_person['loan_marital_status']) ? "--" : LoanHfdRiskPerson::$marital_status[$risk_person['loan_marital_status']];?></td>
                   <?php if(($risk_person['loan_marital_status']) == LoanHfdRiskPerson::MARRIAGE_UNMARRIED): ?>
                    <?php else : ?>
                    <th  class="td28" >配偶当前逾期期数：</th>
                    <td><?php echo $risk_person['spouse_now_overdue_times']."期";?></td>
                    <th  class="td28" >配偶当前逾期金额：</th>
                    <td><?php echo sprintf("%.2f",$risk_person['spouse_now_overdue_money'] / 100)."元";?></td>
                </tr>
                <tr>
                    <th  class="td28">配偶历史最高逾期期数：</th>
                    <td><?php echo $risk_person['spouse_now_overdue_past_max_times']."期";?></td>
                    <td class="td24">配偶户籍类型：</td>
                    <td width="200"><?php echo empty($risk_person['spouse_registry_type']) ? "--" : LoanHfdRiskPerson::$household[$risk_person['spouse_registry_type']];?></td>
                    <td class="td24">配偶学历：</td>
                    <td><?php echo empty($risk_person['spouse_education']) ? "--" : LoanHfdRiskPerson::$education[$risk_person['spouse_education']];?></td>
                </tr>
                <tr>
                    <?php endif; ?>
                    <th  class="td25">家庭资产负债率：</th>
                    <td><?php echo sprintf("%.2f",$risk_person['family_assets_liabilities'] / 100)."%";?></td>
                    <th  class="td25" >法院被执行记录：</th>
                    <td><?php echo empty($risk_person['court_executed']) ? "--" : LoanHfdRiskPerson::$record[$risk_person['court_executed']];?></td>
                    <th  class="td25">工作类型：</th>
                    <td><?php echo empty($risk_person['work_type']) ? "--" : LoanHfdRiskPerson::$job[$risk_person['work_type']];?></td>
                </tr>
                <?php if(($risk_person['work_type']) == LoanHfdRiskPerson::JOB_OFFICE_WORKER): ?>
                <tr>
                    <th  class="td25">单位性质：</th>
                    <td><?php echo empty($risk_person['unit_property']) ? "--" : LoanHfdRiskPerson::$company[$risk_person['unit_property']];?></td>
                    <th  class="td25">单位职位：</th>
                    <td><?php echo empty($risk_person['unit_position']) ? "--" : LoanHfdRiskPerson::$company_position[$risk_person['unit_position']];?></td>
                    <th   class="td25">单位月收入：</th>
                    <td><?php echo sprintf("%.2f",$risk_person['unit_position_moth_income'] / 100)."元";?></td>
                    <th  class="td25">工作年限：</th>
                    <td><?php echo $risk_person['unit_work_life']."年";?></td>
                </tr>
                <?php else :  ?>
                <tr>
                    <th   class="td25">生意类型：</th>
                    <td><?php echo empty($risk_person['business_type']) ? "--" : LoanHfdRiskPerson::$business_type[$risk_person['business_type']];?></td>
                    <th  class="td25">经营年限：</th>
                    <td><?php echo $risk_person['business_operating_life']."年";?></td>
                    <th   class="td25">月均流水：</th>
                    <td><?php echo sprintf("%.2f",$risk_person['business_moth_mean_flow'] / 100)."元";?></td>
                    <th  class="td25">租赁合同期限：</th>
                    <td><?php echo $risk_person['business_lease_contract_period']."年";?></td>
                </tr>
                <tr>
                    <th   class="td25">月租金：</th>
                    <td><?php echo sprintf("%.2f",$risk_person['business_lease_contract_month_rent'] / 100)."元";?></td>
                    <th  class="td25">员工人数：</th>
                    <td><?php echo $risk_person['business_employees_mum'];?></td>
                </tr>
                <?php endif; ?>
            </table>

        </td>
    </tr>
    <?php endif; ?>
    <?php if(!empty($risk_house)): ?>
    <tr>
        <td>
            <table class="tb tb2 fixpadding">

                <?php foreach($risk_house as $key => $value) : ?>
                <tr><th class="partition" colspan="15">
                        <?php if($value['house_attribute'] == LoanHfdRiskHouse::HOUSE_SPARE) {
                            echo "第".$key."套备用房";
                        }else{
                            echo "抵押房";
                        }?>的房产信息</th>
                </tr>
                <tr>
                    <th   class="td25">订单号：</th>
                    <td width="200"><?php echo $value['order_id'];?></td>
                    <th   class="td25">房产证号：</th>
                    <td width="300"><?php echo $value['house_id'];?></td>
                    <th  class="td25">房子属性：</th>
                    <td><?php echo empty($value['house_attribute']) ? "--" : LoanHfdRiskHouse::$house_attribute[$value['house_attribute']];?></td>
                    <th   class="td25">抵押状态：</th>
                    <td><?php echo empty($value['mortgage_status']) ? "--" : LoanHfdRiskHouse::$mortgage_status[$value['mortgage_status']];?></td>
                </tr>
                <tr>
                    <th  class="td25">产权性质：</th>
                    <td><?php echo empty($value['property_right']) ? "--" : LoanHfdRiskHouse::$property_right[$value['property_right']];?></td>
                    <th   class="td25">房产位置：</th>
                    <td><?php echo empty($value['location']) ? "--" : LoanHfdRiskHouse::$location[$value['location']];?></td>
                    <td class="td25">户口人数:</td>
                    <td><?php echo $value['registered_residence_num'];?></td>
                    <td class="td24">房龄:</td>
                    <td><?php echo date("Y",$value['house_age'])."年";?></td>
                </tr>
                <tr>
                    <td class="td25">房产单价:</td>
                    <td><?php echo sprintf("%.2f",$value['house_unit_price'] / 1000000)."万元";?></td>
                    <td class="td25">房子面积:</td>
                    <td><?php echo sprintf("%.2f",$value['house_area'] / 100)."平方米";?></td>
                    <th  class="td25">房子总价：</th>
                    <td><?php echo sprintf("%.2f",$value['house_total_price'] / 1000000)."万元";?></td>
                    <th  class="td25">房子楼层：</th>
                    <td><?php echo empty($value['house_floor']) ? "--" : LoanHfdRiskHouse::$house_floor[$value['house_floor']];?></td>
                </tr>
                <tr>
                    <th class="td25">户型：</th>
                    <td><?php echo empty($value['house_layout']) ? "--" : LoanHfdRiskHouse::$house_layout[$value['house_layout']];?></td>
                    <th   class="td25">装修类型：</th>
                    <td><?php echo empty($value['house_decoration']) ? "--" : LoanHfdRiskHouse::$house_renovation[$value['house_decoration']];?></td>
                    <th class="td25">交通状况：</th>
                    <td><?php echo empty($value['house_traffic']) ? "--" : LoanHfdRiskHouse::$house_traffic[$value['house_traffic']];?></td>
                    <th class="td25">房子使用情况：</th>
                    <td><?php echo empty($value['house_use_type']) ? "--" : LoanHfdRiskHouse::$house_use_type[$value['house_use_type']];?></td>
                </tr>
                <tr>
                    <th class="td25">学区房类型：</th>
                    <td><?php echo empty($value['school_house']) ? "--" : LoanHfdRiskHouse::$school_house[$value['school_house']];?></td>
                    <th class="td25">社区规模及商业配套：</th>
                    <td><?php echo empty($value['community_type']) ? "--" : LoanHfdRiskHouse::$community_type[$value['community_type']];?></td>
                    <th class="td25">银行抵押额度：</th>
                    <td><?php echo sprintf("%.2f",$value['bank_mortgage_amount'] / 1000000)."万元";?></td>
                    <th  class="td25">民间抵押额度：</th>
                    <td><?php echo sprintf("%.2f",$value['local_mortgage_amount'] / 1000000)."万元";?></td>
                </tr>
                <?php endforeach; ?>

            </table>
        </td>
    </tr>
    <?php endif; ?>
</table>
