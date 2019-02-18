<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:47
 */
use common\helpers\Url;

$this->shownav('business', 'menu_business_list');
$this->showsubmenu('项目发布');
?>

    <!--借款项目列表-->
<?php echo $this->render('_list', [
    'loan_project_list' => $loan_project_list,
    'pages' => $pages,
    'action' => 'active'

]); ?>
