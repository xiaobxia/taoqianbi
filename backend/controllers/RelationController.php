<?php
/**
 *
 * @author Shayne Song
 * @date 2017-02-11
 *
 */

namespace backend\controllers;

use Yii;
use common\helpers\Url;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\data\Pagination;

use common\models\risk\Relation;
use common\models\risk\UserRelationship;
use common\models\risk\RcmUser;
use yii\web\Response;

class RelationController extends BaseController{

    public $enableCsrfValidation = false;

    private $relation_names = [];
    private $weights = [];
    private $visited = [];

 	/**
     * @name 用户关系管理-关系配置/actionRelation
     */
    public function actionRelationList(){
  		$condition = self::getFilter();
    	$query = Relation::find()->where($condition)->orderBy('id desc');
    	$countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();

    	 return $this->render('relation-list', array(
            'data_list' => $data,
            'pages' => $pages,
        ));
    }


     private function getFilter(){
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
               $condition .= " AND name like '%" .$search['name']."%'";
            }
            if (isset($search['status']) && !empty(-100 != $search['status'])) {
                $condition .= " AND status = " . intval($search['status']);
            }

	        if (isset($search['add_start']) && !empty($search['add_start'])) {
	             $condition .= " AND weight >= " . intval($search['add_start']);
	        }
	        if (isset($search['add_end']) && !empty($search['add_end'])) {
	             $condition .= " AND weight <= " . intval($search['add_end']);
	        }

        }
        return $condition;
    }

    public function actionRelationAdd(){
    	$relation = new Relation();
        if ( $this->getRequest()->getIsPost()) {
        	$input = $this->request->post('Relation');
            $name = $input['name'];
            if(empty($name)){
                return $this->redirectMessage('请填写关系名称', self::MSG_ERROR);
            }
            $status = intval($input['status'],0);
            $weight = intval($input['weight'],0);
            $message = $input['message'];
            $relation->name = $name;
            $relation->status = $status;
            $relation->weight = $weight;
            $relation->message = $message;

            if ($relation->save()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('relation-list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }

        }
        return $this->render('relation-add',[
            'relation' => $relation,
        ]);
    }

    public function actionRelationEdit(){
    	$id = intval($this->request->get('id',0));;
        $relation = Relation::findOne($id);
        if ( $this->getRequest()->getIsPost()) {
        	$input = $this->request->post('Relation');
            $name = $input['name'];
            if(empty($name)){
                return $this->redirectMessage('请填写关系名称', self::MSG_ERROR);
            }
            $status = intval($input['status'],0);

            $weight = intval($input['weight'],0);
            $message = $input['message'];
            $relation->name = $name;
            $relation->status = $status;
            $relation->weight = $weight;
            $relation->message = $message;
            if ($relation->save()) {
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute('relation-list'));
            } else {
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }

        }
        return $this->render('relation-edit',[
            'relation' => $relation,
        ]);

    }

     /**
     * @name 用户关系管理-用户关系列表/actionRelation
     */
    public function actionRelationshipList(){
  		$condition = self::getResultFilter();
    	$query = UserRelationship::find()->where($condition)->orderBy('id desc');
    	$countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();

    	 return $this->render('relationship-list', array(
            'data_list' => $data,
            'pages' => $pages,
        ));
    }

        public function actionRelationshipAdd(){
    	$relation = new UserRelationship();
        if ( $this->getRequest()->getIsPost()) {
        	$input = $this->request->post('UserRelationship');
            $user_id = $input['user_id'];
            $relation_id = intval($input['relation_id'],0);
            $value = $input['value'];
       		$status = intval($input['status'],0);
       		$message = $input['message'];

            $relation->user_id = $user_id;
            $relation->relation_id = $relation_id;
            $relation->value = $value;
            $relation->status = $status;
            $relation->message = $message;

            if ($relation->save()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('relationship-list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }

        }
        return $this->render('relationship-add',[
            'relation' => $relation,
        ]);
    }

    public function actionRelationshipEdit(){
    	$id = intval($this->request->get('id',0));
        $relation = UserRelationship::findOne($id);
        if ( $this->getRequest()->getIsPost()) {
        	$input = $this->request->post('UserRelationship');
            $user_id = $input['user_id'];
            $relation_id = intval($input['relation_id'],0);
            $value = $input['value'];
       		$status = intval($input['status'],0);
       		$message = $input['message'];

            $relation->user_id = $user_id;
            $relation->relation_id = $relation_id;
            $relation->value = $value;
            $relation->status = $status;
            $relation->message = $message;

            if ($relation->save()) {
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute('relationship-list'));
            } else {
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }

        }
        return $this->render('relationship-edit',[
            'relation' => $relation,
        ]);
    }

    private function getResultFilter(){
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['id']) && !empty($search['id'])) {
                $condition .= " AND id = " . intval($search['id']);
            }
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['relation_id']) && !empty($search['relation_id'])) {
                $condition .= " AND relation_id = " . intval($search['relation_id']);
            }
            if (isset($search['value1']) && !empty($search['value1'])) {
               $condition .= " AND value like '%" .$search['value1']."%'";
            }
            if (isset($search['status']) && !empty(-100 != $search['status'])) {
                $condition .= " AND status = " . intval($search['status']);
            }

	        return $condition;
    	}
	}



    public function actionGetNamesAndWeights(){
        $list = Relation::find()->all();
        foreach ($list as $v) {
            $this->relation_names[$v['id']] = $v['name'];
            $this->weights[$v['id']] = $v['weight'];
        }
        return $this->weights;
    }

    public function actionGetRelation($rcm_user_id, $weight = 50){
        $this->actionGetNamesAndWeights();
        $this->visited = [];
        $result = $this->actionRecur($rcm_user_id, $weight);
        return json_encode($result);
    }


    public function actionRecur($rcm_user_id, $weight){
        $name = "";
        $id_number = "";
        $phone = "";
        $basic_info = RcmUser::find()->where(['id' => $rcm_user_id])->one();
        if(!empty($basic_info)){
            $name = $basic_info->name;
            $id_number = $basic_info->id_number;
            $phone = $basic_info->phone;
        }

        $info = [];
        $friend = [];
        if (!in_array($rcm_user_id, $this->visited) && $weight >= 0) {
            $this->visited[] = $rcm_user_id;
            $relation = UserRelationship::find()->select(['relation_id', 'value'])->where(['user_id' => $rcm_user_id, 'status' => UserRelationship::VALIDATE_TURE])->asArray()->all();
            $friend = [];
            foreach ($relation as $k => $v) {
                $info[$k]['relation_name'] = $this->relation_names[$v['relation_id']];
                $info[$k]['value'] = $v['value'];
                $list = UserRelationship::find()->select(['user_id', 'relation_id', 'value'])->where(['value' => $v['value'], 'status' => UserRelationship::VALIDATE_TURE])->andWhere(['not in', 'user_id', $this->visited])->asArray()->all();
                foreach ($list as $val) {
                    if ($weight - $this->weights[$v['relation_id']] - $this->weights[$val['relation_id']] >= 0) {
                        $friend[] = $this->actionRecur($val['user_id'], $weight - $this->weights[$v['relation_id']] - $this->weights[$val['relation_id']]);
                    }
                }
            }
        }
        return [
            'user_id' => (string)$rcm_user_id,
            'name' => $name,
            'id_number' => $id_number,
            'phone' => $phone,
            'info' => $info,
            'friend' => $friend,
        ];

    }

    public function actionCheck(){
        $params = Yii::$app->request->get();

        $user_id = isset($params['user_id'])?($params['user_id']):"";
        $weight = isset($params['weight'])?($params['weight']):"";
        if(empty($weight)){
            $data = $this->actionGetRelation($user_id);
        }else{
            $data = $this->actionGetRelation($user_id, $weight);
        }
        return $this->render('graph',[
            'data' => $data,
        ]);
    }


    public function actionCheckRelationNetwork(){
        return $this->render('relation-network');
    }


    public function actionTest(){
        try{
            $user_id = 896688;
            $weight = 100000;
            $data = $this->actionGetRelation($user_id, $weight);
            echo '<pre>';
            var_dump(json_decode($data, true));
            echo "<br />";


            // $c[] = "110";
            // $c[] = "213";
            // $list1 = UserRelationship::find()->where(['!=', 'user_id', $c])->asArray()->all();
            // echo "<pre>";
            // var_dump($list1);
            // echo "<br />";
            // $list = UserRelationship::find()->Where(['not in', 'user_id', $c])->asArray()->all();
            // echo "<pre>";
            // var_dump($list);
            // return "finished";


            // $this->actionGetWeights();
            // $this->visited[] = 110;
            // $this->visited[] = 101;
            // $relation = UserRelationship::find()->where(['user_id' => 896688, 'status' => UserRelationship::VALIDATE_TURE])->andWhere(['!=', 'user_id', $this->visited])->asArray()->all();
            // var_dump($relation);
        }catch(\Exception $e){
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
        }
    }
}