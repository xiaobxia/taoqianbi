<?php
namespace backend\controllers;

use Yii;
use common\helpers\Url;
use yii\web\ForbiddenHttpException;
use common\helpers\CommonHelper;
use common\models\AdminOperateLog;
use backend\models\ActionModel;
use backend\models\AdminUserRole;
use common\models\LoanPerson;

/**
 * Base controller
 *
 * @property \yii\web\Request $request The request component.
 * @property \yii\web\Response $response The response component.
 */
abstract class BaseController extends \common\components\BaseController
{
    const MSG_NORMAL = 0;
    const MSG_SUCCESS = 1;
    const MSG_ERROR = 2;

    // 是否验证本系统的权限逻辑
    public $verifyPermission = true;

    public function beforeAction($action) {
        if (parent::beforeAction($action)) {
            //非线上环境强制 填写方法名称
            if (YII_ENV_DEV) {
                $permissionArr = Yii::$app->params['permissionControllers'];
                list($controllerId, $actionId) = explode('/', $this->getRoute());
                $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerId)));
                $controName = $className . "Controller";
                if (array_key_exists($controName, $permissionArr)) {
                    $actionName = str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId)));
                    $class = "backend\\controllers\\" . $controName;
                    $action = "action" . $actionName;
                    $rf = new \ReflectionClass($class);
                    $method = $rf->getMethod($action);
                    $actionModel = new ActionModel($method);
                    $title = $actionModel->getTitle();
                    $name = $actionModel->getName();
                    if (empty($title) || ($title == $name)) {
                        throw new ForbiddenHttpException('抱歉，此控制器：' . $controName . '，此方法：' . $method->name . ', 没有添加注释请添加！如： @name 测试。');
                    }
                }
            }

            if (Yii::$app->request->get('frames')) {
                return $this->redirect(['main/index', 'action' => $this->getRoute()])->send();
            }

            // 验证登录
            $controllerID = Yii::$app->controller->id;
            $controller_ActionID = Yii::$app->controller->action->id;
            if (Yii::$app->user->getIsGuest() && $controllerID!='main' && $controller_ActionID!='login') {
                return $this->redirect(['main/login'])->send();
            }

            if (!empty(Yii::$app->user->identity->role) && ($this->channel_redirect(Yii::$app->user->identity->role) == true)){
                $is_gochannerl=true;
                if($controllerID=='main' && $controller_ActionID=='logout'){
                    $is_gochannerl=false;
                }else if($controllerID=='main' && $controller_ActionID=='login'){
                    $is_gochannerl=false;
                }else if($controllerID=='main' && $controller_ActionID=='home'){
                    $is_gochannerl=false;
                }else if(strstr(Yii::$app->request->url,'/channel/channel-statistic-detail')){
                    $is_gochannerl=false;
                }
                if($controllerID!='channel' && $controller_ActionID!='channel-statistic-detail' && $is_gochannerl){
                    die('<script>parent.location.href="' . Url::toRoute('/channel/channel-statistic-detail') . '";</script>');
                }
            }

            if ($this->verifyPermission) {
                if (! CommonHelper::isLocal()) {
                    $this->saveRequestLog();
                }

                // 验证权限
                if (!Yii::$app->user->identity->getIsSuperAdmin()) {
                    $permissions = Yii::$app->getSession()->get('permissions');
                    if ($permissions) {
                        $permissions = json_decode($permissions, true);
                        if (!in_array($this->getRoute(), $permissions)) {
                            throw new ForbiddenHttpException('您所属的管理员角色无此权限');
                        }
                    } else {
                        $role = Yii::$app->user->identity->role;
                        if ($role) {
                            $roleModel = AdminUserRole::find()->andWhere("name in('" . implode("','", explode(',', $role)) . "')")->all();
                            if ($roleModel) {
                                $arr = array();
                                foreach ($roleModel as $val) {
                                    if ($val->permissions)
                                        $arr = array_unique(array_merge($arr, json_decode($val->permissions)));
                                }
                                Yii::$app->getSession()->set('permissions', json_encode($arr));
                                $permissions = json_decode($permissions, true);
                                if (!in_array($this->getRoute(), $permissions)) {
                                    throw new ForbiddenHttpException('您所属的管理员角色无此权限');
                                }
                            } else {
                                throw new ForbiddenHttpException('您所属的管理员角色无此权限');
                            }


                        } else {
                            throw new ForbiddenHttpException('您所属的管理员角色无此权限');
                        }
                    }
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * 渠道跳转
     * @param string $role 角色权限
     * @return $this 链接跳转
     */
    private function channel_redirect($role = ""){
        new LoanPerson();
        $arr = explode(",",$role);
        foreach ($arr as $val){
            if (array_key_exists($val, LoanPerson::$user_agent_source)){
                return true;
            }
        }
        return false;
    }

    /**
     * 改变redirect的默认行为
     * 调用 yii\web\Response::send() 方法来确保没有其他内容追加到响应中
     *
     * @see yii\web\Controller::redirect()
     */
    public function redirect($url, $statusCode = 302, $isSend = true)
    {
        if (is_array($url)) {
            $url[0] = \yii\helpers\Inflector::camel2id($url[0]);
        } else {
            $url = \yii\helpers\Inflector::camel2id($url);
        }

        $response = parent::redirect($url, $statusCode);

        if ($isSend === true) {
            $response->send();
            exit;
        } else {
            return $response;
        }
    }

    /**
     * 获得请求对象
     */
    public function getRequest()
    {
        return \Yii::$app->getRequest();
    }

    /**
     * 获得返回对象
     */
    public function getResponse()
    {
        return \Yii::$app->getResponse();
    }

    /**
     * 跳转到提示页面
     * @param string $message 提示语
     * @param int $msgType 提示类型，不同提示类型提示语样式不一样
     * @param string $url 自动跳转url地址，不设置则默认显示返回上一页连接
     * @return string
     */
    public function redirectMessage($message, $msgType = self::MSG_NORMAL, $url = '')
    {
        switch ($msgType) {
            case self::MSG_SUCCESS:
                $messageClassName = 'infotitle2';
                break;
            case self::MSG_ERROR:
                $messageClassName = 'infotitle3';
                break;
            default:
                $messageClassName = 'marginbot normal';
                break;
        }
        return $this->render('/message', array(
            'message' => $message,
            'messageClassName' => $messageClassName,
            'url' => $url,
        ));
    }

    /*
     * 保存请求日志
     */
    private function saveRequestLog() {
        $route = Yii::$app->request->url;
        if (Yii::$app->request->method=='POST') {
            $params = array_merge($_GET, $_POST);
            if (isset($params['r'])) {
                unset($params['r']);
            }
            if (isset($params['_csrf'])) {
                unset($params['_csrf']);
            }
            if (isset($params['ADMIN_SID'])) {
                unset($params['ADMIN_SID']);
            }
            if(strpos($route,'&')!==false)
            {
                $route = strstr($route,'&',true);
            }
            $model = new AdminOperateLog();
            $model->admin_user_id = Yii::$app->user->identity->id;
            $model->admin_user_name = Yii::$app->user->identity->username;
            $model->request = \yii::$app->request->method;
            $model->request_params = json_encode($params, JSON_UNESCAPED_UNICODE);
            $model->ip = Yii::$app->request->userIP;
            $model->route = $route;
            return $model->save();
        }
    }

    protected function _setcsvHeader($filename) {
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
        // force download
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-type: application/vnd.ms-excel; charset=utf8");
        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
        //设置utf-8 + bom ，处理汉字显示的乱码
        print(chr(0xEF) . chr(0xBB) . chr(0xBF));
    }

    protected function _array2csv(array &$array)
    {
        if (count($array) == 0) {
            return null;
        }
        set_time_limit(0);//响应时间改为60秒
        ini_set('memory_limit', '512M');
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($array)));
        foreach ($array as $row) {
            fputcsv($df, $row);
        }
        fclose($df);
        return ob_get_clean();
    }

    /**
     * 检测是否有导出权限
     */
    protected function _canExportData(){
        $can = true;
        $is_admin = Yii::$app->user->identity->getIsSuperAdmin();
        $role_str = Yii::$app->user->identity->role;
        $role_arr = (!empty($role_str)) ? explode('-',$role_str) : [];
        if(!$is_admin && !in_array('super_dev', $role_arr)){//如果不是superadmin或者角色不是super_dev则需要判断该用户是否有权限
            $user_arr = Yii::$app->params['ExportDataUser'];
            $user_name = Yii::$app->user->identity->username;
            if(!in_array($user_name, $user_arr )){
                $can = false;
            }
        }
        return $can;
    }
}
