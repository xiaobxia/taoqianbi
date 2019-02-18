<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 11:55
 */

namespace backend\controllers;

use common\models\UserVerification;
use Yii;
use common\helpers\StringHelper;
use common\models\LoanPerson;
use common\models\LoanProtocol;
use common\models\User;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use common\models\LoanRecord;
use common\models\LoanProject;

/**
 * Class LoanController     借款管理控制器
 * @package backend\controllers
 * @author hezhuangzhuang@kdqugou.com
 */
class UserController extends  BaseController
{



    /**
     * 借款人列表
     * @return string
     */
    public function actionLoanPersonList(){
        $condition = "1 = 1 ";//.LoanPerson::PERSON_STATUS_NOPASS;
        if($this->request->get('search_submit')) {        //过滤
            $search = $this->request->get();
            if(!empty($search['id'])) {
                $condition .= " AND id = ".$search['id'];
            }
            if(!empty($search['source_id'])) {
                $condition .= " AND source_id = ".$search['source_id'];
            }
            if(!empty($search['type'])) {
                $condition .= " AND type = ".$search['type'];
            }
            if(!empty($search['name'])) {
                $condition .= " AND name = "."'".$search['name']."'";
            }
            if(!empty($search['phone'])) {
                $condition .= " AND phone = ".$search['phone'];
            }
            if(!empty($search['id_number'])) {
                $condition .= " AND id_number = ".$search['id_number'];
            }
            if(!empty($search['property'])) {
                $condition .= " AND property = ".$search['property'];
            }
            if(!empty($search['begintime'])) {
                $condition .= " AND created_at >= ".strtotime($search['begintime']);
            }
            if(!empty($search['endtime'])) {
                $condition .= " AND created_at < ".strtotime($search['endtime']);
            }
        }
        $loan_person = LoanPerson::find()->where($condition." order by id desc");
        $countQuery = clone $loan_person;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $loan_person = $loan_person->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('loan-person-list', array(
            'loan_person' => $loan_person,
            'pages' => $pages,
        ));
    }
    /**
     * 添加借款人
     * @return string
     */
    public function actionLoanPersonAdd(){
        $loan_person = new LoanPerson();
        if($loan_person->load($this->request->post()) && $loan_person->validate()){
            $type = Yii::$app->request->post("LoanPerson")['type'];
            $loan_person->uid = 0;
            $loan_person->birthday = strtotime($loan_person->birthday);
            $loan_person->status=LoanPerson::PERSON_STATUS_PASS;
            try {
                if ($loan_person->validate() && $loan_person->save()) {
                    return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('loan/loan-person-list'));
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }
        return $this->render('loan-person-add', [
                'loan_person' => $loan_person,
            ]
        );
    }
    /**
     * 查看借款人
     * @return string
     */
    public function actionLoanPersonView($id)
    {
        $loan_person = LoanPerson::find()->where(['id' => intval($id)])->with('creditJxl')->with('creditZmop')->one();

        if (!isset($loan_person) && empty($loan_person)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        $type = Yii::$app->request->get('type');
        $tittle_arr = '';
        if($type == 1) {
            $tittle_arr = LoanPerson::$company;
        } else {
            $tittle_arr = LoanPerson::$person;
        }
        //查看认证表
        $user_verification = UserVerification::findOne(['user_id'=>$id]);
        if(false == $user_verification){
            $user_verification = new UserVerification();
        }
        return $this->render('loan-person-view', [
            'loan_person' => $loan_person,
            'tittle' => $tittle_arr,
            'user_verification'=>$user_verification,
        ]);
    }

    /**
     * 编辑借款人
     * @return string
     */
    public function actionLoanPersonEdit($id){
        $loan_person = LoanPerson::find()->where(['id' => intval($id)])->one();
        $loan_person->birthday = empty($loan_person->birthday) ? "" :date('Y-m-d', $loan_person->birthday);
        if (!isset($loan_person) && empty($loan_person)) {
            throw new NotFoundHttpException('The requested loan person does not exist.');
        }
        if ($this->getRequest()->getIsPost()) {
            $loan_person->load($this->request->post());
            $loan_person->birthday = strtotime($loan_person->birthday);
            if ($loan_person->validate() && $loan_person->save()) {
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute(['user/loan-person-list']));
            } else {
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }
        }
        return $this->render('loan-person-edit',[
            'loan_person' => $loan_person,
        ]);
    }

    /**
     * 删除借款人
     * @return string
     */
    public function actionLoanPersonDel($id)
    {
        $loan_person = LoanPerson::find()->where(['id' => intval($id)])->one();
        if (!$loan_person->delete(false)) {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }
        return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('loan/loan-person-list'));
    }

    /**
     * 查看借款人图片
     * @return string
     * @throws \yii\base\Exception
     */
    public function actionLoanPersonPic(){
        $id = Yii::$app->request->get("id");
        $loan_person = LoanPerson::findOne($id);
        if(empty($loan_person)){
            throw new Exception("不存在该记录");
        }
        $attachment = $loan_person->attachment;
        if(empty($attachment)){
            throw new Exception("该记录不存在附件！");
        }
        $attachment_pic_arr = explode(",", $attachment);
        return $this->render('loan-pic-view',[
            'attachment_pic_arr' => $attachment_pic_arr,
        ]);
    }

    /**
     * 补全借款人信息
     * @return array
     */
    public function actionFillLoanerDetail()
    {
        $phone = Yii::$app->request->get('phone');
        $model = User::find()->where(['username' => $phone])->asArray()->one();
        if (!$model) {
            $data['code'] = -1;
            $data['msg'] = "该借款人不存在";
            return json_encode($data);
        }
        $data = [
            'code' => 0,
            'id' => $model['id'],
            'realname' => $model['realname'],
            'id_card' => $model['id_card'],
            'birthday' => $model['birthday'],
            'sex' => $model['sex'],
        ];
        return json_encode($data);
    }
}