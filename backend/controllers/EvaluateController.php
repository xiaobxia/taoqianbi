<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/8/11
 * Time: 14:06
 */
namespace backend\controllers;
use common\models\HfdOrder;
use common\models\LoanPerson;
use common\models\LoanProject;
use common\models\Shop;
use Yii;
use yii\data\Pagination;
use common\helpers\Url;

class EvaluateController extends BaseController{

    public function getLoanProjectFilter(){

        $where = " 1=1 ";

        return $where;
    }

    //评估列表
    public function actionList(){

        $condition = $this->getLoanProjectFilter();
        $query = HfdOrder::find()->from(HfdOrder::tableName().' as a ')->leftJoin(LoanPerson::tableName().' as b ','a.user_id = b.id')->where($condition)->select('a.*,b.name,b.id_number,b.phone')->orderBy(['a.id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        $projects = LoanProject::find()->where(['status'=>[LoanProject::STATUS_ACTIVE,LoanProject::STATUS_ADMIN],'type'=>LoanProject::TYPE_HOUSE_MORTGAGE])->all();
        $pros = [];
        foreach ($projects as $pro) {
            $pros[$pro['id']] = $pro['loan_project_name'];
        }
        $project_ids = array_keys($pros);
        $_shop = Shop::find()->where(['status' => Shop::SHOP_ACTIVE,'loan_project_id'=>$project_ids])->andwhere(" shop_code !='' ")->select(['shop_name','shop_code'])->orderBy([
            'id' => SORT_DESC,
        ])->all();
        $shop_data = [];
        foreach($_shop as $item){
            $shop_data[$item->shop_code] = $item->shop_name;
        }

        return $this->render('list', array(
            'data' => $data,
            'pages' => $pages,
            'shop_data'=>$shop_data,
        ));

    }

}