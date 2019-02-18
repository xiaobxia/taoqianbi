<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/10/27
 * Time: 13:53
 */
namespace backend\controllers;

use common\models\risk\Rule;
use Yii;
use yii\web\Response;
use yii\data\Pagination;
use yii\web\UploadedFile;
use yii\validators\FileValidator;
use common\helpers\Url;

use common\models\LoanPerson;
use common\models\LoanPersonBadInfo;
use common\models\TaskList;
use common\models\UserDetail;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserQuotaPersonInfo;
use console\models\MobileCity;

/**
 * TaskController controller
 */
class TaskController extends BaseController{

    public $enableCsrfValidation = false;

    /**
     * @name 任务管理 -任务提交筛选页面/actionLoanInfoView
     *
     */
    public function actionLoanInfoView(){
        $data = [
            1=> [ //借款信息
                'key' =>'loan_detail',
                'name'=>'借款信息',
                'data'=>[
                    0=>[
                        'key'=>'borrowing_platform_id',
                        'name'=>'借款平台',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>[
                            UserLoanOrder::SUB_TYPE_YGD=>'小钱包',
        					UserLoanOrder::SUB_TYPE_XJD=>APP_NAMES,
                        ],
                    ],
                    1=>[
                        'key'=>'loan_time',
                        'name'=>'借款时间',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[],
                    ],
                    2=>[
                        'key'=>'credit_amount',
                        'name'=>'授信额度',
                        'option'=>'int_between',//表示表示整型区间
                        'value'=>[],
                    ],
                    3=>[
                        'key'=>'loan_money',
                        'name'=>'借款金额',
                        'option'=>'int_between',//表示表示整型区间
                        'value'=>[],
                    ],
                    4=>[
                        'key'=>'loan_term',
                        'name'=>'借款期限',
                        'option'=>'int_between',//表示表示整型区间
                        'value'=>[],
                    ],
                    5=>[
                        'key'=>'order_sataus',
                        'name'=>'借款状态',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>UserLoanOrder::$status,
                    ],
                    6=>[
                        'key'=>'machine_check_result',
                        'name'=>'机审结果',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>[
                            -1=>'未通过',
                            0=>'未机审',
                            1=>'通过'
                        ],
                    ],
                    7=>[
                        'key'=>'machine_check_code',
                        'name'=>'机审审核码',
                        'option'=>'string',
                        'value'=>[],
                    ],
                    8=>[
                        'key'=>'machine_check_time',
                        'name'=>'机审时间',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[],
                    ],
                    9=>[
                        'key'=>'trial_check',
                        'name'=>'是否初审',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>[
                            false=>'未初审',
                            true=>'已初审'
                        ],
                    ],
                    10=>[
                        'key'=>'trial_check_user_name',
                        'name'=>'初审人ID',
                        'option'=>'string',
                        'value'=>[],
                    ],
                    11=>[
                        'key'=>'trial_check_result',
                        'name'=>'初审结果',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>[
                            -1=>'不通过',
                            1=>'通过'
                        ],
                    ],
                    12=>[
                        'key'=>'trial_check_time',
                        'name'=>'初审时间',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[],
                    ],
                    13=>[
                        'key'=>'trial_check_code',
                        'name'=>'初审审核码',
                        'option'=>'string',
                        'value'=>[],
                    ],
                    14=>[
                        'key'=>'review_check',
                        'name'=>'是否复审',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>[
                            false=>'未复审',
                            true=>'已复审'
                        ],
                    ],
                    15=>[
                        'key'=>'review_check_user_name',
                        'name'=>'复审人ID',
                        'option'=>'string',
                        'value'=>[],
                    ],
                    16=>[
                        'key'=>'review_check_result',
                        'name'=>'复审结果',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>[
                            -1=>'不通过',
                            1=>'通过'
                        ],
                    ],
                    17=>[
                        'key'=>'review_check_time',
                        'name'=>'复审时间',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[],
                    ],
                    18=>[
                        'key'=>'treview_check_code',
                        'name'=>'复审审核码',
                        'option'=>'string',
                        'value'=>[],
                    ],
                    19=>[
                        'key'=>'plan_repayment_time',
                        'name'=>'应还日期',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[],
                    ],
                    20=>[
                        'key'=>'true_repayment_time',
                        'name'=>'实际还款日期',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[],
                    ],
                    21=>[
                        'key'=>'repayment_status',
                        'name'=>'还款状态',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>UserLoanOrderRepayment::$status
                    ],
                    22=>[
                        'key'=>'overdue_day',
                        'name'=>'逾期天数',
                        'option'=>'int_between',//表示表示整型区间
                        'value'=>[],
                    ],
                ]
            ],
            2=>[ //用户数据
                'key' =>'user_detail',
                'name'=>'用户数据',
                'data'=>[
                    1=>[
                        'key'=>'user_sex',
                        'name'=>'用户性别',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>LoanPerson::$sexes
                    ],
                    2=>[
                        'key'=>'user_birthday',
                        'name'=>'用户年龄',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[]
                    ],
                    3=>[
                        'key'=>'user_marriage',
                        'name'=>'用户婚姻',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>UserQuotaPersonInfo::$marriage
                    ],
                    4=>[
                        'key'=>'user_mobile_operators',
                        'name'=>'手机运营商',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>MobileCity::$operator,
                        'child'=>[
                            MobileCity::CHINA_UNICOM=>[],
                            MobileCity::CHINA_MOBILE=>MobileCity::$china_mobile_sub,
                            MobileCity::CHINA_TELECOMMUNICATIONS=>MobileCity::$china_telecommunications_sub,
                        ]
                    ],
                    5=>[
                        'key'=>'user_mobile_real_name_status',
                        'name'=>'手机号是否实名',
                        'option'=>'list',//表示列表选项并且可以多选
                        'value'=>[
                            false=>'未实名',
                            true=>'已实名'
                        ]
                    ],
                    6=>[
                        'key'=>'user_mobile_real_name_time',
                        'name'=>'手机号实名时间',
                        'option'=>'date_between',//表示时间区间
                        'value'=>[]
                    ],
                ]
            ],
            3=>[//用户特征
                'key'=>'user_feature',
                'name'=>'用户特征',
                'data'=>[],
            ],
            4=>[//征信评级
                'key'=>'user_credit',
                'name'=>'征信评级',
                'data'=>[
                    1=>[
                        'key'=>'credit_forbid_score',
                        'name'=>'禁止项得分',
                        'option'=>'float_between',//表示表示浮点型区间
                        'value'=>[],
                    ],
                    2=>[
                        'key'=>'credit_anti_fraud_score',
                        'name'=>'反欺诈得分',
                        'option'=>'float_between',//表示表示浮点型区间
                        'value'=>[],
                    ],
                    3=>[
                        'key'=>'credit_grade_score',
                        'name'=>'信用评估得分',
                        'option'=>'float_between',//表示表示浮点型区间
                        'value'=>[],
                    ],
                ]
            ],
        ];

        return $this->render('loan-info-filtrate', array(
            'data' => $data,
        ));

    }


