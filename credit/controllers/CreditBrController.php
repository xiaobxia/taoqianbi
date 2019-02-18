<?php

namespace credit\controllers;

use common\api\RedisQueue;
use Yii;
use common\services\UserService;
use common\models\CreditBr;
use yii\base\Exception;

class CreditBrController extends BaseController
{
    protected $userService;

    protected $br_json = array();

    /**
     * 构造函数中注入UserService的实例到自己的成员变量中
     * 也可以通过Yii::$container->get('userService')的方式获得
     */
    public function __construct($id, $module, UserService $userService, $config = [])
    {
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    /**
     * 读取百融SDK数据信息
     * @name 读取百融SDK数据信息BrSdk [BrSdk]
     * @method post
     * @param  string $event 百融SDK返回event
     * @param  string $af_swift_number 百融SDK返回af_swift_number
     * @return array
     */
    public function actionBrSdk(){
        $loanPerson = Yii::$app->user->identity;
        if(empty($loanPerson)){
            return [
                'code'=>-2,
                'message'=>'用户未登录',
            ];
        }

        $br_json = $this->request->post();
        if(!isset($br_json['event']) || !isset($br_json['af_swift_number'])){
            return [
                'code'=>-1,
                'message'=>'缺少必要参数',
            ];
        }
        switch($br_json['event']){
            case 'antifraud_register':
                $type = CreditBr::REGISTER_EQUIPMENT;
                break;
            case 'antifraud_login':
                $type = CreditBr::SIGN_EQUIPMENT;
                break;
            case 'antifraud_lend':
                $type = CreditBr::LOAN_EQUIPMENT;
                break;
            default:
                return [
                    'code' => -1,
                    'message' => '未知的产品类型'
                ];
                break;
        }

        try {
            $json_data = json_encode([
                'user_id' => $loanPerson->id,
                'type' => $type,
                'br_json' => $br_json
            ]);
            $result = RedisQueue::push([RedisQueue::LIST_BR_CREDIT_DATA, $json_data]);
            if (!$result) {
                \YII::error('push br list failed, data: ' . $json_data, 'credit_br');
                return ['code' => -1, 'message' => '上传失败'];
            }

            return ['code'=> 0, 'message' => '上传成功'];
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), 'credit_br');
            return ['code' => -2, 'message' => '上传失败'];
        }
    }
}