<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/6/11
 * Time: 11:59
 */
namespace backend\controllers;

use backend\models\AdminOperatorLog;
use common\models\UserCompanyOperateLog;
use common\models\UserLoginUploadLog;
use common\models\UserOperateLog;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\db\Query;
use common\helpers\Url;
use yii\redis\ActiveQuery;
use common\models\UserRegisterInfo;

class LogController extends  BaseController{

    protected function getFilter() {
        $condition = '1 = 1';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if(isset($search['user_id'])&&!empty($search['user_id'])){
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if(isset($search['deviceId'])&&!empty($search['deviceId'])){
                $condition .= " AND deviceId like '%" . $search['deviceId']."%'";
            }
        }
        return $condition;
    }


    /**
     * @return 员工帮获取登录信息
     * @name 用户管理-其他管理-日志管理/actionYgbLoginLogList
     */
    public function  actionYgbLoginLogList(){


        $condition = $this->getFilter();
        $query = UserLoginUploadLog::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);

        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $user_login_upload_log = $query->with([
            'loanPerson' => function(Query $query) {
                $query->select(['id', 'name', 'phone']);
            },
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));


        return $this->render('ygb-login-log-list', array(
            'user_login_upload_log' => $user_login_upload_log,
            'pages' => $pages,
        ));
    }

    /**
     * 操作公司日志列表
     * @return string
     * @name 用户管理-其他管理-日志管理-公司操作日志/actionCompanyLogList
     */
    public function actionCompanyLogList()
    {
        $condition = "1=1";
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if(isset($search['company_id'])&& !empty($search['company_id'])){
                $condition .= " AND company_id = " . intval($search['company_id']);
            }
            if(isset($search['company_name'])&& !empty($search['company_name'])){
                $condition .= " AND company_name like '%" . $search['company_name']."%'";
            }
            if (isset($search['type']) && $search['type'] != NULL) {
                $condition .= " AND type = '" . $search['type']."'";
            }
        }
        $query = UserCompanyOperateLog::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $log = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('company-log-list', array(
            'log' => $log,
            'pages' => $pages,
        ));
    }

    /**
     * 操作用户日志列表
     * @return string
     * @name 用户管理-其他管理-日志管理-用户操作日志/actionUserLogList
     */
    public function actionUserLogList()
    {
        $condition = "1=1";
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if(isset($search['user_id'])&& !empty($search['user_id'])){
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['type']) && $search['type'] != NULL) {
                $condition .= " AND type = '" . $search['type']."'";
            }
        }
        $query = UserOperateLog::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $log = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('user-log-list', array(
            'log' => $log,
            'pages' => $pages,
        ));
    }

    /**
     * 管理员操作日志列表
     * @return string
     * @name 用户管理-其他管理-日志管理-管理员操作日志/actionUserLogList
     */
    public function actionAdminOperatorLogList()
    {
        $condition = "1=1";
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if(isset($search['admin_id'])&& !empty($search['admin_id'])){
                $condition .= " AND admin_id = " . intval($search['admin_id']);
            }
            if (isset($search['action']) && $search['action'] != NULL) {
                $condition .= " AND action = '" . trim($search['action'])."'";
            }
            if (isset($search['extra_id']) && $search['extra_id'] != NULL) {
                $condition .= " AND extra_id = '" . intval($search['extra_id'])."'";
            }
        }
        $query = AdminOperatorLog::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('id')]);
        $pages->pageSize = 15;
        $log = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('admin-operator-log-list', array(
            'log' => $log,
            'pages' => $pages,
        ));
    }

    /**
     * 用户注册日志
     * @return string
     * @name 用户管理-其他管理-日志管理-用户注册日志/actionUserRegisterInfoList
     */
    public function actionUserRegisterInfoList(){
    	$condition = "1=1";
    	if ($this->getRequest()->getIsGet()) {
    		$search = $this->request->get();
    		if(isset($search['id'])&& !empty($search['id'])){
    			$condition .= " AND id = " . intval($search['id']);
    		}
    		if (isset($search['user_id']) && $search['user_id'] != NULL) {
    			$condition .= " AND user_id = '" . intval($search['user_id'])."'";
    		}
    		if (isset($search['clientType']) && $search['clientType'] != NULL) {
    			$condition .= " AND clientType = '" . intval($search['clientType'])."'";
    		}
    		if (isset($search['source']) && $search['source'] != NULL) {
    			$condition .= " AND source = '" . intval($search['source'])."'";
    		}
    	}
    	$query = UserRegisterInfo::find()->where($condition)->orderBy([
    			'id' => SORT_DESC,
    	]);
    	$countQuery = clone $query;
    	$pages = new Pagination(['totalCount' => $countQuery->count('id')]);
    	$pages->pageSize = 15;
    	$log = $query->offset($pages->offset)->limit($pages->limit)->all();

    	return $this->render('user-register-info-list', array(
    			'log' => $log,
    			'pages' => $pages,
    	));
    }
}