    /**
     * @name 任务管理 -任务提交/actionLoanInfoList
     *
     */
    public function actionLoanInfoList(){
        $this->response->format = Response::FORMAT_JSON;

        $params = Yii::$app->request->post();

        //筛选条件对应的 json 字符串
        $data = isset($params['data'])?($params['data']):"";
        if(empty($data)){
            return [
                'code'=>-1,
                'message'=>'参数不能为空',
            ];
        }
        $title = isset($params['title'])?($params['title']):"";
        if(empty($title)){
            return [
                'code'=>-1,
                'message'=>'请填写任务名称',
            ];
        }
        //任务描述
        $task_detail = isset($params['remark'])?($params['remark']):"";
        $task_list = new TaskList();
        $task_list->type = TaskList::TYPE_ORDER_DETAIL;
        $task_list->title = $title;
        $task_list->task_detail = $task_detail;
        $task_list->status = TaskList::STATUS_SUBMIT;
        $task_list->excute_task = $data;
        $task_list->created_by = Yii::$app->user->identity->username;
        $task_list->updated_at = time();
        $task_list->created_at = time();

        if($task_list->save()){
            return [
                'code'=>0,
                'message'=>"创建成功"
            ];
        }else{
            return [
                'code'=>-1,
                'message'=>"创建失败"
            ];
        }

    }

