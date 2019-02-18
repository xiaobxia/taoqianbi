<?php

namespace backend\controllers;
use common\api\RedisQueue;
use common\models\Category;
use common\models\CategoryMobile;
use common\models\mongo\alipay\AlipayDataOrgMongo;
use common\models\mongo\alipay\AlipayFormatReportMongo;
use common\models\mongo\risk\MobileContactsReportMongo;
use common\models\risk\Rule;
use common\models\ScoreSetting;
use common\models\SpeaialRuleValue;
use common\models\UserMessageList;
use common\models\TemplateList;
use common\models\UserMobileContacts;
use Yii;
use yii\base\Exception;
use yii\data\Pagination;
use yii\web\UploadedFile;
use common\helpers\StringHelper;
use yii\validators\FileValidator;
use common\helpers\Url;


/**
 * AttachmentController controller
 */
class RuleRiskController extends BaseController
{


    /**
     * @return string
     * @name 数据分析 -评分设置/actionScoreSetting
     */
    public function actionSettingRisk()
    {

        $where = [];
        $query = SpeaialRuleValue::find()->where($where)->orderBy('id desc');
        $countquery = clone $query;
        $pages = new Pagination(['totalCount' => $countquery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('setting-risk', array(
            'data' => $data,
            'pages' => $pages,

        ));
    }

    /**
     * @return string
     * @name 数据分析 -评分树数据/actionGetUserScore
     */
    public function actionGetRuleValue()
    {
        $rule_ids = $this->request->post('rule_ids');
        if($rule_ids){
           if(!strpos($rule_ids,',')){
               $rule_ids = $rule_ids.',';
           }

        }else{
            throw new Exception('规则ID不能为空');
        }
        $file = UploadedFile::getInstanceByName('attach');
        $order_file = UploadedFile::getInstanceByName('order');
//        if(!$file && !$order_file){
//            throw new Exception('文件不能为空');
//        }

        if ($file) {
            $result = $this->uploadFile($file, $rule_ids,1);
        }
        if ($order_file) {
            $result = $this->uploadFile($order_file, $rule_ids,2);
        }

        $str = json_encode($result);
        $r = RedisQueue::push([SpeaialRuleValue::LIST_GET_RULE_RUN_VALUE, $str]);
        if ($r) {
            return $this->redirectMessage('任务生成成功，正在处理中，请耐心等待', self::MSG_SUCCESS);
        } else {
            return $this->redirectMessage('任务生成失败', self::MSG_ERROR);
            }

    }
    /**
     * @return string
     * @name 下载/actionGetUserScore
     */
    public function actionDownload(){
        $set_id = $this->request->get('id');
        $path = Yii::getAlias('@backend/web/admin_resource/rule_risk_result');
        $path = $path."/";
        $filepath =  $path.'get_rule_value'.'_'.$set_id.'.txt';
        $mime = 'application/force-download';
        header('Pragma: public'); // required
        header('Expires: 0'); // no cache
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private',false);
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.basename($filepath).'"');
        header('Connection: close');
        ob_clean();
        flush();
        readfile($filepath); // push it out
        exit();

    }
    public function uploadFile($file,$rule_ids,$type =null){
        $fileExtension = $file->extension;
        $validator = new FileValidator();
        $Extensions = ['txt','xlsx','csv'];

        $validator->extensions = $Extensions;
        if(!in_array($fileExtension, $Extensions)){
            $validator->maxSize = 1024 * 1024;
        }
        $validator->checkExtensionByMimeType = false;
        $error = '';
        if (!$validator->validate($file, $error)) {
            return $this->redirectMessage('文件不符合要求：' . $error, self::MSG_ERROR);
        }

        $path = Yii::getAlias('@backend/web/admin_resource/rule_risk') ;

        $path = $path."/";
        $time = date('Y-m-d',time());
        $path = $path.$time."/";
        if(!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $file_extend = explode(".",$file->name);
        $file_extend = $file_extend[count($file_extend)-1];
        $file_name = time().".".$file_extend;
        $ret = move_uploaded_file($file->tempName, $path.$file_name);
        $new_arr = [];
        if ($ret) {
        $content = file_get_contents($path . '/' . $file_name);
        $model = new SpeaialRuleValue();
        $model->rule_ids = $rule_ids;
        $model->status = 0;
        $model->save();
        $set_id = $model->id;
         if($type == 1){
             $new_arr = [
                 'set_id'=>$set_id,
                 'user_ids'=>$content,
                 'rule_ids'=>$rule_ids
             ];
         }else{
             $new_arr = [
                 'set_id'=>$set_id,
                 'order_ids'=>$content,
                 'rule_ids'=>$rule_ids
             ];
         }

        }
        return $new_arr;

    }


}