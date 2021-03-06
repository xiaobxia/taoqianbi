<?php

use backend\components\widgets\LinkPager;
use yii\widgets\ActiveForm;
use common\models\UserLoanOrderCount;
use yii\helpers\Html;
/**
 * @var backend\components\View $this
 */
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'action' => ['core-data/daily-prohibited-items'], 'options' => ['style' => 'margin-top:5px;']]); ?>
   日期：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_start')) ? date("Y-m-d", time()-7*86400) : Yii::$app->request->get('add_start'); ?>"  name="add_start" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
    至：<input type="text" value="<?php echo empty(Yii::$app->request->get('add_end')) ? date("Y-m-d", time()) : Yii::$app->request->get('add_end'); ?>"  name="add_end" onfocus="WdatePicker({startDate:'%y-%M-%d',dateFmt:'yyyy-MM-dd',alwaysUseStartDate:true,readOnly:true})">&nbsp;
禁止项：<?php echo Html::dropDownList('type', Yii::$app->request->get('type', ''), $items); ?>&nbsp;


    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>

    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">

            <tr class="header">
                <th>日期</th>
                <th>禁止项</th>
             <th>验证次数</th>
                <th >命中次数</th>
                <th >命中率</th>
                <th >更新时间</th>


            </tr>
            <?php foreach($data as $k=> $item):?>
            <tr class="hover">
                    <td style="width: 15%" class="td25"><?php echo $item['date']?></td>
                    <td style="width: 15%" class="td25"><?php echo empty($item['name']) ? (empty($type)?"全部": $items[$type]) : $item['name']; ?></td>
                    <td style="width: 15%" class="td25"><?php echo $item['count_all']?></td>
                    <td style="width: 15%" class="td25"><?php echo $item['count_hit']?></td>
                    <td style="width: 15%" class="td25"><?php echo empty($item['count_all'])?"--":sprintf("%0.2f",$item['count_hit']/$item['count_all']*100)."%"; ?></td>
                    <td style="width: 15%" class="td25"><?php echo $item['updated_at'];?></td>
            </tr>

      <?php endforeach?>
        </table>
        <?php if (empty($data)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>