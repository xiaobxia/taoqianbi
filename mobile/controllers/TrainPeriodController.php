<?php
/**
 * Created by PhpStorm.
 * User: kevin
 * Date: 2016/3/14
 * Time: 14:51
 */

namespace mobile\controllers;

use common\models\CreditZmop;
use common\models\LoanRecordPeriod;
use common\models\LoanRepayment;
use Yii;
use common\api\umpay\sdk_new\RSACryptUtil;
use common\models\LoanPerson;
use common\models\LoanPersonInfo;
use common\models\Shop;
use common\services\UserService;
use yii\base\Exception;
use yii\base\UserException;
use yii\filters\AccessControl;
use backend\models\AdminUser;
use common\models\User;
use common\services\loanService;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\helpers\Url;
use common\models\College;
use common\models\Province;
use common\models\City;
use common\models\Area;
use common\models\Goods;
use common\models\TrainOrders;
use common\models\CreditJxl;
use common\models\CreditJxlSmsLog;
use common\services\ZmopService;
use yii\validators\FileValidator;
use common\helpers\StringHelper;
use common\helpers\MessageHelper;
use common\models\LoanPersonInfoImage;
use yii\data\Pagination;
use yii\db\Query;
use common\models\LoanRepaymentPeriod;


// require_once Yii::getAlias('@common/api/oss') . '/sdk.class.php';

class TrainPeriodController extends  BaseController
{

    public $bucket = 'kd-attach';

    public $ossService;

    public function init()
    {
//         require_once Yii::getAlias('@common/api/oss') . '/sdk_xjk.class.php';
    	require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';
        $this->ossService = new \ALIOSS();
        $this->ossService->set_debug_mode(true);
        $this->bucket = DEFAULT_OSS_BUCKET;
        
        parent::init();
        // other init
        //指定跳app登录

    }
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                // 除了下面的action其他都需要登录
                'except' => [ 'index','reg-get-code'],//,'page-live','saveaddress'
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionUploadPicture()
    {
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$person_id)->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }
        if($is_save){
            return [
                'code'=>-100,
                'message'=>'信息已经提交，不能修改',
            ];
        }
        $ret = UserService::getInfo();
        if(isset($ret['loan_person']['id']))
        {
            $loan_person_id = $ret['loan_person']['id'];
        }
        else
        {
            return [
                'code'=>-1,
                'message'=>'获取用户数据失败'
            ];
        }
        $image_one_file = UploadedFile::getInstanceByName('head-img1');
        $image_two_file = UploadedFile::getInstanceByName('head-img2');
        $image_three_file = UploadedFile::getInstanceByName('head-img3');
        $image_four_file = UploadedFile::getInstanceByName('head-img4');
        $file_all= array();
        if($image_one_file)
        {
            $file_all[1] = $image_one_file;
        }
        if($image_two_file)
        {
            $file_all[2] = $image_two_file;
        }
        if($image_three_file)
        {
            $file_all[3] = $image_three_file;
        }
        if($image_four_file)
        {
            $file_all[4] = $image_four_file;
        }

        $data_url = array();
        $count_source = count($file_all);
        $count_now = 0;

        foreach($file_all as $key=>$file)
        {
            $validator = new FileValidator();
            $validator->extensions = ['jpg', 'jpeg', 'JPG', 'JPEG', 'png', 'PNG', 'gif', 'GIF'];
            $validator->maxSize = 2 * 1024 * 1024;
            $validator->checkExtensionByMimeType = false;
            if (!$validator->validate($file, $error)) {
                continue;
            }
            $charid = strtoupper(md5(uniqid(mt_rand(), true)));
            $filename = substr($charid, 7, 13);
            $object = 'train_period'.'/'.$loan_person_id.'/'.$filename.'.'.$file->extension;
            $filename_extension = $filename.'.'.$file->extension;
            $file_path = $file->tempName;

            $response = $this->ossService->upload_file_by_file($this->bucket, $object, $file_path);

            if ($response->isOK()) {
                $file_url = 'http://res.kdqugou.com/'.$object;

                //存数据库

                if(isset(LoanPersonInfoImage::$card_list_type[$key])){
                    $loan_person_info_image = LoanPersonInfoImage::find()->where(' loan_person_id='.$loan_person_id." and type=".$key)->one();
                    if(empty($loan_person_info_image)){
                        $loan_person_info_image = new LoanPersonInfoImage();
                    }
                    $loan_person_info_image->loan_person_id = $loan_person_id;
                    $loan_person_info_image->type=$key;
                    $loan_person_info_image->status = LoanPersonInfoImage::STATUS_NORMAL;
                    $loan_person_info_image->url = $file_url;
                    if($loan_person_info_image->save()){
                        $count_now++;
                    }

                }else{
                    continue;
                }

//                $data_url[$key]= array(
//                    'type'=>$key,
//                    'url'=>$file_url
//                );
//                $count_now++;
            } else {
                continue;
            }
        }



        if($count_source == $count_now)
        {
            //更新is_save字段
            $loan_person_info = LoanPersonInfo::find()->where(' loan_person_id='.$loan_person_id)->one();
            if(!empty($loan_person_info)){
                $loan_person_info->is_save=1;
                $loan_person_info->save();
            }
            return ['code'=>0,'message'=>"信息保存成功，请等待审核",'url'=>Url::toRoute("train-period/page-personal-center")];
        }

        return ['code'=>-2,'message'=>"上传失败"];

    }
    /* -------------------------- 页面渲染 ---------------------------------------- */
    /**
     * @return array
     * @throws \Exception
     * 发送聚信立短信授权
     */
