<?php

namespace backend\controllers;

use common\models\LoanPerson;
use yii\base\Exception;
use common\models\CreditLzf;
use Yii;

class LzfController extends BaseController
{

    /**
     * @return string
     * @name 征信管理-用户征信管理-用户征信管理-孚临灵芝分/actionUserReportView
     */
    public function actionUserReportView(){
        try {
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::find()->where(['id' => $id])->one();
            if(is_null($loanPerson)){
                throw new Exception('用户不存在');
            }
            $lxf = CreditLzf::find()->where(['person_id'=>$loanPerson->id])->one();
            if (!empty($lxf) && !empty($lxf['score'])){
                $score = $lxf['score'] ?? 0;
            }

            if (empty($score)){
                $service = Yii::$container->get('lzfService');
                $result = $service->getScore($loanPerson);
                $score = $result['score'];
            }
        }catch (\Exception $e) {
//            echo '孚临接口异常：' . $e->getMessage() . $e->getFile() . $e->getLine();
            return $this->redirectMessage('获取孚临灵芝分失败'. $e->getMessage() . $e->getFile() . $e->getLine(), self::MSG_ERROR);
        }
        return $this->render('user-report-view', array(
            'score' => $score
        ));
    }

}