<?php
namespace backend\controllers;

use common\models\CreditMgLog;
use common\models\CreditTd;
use common\models\CreditZmopLog;
use Yii;
use common\models\CreditZmop;
use common\models\CreditFkb;
use common\models\CreditMg;
use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use yii\web\Response;


class LoanPersonBadInfoController extends BaseController
{

    public function actionCheckBadInfo(){
        $this->response->format = Response::FORMAT_JSON;
        $id = intval($this->request->get('id'));
        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return [
                'code' => -1,
                'message' => '芝麻信用不良信息分析:借款人不存在'
            ];
        }
        $zm_log = CreditZmopLog::find()->where(['person_id'=>$id])->orderBy(['id'=>SORT_DESC])->one();
        if(is_null($zm_log) || (time()-$zm_log['created_at'])>86400*30){
            return [
                'code'=> -1,
                'message' => '芝麻信用不良信息分析:芝麻信用信息已过期，请先获取最新报告'
            ];
        }
        $mg_log = CreditMgLog::find()->where(['person_id'=>$id])->orderBy(['id'=>SORT_DESC])->one();
        if(is_null($mg_log) || (time()-$mg_log['created_at'])>86400*30){
            return [
                'code'=> -1,
                'message' => '芝麻信用不良信息分析:蜜罐信息已过期，请先获取最新报告'
            ];
        }
        $badInfo = Yii::$container->get('loanPersonBadInfoService');
        $log_id = $badInfo->createLog($loanPerson['id']);
        if(!$log_id){
            return [
                'code' => -1,
                'message' => '芝麻信用不良信息分析:日志生成失败'
            ];
        }
        $result = $this->checkZmBadInfo($loanPerson,$log_id);
        if($result['code'] != 0){
            return $result;
        }
        $ret = $this->checkMgBadInfo($loanPerson,$log_id);
        if($ret['code'] != 0){
            return $ret;
        }
        $res = $this->checkTdBadInfo($loanPerson,$log_id);
        if($res['code'] != 0 ){
            return $res;
        }
        return [
            'code'=>0,
            'message'=>'不良信息分析:获取成功'
        ];
    }
    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * 获取用户芝麻信用的不良信息
     */
    public function checkZmBadInfo(LoanPerson $loanPerson,$log_id){
        $id = $loanPerson['id'];
        $creditZmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$id]);
        if(is_null($creditZmop)){
            return [
                'code' => -1,
                'message' => '芝麻信用不良信息分析:用户芝麻信用未授权'
            ];
        }
        if($creditZmop['status'] == 2){
            return [
                'code' => -1,
                'message' => '芝麻信用不良信息分析:用户已取消芝麻信用授权'
            ];
        }


        if( empty($creditZmop['das_info'])){
            return [
                'code' => -1,
                'message' => '芝麻信用不良信息分析:请先获取DAS信息'
            ];
        }
        if ( empty($creditZmop['watch_matched']) ){
            return [
                'code' => -1,
                'message' => '芝麻信用不良信息分析:请先获取行业关注名单'
            ];
        }

        $badInfo = Yii::$container->get('loanPersonBadInfoService');
        //获取黑名单信息
        $black_list = $badInfo->getZmBlacklistInfo($creditZmop);
        $black_list_count = count($black_list);
        if(!empty($black_list)){
            $result = $badInfo->saveBadInfo(1,$loanPerson,$black_list,1,$log_id);
            if(!$result){
                return [
                    'code' => -1,
                    'message' => '芝麻信用不良信息分析:黑名单信息保存失败'
                ];
            }
        }
        //获取灰名单信息
        $gray_list = $badInfo->getZmGraylistInfo($creditZmop);
        $gray_list_count = count($gray_list);
        if(!empty($gray_list)){
            $result = $badInfo->saveBadInfo(2,$loanPerson,$gray_list,1,$log_id);
            if(!$result){
                return [
                    'code' => -1,
                    'message' => '芝麻信用不良信息分析:灰名单信息保存失败'
                ];
            }
        }
        $log_result = $badInfo->updateLog($log_id,['gray_count'=>$gray_list_count,'black_count'=>$black_list_count]);
        if(!$log_result){
            return [
                'code'=> -1,
                'message'=>'芝麻信用不良信息分析:日志保存失败'
            ];
        }
        return [
            'code' => 0,
            'message' => "芝麻信用不良信息分析:分析成功，黑名单匹配到{$black_list_count}条，灰名单匹配到{$gray_list_count}条"
        ];

    }


    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * 获取用户蜜罐的不良信息
     */
    public function checkMgBadInfo(LoanPerson $loanPerson,$log_id){
        $id = $loanPerson['id'];
        $creditMg = CreditMg::findLatestOne(['person_id'=>$id]);
        if(is_null($creditMg)){
            return [
                'code' => -1,
                'message' => '蜜罐不良信息分析:请先获取蜜罐信息'
            ];
        }
        $data = json_decode($creditMg['data'],true);

        $badInfo = Yii::$container->get('loanPersonBadInfoService');
        //获取黑名单
        $black_list = $badInfo->getMgBlacklistInfo($data);
        $black_list_count = count($black_list);
        if( $black_list_count > 0){
            $black_ret = $badInfo->saveBadInfo(1,$loanPerson,$black_list,2,$log_id);
            if(!$black_ret){
                return [
                    'code' => -1,
                    'message' => '蜜罐不良信息分析:黑名单类型的用户不良信息保存失败'
                ];
            }
        }
        //获取灰名单
        $gray_list = $badInfo->getMgGraylistInfo($data);
        $gray_list_count = count($gray_list);
        if($gray_list_count > 0){
            $gray_ret = $badInfo->saveBadInfo(2,$loanPerson,$gray_list,2,$log_id);
            if(!$gray_ret){
                return [
                    'code' => -1,
                    'message' => '蜜罐不良信息分析:灰名单类型的用户不良信息保存失败'
                ];
            }
        }
        $log_result = $badInfo->updateLog($log_id,['gray_count'=>$gray_list_count,'black_count'=>$black_list_count]);
        if(!$log_result){
            return [
                'code'=> -1,
                'message'=>'蜜罐信用不良信息分析:日志保存失败'
            ];
        }
        return [
            'code' => 0,
            'message' => "蜜罐不良信息分析:分析成功，黑名单匹配到{$black_list_count}条，灰名单匹配到{$gray_list_count}条"
        ];
    }

    public function actionView(){
        $id = intval($this->request->get('id'));
        $log_id = intval($this->request->get('log_id'));
        $where = [
            'person_id'=>$id
        ];
        if(!empty($log_id)){
            $where['log_id'] = $log_id;
        }
        $loanPerson = LoanPerson::findOne($id);
        if(is_null($loanPerson)){
            return $this->redirectMessage('该用户不存在',self::MSG_ERROR);
        }

        $badInfo = LoanPersonBadInfo::find()->where($where)->orderBy('id desc')->asArray()->all();
        $black_list = [];
        $gray_list = [];
        if(!empty($badInfo)){
            foreach($badInfo as $v){
                $v['source'] = LoanPersonBadInfo::$source_list[$v['source']];
                $v['rule_type'] = LoanPersonBadInfo::$rule_type_list[$v['rule_type']];
                $v['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
                switch($v['list_type']){
                    case LoanPersonBadInfo::LIST_TYPE_BLACK:
                        $black_list[] = $v;
                        break;
                    case LoanPersonBadInfo::LIST_TYPE_GRAY:
                        $gray_list[] = $v;
                        break;
                }
            }
        }

        return $this->render('user-bad-info-view',[
            'loanPerson' => $loanPerson,
            'black_list' => $black_list,
            'gray_list' => $gray_list
        ]);
    }

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     * 获取用户同盾的不良信息
     */
    public function checkTdBadInfo(LoanPerson $loanPerson,$log_id){
        $id = $loanPerson['id'];
        $creditTd = CreditTd::findLatestOne(['person_id'=>$id]);
        if(is_null($creditTd)){
            return [
                'code' => -1,
                'message' => '同盾不良信息分析:请先获取同盾信息'
            ];
        }
        if( (time()-$creditTd['created_at']) > 86400*30){
            return [
                'code' => -1,
                'message' => '同盾不良信息分析:用户信息已过期，请重新提交并获取用户信息'
            ];
        }

        $badInfo = Yii::$container->get('loanPersonBadInfoService');
        //获取黑名单
        $black_list = $badInfo->getTdBlacklistInfo($creditTd);
        $black_list_count = count($black_list);
        if( $black_list_count > 0){
            $black_ret = $badInfo->saveBadInfo(1,$loanPerson,$black_list,3,$log_id);
            if(!$black_ret){
                return [
                    'code' => -1,
                    'message' => '同盾不良信息分析:黑名单类型的用户不良信息保存失败'
                ];
            }
        }
        //获取灰名单
        $gray_list = $badInfo->getTdGraylistInfo($creditTd);
        $gray_list_count = count($gray_list);
        if($gray_list_count > 0){
            $gray_ret = $badInfo->saveBadInfo(2,$loanPerson,$gray_list,3,$log_id);
            if(!$gray_ret){
                return [
                    'code' => -1,
                    'message' => '同盾不良信息分析:灰名单类型的用户不良信息保存失败'
                ];
            }
        }
        $log_result = $badInfo->updateLog($log_id,['gray_count'=>$gray_list_count,'black_count'=>$black_list_count]);
        if(!$log_result){
            return [
                'code'=> -1,
                'message'=>'同盾信用不良信息分析:日志保存失败'
            ];
        }
        return [
            'code' => 0,
            'message' => "同盾不良信息分析:分析成功，黑名单匹配到{$black_list_count}条，灰名单匹配到{$gray_list_count}条"
        ];
    }
}