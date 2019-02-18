<?php
namespace backend\controllers;
use Yii;
use common\helpers\Url;
use yii\web\NotFoundHttpException;
use backend\controllers\BaseController;
use common\models\message\Message;
use common\models\message\MessageLog;

class MessageController extends BaseController{

    /**
    * edit: jyqiang
    * date: 2017-01-03
    * @name 站内消息通知-显示列表
    */
    public function actionMessageList(){
        // 获取所有通知消息
        $message_all        = Message::getMessageList();
        $message_list       = $message_all['message_list'];
        $pages              = $message_all['pages'];
        // 获取当前登录用户的消息阅读状态记录
        $message_log_list   = MessageLog::getMessageLogList();
        $message_log_arr    = array();
        foreach ($message_log_list as $key => $value) {
            $message_log_arr[$value['message_id']] = $value;
        }
        foreach ($message_list as $key => $value) {
            $message_log_info  = isset($message_log_arr[$value['id']]) ? $message_log_arr[$value['id']] : array();
            if (empty($message_log_info)) {
                $info['read_status']   = 0;
            }else{
                $info['read_status']   = $message_log_info['read_status'];
            }
            $info = array_merge($info, $value);
            $message_list[$key]    = $info;
            unset($info);
        }
        // var_dump($message_list);exit();
        return $this->render('message-list', array(
            'message_list' => $message_list,
            'pages' => $pages,
        ));
    }

    /**
    * @name 站内消息通知-发布消息
    */
    public function actionMessageAdd(){
        $model  = new Message();
        if($this->getRequest()->getIsPost())
        {
            $message_type   = $this->request->post('message_type');
            $message_title  = $this->request->post('message_title');
            $message_body   = $this->request->post('message_body');
            $model->sender_name         = Yii::$app->user->identity->username;
            $model->message_title       = $message_title;
            $model->message_body        = $message_body;
            $model->message_type        = $message_type;
            $model->created_at          = time();
    		if ($model->save()) {
    			return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('message/message-list'));
    		} else {
    			return $this->redirectMessage('添加失败', self::MSG_ERROR);
    		}
        }
        return $this->render('message-add');
    }

    /**
    * @name 站内消息通知-编辑消息
    */
    public function actionMessageEdit(){
        $id = $this->request->get('id');
        $view_info = Message::messageViewById($id);
        // 如果编辑模块找不到对应的ID，从哪儿来，回哪儿去
        if (!$view_info){
            $backUrl = Yii::$app->request->referrer;
            return $this->redirect($backUrl);
        }
        if ($this->getRequest()->getIsPost()) {
            $message_type   = $this->request->post('message_type');
            $message_title  = $this->request->post('message_title');
            $message_body   = $this->request->post('message_body');
            $model  = new Message();
            $model  = Message::findOne($id);
            $model->sender_name         = Yii::$app->user->identity->username;
            $model->message_title       = $message_title;
            $model->message_body        = $message_body;
            $model->message_type        = $message_type;
            $model->created_at          = time();
            if ($model->save()) {
    			return $this->redirectMessage('编辑成功', self::MSG_SUCCESS, Url::toRoute('message/message-list'));
    		} else {
    			return $this->redirectMessage('编辑失败', self::MSG_ERROR);
    		}
        }
        return $this->render('message-edit', array('view_info' => $view_info));
    }

    /**
    * @name 站内消息通知-删除消息
    */
    public function actionMessageDel(){
        $id = $this->request->get('id');
        $backUrl = Yii::$app->request->referrer;
        $view_info = Message::messageViewById($id);
        // 如果编辑模块找不到对应的ID，从哪儿来，回哪儿去
        if (!$view_info){
            return $this->redirect($backUrl);
        }
        $model = new Message();
        $model = Message::findOne($id);
        $model->delete_status   = 1;
        $model->updated_at      = time();
        $model->sender_name     = Yii::$app->user->identity->username;
        if ($model->save()) {
            return $this->redirectMessage('删除成功', self::MSG_SUCCESS, $backUrl);
        }else{
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }

    }


    /**
    * @name 站内消息通知-查看消息详情
    */
    public function actionMessageView(){
        $messsage_id = $this->request->get('id');
        if ($view_info = Message::messageViewById($messsage_id)) {
            // 如果查看成功就将用户ID与消息ID写入message_log中
            MessageLog::update_read_user($messsage_id);
            return $this->render('message-view', array('view_info' => $view_info));
        }else{
            return redirect('message/message-list');
        }
    }


}