//    public function actionJxlSms(){
//        $this->response->format = Response::FORMAT_JSON;
//        $person_id = $this->_getPersonId();
//        $loanperson = LoanPerson::findOne($person_id);
//        $sms_limit = 20;    //短信每日限制次数
//        $sms_interval = 55; //发送短信间隔
//        $phone = $loanperson->phone;    //获取借款人手机号
//        $content = '请尽快在 http://wechat.juxinli.com/#/XiaoYuJinFu/apply 完成聚信立的信用评测。';    //短信内容
//        $current_time = time();
//        $today = strtotime('today');
//        $count = CreditJxlSmsLog::find()->where(['loan_person_id'=>$person_id,'send_status'=>1])->andWhere(['>','send_time',$today])->count();   //统计借款人当日发送短信数量
//        if( ! is_null($count)){
//            if($count > $sms_limit){
//                Yii::info(" method:".__METHOD__." line:".__LINE__.'loan_person_id:'.$person_id.'message:'."超出当日短信发送次数");
//                return $this->messageError('jxl','超过当日短信发送次数');
//            }
//        }
//        $log = CreditJxlSmsLog::find()->where(['loan_person_id'=>$person_id])->orderBy('id desc')->one();
//        if( ! is_null($log)){
//            $send_time = $log->send_time;
//            if(($current_time - $send_time) < $sms_interval){
//                Yii::info(" method:".__METHOD__." line:".__LINE__.'loan_person_id:'.$person_id.'message:'."短信发送间隔过短");
//                return $this->messageError('jxl','短信发送失败');
//            }
//        }
//        // $result = MessageHelper::sendSMS($phone,$content,'smsService8');
//        $result = MessageHelper::sendSMS($phone, $content, 'smsServiceXQB_XiAo', $loanperson->source_id);
//        $smslog = new CreditJxlSmsLog();
//        $smslog->loan_person_id = $person_id;
//        $smslog->send_status = $result ? 1 : 2;
//        $smslog->send_time = time();
//        $smslog->save();
//        if($result){
//            return $this->messageSuccess('jxl','短信发送成功');
//        }else{
//            return $this->messageError('jxl','短信发送失败');
//        }
//    }

    public function actionPageApply(){
        $person_id = $this->_getPersonId();
        $trainOrders = TrainOrders::find()->where(['loan_person_id'=>$person_id])->with([
            'shop' => function($query){
                $query->select([
                    'province_id','province','city_id','city','area_id','area','shop_name'
                ]);
            },
            'goods' => function($query){
                $query->select([
                    'name','price','period'
                ]);
            }
        ])->one();
        $school = '';
        $course = '';
        if($trainOrders){
            $school = $this->getshopbyarea($trainOrders['shop']['area_id']);
            $school = json_encode($school);
            $course = $this->getgoodsbyshop($trainOrders['shop_id']);
            $course = json_encode($course);
        }
        $this->view->title = '申请分期';
        $pca = $this->getProvinceCityArea();

        return $this->renderPartial('apply-info',[
            'pca' => $pca,
            'trainOrders' => $trainOrders,
            'school' => $school,
            'course' => $course
        ]);
    }

    public function actionPagePerson(){

        $this->view->title = '个人信息';
        $marital = array();
		
        foreach(LoanPersonInfo::$marital_status_list as $key=>$item)
        {
            $marital[] = array(
                    'value'=>$key,
                    'text'=>$item
            );
        }
        $marital = json_encode($marital);

        $degree = array();
        foreach(LoanPersonInfo::$degree_list as $key=>$item)
        {
            $degree[] = array(
                'value'=>$key,
                'text'=>$item
            );
        }
        $degree = json_encode($degree);

        $identity = array();
        foreach(LoanPersonInfo::$identity_list as $key=>$item)
        {
            $identity[] = array(
                'value'=>$key,
                'text'=>$item
            );
        }
        $identity = json_encode($identity);

        $college = self::getProvinceScholl();
        $ret = UserService::getInfo();
        $loan_person_id = 0;
        if(isset($ret['loan_person']['id']))
        {
            $loan_person_id = $ret['loan_person']['id'];
        }

        //填充已经填写的信息
        $loan_person = LoanPerson::find()->select(['id','name','id_number','is_verify'])->where(" id=".$loan_person_id)->one();
        if(empty($loan_person))
        {
            //表示没有该用户
            Yii::error(" method:".__METHOD__." line:".__LINE__."借款人不存在");
            return;
        }
        $realname = isset($loan_person['name'])?$loan_person['name']:"";
        $id_number = isset($loan_person['id_number'])?$loan_person['id_number']:"";
        $is_verify = isset($loan_person['is_verify'])?$loan_person['is_verify']:0;


        $loan_person_info = LoanPersonInfo::find()->where(" loan_person_id=".$loan_person_id)->one();
        $qq = isset($loan_person_info['qq'])?$loan_person_info['qq']:"";
        $marital_status = isset($loan_person_info['marital_status'])?$loan_person_info['marital_status']:0;

        if(empty($marital_status))
        {
            $marital_status_text = "";
        }
        else
        {
            $marital_status_text = isset(LoanPersonInfo::$marital_status_list[$loan_person_info['marital_status']])?LoanPersonInfo::$marital_status_list[$loan_person_info['marital_status']]:"";
        }

        $_degree = isset($loan_person_info['degree'])?$loan_person_info['degree']:0;

        if(empty($_degree))
        {
            $degree_text = "";
        }
        else
        {
            $degree_text = isset(LoanPersonInfo::$degree_list[$loan_person_info['degree']])?LoanPersonInfo::$degree_list[$loan_person_info['degree']]:"";
        }

        $job_status = isset($loan_person_info['job_status'])?$loan_person_info['job_status']:0;
        if(empty($job_status))
        {
            $job_status_text = "";
        }
        else
        {
            $job_status_text = isset(LoanPersonInfo::$identity_list[$loan_person_info['job_status']])?LoanPersonInfo::$identity_list[$loan_person_info['job_status']]:"";
        }
        $school_area = 0;
        $school_id = 0;
        $school_name = "";
        $school_district_text="";
        if(!empty($_degree))
        {
            if(LoanPersonInfo::DEGREE_COLLEGE>$_degree)
            {
                //高中以及以下
                $school_name = isset($loan_person_info['school_name'])?$loan_person_info['school_name']:"";
                $school_district_text = isset($loan_person_info['school_district_text'])?$loan_person_info['school_district_text']:"";
            }
            else
            {
                $school_area = isset($loan_person_info['school_area'])?isset($loan_person_info['school_area']):"0";
                $school_id = isset($loan_person_info['school_id'])?isset($loan_person_info['school_id']):"0";
                $school_name = isset($loan_person_info['school_name'])?$loan_person_info['school_name']:"";
                $school_district_text = isset($loan_person_info['school_district_text'])?$loan_person_info['school_district_text']:"";
            }
        }

        //判断个人信息是否填写完成
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$loan_person_id)->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }


        return $this->renderPartial('person-info',[
            'marital'=>$marital,
            'degree'=>$degree,
            'identity'=>$identity,
            'college'=>$college,
            'loan_person_id'=>$loan_person_id,
            'realname'=>$realname,
            'id_number'=>$id_number,
            'qq'=>$qq,
            'marital_status'=>$marital_status,
            'marital_status_text'=>$marital_status_text,
            '_degree'=>$_degree,
            'degree_text'=>$degree_text,
            'job_status'=>$job_status,
            'job_status_text'=>$job_status_text,
            'school_area'=>$school_area,
            'school_id'=>$school_id,
            'school_name'=>$school_name,
            'school_district_text'=>$school_district_text,
            'is_verify'=>$is_verify,
            'is_save'=>$is_save
        ]);
    }

    public function actionPageLive(){
        $this->view->title = '居住信息';
        $pca = $this->getProvinceCityArea();
        $ret = UserService::getInfo();
        $loan_person_id = 0;
        $show_already_data = array(
            'present_province'=>0,
            'present_city'=>0,
            'present_area'=>0,
            'present_district_text'=>"",
            'present_address'=>"",
            'family_province'=>0,
            'family_city'=>0,
            'family_area'=>0,
            'family_district_text'=>"",
            'family_address'=>"",
        );
        if(isset($ret['loan_person']['id']))
        {
            $loan_person_id = $ret['loan_person']['id'];
            $loan_person_info = LoanPersonInfo::find()->where('loan_person_id='.$loan_person_id)->one();
            if(!empty($loan_person_info))
            {
                $show_already_data['present_province'] = $loan_person_info['present_province'];
                $show_already_data['present_city'] = $loan_person_info['present_city'];
                $show_already_data['present_area'] = $loan_person_info['present_area'];
                $show_already_data['present_district_text'] = $loan_person_info['present_district_text'];
                $show_already_data['present_address'] = $loan_person_info['present_address'];
                $show_already_data['family_province'] = $loan_person_info['family_province'];
                $show_already_data['family_city'] = $loan_person_info['family_city'];
                $show_already_data['family_area'] = $loan_person_info['family_area'];
                $show_already_data['family_district_text'] = $loan_person_info['family_district_text'];
                $show_already_data['family_address'] = $loan_person_info['family_address'];
            }
        }

        //获取是否用户已经填写了相关信息


        return $this->renderPartial('live-info',[
            'pca' => $pca,
            'loan_person_id'=>$loan_person_id,
            'show_already_data'=>json_encode($show_already_data)
        ]);
    }

    public function actionPageJob(){
        $person_id = $this->_getPersonId();
        $loanPersoninfo = LoanPersonInfo::find()->select([
            'company_name','company_address','company_phone','company_area','company_city','company_province','company_pca'
        ])->where(['loan_person_id'=>$person_id])->asArray()->one();
        $pca = $this->getProvinceCityArea();
        $this->view->title = '工作信息';
        return $this->renderPartial('job-info',[
            'pca' => $pca,
            'loanPersoninfo' => $loanPersoninfo,
        ]);
    }

    public function actionPageContact(){
        $this->view->title = '联系人信息';
        return $this->renderPartial('contact-info');
    }

    public function actionPageContactAdd(){
        $this->view->title = '添加联系人';

        $contact_list = array();
        foreach(LoanPersonInfo::$contact_list as $key=>$item)
        {
            $contact_list[] = array(
                'value'=>$key,
                'text'=>$item
            );
        }
        $contact_list = json_encode($contact_list);

        $ret = UserService::getInfo();
        $loan_person_id = 0;
        if(isset($ret['loan_person']['id']))
        {
            $loan_person_id = $ret['loan_person']['id'];
        }

        //获取已经填写的资料
        $loan_person_info = LoanPersonInfo::find()->where(' loan_person_id='.$loan_person_id)->one();
        $first_contact_relation=0;
        $first_contact_name="";
        $first_contact_phone="";
        $second_contact_relation=0;
        $second_contact_name="";
        $second_contact_phone="";
        $third_contact_relation=0;
        $third_contact_name="";
        $third_contact_phone="";
        $first_contact_relation_result="";
        $second_contact_relation_result="";
        $third_contact_relation_result="";


        if((false == $loan_person_info)||empty($loan_person_info)){
            $loan_person_info = array();
        }else{
            $first_contact_relation=$loan_person_info['first_contact_relation'];
            $first_contact_name=$loan_person_info['first_contact_name'];
            $first_contact_phone=$loan_person_info['first_contact_phone'];
            $first_contact_relation_result = empty($first_contact_relation) ? '' : LoanPersonInfo::$contact_list[$first_contact_relation];

            $second_contact_relation=$loan_person_info['second_contact_relation'];
            $second_contact_name=$loan_person_info['second_contact_name'];
            $second_contact_phone=$loan_person_info['second_contact_phone'];
            $second_contact_relation_result = empty($second_contact_relation) ? '' : LoanPersonInfo::$contact_list[$second_contact_relation];

            $third_contact_relation=$loan_person_info['third_contact_relation'];
            $third_contact_name=$loan_person_info['third_contact_name'];
            $third_contact_phone=$loan_person_info['third_contact_phone'];
            $third_contact_relation_result = empty($third_contact_relation) ? '' : LoanPersonInfo::$contact_list[$third_contact_relation];
        }


        return $this->renderPartial('contact-add-info',[
            'contact_list'=>$contact_list,
            'loan_person_id'=>$loan_person_id,
            'first_contact_relation'=>$first_contact_relation,
            'first_contact_name'=>$first_contact_name,
            'first_contact_phone'=>$first_contact_phone,
            'second_contact_relation'=>$second_contact_relation,
            'second_contact_name'=>$second_contact_name,
            'second_contact_phone'=>$second_contact_phone,
            'third_contact_relation'=>$third_contact_relation,
            'third_contact_name'=>$third_contact_name,
            'third_contact_phone'=>$third_contact_phone,
            'first_contact_relation_result'=>$first_contact_relation_result,
            'second_contact_relation_result'=>$second_contact_relation_result,
            'third_contact_relation_result'=>$third_contact_relation_result,
        ]);
    }

    public function actionPageTrust(){
        $this->view->title = '信用信息';
        $person_id = $this->_getPersonId();
        $loanperson = LoanPerson::findOne($person_id);
        if($loanperson->is_verify != 1){
            return $this->redirect(['train-period/page-person']);
        }
        $zmop = CreditZmop::gainCreditZmopLatest(['person_id'=>$person_id,'status'=>1]);
        $zmop_judge = $zmop ? true : false;
        $jxl = CreditJxl::findLatestOne(['person_id'=>$person_id,'status'=>1]);
        $jxl_judge = $jxl ? true : false;
        return $this->renderPartial('trust-info',[
            'zmop_judge' => $zmop_judge,
            'jxl_judge' => $jxl_judge,
        ]);
    }

    public function actionPagePersonalCenter(){

        $this->view->title = '个人中心';
        //获取用户姓名
        $ret = UserService::getInfo();

        $name = "";
        if(isset($ret['loan_person']['name']))
        {
            $name = $ret['loan_person']['name'];
        }

       // $url = Url::toRoute("train-period/page-person");
        //判断个人信息是否填写完成
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$ret['loan_person']['id'])->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }
        if($is_save){
            $person_url = Url::toRoute("train-period/page-personal-data");
        }else{
            $person_url = Url::toRoute("train-period/page-person");
        }

        return $this->renderPartial('personal-center',[
            'name'=>$name,
            'person_url'=>$person_url
        ]);
    }
    public function actionPagePersonalRepay(){
        $loan_person = $this->_getPersonId();
        $repayinglist = LoanRepaymentPeriod::find()->where([
            'tb_loan_repayment_period.loan_person_id'=>$loan_person,
            'tb_loan_repayment_period.status' => [LoanRepaymentPeriod::STATUS_REPAYING],
            'source' => LoanRecordPeriod::SOURCE_KDKJ,
        ])->andWhere([
            '>','plan_repayment_time',time()
        ])->andWhere([
            '<','plan_repayment_time',strtotime(date('Y-m-d 23:59:59',time()).'+1 month')
        ])->joinWith([
            'loanRecordPeriod' => function($query){}
        ])->asArray()->all();
        $delayedlist = LoanRepaymentPeriod::find()->where([
            'tb_loan_repayment_period.loan_person_id'=>$loan_person,
            'tb_loan_repayment_period.status' => [LoanRepaymentPeriod::STATUS_DELAYED],
            'source' => LoanRecordPeriod::SOURCE_KDKJ,
        ])->joinWith([
            'loanRecordPeriod' => function($query){}
        ])->asArray()->all();
        $delayed_list = [];
        $repaying_list = [];
        $repayment_status = [
            LoanRepaymentPeriod::STATUS_REPAYING => '待还款',
            LoanRepaymentPeriod::STATUS_DELAYED => '已逾期',
        ];
        if(!is_null($repayinglist)){
            foreach($repayinglist as $value){
                $repaying_list[] = [
                    'repayment_money' => StringHelper::safeConvertIntToCent($value['plan_repayment_money']).'元',
                    'repayment_time' => '距离还款日 '. floor(($value['plan_repayment_time'] - time())/86400) . '天',
                    'detail' => "[{$value['period']}/{$value['loanRecordPeriod']['period']}期] {$value['loanRecordPeriod']['product_type_name']} ".$repayment_status[$value['status']],
                ];
            }
        }
        if(!is_null($delayedlist)){
            foreach($delayedlist as $value){
                $delayed_list[] = [
                    'repayment_money' => StringHelper::safeConvertIntToCent($value['plan_repayment_money']).'元',
                    'repayment_time' => '还款日 '.date('Y-m-d',$value['plan_repayment_time']),
                    'detail' => "[{$value['period']}/{$value['loanRecordPeriod']['period']}期] {$value['loanRecordPeriod']['product_type_name']} ".$repayment_status[$value['status']],
                ];
            }
        }
        $this->view->title = '立即还款';
        return $this->renderPartial('personal-repay',[
            'delayed_list' => $delayed_list,
            'repaying_list' => $repaying_list,
        ]);
    }
    public function actionPagePersonalLoan(){
        $this->view->title = '我的贷款';
		
        $person_id = $this->_getPersonId();
		
        $condition = " loan_person_id=".$person_id." and status not in(3,7,10,14,16) "." and source=".LoanRecordPeriod::SOURCE_KDKJ;


        $query = LoanRecordPeriod::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);
        $loan_record_list = $query->with([
            'loanPerson' => function(Query $query) {
                $query->select(['id', 'name', 'phone']);
            },
            'loanProject' => function(Query $query) {
                $query->select(['id', 'loan_project_name']);
            },
            'loanRepayment'=>function(Query $query){
                $query->select(['id','repayment_amount','period_repayment_amount',
                    'repaymented_amount','next_period_repayment_id','status','period']);
            },
        ])->asArray()->all();
		

        $total_money=0;
        foreach ($loan_record_list as &$item) {
            //查询还款明细

            $loanRepaymentPeriod = array(
                'remaining_period'=>0,
                'late_period'=>0,
                'plan_repayment_time'=>''
            );
            $total_money+=$item['amount'];

            if(isset($item['loanRepayment']['id'])){
                $ret = LoanRepaymentPeriod::find()->select([
                    'id', 'repayment_id','period','plan_repayment_money',
                    'plan_repayment_time','plan_next_repayment_time','plan_will_repayment_amount','true_repayment_money',
                    'true_repayment_time','status','plan_repayment_principal','plan_repayment_interest',
                    'true_repayment_principal','true_repayment_interest'
                ])->where(' repayment_id='.$item['loanRepayment']['id']." order by id asc")->asArray()->all();

                $is_first = 1;

                foreach($ret as $_ret){
                    if(LoanRepaymentPeriod::STATUS_REPAYED !=$_ret['status']){
                        $loanRepaymentPeriod['remaining_period']++;
                    }
                    if(LoanRepaymentPeriod::STATUS_DELAYED == $_ret['status']){
                        $loanRepaymentPeriod['late_period']++;
                    }
                    if(LoanRepaymentPeriod::STATUS_REPAYED != $_ret['status']){
                        if(1 == $is_first){
                            $loanRepaymentPeriod['plan_repayment_time'] = date('Y-m-d',$_ret['plan_repayment_time']);
                            $is_first++;
                        }

                    }

                }
                $item['loanRepaymentPeriod']=$loanRepaymentPeriod;

            }

       }

        return $this->renderPartial('personal-loan',
            [
                'loan_record_list'=>$loan_record_list,
                'total_money'=>$total_money
            ]);
    }
    public function actionPagePersonalData(){
        $this->view->title = '我的资料';

        $ret = UserService::getInfo();
        $loan_person = $ret['loan_person'];
        $data = array();
        if(isset($loan_person['name'])){
            $data['name'] = $loan_person['name'];
        }else{
            $data['name']="";
        }
        if(isset($loan_person['id_number'])){
            $data['id_number'] =  substr($loan_person['id_number'],0,6).'********'.substr($loan_person['id_number'],-4,4);
        }else{
            $data['id_number']="";
        }

        $loan_person_info = LoanPersonInfo::find()->where(' loan_person_id='.$loan_person['id'])->one();
        if(empty($loan_person_info)){
            $data['qq']= "";
            $data['marital_status']="";
            $data['degree']="";
            $data['job_status']="";
            $data['present_district_text']="";
            $data['present_address']="";
            $data['family_district_text']="";
            $data['family_address']="";
            $data['company_name']="";
            $data['company_pca']="";
            $data['company_address']="";
            $data['company_phone']="";

            $data['first_contact_name']="";
            $data['first_contact_phone']="";
            $data['first_contact_relation']="";
            $data['second_contact_name']="";
            $data['second_contact_phone']="";
            $data['second_contact_relation']="";
            $data['third_contact_name']="";
            $data['third_contact_phone']="";
            $data['third_contact_relation']="";

        }else{
            $data['qq']= $loan_person_info['qq'];
            $data['marital_status'] = isset(LoanPersonInfo::$marital_status_list[$loan_person_info['marital_status']])?LoanPersonInfo::$marital_status_list[$loan_person_info['marital_status']]:"";
            $data['degree'] = isset(LoanPersonInfo::$degree_list[$loan_person_info['degree']])?LoanPersonInfo::$degree_list[$loan_person_info['degree']]:"";
            $data['job_status'] = isset(LoanPersonInfo::$identity_list[$loan_person_info['job_status']])?LoanPersonInfo::$identity_list[$loan_person_info['job_status']]:"";
            $data['present_district_text'] = $loan_person_info['present_district_text'];
            $data['present_address'] = $loan_person_info['present_address'];
            $data['family_district_text'] = $loan_person_info['family_district_text'];
            $data['family_address'] = $loan_person_info['family_address'];
            $data['company_name'] = $loan_person_info['company_name'];
            $data['company_pca'] = $loan_person_info['company_pca'];
            $data['company_address'] = $loan_person_info['company_address'];
            $data['company_phone'] = $loan_person_info['company_phone'];

            $data['first_contact_name'] = $loan_person_info['first_contact_name'];
            $data['first_contact_phone'] = $loan_person_info['first_contact_phone'];
            $data['first_contact_relation'] = isset(LoanPersonInfo::$contact_list[$loan_person_info['first_contact_relation']])?LoanPersonInfo::$contact_list[$loan_person_info['first_contact_relation']]:"";
            $data['second_contact_name'] = $loan_person_info['second_contact_name'];
            $data['second_contact_phone'] = $loan_person_info['second_contact_phone'];
            $data['second_contact_relation'] = isset(LoanPersonInfo::$contact_list[$loan_person_info['second_contact_relation']])?LoanPersonInfo::$contact_list[$loan_person_info['second_contact_relation']]:"";
            $data['third_contact_name'] = $loan_person_info['third_contact_name'];
            $data['third_contact_phone'] = $loan_person_info['third_contact_phone'];
            $data['third_contact_relation'] = isset(LoanPersonInfo::$contact_list[$loan_person_info['third_contact_relation']])?LoanPersonInfo::$contact_list[$loan_person_info['third_contact_relation']]:"";



        }
        //id_number $marital_status_list $degree_list  $identity_list


        return $this->renderPartial('personal-data',[
            'data'=>$data
        ]);
    }
    public function actionPagePersonalNews(){
        $this->view->title = '消息通知';
        return $this->renderPartial('personal-news');
    }

    public function getInfo(){
        $fine =Yii::$app->user->identity;
        $data = array();
        if($fine)
        {
            $data['user_info'] = $fine;
            $phone = $fine['phone'];
            $ret = LoanPerson::findByPhone($phone);
            if($ret)
            {
                $data['loan_person'] = $ret;

                return $data;
            }
            else
            {
                return NULL;
            }

        }
        else
        {
            return NULL;
        }
    }

    public function actionPagePhoto(){
        $this->view->title = '拍照验证';
        $loan_person_id=0;
        $ret = $this->getInfo();
        if(isset($ret['loan_person']['id']))
        {
            $loan_person_id = $ret['loan_person']['id'];
        }else{
            return ['code'=>-1,'message'=>'获取用户数据失败'];
        }
        $loan_person_info_image = LoanPersonInfoImage::find()->where(' loan_person_id='.$loan_person_id)->all();
        $image_one = "";
        $image_two = "";
        $image_three = "";
        $image_four = "";
        foreach($loan_person_info_image as $item){
            switch($item['type']){
                case LoanPersonInfoImage::ID_CARD_POST:
                    $image_one=$item['url'];
                    break;
                case LoanPersonInfoImage::ID_CARD_OPPOS:
                    $image_two = $item['url'];
                    break;
                case LoanPersonInfoImage::ID_CARD_GROUP:
                    $image_three = $item['url'];
                    break;
                case LoanPersonInfoImage::EMPLOYEE_CARD:
                    $image_four = $item['url'];
                    break;
                default:
                    break;
            }
        }

        return $this->renderPartial('photo-info',[
            'image_one'=>$image_one,
            'image_two'=>$image_two,
            'image_three'=>$image_three,
            'image_four'=>$image_four
        ]);
    }


    /* -------------------------- 数据接口 ---------------------------------------- */

    /**
     * @return array
     * @throws \Exception
     * 芝麻信用短信授权
     */
    public function actionZmopSms()
    {
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $loanperson = LoanPerson::findOne(['id' => $person_id, 'is_verify' => 1]);
        if (is_null($loanperson)) {
            return $this->messageError('', '未通过实名认证');
        }
        $name = $loanperson->name;
        $id_number = $loanperson->id_number;
        $phone = $loanperson->phone;
        $zmopService = new ZmopService();
		$person_id = strval($person_id);

        $result = $zmopService->batchFeedback($name, $id_number, $phone, $person_id);
        if ($result['success'] == true && $result['biz_success'] == true) {
            return $this->messageSuccess('msm','授权短信已发送');
        } else {
            return $this->messageError('msm',$result['error_message']);
        }
    }
    public function actionSetTrainorders(){
        $this->response->format = Response::FORMAT_JSON;
        $person_info = UserService::getInfo();
        $uid = $person_info['user_info']['id'];
        $person_id = $this->_getPersonId();
        $loanperson = LoanPerson::findOne(['id'=>$person_id]);

        if(is_null($loanperson)){
            throw new \Exception('借款人不存在');
        }
        //查询该用户是否有在申请中

        $loan_recod = LoanRecordPeriod::find()->where(' user_id='.$uid.' and loan_person_id='.$person_id." and source=".LoanRecordPeriod::SOURCE_KDKJ)->asArray()->all();
		
        $count_total = count($loan_recod);
        foreach($loan_recod as $item){
            if(LoanRecordPeriod::STATUS_APPLY_REPAY_SUCCESS == $item['status']){
                $count_total--;
            }
        }
        if($count_total){
            return ['code'=>-100,'message'=>'你存在未完结的借款，请先处理'];
        }
		
        $shop_id = intval($this->request->post('shop_id'));
        if(empty($shop_id)){
            return $this->messageError('school','请选择培训学校');
        }
        $goods_id = intval($this->request->post('goods_id'));
        if(empty($goods_id)){
            return $this->messageError('goods','请选择报名课程');
        }
        $goods = Goods::find()->where(['id'=>$goods_id,'shop_id'=>$shop_id])->one();
		

        $price = StringHelper::safeConvertCentToInt(intval($this->request->post('price')));
        if($price > $goods->price || $price <= 0){
            return $this->messageError('price','分期金额不能超过'.StringHelper::safeConvertIntToCent($goods->price).'元');
        }
        $period = intval($this->request->post('period'));
        if($period > $goods->period || $period <= 0){
            return $this->messageError('period','期限不能超过'.$goods->period.'个月');
        }
		
        //插入借款人项目记录
        $ret = Yii::$app->db_kdkj->createCommand()->insert(LoanRecordPeriod::tableName(), [
            'user_id' => $uid,
            'loan_person_id' => $person_id,
            'type' => 4,//$item['type'],//项目类型，上线前需要修改
            'loan_project_id' => 10,//14,//10,//$item['loan_project_id'],//项目名称，上线前需要修改
            'shop_id' => $shop_id,
            'repay_type' => LoanRecordPeriod::REPAY_TYPE_DEBX,
            'amount' => $price,
            'apr' => 0,
            'service_apr' => 0,
            'fee_amount' => 0,
            'period' => $period,
            'product_type_name' => $goods['name'],
            'product_id'=>$goods_id,
            'apply_time' => time(),
            'source' => LoanRecordPeriod::SOURCE_KDKJ,
            'status' => LoanRecordPeriod::STATUS_APPLY_TRIAL_APPLY,
            'created_at' => time(),
            'remark' => ''
        ])->execute();	
		
        if(false == $ret){
            return ['code'=>-100,'message'=>'申请失败，请稍后再试'];
        }

        //$train_order->save();
        //判断个人信息是否填写完成
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$person_id)->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }
        if($is_save){
            $url = Url::toRoute("train-period/page-personal-center");
        }else{
            $url = Url::toRoute("train-period/page-person");
        }

        return [
            'code' => 0,
            'message' => "success",
            'url'=>$url,
            'data' => ''
        ];
    }
    /**
     * 根据借款人获取居住地址
     * @return array
     * @throws \Exception
     */
    public function actionGetPresentaddress()
    {
        $this->response->format = Response::FORMAT_JSON;
        if(NULL == $this->request->post('loan_person_id'))
        {
            throw new \Exception('参数丢失');
        }
        else
        {
            $loan_person_id = $this->request->post('loan_person_id');
        }

        $loan_person_info = LoanPersonInfo::find()->select(['id','loan_person_id','present_province','present_city','present_area','present_address'])->where('loan_person_id='.$loan_person_id)->asArray()->one();

        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$loan_person_info
        ];

    }


    public function actionSavecontactinfo()
    {
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$person_id)->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }
        if($is_save){
            return [
                'code'=>-100,
                'message'=>'信息已经提交，不能修改',
            ];
        }
        //第一联系人
        if(NULL == $this->request->post('first_contact_relation'))
        {
            return [
                'code'=>-1,
                'message'=>'请选择关系',
            ];
        }
        else
        {
            $first_contact_relation = $this->request->post('first_contact_relation');
            $contact_list = array_keys(LoanPersonInfo::$contact_list);
            if (!in_array($first_contact_relation, $contact_list)) {
                return [
                    'code'=>-1,
                    'message'=>'关系选择有误',
                ];
            }

        }

        if(NULL == $this->request->post('first_contact_name'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写姓名',
            ];
        }
        else
        {
            $first_contact_name = $this->request->post('first_contact_name');
            if( ! $this->checkRealnameValid($first_contact_name) )
            {
                return [
                    'code'=>-1,
                    'message'=>'姓名非法',
                ];
            }

        }

        if(NULL == $this->request->post('first_contact_phone'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写手机号',
            ];
        }
        else
        {
            $first_contact_phone = $this->request->post('first_contact_phone');
            if( ! $this->checkPhoneValid($first_contact_phone) )
            {
                return [
                    'code'=>-1,
                    'message'=>'手机号非法',
                ];
            }

        }

        //第二联系人
        if(NULL == $this->request->post('second_contact_relation'))
        {
            return [
                'code'=>-1,
                'message'=>'请选择关系',
            ];
        }
        else
        {
            $second_contact_relation = $this->request->post('second_contact_relation');
            $contact_list = array_keys(LoanPersonInfo::$contact_list);
            if (!in_array($second_contact_relation, $contact_list)) {
                return [
                    'code'=>-1,
                    'message'=>'关系选择有误',
                ];
            }

        }

        if(NULL == $this->request->post('second_contact_name'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写姓名',
            ];
        }
        else
        {
            $second_contact_name = $this->request->post('second_contact_name');
            if( ! $this->checkRealnameValid($second_contact_name) )
            {
                return [
                    'code'=>-1,
                    'message'=>'姓名非法',
                ];
            }

        }

        if(NULL == $this->request->post('second_contact_phone'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写手机号',
            ];
        }
        else
        {
            $second_contact_phone = $this->request->post('second_contact_phone');
            if( ! $this->checkPhoneValid($second_contact_phone) )
            {
                return [
                    'code'=>-1,
                    'message'=>'手机号非法',
                ];
            }

        }

        //第三联系人

        if(NULL == $this->request->post('third_contact_relation'))
        {
            return [
                'code'=>-1,
                'message'=>'请选择关系',
            ];
        }
        else
        {
            $third_contact_relation = $this->request->post('third_contact_relation');
            $contact_list = array_keys(LoanPersonInfo::$contact_list);
            if (!in_array($third_contact_relation, $contact_list)) {
                return [
                    'code'=>-1,
                    'message'=>'关系选择有误',
                ];
            }

        }

        if(NULL == $this->request->post('third_contact_name'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写姓名',
            ];
        }
        else
        {
            $third_contact_name = $this->request->post('third_contact_name');
            if( ! $this->checkRealnameValid($third_contact_name) )
            {
                return [
                    'code'=>-1,
                    'message'=>'姓名非法',
                ];
            }

        }

        if(NULL == $this->request->post('third_contact_phone'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写手机号',
            ];
        }
        else
        {
            $third_contact_phone = $this->request->post('third_contact_phone');
            if( ! $this->checkPhoneValid($third_contact_phone) )
            {
                return [
                    'code'=>-1,
                    'message'=>'手机号非法',
                ];
            }

        }

        if(NULL == $this->request->post('loan_person_id'))
        {
            return [
                'code'=>-2,
                'message'=>'该用户不存在',
            ];
        }
        else
        {
            $loan_person_id = $this->request->post('loan_person_id');
        }

        $loan_person = LoanPerson::find()->where('id='.$loan_person_id)->one();
        if(empty($loan_person))
        {
            return [
                'code'=>-2,
                'message'=>'该用户不存在',
            ];
        }

        $loan_person_info = LoanPersonInfo::find()->where('loan_person_id='.$loan_person_id)->one();
        if(empty($loan_person_info))
        {
            $loan_person_info = new LoanPersonInfo();
        }

        $loan_person_info->first_contact_relation=$first_contact_relation;
        $loan_person_info->first_contact_name=$first_contact_name;
        $loan_person_info->first_contact_phone=$first_contact_phone;

        $loan_person_info->second_contact_relation=$second_contact_relation;
        $loan_person_info->second_contact_name=$second_contact_name;
        $loan_person_info->second_contact_phone=$second_contact_phone;

        $loan_person_info->third_contact_relation=$third_contact_relation;
        $loan_person_info->third_contact_name=$third_contact_name;
        $loan_person_info->third_contact_phone=$third_contact_phone;

        if($loan_person_info->save())
        {
            return [
                'code'=>0,
                'message'=>'success',
            ];
        }
        else
        {
            return [
                'code'=>-3,
                'message'=>'系统繁忙，请稍后再试',
            ];
        }

    }
    /**
     * 保存个人信息
     * @return array
     */
    public function actionSavepersoninfo()
    {
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$person_id)->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }
        if($is_save){
            return [
                'code'=>-100,
                'message'=>'信息已经提交，不能修改',
            ];
        }
        if(NULL == $this->request->post('realname'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写姓名',
            ];
        }
        else
        {
            $realname = $this->request->post('realname');
            if( ! $this->checkRealnameValid($realname) )
            {
                return [
                    'code'=>-1,
                    'message'=>'姓名非法',
                ];
            }

        }

        if(NULL == $this->request->post('id_number'))
        {
            return [
                'code'=>-1,
                'message'=>'请填写身份证',
            ];
        }
        else
        {
            $id_number = $this->request->post('id_number');
            if( ! $this->checkIdnumberValid($id_number))
            {
                return [
                    'code'=>-1,
                    'message'=>'身份证非法',
                ];
            }
        }

        $qq = $this->request->post('qq');
        if(!empty($qq))
        {
            if(!preg_match("/^[1-9]\d{4,18}$/i",$qq))
            {
                return [
                    'code'=>-1,
                    'message'=>'QQ非法',
                ];
            }
        }


        if("0" == $this->request->post('marital_status'))
        {
            return [
                'code'=>-1,
                'message'=>'请选择婚姻状况',
            ];
        }
        else
        {
            $marital_status = $this->request->post('marital_status');
            $marital_status_rule = array_keys(LoanPersonInfo::$marital_status_list);
            if( ! in_array($marital_status,$marital_status_rule))
            {
                return [
                    'code'=>-1,
                    'message'=>'婚姻状况选择有误',
                ];
            }
        }

        if("0" == $this->request->post('degree'))
        {
            return [
                'code'=>-1,
                'message'=>'请选择学历',
            ];
        }
        else
        {
            $degree = $this->request->post('degree');       //学历
            $degree_rule = array_keys(LoanPersonInfo::$degree_list);
            if (!in_array($degree, $degree_rule)) {
                return [
                    'code'=>-1,
                    'message'=>'学历选择有误',
                ];
            }
        }

        if("0" == $this->request->post('job_status'))
        {
            return [
                'code'=>-1,
                'message'=>'请选择就职状态',
            ];
        }
        else
        {
            $job_status = $this->request->post('job_status');
            $job_status_rule = array_keys(LoanPersonInfo::$identity_list);
            if( ! in_array($job_status,$job_status_rule)){
                return [
                    'code'=>-1,
                    'message'=>'就职状态选择有误',
                ];
            }

        }

        if(LoanPersonInfo::DEGREE_COLLEGE > $degree)
        {
            //高中及以下
            if(NULL == $this->request->post('school_name'))
            {
                return [
                    'code'=>-1,
                    'message'=>'请输入毕业院校',
                ];
            }
            $school_area = 0;
            $school_id = 0;
            $school_name = $this->request->post('school_name');
            $school_district_text = $this->request->post('school_district_text');
        }
        else
        {
            if(NULL == $this->request->post('school_name'))
            {
                return [
                    'code'=>-1,
                    'message'=>'请选择毕业院校',
                ];
            }
            $school_area = $this->request->post('school_area');
            $school_id = $this->request->post('school_id');
            $school_name = $this->request->post('school_name');
            $school_district_text = $this->request->post('school_district_text');
        }

        if(NULL == $this->request->post('loan_person_id'))
        {
            return [
                'code'=>-2,
                'message'=>'该用户不存在',
            ];
        }
        else
        {
            $loan_person_id = $this->request->post('loan_person_id');
        }

        $loan_person = LoanPerson::find()->where('id='.$loan_person_id)->one();
        if(empty($loan_person))
        {
            return [
                'code'=>-2,
                'message'=>'该用户不存在',
            ];
        }


        //判断是否实名认证
        $is_verify = $loan_person['is_verify'];
        if(1 != $is_verify)
        {
            //需要实名认证
            try{
                $ret = UserService::realnameVerify($realname,$id_number);
                $loan_person->is_verify=1;
                $loan_person->name = $realname;
                $loan_person->id_number = $id_number;
                if(!$loan_person->save())
                {
                    return [
                        'code'=>-3,
                        'message'=>'实名认证失败',
                    ];
                }
            }catch(\Exception $e){
                return [
                    'code'=>-4,
                    'message'=>'实名认证失败',
                ];

            }
        }


        $loan_person_info = LoanPersonInfo::find()->where('loan_person_id='.$loan_person_id)->one();
        if(empty($loan_person_info))
        {
            $loan_person_info = new LoanPersonInfo();
        }

        if(1 != $is_verify)
        {
            $loan_person_info->realname=$realname;
            $loan_person_info->id_number=$id_number;
        }

        $loan_person_info->qq=$qq;
        $loan_person_info->marital_status=$marital_status;
        $loan_person_info->degree=$degree;
        $loan_person_info->job_status=$job_status;
        $loan_person_info->school_area=$school_area;
        $loan_person_info->school_id=$school_id;
        $loan_person_info->school_name=$school_name;
        $loan_person_info->school_district_text=$school_district_text;
        $loan_person_info->loan_person_id=$loan_person_id;

        if(!$loan_person_info->save())
        {
            return [
                'code'=>-5,
                'message'=>'操作失败',
            ];
        }

        return [
            'code'=>0,
            'message'=>'success',
        ];

    }

    /**
     * 保存家庭地址和现居住地址
     * @return array
     * @throws \Exception
     */

    public function actionSaveaddress()
    {
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$person_id)->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }
        if($is_save){
            return [
                'code'=>-100,
                'message'=>'信息已经提交，不能修改',
            ];
        }
        //借款人的居住地址


        if((NULL == $this->request->post('present_province'))||(NULL == $this->request->post('present_city'))||
            (NULL == $this->request->post('present_area'))||(NULL == $this->request->post('present_address'))||
            (NULL == $this->request->post('loan_person_id')||(NULL == $this->request->post('present_district_text')))
        )
        {
            return [
                'code'=>-1,
                'message'=>'居住地址区域或者地址不能为空',
            ];
        }
        else
        {
            $present_province = $this->request->post('present_province');
            $present_city = $this->request->post('present_city');
            $present_area = $this->request->post('present_area');
            $present_district_text = $this->request->post('present_district_text');
            $present_address = $this->request->post('present_address');
            $loan_person_id = $this->request->post('loan_person_id');
        }

        //借款人的家庭地址
        if((NULL == $this->request->post('family_province'))||(NULL == $this->request->post('family_city'))||
            (NULL == $this->request->post('family_area'))||(NULL == $this->request->post('family_address'))||
            (NULL == $this->request->post('loan_person_id')||(NULL == $this->request->post('family_district_text')))
        )
        {
            return [
                'code'=>-2,
                'message'=>'家庭地址区域或者地址不能为空',
            ];
        }
        else
        {
            $family_province = $this->request->post('family_province');
            $family_city = $this->request->post('family_city');
            $family_area = $this->request->post('family_area');
            $family_district_text = $this->request->post('family_district_text');
            $family_address = $this->request->post('family_address');
            $loan_person_id = $this->request->post('loan_person_id');
        }

        //保存
        $loan_person_info = LoanPersonInfo::find()->where('loan_person_id='.$loan_person_id)->one();

        $loan_person_info->present_province = $present_province;
        $loan_person_info->present_city = $present_city;
        $loan_person_info->present_area = $present_area;
        $loan_person_info->present_district_text = $present_district_text;
        $loan_person_info->present_address = $present_address;

        $loan_person_info->family_province = $family_province;
        $loan_person_info->family_city = $family_city;
        $loan_person_info->family_area = $family_area;
        $loan_person_info->family_district_text = $family_district_text;
        $loan_person_info->family_address = $family_address;

        if($loan_person_info->save())
        {
            return [
                'code'=>0,
                'message'=>'success',
            ];
        }
        else
        {
            return [
                'code'=>-3,
                'message'=>'操作失败，请稍后再试',
            ];
        }


    }
    /**
     * 保存借款人的居住地址
     * @return array
     * @throws \Exception
     */
    public function actionSetPresentaddree()
    {
        $this->response->format = Response::FORMAT_JSON;
        if((NULL == $this->request->post('present_province'))||(NULL == $this->request->post('present_city'))||
            (NULL == $this->request->post('present_area'))||(NULL == $this->request->post('present_address'))||
            (NULL == $this->request->post('loan_person_id'))
        )
        {
            throw new \Exception('参数丢失');
        }
        else
        {
            $present_province = $this->request->post('present_province');
            $present_city = $this->request->post('present_city');
            $present_area = $this->request->post('present_area');
            $present_address = $this->request->post('present_address');
            $loan_person_id = $this->request->post('loan_person_id');
        }

        $loan_person_info = LoanPersonInfo::find()->where('loan_person_id='.$loan_person_id)->one();

        $loan_person_info->present_province = $present_province;
        $loan_person_info->present_city = $present_city;
        $loan_person_info->present_area = $present_area;
        $loan_person_info->present_address = $present_address;

        if($loan_person_info->save())
        {
            return [
                'code'=>0,
                'message'=>'success',
            ];
        }
        else
        {
            throw new \Exception('系统繁忙，请稍后再试');
        }
    }

    /**
     * 根据借款人获取家庭地址
     * @return array
     * @throws \Exception
     */
    public function actionGetFamilyaddree()
    {
        $this->response->format = Response::FORMAT_JSON;
        if(NULL == $this->request->post('loan_person_id'))
        {
            throw new \Exception('参数丢失');
        }
        else
        {
            $loan_person_id = $this->request->post('loan_person_id');
        }

        $loan_person_info = LoanPersonInfo::find()->select(['id','loan_person_id','family_province','family_city','family_area','family_address'])->where('loan_person_id='.$loan_person_id)->asArray()->one();

        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$loan_person_info
        ];

    }
    /**
     * 保存借款人的家庭地址
     * @return array
     * @throws \Exception
     */
    public function actionSetFamilyaddree()
    {
        $this->response->format = Response::FORMAT_JSON;
        if((NULL == $this->request->post('family_province'))||(NULL == $this->request->post('family_city'))||
            (NULL == $this->request->post('family_area'))||(NULL == $this->request->post('family_address'))||
            (NULL == $this->request->post('loan_person_id'))
            )
        {
            throw new \Exception('参数丢失');
        }
        else
        {
            $family_province = $this->request->post('family_province');
            $family_city = $this->request->post('family_city');
            $family_area = $this->request->post('family_area');
            $family_address = $this->request->post('family_address');
            $loan_person_id = $this->request->post('loan_person_id');
        }

        $loan_person_info = LoanPersonInfo::find()->where('loan_person_id='.$loan_person_id)->one();

        $loan_person_info->family_province = $family_province;
        $loan_person_info->family_city = $family_city;
        $loan_person_info->family_area = $family_area;
        $loan_person_info->family_address = $family_address;

        if($loan_person_info->save())
        {
            return [
                'code'=>0,
                'message'=>'success',
            ];
        }
        else
        {
            throw new \Exception('系统繁忙，请稍后再试');
        }
    }

    /**
     * 根据区域ID来获取相关学校接口
     * @return array
     * @throws UserException
     */
    public function actionGetshopByarea()
    {
        $this->response->format = Response::FORMAT_JSON;
        if(NULL == $this->request->post('area_id'))
        {
            throw new \Exception('参数丢失');
        }
        else
        {
            $area_id = $this->request->post('area_id');
        }
        $list = $this->getshopbyarea($area_id);
        if( ! $list){
            return $this->messageError(null,null);
        }
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$list
        ];

    }

    /**
     * @param $area_id
     * @return array|bool
     * 通过area_id 获取门店信息
     */
    private function getshopbyarea($area_id){
        $shop_data = Shop::find()->select(['id','shop_name'])->where(['area_id'=>$area_id,'loan_project_id'=>8])->asArray()->all();
        if(empty($shop_data)){
            return false;
        }
        $list = [];
        foreach($shop_data as $v){
            $list[] = [
                'text' => $v['shop_name'],
                'value' => $v['id'],
            ];
        }
        return $list;
    }


    /**
     * 根据学校来获取课程
     * @return array
     * @throws \Exception
     */
    public function actionGetgoodsByshop()
    {
        $this->response->format = Response::FORMAT_JSON;
        if(NULL == $this->request->post('shop_id'))
        {
            throw new \Exception('参数丢失');
        }
        else
        {
            $shop_id = intval($this->request->post('shop_id'));
        }
        $data = $this->getgoodsbyshop($shop_id);
        if(!$data){
            return $this->messageError(null,null);
        }
        return [
            'code'=>0,
            'message'=>'success',
            'data'=>$data,
        ];
    }

    private function getgoodsbyshop($shop_id){
        $goods_data = Goods::find()->select(['id','name','price','period'])->where(['shop_id'=>$shop_id,'status'=>Goods::GOODS_ACTIVE])->asArray()->all();
        if(empty($goods_data)){
            return false;
        }
        $data = [];
        foreach($goods_data as $v){
            $data[] = [
                'value'=>$v['id'],
                'text'=>$v['name'],
                'price' => $v['price'],
                'period' => $v['period'],
            ];
        }
        return $data;
    }

    /**
     * @return array
     * @throws \Exception
     * 获取商品详情
     */
    public function actionGetGoodsdetail()
    {
        $this->response->format = Response::FORMAT_JSON;
        if(NULL == $this->request->post('goods_id'))
        {
            throw new \Exception('参数丢失');
        }
        else
        {
            $goods_id = $this->request->post('goods_id');
        }

        $detail = Goods::find()->select(['price','period'])->where('id='.$goods_id.' and status='.Goods::GOODS_ACTIVE)->asArray()->one();
        $detail['price'] = StringHelper::safeConvertIntToCent($detail['price']);
        if(is_null($detail)){
            throw new \Exception('无此商品');
        }
        return $this->messageSuccess($detail);
    }


    /**
     * 录入个人信息
     */
    public function actionSetPersoninfo()
    {
        $this->response->format = Response::FORMAT_JSON;
//        if($this->request->isAjax){
            $person_id = $this->_getPersonId();
            $loanperson = LoanPerson::findOne(['id'=>$person_id]);
            if(is_null($loanperson)){
                throw new \Exception('借款人不存在');
            }
            $realname = trim($this->request->get('realname'));    //姓名
            if( ! $this->checkRealnameValid($realname) ){
                return $this->messageError('realname','请填写正确的姓名');
            }
            $id_number = $this->request->get('id_number');
            if( ! $this->checkIdnumberValid($id_number)){
                return $this->messageError('id_number','请填写正确的身份证');
            }
            $qq = $this->request->get('qq');
            if(!preg_match('/\d{2,18}/',$qq)){
                return $this->messageError('qq','请填写正确的QQ号码');
            }
            $job_status =  $this->request->get('job_status');   //就业状况
            $job_status_rule = array_keys(LoanPersonInfo::$job_status_list);
            if( ! in_array($job_status,$job_status_rule)){
                throw new \Exception('参数丢失');
            }
            $marital_status = $this->request->get('marital_status');  //婚姻状况
            $marital_status_rule = array_keys(LoanPersonInfo::$marital_status_list);
            if( ! in_array($marital_status,$marital_status_rule)){
                throw new \Exception('参数丢失');
            }
            $degree = $this->request->get('degree');        //学历
            $degree_rule = array_keys(LoanPersonInfo::$degree_list);
            if (!in_array($degree, $degree_rule)) {
                throw new \Exception('参数丢失');
            }
            $school_id = intval($this->request->get('school_id'));   //学校id
            $school = College::find()->where(['id' => $school_id])->asArray()->one();
            if (is_null($school)) {
                throw new \Exception('参数丢失');
            }
            $school_name = $school['name'];
            if($loanperson->is_verify != 1){
                try{
                    $userservices = Yii::$container->get('userService');
                    $person_info =$userservices->realnameVerify($realname,$id_number);
                }catch(\Exception $e){
                    return $this->messageError('id_number',$e->getMessage());
                }
            }
            $db = Yii::$app->db_kdkj;
            $transaction = $db->beginTransaction();
            try{
                if($loanperson->is_verify != 1){
                    $loanperson->id_number = $person_info['id_card'];
                    $loanperson->name = $person_info['realname'];
                    $loanperson->birthday = strtotime($person_info['birthday']);
                    $loanperson->is_verify = 1;
                    $result = $loanperson->save();
                    if(! $result){
                        throw new \Exception('借款人表保存失败');
                    }
                }
                $loanpersoninfo = LoanPersonInfo::findOne(['loan_person_id'=>$person_id]);
                if(is_null($loanpersoninfo)) $loanpersoninfo = new LoanPersonInfo();
                $loanpersoninfo->loan_person_id = $person_id;
                $loanpersoninfo->id_number = $loanperson['id_number'];
                $loanpersoninfo->realname = $loanperson['name'];
                $loanpersoninfo->job_status = $job_status;
                $loanpersoninfo->marital_status = $marital_status;
                $loanpersoninfo->school_name = $school_name;
                $loanpersoninfo->qq = $qq;
                $loanpersoninfo->school_id = $school_id;
                $loanpersoninfo->degree = $degree;
                $loanpersoninfo->save();
                $transaction->commit();
            }catch(\Exception $e){
                $transaction->rollBack();
                throw new \Exception($e);
            }
            return $this->messageJump(Url::toRoute('user-info/contact'));
//        }
    }

    public function actionGetPersoninfo(){
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $loanpersoninfo = LoanPersonInfo::find()->where(['loan_person_id'=>$person_id])->select([
            'realname','id_number','qq','marital_status','degree','job_status','school_name','school_id'
        ])->asArray()->one();
        if(is_null($loanpersoninfo)){
            return $this->messageError(null,null);
        }
        return $this->messageSuccess($loanpersoninfo);
    }

    /**
     * @return array
     * 录入家庭信息
     */
    public function actionSetFamilyinfo()
    {
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $loanpersoninfo = LoanPersonInfo::findOne(['loan_person_id' => $person_id]);
        $family_province = intval($this->request->get('family_province'));   //家庭住址所在省份
        if (empty($family_province) || is_null(Province::findOne(['province_id' => $family_province]))) {
            return $this->messageInvalid();
        }
        $family_city = intval($this->request->get('family_city'));  //家庭住址所在城市
        if (empty($family_city) || is_null(City::findOne(['city_id' => $family_city, 'parent_id' => $family_province]))) {
            return $this->messageInvalid();
        }
        $family_area = intval($this->request->get('family_area'));  //家庭住址所在区县
        if (empty($family_area) || is_null(Area::findOne(['area_id' => $family_area, 'parent_id' => $family_city]))) {
            return $this->messageInvalid();
        }
        $present_province = intval($this->request->get('present_province'));    //现居地所在省份
        if (empty($present_province) || is_null(Province::findOne(['province_id' => $present_province]))) {
            return $this->messageInvalid();
        }
        $present_city = intval($this->request->get('present_city'));    //现居地所在城市
        if (empty($present_city) || is_null(City::findOne(['city_id' => $present_city, 'parent_id' => $present_province]))) {
            return $this->messageInvalid();
        }
        $present_area = intval($this->request->get('present_area'));    //现居地所在区县
        if (empty($present_area) || is_null(Area::findOne(['area_id' => $present_area, 'parent_id' => $present_city]))) {
            return $this->messageInvalid();
        }
        $family_address = trim($this->request->get('family_address'));  //家庭地址
        if (empty($family_address)) {
            return $this->messageError('family_address', '请填写家庭住址');
        }
        if (!$this->checkAddressVaild($family_address)) {
            return $this->messageError('family_address', '请填写正确的家庭住址');
        }
        $present_address = trim($this->request->get('present_address'));    //现居地
        if (empty($present_address)) {
            return $this->messageError('present_address', '请填写现居住址');
        }
        if (!$this->checkAddressVaild($present_address)) {
            return $this->messageError('present_address', '请填写正确的现居住址');
        }
        try {
            $loanpersoninfo->family_province = $family_province;
            $loanpersoninfo->family_city = $family_city;
            $loanpersoninfo->family_area = $family_area;
            $loanpersoninfo->present_province = $present_province;
            $loanpersoninfo->present_city = $present_city;
            $loanpersoninfo->present_area = $present_area;
            $loanpersoninfo->family_address = $family_address;
            $loanpersoninfo->present_address = $present_address;
            $loanpersoninfo->save();
        } catch (\Exception $e) {
            return $this->messageBusy();
        }
        return $this->messageSuccess('ok');
    }

    public function actionGetFamilyinfo(){
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $loanpersoninfo = LoanPersonInfo::find()->where(['loan_person_id'=>$person_id])->select([
            'family_province','family_city','family_area','present_province','present_city','present_area','family_address','present_address'
        ])->asArray()->one();
        if(is_null($loanpersoninfo)){
            return $this->messageError(null,null);
        }
        return $this->messageSuccess($loanpersoninfo);
    }

    /**
     * @return array
     * @throws \Exception
     * 保存用户公司信息
     */
    public function actionSetCompanyinfo(){
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $ret = LoanPersonInfo::find()->where(' loan_person_id='.$person_id)->one();
        $is_save= 0;
        if(!empty($ret)){

            $is_save=$ret['is_save'];
        }
        if($is_save){
            return [
                'code'=>-100,
                'message'=>'信息已经提交，不能修改',
            ];
        }
        $loan_person_info = LoanPersonInfo::find()->where(' loan_person_id='.$person_id)->one();
        if(empty($loan_person_info)){
            //job_status
            return [
                'code'=>-101,
                'message'=>'系统繁忙，请稍后再试',
            ];
        }
        $job_status=$loan_person_info['job_status'];

        $must = 0;
        if(LoanPersonInfo::IDENTITY_STATIC_JOB == $job_status){
            $must = 1;
        }
        $loanpersoninfo = LoanPersonInfo::findOne(["loan_person_id" => $person_id]);
        if(!$loanpersoninfo){
            $loanpersoninfo = new LoanPersonInfo();
        }
        $company_name = $this->request->post('company_name');
        $company_address = $this->request->post('company_address');
        $company_phone = $this->request->post('company_phone');
        $company_area = intval($this->request->post('company_area'));
        $company_city = intval($this->request->post('company_city'));
        $company_pca = trim($this->request->post('company_pca'));

        $company_province = intval($this->request->post('company_province'));
        if($must || $company_name || $company_address || $company_phone || $company_area || $company_city || $company_pca){
            if( ! $this->checkCompanynameVaild($company_name)){
                return $this->messageError('company_name','请填写正确的公司名');
            }
            if( ! $this->checkAddressVaild($company_address)){
                return $this->messageError('company_address','请填写正确的公司地址');
            }
            if(! $this->checkTelephoneValid($company_phone)){
                return $this->messageError('company_phone','请填写正确的公司电话');
            }
            if(empty($company_area)){
                return $this->messageError('company_province','请选择公司地址');
            }
            if(empty($company_city)){
                return $this->messageError('company_province','请选择公司地址');
            }
            if(empty($company_province)){
                return $this->messageError('company_province','请选择公司地址');
            }
            if(!$this->checkAddressVaild($company_pca)){
                return $this->messageError('company_pca','系统繁忙，请稍后再试');
            }
        }

        try{
            $loanpersoninfo->company_name = $company_name;
            $loanpersoninfo->company_address = $company_address;
            $loanpersoninfo->company_phone = $company_phone;
            $loanpersoninfo->company_area = $company_area;
            $loanpersoninfo->company_city = $company_city;
            $loanpersoninfo->company_province = $company_province;
            $loanpersoninfo->company_pca = $company_pca;
            $loanpersoninfo->save();
        }catch(\Exception $e){
            Yii::error(" method:".__METHOD__." line:".__LINE__.$e);
            return $this->messageBusy();
        }
        return $this->messageSuccess('ok');

    }

    /**
     * 录入紧急联系人信息
     */
    public function actionSetContactinfo()
    {
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $loanpersoninfo = LoanPersonInfo::findOne(["loan_person_id" => $person_id]);
        if (is_null($loanpersoninfo) || empty($loanpersoninfo->id_number)) {
            return $this->messageJump(Url::toRoute('user-info/index'));
        }
        $first_contact_name = $this->request->get('first_contact_name');
        if (!$this->checkRealnameValid($first_contact_name)) {
            return $this->messageError('first_contact_name', '请填写正确的姓名');
        }
        $first_contact_phone = $this->request->get('first_contact_phone');
        if (!$this->checkPhoneValid($first_contact_phone)) {
            return $this->messageError('first_contact_phone', '请填写正确的手机号');
        }
        $first_contact_relation = $this->request->get('first_contact_relation');
        $first_contact_relation_rule = array_keys(LoanPersonInfo::$first_contact_list);
        if (!in_array($first_contact_relation, $first_contact_relation_rule)) {
            throw new \Exception('参数丢失');
        }
        $second_contact_name = $this->request->get('second_contact_name');
        if (!$this->checkRealnameValid($second_contact_name)) {
            return $this->messageError('second_contact_name', '请填写正确的姓名');
        }
        $second_contact_phone = $this->request->get('second_contact_phone');
        if (!$this->checkPhoneValid($second_contact_phone)) {
            return $this->messageError('second_contact_phone', '请填写正确的手机号');
        }
        $contact_relation_rule = array_keys(LoanPersonInfo::$second_contact_list);
        $second_contact_relation = $this->request->get('second_contact_relation');
        if (!in_array($second_contact_relation, $contact_relation_rule)) {
            throw new \Exception('参数丢失');
        }
        $third_contact_name = $this->request->get('third_contact_name');
        if (!$this->checkRealnameValid($third_contact_name)) {
            return $this->messageError('third_contact_name', '请填写正确的姓名');
        }
        $third_contact_phone = $this->request->get('third_contact_phone');
        if (!$this->checkPhoneValid($third_contact_phone)) {
            return $this->messageError('third_contact_phone', '请填写正确的手机号');
        }
        $contact_relation_rule = array_keys(LoanPersonInfo::$third_contact_list);
        $third_contact_relation = $this->request->get('third_contact_relation');
        if (!in_array($third_contact_relation, $contact_relation_rule)) {
            throw new \Exception('参数丢失');
        }
        try {
            $loanpersoninfo->first_contact_name = $first_contact_name;
            $loanpersoninfo->first_contact_phone = $first_contact_phone;
            $loanpersoninfo->first_contact_relation = $first_contact_relation;
            $loanpersoninfo->second_contact_name = $second_contact_name;
            $loanpersoninfo->second_contact_phone = $second_contact_phone;
            $loanpersoninfo->second_contact_relation = $second_contact_relation;
            $loanpersoninfo->third_contact_name = $third_contact_name;
            $loanpersoninfo->third_contact_phone = $third_contact_phone;
            $loanpersoninfo->third_contact_relation = $third_contact_relation;
            $loanpersoninfo->save();
        } catch (\Exception $e) {
            return $this->messageBusy();
        }
        return $this->messageJump(Url::toRoute('user-info/family'));
    }

    public function actionGetContactinfo(){
        $this->response->format = Response::FORMAT_JSON;
        $person_id = $this->_getPersonId();
        $loanpersoninfo = LoanPersonInfo::find()->where(['loan_person_id'=>$person_id])->select([
            'first_contact_name','first_contact_phone','first_contact_relation','second_contact_name','second_contact_phone',
            'second_contact_relation','third_contact_name','third_contact_phone','third_contact_relation'
        ])->asArray()->one();
        if(is_null($loanpersoninfo)){
            return $this->messageError(null,null);
        }
        return $this->messageSuccess($loanpersoninfo);
    }
