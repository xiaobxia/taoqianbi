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
use common\models\HfdHouse;
use common\helpers\Url;
use yii\helpers\Html;

?>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/jquery.js'); ?>"></script>
<style>
    .td24{
        width: 5%;
        font-weight: bold;
    }
    .td25{
        width: 7%;
        font-weight: bold;
    }
</style>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">运营管理的详情页</th></tr>
    <tr>
        <td >
            <table class="tb tb2 fixpadding">
                <tr><th class="partition" colspan="15">借款人信息</th></tr>
                <tr>
                    <td class="td24">用户ID：</td>
                    <td  width="200"><?php echo $loan_person['id'];?></td>
                    <td class="td24">姓名：</td>
                    <td><?php echo $loan_person['name'];?></td>
                    <td class="td24">身份证号：</td>
                    <td><?php echo $loan_person['id_number'];?></td>
                    <td class="td24">联系方式：</td>
                    <td><?php echo $loan_person['phone'];?></td>
                </tr>
                <tr>
                    <td class="td24">年龄：</td>
                    <td class="mark"><?php
                        $birthday = $loan_person['birthday'];
                        if(empty($birthday)){
                            echo "--";
                        }else{
                            $birthday = date('Y-m-d',$birthday);
                            $age = date('Y', time()) - date('Y', strtotime($birthday)) - 1;
                            if (date('m', time()) == date('m', strtotime($birthday))){

                                if (date('d', time()) > date('d', strtotime($birthday))){
                                    $age++;
                                }
                            }elseif (date('m', time()) > date('m', strtotime($birthday))){
                                $age++;
                            }
                            echo $age;
                        }
                        ?></td>
                    <?php if(!empty($borrower_person)) :?>
                    <td class="td24">户籍：</td>
                    <td><?php echo $borrower_person['registry_province'].$borrower_person['registry_city'].$borrower_person['registry_area'];?></td>
                    <td class="td24">婚姻状况：</td>
                    <td><?php echo empty($borrower_person['marital_status']) ? "--" : LoanPersonHfdOperate::$marital_status[$borrower_person['marital_status']];?></td>
                    <td class="td24">联系地址：</td>
                    <td><?php echo $borrower_person['contact_province'].$borrower_person['contact_city'].$borrower_person['contact_area'].$borrower_person['contact_address'];?></td>
                    <?php endif; ?>
                </tr>
            </table>
        </td>
    </tr>

    <?php if(!empty($hfd_house)): ?>
    <tr>
        <td >
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
                    <td><?php echo date("Y",$item['house_year'])."年";?></td>
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
</table>