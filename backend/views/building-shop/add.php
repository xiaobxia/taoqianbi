<?php

use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
$this->shownav('asset', 'menu_shop_add');
$this->showsubmenu('商户添加');
?>

<?php echo $this->render('_form', [
    'model' => $model,
    'provinces' => $provinces,
    'cities' => $cities,
    'areas' => $areas,
    'loans' => $loans,
]); ?>