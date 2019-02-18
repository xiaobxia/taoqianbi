<?php
namespace backend\controllers;

use Yii;
use common\helpers\Url;
use yii\web\Response;

use backend\controllers\BaseController;
use backend\models\ActionModel;
use backend\models\DocumentApi;

/**
 * Document controller
 */
class DocumentController extends BaseController {
    public $layout = false;

    public $verifyPermission = false;

    /**
     * 文档首页
     */
	public function actionApi() {
	    if(YII_ENV == 'prod') {
	        $this->redirect('main/login');
	    }

        $action = $this->request->get('action');
        $check_credit =explode("\\", $action)[0]; //为credit控制器找唯一的api调试
        $navItems = [];
        $currentAction = null;
        $debugRoute = $debugUrl = '';
        $configs = Yii::$app->params['apiList'];
        foreach ($configs as $config) {
            $items = [];
            $rf = new \ReflectionClass($config['class']);
            $methods = $rf->getMethods(\ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (strpos($method->name, 'action') === false || $method->name == 'actions') {
                    continue;
                }
                $actionModel = new ActionModel($method);
                $active = false;
                if ($action) {
                    list($class, $actionName) = explode('::', $action);
                    if ($class == $config['class'] && $actionName == $method->name) {
                        $currentAction = $actionModel;
                        $debugRoute = $actionModel->getRoute();
                        if($check_credit == 'credit'){
                            $debugUrl = str_replace(
                                            ['backend', 'admin.kdqugou.com'],
                                            ['credit', 'api.kdqugou.com'],
                                            $this->request->getHostInfo() . $this->request->getBaseUrl()
                                    ) . '/' . $debugRoute;
                        }else {
                            $debugUrl = str_replace(
                                            ['backend', 'admin.kdqugou.com'],
                                            ['frontend', 'api.kdqugou.com'],
                                            $this->request->getHostInfo() . $this->request->getBaseUrl()
                                    ) . '/' . $debugRoute;

                        }

                        $active = true;
                    }
                }

                $items[] = [
                    'label' => $actionModel->getTitle(),
                    'url' => Url::toRoute(['document/api', 'action' => "{$config['class']}::{$method->name}"]),
                    'active' => $active,
                ];
            }
            $navItems[] = [
                'label' => $config['label'],
                'url' => '#',
                'items' => $items
            ];
        }
        if ($currentAction) {
            $api = DocumentApi::findOne(['name' => $action]);
            $api || $api = new DocumentApi();
            $currentAction->data = [
                'response' => $api->response,
                'desc' => $api->desc,
            ];
        }

        return $this->render('api', [
            'action' => $action,
            'navItems' => $navItems,
            'model' => $currentAction,
            'debugRoute' => $debugRoute,
            'debugUrl' => $debugUrl,
        ]);
    }

    /**
     * 接口API文档
     * @param string $channel controller，service
     * @param type $controller
     */
    public function actionPartnerApi($partner, $channel = 'controller') {

        if ($channel == 'service') {
            $controller = ucfirst($partner);
            $class = "common\\services\\channel\\" . $controller . "Service";
        }else{
            $controller = 'Interface'.ucfirst($partner);
            $class = "frontend\\controllers\\" . $controller . "Controller";
        }

        if(!class_exists($class)) {
            throw new \Exception('找不到类 '.$class);
        }

        $encryptAction = 'actionEncryptTestData';

        $rf = new \ReflectionClass($class);
        $methods = $rf->getMethods(\ReflectionMethod::IS_PUBLIC);
        $classComment = $rf->getDocComment();
        $actions = [];
        $navItems = [
            [
                'label' => '通用说明',
                'url' => '#desc',
            ]
        ];
        foreach ($methods as $method) {
            if (strpos($method->name, 'action') === false || $method->name == 'actions' || $method->name==='actionEncryptTestData') {
                continue;
            }
            $action = new ActionModel($rf->getMethod($method->name));
            $navItems[] = [
                'label' => $action->getTitle(),
                'url' => '#'.substr($action->getRoute(), strrpos($action->getRoute(), '/')+1),
            ];
            $actions[] = $action;
        }
        $classDoc = [
            'name'=>$partner,
            'desc'=>''
        ];

        if (preg_match('/@name\s*(.*)\n/', $classComment, $matches) && !empty($matches[1])) {
            $classDoc['name'] = trim($matches[1], "\t\n\r\0\x0B");
        }

        if (preg_match_all('/@desc\s*(.*)\n/', $classComment, $matches) && !empty($matches[1])) {
            foreach($matches[1] as $val ) {
                $classDoc['desc'] .= trim($val, "\t\n\r\0\x0B").'<br/><br/>';
            }
        }

        return $this->render('interface', [
            'controller' => $controller,
            'actions' => $actions,
            'classDoc'=>$classDoc,
            'partner'=>$partner,
            'navItems'=>$navItems,
            'encrypt'=>$rf->hasMethod($encryptAction)
        ]);
    }

