<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 16:06
 */
use backend\components\widgets\ActiveForm;
use common\models\LoanPerson;
use common\helpers\Url;
?>
<?php echo $this->render('_loan-person-form', ['loan_person' => $loan_person,'data' => $data,'company' => $company]); ?>
<script>
   $('#uid').parent().css({display : "none"});
   $('#open_id').parent().css({display : "none"});
</script>