    public function getFilter(){
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['created_by']) && !empty($search['created_by'])) {
                $condition .= " AND created_by like  '%" . $search['created_by']."%'";
            }
            if (isset($search['title']) && !empty($search['title'])) {
                $condition .= " AND title like  '%" . $search['title']."%'";
            }
            if (isset($search['status'])) {
                if(-100 != $search['status']){
                    $condition .= " AND status = " . intval($search['status']);
                }

            }
            if (isset($search['created_at_start'])&&!empty($search['created_at_start'])) {
                $condition .= " AND created_at_start >= " . strtotime(date('Y-m-d',$search['created_at_start']));

            }
            if (isset($search['created_at_end'])&&!empty($search['created_at_end'])) {
                $condition .= " AND created_at_end < " . (strtotime(date('Y-m-d',$search['created_at_end']))+24*3600);

            }

        }
        return $condition;
    }

    /**
     * @name 任务管理 -任务列表/actionList
     *
     */
    public function actionList(){
        $condition = self::getFilter();

        $query = TaskList::find()->where($condition)->orderBy(['id'=>SORT_DESC]);
        $countQuery = clone  $query;
        $pages = new Pagination(['totalCount' => $countQuery->count()]);
        $pages->pageSize = 15;
        $data = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();



        return $this->render('list', array(
            'data_list' => $data,
            'pages' => $pages,
        ));

    }
    /**
     * @name 任务管理 -详情/actionOrderDetailView
     */
    public function actionOrderDetailView($id){
        $task_list = TaskList::findOne(['id'=>$id]);
        if(false == $task_list){
            return 0;
        }
        $excute_task ="";
        $ret = json_decode($task_list->excute_task);
        if(false == $ret){
            $excute_task="";
        }else{
            foreach($ret as $key =>$item){
                if(isset($item->option)&&isset($item->value)) {
                    $value = $item->value;
                    $value = json_encode($value);
                    if(isset(TaskList::$order_detail_table[$key])){
                        if(empty($excute_task)){
                            $excute_task = TaskList::$order_detail_table[$key].":".$value;
                        }else{
                            $excute_task = $excute_task." ".TaskList::$order_detail_table[$key].":".$value;
                        }
                    }
                }
            }
        }




        $data = [
            'id'=>$task_list->id,
            'type'=>isset(TaskList::$type[$task_list->type])?TaskList::$type[$task_list->type]:"",
            'title'=>$task_list->title,
            'task_detail'=>$task_list->task_detail,
            'status'=>isset(TaskList::$status[$task_list->status])?TaskList::$status[$task_list->status]:"",
            'excute_task'=>$excute_task,
            'excute_start'=>empty($task_list->excute_start)?"--":date("Y-m-d H:i:s",$task_list->excute_start),
            'excute_end'=>empty($task_list->excute_start)?"--":date("Y-m-d H:i:s",$task_list->excute_start),
            'updated_at'=>empty($task_list->excute_start)?"--":date("Y-m-d H:i:s",$task_list->excute_start),
            'created_at'=>empty($task_list->excute_start)?"--":date("Y-m-d H:i:s",$task_list->excute_start),
            'created_by'=>$task_list->created_by,
            'operator_name'=>$task_list->operator_name,
            'remark'=>$task_list->remark,
        ];

        return $this->render('order-detail-view', array(
            'data' => $data,
        ));

    }

    /**
     * @name 任务管理 -下载/actionOrderDetailDownload
     *
     */
    public function actionOrderDetailDownload($id){
        $task_list = TaskList::findOne(['id'=>$id]);

        if(false == $task_list){
            return [
                'code'=>-1,
                'message'=>'获取数据失败'
            ];
        }
        $excute_task = $task_list->excute_task;
        $excute_task = json_decode($excute_task);
        if(false == $excute_task){
            return [
                'code'=>-1,
                'message'=>'获取数据失败'
            ];
        }
        //征信数据
        $rule = Rule::find()->where(['status'=>Rule::STATUS_NORMAL])->select(['id','name'])->asArray()->all();
        $rules = [];
        foreach($rule as $item){
            $rules[$item['id']] = $item['name'];
        }

        $client = Yii::$app->solr;

        $q_str = "";
        foreach($excute_task as $key =>$item){
            if(isset($item->option)&&isset($item->value)) {
                $option = $item->option;
                $value = $item->value;
                $str = self::_option($key,$option,$value);
                if(!empty($str)){
                    if (empty($q_str)) {
                        $q_str = $str;
                    } else {
                        $q_str = $q_str . " AND " . $str;
                    }
                }
            }
        }
        $select = [
            'query'=>$q_str,
            'start'=>0,
            'rows'=>1,
        ];
        $query = $client->createSelect($select);
        $items = $client->select($query);
        $count = $items->getNumFound();
        $select = [
            'query'=>$q_str,
            'start'=>0,
            'rows'=>$count,
        ];
        $query = $client->createSelect($select);
        $items = $client->select($query);

        $data = [];
        foreach ($items as $item){
            $item = self::object2array($item);
            $_data = [];


            foreach(TaskList::$order_detail_table as $key =>$_item){
                if(isset($item[$key])){
                    $_data[$_item] =str_replace(","," ",$item[$key]);
                }else{
                    $_data[$_item] ="";
                }

                switch ($key){
                    case "borrowing_platform_id":
                        $_data[$_item] =isset(UserLoanOrder::$sub_order_type[$item[$key]])?UserLoanOrder::$sub_order_type[$item[$key]]:"";
                        break;
                    case "credit_amount":
                        $_data[$_item] = sprintf("%0.2f",$item[$key]/100);
                        break;
                    case "loan_money":
                        $_data[$_item] = sprintf("%0.2f",$item[$key]/100);
                        break;
                    case "loan_fee":
                        $_data[$_item] = sprintf("%0.2f",$item[$key]/100);
                     break;
                    case "order_sataus":
                        $_data[$_item] =isset(UserLoanOrder::$status[$item[$key]])?UserLoanOrder::$status[$item[$key]]:"";
                        break;
                    case "machine_check_result":
                        if($item[$key]>0){
                            $_data[$_item] = "通过";
                        }else if($item[$key]<0){
                            $_data[$_item] = "没通过";
                        }else{
                            $_data[$_item] = "";
                        }
                        break;
                    case "trial_check":
                        $_data[$_item] =($item[$key]>0)?"是":"否";
                        break;
                    case "trial_check_result":
                        if($item[$key]>0){
                            $_data[$_item] = "通过";
                        }else if($item[$key]<0){
                            $_data[$_item] = "没通过";
                        }else{
                            $_data[$_item] = "";
                        }
                        break;
                    case "review_check":
                        $_data[$_item] =($item[$key]>0)?"是":"否";
                        break;
                    case "review_check_result":
                        if($item[$key]>0){
                            $_data[$_item] = "通过";
                        }else if($item[$key]<0){
                            $_data[$_item] = "没通过";
                        }else{
                            $_data[$_item] = "";
                        }
                        break;
                    case "plan_repayment_principal":
                        $_data[$_item] = sprintf("%0.2f",$item[$key]/100);
                        break;
                    case "plan_repayment_late_fee":
                        $_data[$_item] = sprintf("%0.2f",$item[$key]/100);
                        break;
                    case "repayment_status":
                        $_data[$_item] =isset(UserLoanOrderRepayment::$status[$item[$key]])?UserLoanOrderRepayment::$status[$item[$key]]:"";
                        break;
                    case "user_sex":
                        $_data[$_item] =isset(LoanPerson::$sexes[$item[$key]])?LoanPerson::$sexes[$item[$key]]:"";
                        break;
                    case "user_marriage":
                        $_data[$_item] =isset(UserQuotaPersonInfo::$marriage[$item[$key]])?UserQuotaPersonInfo::$marriage[$item[$key]]:"";
                        break;
                    case "user_register_source":
                        $_data[$_item] =isset(LoanPerson::$person_source[$item[$key]])?LoanPerson::$person_source[$item[$key]]:"";
                        break;
                }

            }
            foreach($item as $_key =>$ok){
                if(strstr($_key,"user_feature_")){
                    $str_array = explode("user_feature_",$_key);
                    if($str_array && (2 == count($str_array))){
                        if(isset($rules[$str_array[1]])){
                            $_data[$rules[$str_array[1]]] =$ok;

                        }
                    }
                }
            }

            $rules_data = [];
            foreach($rules as $key => $_rule){
                $keys = "user_feature_".$key;
                $rules_data[$keys] = [
                    'key'=>$key,
                    'value'=>'',
                ];
            }
            foreach($item as $_key =>$ok){
                if(isset($rules_data[$_key])){
                    $rules_data[$_key]['value'] = $ok;
                }
            }
            $rules_data_text = [];
            foreach($rules_data as  $_rule_data){
                $key = $_rule_data['key'];
                if(isset($rules[$key])){
                    $rules_data_text[$rules[$key]] = $_rule_data['value'];
                }else{
                    $rules_data_text[$key] = $_rule_data['value'];
                }

            }

            $data[] = array_merge($_data,$rules_data_text);
        }
        return $this->_exportOrderInfos($title="",$data);
    }

    public function object2array($object) {
        $array = [];

        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        }
        else {
            $array = $object;
        }
        return $array;
    }


    public function _exportOrderInfos($title='',$data){
        $title = empty($title)?date("Y-m-d H:i:s"):$title;
        $this->_setcsvHeader($title.'.csv');
        echo $this->_array2csv($data);
        exit;
    }

    public static $method = [
        "list"=>"",
        "date_between"=>"date_between",
        "int_between"=>"int_between",
        "float_between"=>"float_between",
    ];

    public function _option($variable,$option,$conditon){
        $method = ["list","date_between"];
        $q_str = "";
        if(isset(self::$method[$option])){
            switch ($option){
                case 'list':
                    if (!empty($conditon) && is_array($conditon)) {
                        $list = "(";
                        foreach ($conditon as $item) {
                            if ("(" == $list) {
                                $list = $list . $variable.":" . $item;
                            } else {
                                $list = $list . " OR ".$variable.":" . $item;
                            }
                        }

                        $list = $list . ")";
                        if ("()" != $list) {
                            $q_str = $list;
                        }
                    }
                    break;
                case 'date_between':
                    if (!empty($conditon) && is_array($conditon)) {
                        $count = count($conditon);
                        if(2 == $count){
                            $q_str = "[";
                            if(!empty($conditon[0])){
                                $date = date("Y-m-d",$conditon[0])."T";
                                $house = date("H:i:s",$conditon[0])."Z";
                                $q_str = $q_str.$date.$house." TO ";
                            }else{
                                $q_str = $q_str."* TO ";
                            }

                            if(!empty($conditon[1])){
                                $date = date("Y-m-d",$conditon[1])."T";
                                $house = date("H:i:s",$conditon[1])."Z";
                                $q_str = $q_str.$date.$house;
                            }else{
                                $q_str = $q_str."*";
                            }
                            $q_str = $q_str."]";
                            if("[]" != $q_str){
                                $q_str = $variable.":".$q_str;
                                return $q_str;
                            }else{
                                return "";
                            }

                        }
                    }
                    break;
                case "int_between":
                    if (!empty($conditon) && is_array($conditon)) {
                        $count = count($conditon);
                        if(2 == $count){
                            $q_str = "[";
                            if(!empty($conditon[0])){
                                $q_str = $q_str.$conditon[0]." TO ";
                            }else{
                                $q_str = $q_str."* TO ";
                            }

                            if(!empty($conditon[1])){
                                $q_str = $q_str.$conditon[1];
                            }else{
                                $q_str = $q_str."*";
                            }
                            $q_str = $q_str."]";
                            if("[]" != $q_str){
                                $q_str = $variable.":".$q_str;
                                return $q_str;
                            }else{
                                return "";
                            }

                        }
                    }
                    break;
                case "float_between":
                    if (!empty($conditon) && is_array($conditon)) {
                        $count = count($conditon);
                        if(2 == $count){
                            $q_str = "[";
                            if(!empty($conditon[0])){
                                $q_str = $q_str.$conditon[0]." TO ";
                            }else{
                                $q_str = $q_str."* TO ";
                            }

                            if(!empty($conditon[1])){
                                $q_str = $q_str.$conditon[1];
                            }else{
                                $q_str = $q_str."*";
                            }
                            $q_str = $q_str."]";
                            if("[]" != $q_str){
                                $q_str = $variable.":".$q_str;
                                return $q_str;
                            }else{
                                return "";
                            }

                        }
                    }
                    break;
                default:
                    break;
            }

            return $q_str;

        }else{
            return $q_str;
        }

    }


}