    /**
     * 保存接口文档信息
     */
    public function actionApiSave($action) {
        $this->response->format = Response::FORMAT_JSON;
        $model = DocumentApi::findOne(['name' => $action]);
        if (!$model) {
            $model = new DocumentApi();
        }
        if ($model->load($this->request->post()) && $model->validate()) {
            $model->name = $action;
            if ($model->save()) {
                return ['result' => true];
            }
        }
        return ['result' => false];
    }

    /**
     * 在线调试，目前只支持frontend下的controller
     * @param string $route
     */
    public function actionApiDebug($route)
    {
        $this->layout = "@backend/views/document/debug";
        try {
            $route = trim($route, '/');
            // 通过路由找到controller和action名称
            list($controllerId, $actionId) = explode('/', $route);
            $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerId)));
            $actionName = str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId)));
            $class = "frontend\\controllers\\" . $className . "Controller";
            $action = "action" . $actionName;

            $rf = new \ReflectionClass($class);
            $method = $rf->getMethod($action);
            $actionModel = new ActionModel($method);
        } catch (\Exception $e) {
            return '无对应的Controller或Action，请检测route参数是否正确';
        }

        $debugUrl = str_replace('backend', 'frontend', $this->request->getBaseUrl()) . '/' . $route;
        return $this->render('_debug', [
            'debugUrl' => $debugUrl,
            'route' => $route,
            'model' => $actionModel,
        ]);
    }

    /**
     * 加密API调试
     * @param string $route
     */
    public function actionEncryptApiDebug($route) {
        $this->layout = "@backend/views/document/debug";
        try {
            $route = trim($route, '/');
            // 通过路由找到controller和action名称
            list($controllerId, $actionId) = explode('/', $route);
            $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerId)));
            $actionName = str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId)));
            $class = "frontend\\controllers\\" . $className . "Controller";
            $action = "action" . $actionName;
                        $encryptAction = 'actionEncryptTestData';

            $rf = new \ReflectionClass($class);
            $method = $rf->getMethod($action);
            $actionModel = new ActionModel($method);
                        if(!$rf->hasMethod($encryptAction)) {
                            throw new \Exception('加密API调试需要在API中添加'.$encryptAction.'方法');
                        }
        } catch (\Exception $e) {
            return '无对应的Controller或Action，请检测route参数是否正确';
        }

        $debugUrl = str_replace('backend', 'frontend', $this->request->getBaseUrl()) . '/' . $route;
        $encryptUrl = str_replace('backend', 'frontend', $this->request->getBaseUrl()) .'/'.$controllerId. '/encrypt-test-data';
        return $this->render('_encryptApiDebug', [
                        'encryptUrl' => $encryptUrl,
            'debugUrl' => $debugUrl,
            'route' => $route,
            'model' => $actionModel,
        ]);
    }

    /**
     * 获取签名
     */
    public function actionGetSign($route=null) {
        $params = $this->request->post();
        unset($params['sign']);
        unset($params[\yii::$app->request->csrfParam]);

        if ($route) {
            $route = trim($route, '/');
            // 通过路由找到controller和action名称
            list($controllerId, $actionId) = explode('/', $route);
            $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $controllerId)));
            $actionName = str_replace(' ', '', ucwords(str_replace('-', ' ', $actionId)));
            $className = "frontend\\controllers\\" . $className . "Controller";

            $rf = new \ReflectionClass($className);
            if ($rf->hasMethod('getTestSign')) {
                return $className::getTestSign($params);
            }
        }

        return \common\models\Order::getSign($params);
    }
}
