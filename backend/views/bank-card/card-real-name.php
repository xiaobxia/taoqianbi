<?php

use backend\components\widgets\LinkPager;
use common\models\UserRealnameVerify;
use yii\widgets\ActiveForm;
use common\models\User;
use yii\helpers\Html;
use common\helpers\Url;
/**
 * @var backend\components\View $this
 */
$this->shownav('user', 'menu_card_realname');
$this->showsubmenu('用户实名列表');
?>
    <script language="javascript" type="text/javascript" src="<?php echo Url::toStatic('/js/My97DatePicker/WdatePicker.js'); ?>"></script>
<?php $form = ActiveForm::begin(['id' => 'search_form','method'=>'get', 'options' => ['style' => 'margin-top:5px;']]); ?>
    用户ID：<input type="text" value="<?php echo Yii::$app->getRequest()->get('user_id', ''); ?>" name="user_id" class="txt" style="width:60px;">&nbsp;
    姓名：<input type="text" value="<?php echo Yii::$app->getRequest()->get('name', ''); ?>" name="name" class="txt" style="width:70px;">&nbsp;
    身份证号：<input type="text" value="<?php echo Yii::$app->getRequest()->get('id_card', ''); ?>" name="id_card" class="txt" style="width:120px;">&nbsp;
    <input type="submit" name="search_submit" value="过滤" class="btn">
<?php ActiveForm::end(); ?>
    <form name="listform" method="post">
        <table class="tb tb2 fixpadding">
            <tr class="header">
                <th>用户ID</th>
                <th>持卡人实名</th>
                <th>身份证号</th>
                <th>添加时间</th>
                <th>更新时间</th>
            </tr>
            <?php foreach ($real_name_info as $value): ?>
                <tr class="hover">

                    <td class="td25"><?php echo $value['user_id']; ?></td>
                    <td class="td25"><?php echo $value['realname']; ?></td>
                    <td class="td25"><?php echo $value['id_card']; ?></td>
                    <td class="td25"><?php echo date('Y-m-d H:i:s',$value['created_at']); ?></td>
                    <td class="td25"><?php echo date('Y-m-d H:i:s',$value['updated_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if (empty($real_name_info)): ?>
            <div class="no-result">暂无记录</div>
        <?php endif; ?>
    </form>
<?php echo LinkPager::widget(['pagination' => $pages]); ?>