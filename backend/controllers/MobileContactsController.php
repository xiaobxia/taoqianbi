<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/6/11
 * Time: 11:59
 */
namespace backend\controllers;

use common\models\LoanPerson;
use common\models\UserMobileContacts;
use common\models\UserPhoneMessage;
use common\models\PushSms;
use common\models\message\MessageCollectLog;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\redis\ActiveQuery;
use common\models\mongo\statistics\UserMobileContactsMongo;
use common\models\mongo\statistics\UserPhoneMessageMongo;
use yii\web\UploadedFile;

class MobileContactsController extends  BaseController{

    protected function getFilter() {
        $condition = [];
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if(isset($search['user_id'])&&!empty($search['user_id'])){
                $condition[] = ['=','user_id',$search['user_id']] ;
                //$condition .= " AND user_id = " . intval($search['user_id']);
            }

            if(isset($search['mobile'])&&!empty($search['mobile'])){
                $condition[] = ['like','mobile',$search['mobile']] ;
//                $condition .= " AND mobile like '%" . $search['mobile']."%'";
            }

            if(isset($search['name'])&&!empty($search['name'])){
                $condition[] = ['like','name',$search['name']] ;
//                $condition .= " AND name like '%" . $search['name']."%'";
            }


        }

        return $condition;

    }


    /**
     * @return string
     * @name 用户管理-用户管理-用户通讯录/actionMobileContactsList
     */
    public function  actionMobileContactsList(){
        $conditions = $this->getFilter();
        if( !empty($conditions) && empty($this->request->get('user_id'))){
            return $this->redirectMessage('用户ID为必填选项', self::MSG_ERROR);
        }
        $query = UserMobileContactsMongo::find();
        foreach ($conditions as $condition){
            $query = $query->andWhere($condition);
        }
        $query = $query->orderBy([
            '_id' => SORT_DESC,
        ]);
        $countQuery = clone $query;
        $count = $countQuery->count();

//        if($count<=0){
//            $count = $countQuery->count('*', Yii::$app->get('mongodb_rule'));
//            if($count<=0){
//                $query = UserMobileContacts::find();
//                foreach ($conditions as $condition){
//                    $query = $query->andWhere($condition);
//                }
//                $query = $query->orderBy([
//                        'id' => SORT_DESC,
//                ]);
//                $count = $query->count();
//                $pages = new Pagination(['totalCount' => $count]);
//                $pages->pageSize = 15;
//                $loan_mobile_contacts_list = $query->offset($pages->offset)->limit($pages->limit)->all();
//            }else{
//                $pages = new Pagination(['totalCount' => $count]);
//                $pages->pageSize = 15;
//                $loan_mobile_contacts_list = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('mongodb_rule'));
//            }
//        }else{
            $pages = new Pagination(['totalCount' => $count]);
            $pages->pageSize = 15;
            $loan_mobile_contacts_list = $query->offset($pages->offset)->limit($pages->limit)->all();
//        }

        return $this->render('mobile-contacts-list', array(
            'loan_mobile_contacts_list' => $loan_mobile_contacts_list,
            'loan_repayment_list'=>array(),
            'pages' => $pages,
        ));
    }

    /**
     * @return string
     * @name 用户管理-用户管理-导出用户通讯录/actionMobileContactsExport
     **/
    public function actionMobileContactsExport(){
        $conditions = $this->getFilter();
        if( !empty($conditions) && empty($this->request->get('user_id'))){
            return $this->redirectMessage('用户ID为必填选项', self::MSG_ERROR);
        }
        $query = UserMobileContactsMongo::find();
        foreach ($conditions as $condition){
            $query = $query->andWhere($condition);
        }
        $query = $query->orderBy([
            '_id' => SORT_DESC,
        ]);
        $result=$query->all();
        if(!empty($result)){
            $user_id=$this->request->get('user_id');
            $this->_setcsvHeader($user_id.'-'.time().'.csv');
            $items = [];
            foreach($result as $value){
                $items[] = [
                    '姓名' => $value['name'],
                    '手机号' => $value['mobile'],
                ];
            }
            echo $this->_array2csv($items);
        }
        unset($result);
    }

    /**
     *  @return   获取通话记录
     *
     */
    public function actionCallLogList(){
        $params = $this->getRequest()->get();

        $personInfo = Yii::$container->get('loanCollectionService')->getLoanPersonInfo($params['user_id']);

        $contactDropList = [];
        foreach ($personInfo['contact'] as $index => $contact) {
            //$contactDropList[$index] = "{$contact['name']}    {$contact['relation']}    {$contact['phone']}    {$contact['times']}";
            $contactDropList[$index]['name'] = $contact['name'];
            $contactDropList[$index]['relation'] = $contact['relation'];
            $contactDropList[$index]['phone'] = $contact['phone'];
            $contactDropList[$index]['times'] = $contact['times'];
        }

        return $this->render('call-log-list', array(
            'loan_mobile_contacts_list' => $contactDropList,
        ));
    }
    /**
     * @return  接受ajax数据 返回电话号码
     * @name 获取聚信立通话记录
     */
    public function actionCallLogone(){
         $params = $this->getRequest()->get();

        $personInfo = Yii::$container->get('loanCollectionService')->getLoanPersonInfo($params['user_id']);

        $contactDropList = [];
        foreach ($personInfo['contact'] as $index => $contact) {
            //$contactDropList[$index] = "{$contact['name']}    {$contact['relation']}    {$contact['phone']}    {$contact['times']}";
            $contactDropList[$index]['name'] = $contact['name'];
            $contactDropList[$index]['relation'] = $contact['relation'];
            $contactDropList[$index]['phone'] = $contact['phone'];
            $contactDropList[$index]['times'] = $contact['times'];
        }
        return \yii\helpers\Json::encode(['phone'=>$contactDropList[$params['contactid']]['phone']]);
    }
    /**
     * @return 获取短信记录
     * @name 借款管理-用户借款管理-借款列表-查看-手机短信息/actionPhoneMessageList
     */
    public function  actionPhoneMessageList(){
        $conditions = $this->getPhoneMessageFilter();
        $query = \common\models\mongo\mobileInfo\UserPhoneMessageMongo::find();
        foreach ($conditions as $condition){
            $query = $query->andWhere($condition);
        }
        $query = $query->orderBy([
                'message_date' => SORT_DESC,
        ]);
        $countQuery = clone $query;
        $count = $countQuery->count();
        $pages = new Pagination(['totalCount' => $count]);
        $pages->pageSize = 15;
        $list = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('phone-message-list', array(
            'list' => $list,
            'pages' => $pages,
        ));

    }

    protected function getPhoneMessageFilter() {
        $condition = [];
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if(isset($search['user_id'])&&!empty($search['user_id'])){
                $condition[] = ['=','user_id',$search['user_id']];
                //$condition['user_id'] = $search['user_id'];
            }
            if(isset($search['phone'])&&!empty($search['phone'])){
                //$condition .= " AND b.phone = '" . $search['phone']."'";
                $condition[] = ['=','phone',$search['phone']];
            }
//             if(isset($search['name'])&&!empty($search['name'])){
//                 $condition .= " AND b.name = '" . $search['name']."'";
//             }
//             if(isset($search['mobile'])&&!empty($search['mobile'])){
//                 $condition .= " AND a.phone = '" . $search['mobile']."'";
//             }
            if(isset($search['message_content'])&&!empty($search['message_content'])){
                //$condition .= " AND a.message_content like '%" . $search['message_content']."%'";
                $condition[] = ['like','message_content',$search['message_content']];
            }
        }

        return $condition;
    }


    /**
     * @return  短信上行日志
     * @name 短信上行日志
     */
    public function actionMessageLog()
    {
        $search = $this->request->get();
        $query = MessageCollectLog::find()->orderBy('id DESC');
        if (isset($search['phone'])) {
            $query->andFilterWhere(['phone' => $search['phone']]);
        }
        if (isset($search['type'])) {
            $query->andFilterWhere(['type' => $search['type']]);
        }
        if (isset($search['message'])) {
            $query->andFilterWhere(['like', 'message', $search['message']]);
        }
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('message-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * @return  短信发送状态
     * @name 短信发送状态
     */
    public function actionMessageStatus()
    {
        $search = $this->request->get();
        $query = PushSms::find()->orderBy('id DESC');

        if (\Yii::$app->request->IsPost) {
            $file = UploadedFile::getInstanceByName('file');
            if ($file) {
                set_time_limit(0);
                ini_set('memory_limit', '1024M');

                $path = 'uploads/' . $file->baseName . '.' . $file->extension;
                $file->saveAs($path);
                $handle = fopen($path, "r");
                while (($fileop = fgetcsv($handle, 1000, ",")) !== false)
                 {
                    // $id = $fileop[0];
                    // 编号 发送日期    号码  提交状态    回执状态    回执时间    内容
                    $created_at = strtotime($fileop[1]);
                    $phone = $fileop[2];
                    $status = mb_convert_encoding($fileop[3], "UTF-8", "GBK"); // 默认导入进行转换
                    $smsStatus = $fileop[4];
                    $updated_at = strtotime($fileop[5]);
                    $content = mb_convert_encoding($fileop[6], "UTF-8", "GBK");

                    $record = new PushSms();
                    $record->phone = $phone;
                    $record->content = $content;
                    $record->source = PushSms::SOURCE_YYTG;
                    $record->source_id = LoanPerson::PERSON_SOURCE_MOBILE_CREDIT;
                    $record->status = $status == '成功' ? PushSms::STATUS_SUCC : PushSms::STATUS_FAIL;
                    $record->remark = "csv导入";
                    $record->channel = "smsServiceXQB_XiAo_YX";
                    $record->audit_person = 'zhou';
                    $record->sms_status = $smsStatus;
                    $record->created_at = $created_at;
                    $record->updated_at = $updated_at;
                    $save = $record->save();
                 }

                echo "data upload successfully";
            }
        }

        if (isset($search['phone'])) {
            $query->andFilterWhere(['phone' => $search['phone']]);
        }
        if (isset($search['type'])) {
            $query->andFilterWhere(['type' => $search['type']]);
        }
        if (isset($search['message'])) {
            $query->andFilterWhere(['like', 'message', $search['message']]);
        }
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('message-status', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }
}