//    public function actionGetaddress(){
//        $this->response->format = Response::FORMAT_JSON;
//        $type = $this->request->get('type');
//        $parent_id = $this->request->get('parent_id');
//        switch($type){
//            case 'province':
//                $result = City::find()->select(['city as name','city_id as id'])->where(['parent_id'=> $parent_id])->asArray()->all();
//                if( ! is_null($result)){
//                    return $this->messageSuccess($result);
//                }
//                break;
//            case 'city':
//                $result = Area::find()->select(['area_id as id','area as name'])->where(['parent_id'=> $parent_id])->asArray()->all();
//                if( ! is_null($result)){
//                    return $this->messageSuccess($result);
//                }
//                break;
//        }
//    }


    /**
     * 返回省对应的院校
     * @return string
     */
    private function getProvinceScholl()
    {
        $province = Province::find()->select(['province_id as id','province as name'])->asArray()->all();
        $college = College::find()->select(['id','name','province_id'])->asArray()->all();
        $all_list = [];
        $college_list = [];
        $tmp_list = [];
        foreach ($province as $item)
        {
            $all_list[$item['id']] = [
                'text' => $item['name'],
                'value' => $item['id'],
                'children' => [],
            ];
        }

        foreach ($college as $item)
        {
            $college_list[$item['id']] = [
                'text' => $item['name'],
                'value' => $item['id'],
            ];
            $tmp_list[$item['id']] = $item['province_id'];
        }

        foreach($college_list as $item)
        {
            $all_list[$tmp_list[$item['value']]]['children'][] = $item;
        }

        $list = array_values($all_list);

        return json_encode($list);
    }


    /**
     * @return array
     * 返回省市区树结构
     */
    private function getProvinceCityArea(){
        $province = Province::find()->select(['province_id as id','province as name'])->asArray()->all();
        $city = City::find()->select(['city_id as id','city as name','parent_id'])->asArray()->all();
        $area = Area::find()->select(['area_id as id','area as name','parent_id'])->asArray()->all();
        $all_list = [];
        $city_list = [];
        $tmp_list = [];
        foreach ($province as $item) {
            $all_list[$item['id']] = [
                'text' => $item['name'],
                'value' => $item['id'],
                'children' => [],
            ];
        }
        foreach ($city as $item){
           $city_list[$item['id']] = [
               'text' => $item['name'],
               'value' => $item['id'],
               'children' => [],
           ];
           $tmp_list[$item['id']] = $item['parent_id'];
        }
        foreach ($area as $item){
            $city_list[$item['parent_id']]['children'][] = [
                'text' => $item['name'],
                'value' => $item['id']
            ];
        }

        foreach($city_list as $item){
           $all_list[$tmp_list[$item['value']]]['children'][] = $item;
        }
        $list = array_values($all_list);
        return json_encode($list);
    }

    /**
     * @return array
     * 返回省大学树结构
     */
    public function actionProvinceCollege(){
        $this->response->format = Response::FORMAT_JSON;
        $province = Province::find()->select(['province_id as id','province as name'])->asArray()->all();
        $college = College::find()->select(['province_id','name','id'])->asArray()->all();
        $list = [];
        foreach($province as $item){
            $list[$item['id']] = [
                'province_title' => $item['name'],
                'province_id' => $item['id'],
                'college' => [],
            ];
        }
        foreach($college as $item){
            $list[$item['province_id']][$item['id']] = [
                'college_title' => $item['name'],
                'college_id' => $item['id'],
            ];
        }
        return $list;
    }


    /**
     * @return array
     * @throws \Exception
     * 获取就业状况列表
     */
    public function actionGetJob(){
        $this->response->format = Response::FORMAT_JSON;
        if($this->request->isAjax){
            return $this->_getMap(LoanPersonInfo::$job_status_list);
        }
    }

    /**
     * @return array
     * @throws \Exception
     * 获取第一联系人链表
     */
    public function actionGetFirstContact(){
        $this->response->format = Response::FORMAT_JSON;
        if($this->request->isAjax){
            return $this->_getMap(LoanPersonInfo::$first_contact_list);
        }
    }

    /**
     * @return array
     * @throws \Exception
     * 获取第二联系人链表
     */
    public function actionGetSecondContact(){
        $this->response->format = Response::FORMAT_JSON;
        if($this->request->isAjax){
            return $this->_getMap(LoanPersonInfo::$second_contact_list);
        }
    }

    /**
     * @return array
     * @throws \Exception
     * 获取第三联系人链表
     */
    public function actionGetThirdContact(){
        $this->response->format = Response::FORMAT_JSON;
        if($this->request->isAjax){
            return $this->_getMap(LoanPersonInfo::$third_contact_list);
        }
    }

    /**
     * @return array
     * @throws \Exception
     * 获取学历列表
     */
    public function actionGetDegree(){
        $this->response->format = Response::FORMAT_JSON;
        if($this->request->isAjax){
            return $this->_getMap(LoanPersonInfo::$degree_list);
        }
    }

    /**
     * @return array
     * 获取婚姻状态列表
     */
    public function actionGetMarital(){
        $this->response->format = Response::FORMAT_JSON;
        if($this->request->isAjax){
            return $this->_getMap(LoanPersonInfo::$marital_status_list);
        }
    }

    private function _getPersonId(){
        $user = UserService::getInfo();
        if(empty($user['loan_person']['id'])){
            throw new \Exception('借款人不存在');
        }
        $person_id = $user['loan_person']['id'];
        return $person_id;
    }

    /**
     * @param $arr
     * @return array
     * @throws \Exception
     * 返回model类里的映射关系
     */
    private function _getMap($arr){
        if(!is_array($arr)){
            throw new \Exception('不是一个数组');
        }
        $list = [];
        foreach($arr as $k=>$v){
            $list[] = ['value' => $k, 'text' => $v];
        }
        return $list;
    }


    /**
     * @param $message
     * @return array
     * 返回成功信息
     */
    private function messageSuccess($data='',$message='success'){
        return [
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * @param $url
     * @return array
     * 返回跳转信息
     */
    private function messageJump($data,$message='render'){
        return [
            'code' => 1,
            'data' => $data,
            'message' => $message,
        ];
    }

    /**
     * @param $obj
     * @param $message
     * @return array
     * 返回错误信息
     */
    private function messageError($data,$message){
        return [
            'code' => 2,
            'data' => $data,
            'message' => $message,
        ];
    }


    private function messageBusy(){
        return [
            'code' => 4,
            'message' => '服务器忙',
        ];
    }

    /**
     * @param $name
     * @return bool
     * 中文名验证
     */
    private function checkRealnameValid($name){
        $preg = "/^[\x80-\xff]{4,30}$/";
        if( ! preg_match($preg,$name) ){
            return false;
        }
        return true;
    }

    //公司名验证
    private function checkCompanynameVaild($name){
        $preg = '/^[\x80-\xff\w\d\s]{1,100}$/';
        return preg_match($preg,$name);
    }
    //地址验证
    private function checkAddressVaild($address){
        $preg = '/^[\x80-\xff\s\w\d]{1,100}$/';
        return preg_match($preg,$address);
    }
    /**
     * @param $id_number
     * @return bool
     * 身份证验证
     */
    private function checkIdnumberValid($id_number)
    {
        $vCity = array(
            '11','12','13','14','15','21','22',
            '23','31','32','33','34','35','36',
            '37','41','42','43','44','45','46',
            '50','51','52','53','54','61','62',
            '63','64','65','71','81','82','91'
        );

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $id_number)) return false;

        if (!in_array(substr($id_number, 0, 2), $vCity)) return false;

        $id_number = preg_replace('/[xX]$/i', 'a', $id_number);
        $vLength = strlen($id_number);

        if ($vLength == 18)
        {
            $vBirthday = substr($id_number, 6, 4) . '-' . substr($id_number, 10, 2) . '-' . substr($id_number, 12, 2);
        } else {
            $vBirthday = '19' . substr($id_number, 6, 2) . '-' . substr($id_number, 8, 2) . '-' . substr($id_number, 10, 2);
        }

        if (date('Y-m-d', strtotime($vBirthday)) != $vBirthday) return false;
        if ($vLength == 18)
        {
            $vSum = 0;

            for ($i = 17 ; $i >= 0 ; $i--)
            {
                $vSubStr = substr($id_number, 17 - $i, 1);
                $vSum += (pow(2, $i) % 11) * (($vSubStr == 'a') ? 10 : intval($vSubStr , 11));
            }

            if($vSum % 11 != 1) return false;
        }

        return true;
    }

    private function checkTelephoneValid($phone){
        $preg = '/^\d{3}\-\d{8}$|^1\d{10}$/';
        return preg_match($preg,$phone);
    }
    /**
     * @param $phone
     * @return bool
     * 手机号验证
     */
    private function checkPhoneValid($phone)
    {
        if(!is_numeric($phone))
        {
            return false;
        }
        return preg_match('/^1\d{10}$/', $phone) ? true : false;
    }


}