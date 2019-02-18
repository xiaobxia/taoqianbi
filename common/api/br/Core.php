<?php
namespace common\api\br;

use common\services\ProxyService;
use Yii;
use common\models\CreditBr;

class Core
{
    private $username;
    private $password;
    private $apicode;
    private $br_login_url;
    private $tokenid;

    public $br_data_url;
    public $isLogin = false; //是否登录
    public $res_login;
    public $userList;    //列表
    private $pass = true; //是否含有查询的必填字段 name,cell,id

    public static $title = array();

    private static $_instance;


    function __construct($username, $password, $apicode, $querys, $br_login_url = 'https://api.100credit.cn/bankServer2/user/login.action')
    {
        $this->username = $username;
        $this->password = $password;
        $this->apicode = $apicode;

        $this->br_login_url = $br_login_url;
        $this->querys = $querys;

        $this->login();
    }

    public static function getInstance($username, $password, $apicode, $querys)
    {
        //对象方法不能访问普通的对象属性，所以$_instance需要设为静态的
        if (self::$_instance === null) {
            //                self::$_instance=new SqlHelper();//方式一
            self::$_instance = new self($username, $password, $apicode, $querys);//方式二
        }
        return self::$_instance;
    }

    public function login() {
        $this->res_login = $this->getLoginData();

        if (CONFIG::isDebug()) {
            Yii::info('登录结果：' . $this->res_login);
        }

        if ($this->res_login) {

            $loginData = json_decode($this->res_login, true);
            //var_dump($loginData);
            if ($loginData['code'] == 0) {
                $this->isLogin = true;
                $this->tokenid = $loginData['tokenid'];      //取得tokenid
            } else {
                $this->isLogin = false;
            }

        }
    }

    /**
     * 获取百融登录数据
     * @return mixed
     */
    public function getLoginData() {

        $postData = array(
            "userName" => $this->username,
            "password" => $this->password,
            "apiCode" => $this->apicode
        );

        return $this->post($this->br_login_url, $postData);
    }

    function mapping($headerTitle)
    {
        if (!($this->pass)) {
            return;
        }

        //正式环境套餐字段固定
        if (STATUS == 1) {
            $this->headerTitle = $headerTitle;
        }

        $arr_querys = $this->querys;

        $arr = null;
        if (is_array($headerTitle)) {
            foreach ($headerTitle as $key => $val) {
                $arr = $this->query($key, $arr_querys[$key], $val);
            }
        }
        return $arr;

    }

    //查询数据接口
    function query($filename, $url, $titles)
    {
        //未登录先登录
        if (!$this->res_login) {
            $this->login();
            return;
        }

        $data = $this->userList;
        if (STATUS == 2) {
            $meal = '';
        } else {
            $meal = join(',', $titles);
        }

        foreach ($data as $key1 => $value1) {
            if ($key1 == 'name') {
                $data[$key1] = $value1;
            }
            if ($key1 == 'mail') {
                if ($filename == 'huaxiang') {
                    $data[$key1] = array($value1);
                } else {
                    $data[$key1] = $value1;
                }
            }
            if ($key1 == 'cell') {
                if ($filename == 'huaxiang') {
                    $data[$key1] = array($value1);
                } else {
                    $data[$key1] = $value1;
                }
            }
            if (STATUS == 2 && preg_match('/meal/', $key1)) {
                $mealArr = explode('|', $value1);
                $meal = join(',', $mealArr);
            }
        }
        $data['meal'] = $meal;

        $temp_res = $this->getPostData($url, $data);
        //查询返回值
        //json string 格式
        if (empty($temp_res))
            return false;
        $temp_res_arr = json_decode($temp_res, true);
        //重新登录
        if ($temp_res_arr['code'] == 100004) {
            $this->login();
            $this->mapping();
            return;
        }

        return $temp_res;
    }

    public function getPostData($url, $data)
    {

        $postData = array(
            'tokenid' => $this->tokenid,
            'interCommand' => '1000',
            'apiCode' => $this->apicode,
            'jsonData' => json_encode($data),
            'checkCode' => md5(json_encode($data) . md5($this->apicode . $this->tokenid))
        );

        return $this->post($url, $postData);
    }

    private function validator($checktarget, $targetList)
    {
        foreach ($checktarget as $attr) {
            if (!(isset($targetList[$attr]) && strlen($targetList[$attr]) > 0)) {
                $this->pass = false;
                break;
            }
        }
        return $this->pass;
    }

    function pushTargetList($targetList, $type)
    {
        if ($targetList && is_array($targetList)) {
            switch ($type) {
                case CreditBr::SPECIAL_LIST:
                    $checktarget = ['name', 'cell', 'id'];
                    $this->validator($checktarget, $targetList);
                    break;
                case CreditBr::APPLY_LOAN_STR:
                    $checktarget = ['name', 'cell', 'id'];
                    $this->validator($checktarget, $targetList);
                    break;
                case CreditBr::REGISTER_EQUIPMENT:
                    $checktarget = ['cell', 'event', 'af_swift_number'];
                    $this->validator($checktarget, $targetList);
                    break;
                case CreditBr::SIGN_EQUIPMENT:
                    $checktarget = ['event', 'af_swift_number'];
                    $this->validator($checktarget, $targetList);
                    break;
                case CreditBr::LOAN_EQUIPMENT:
                    $checktarget = ['name', 'cell', 'id', 'event', 'af_swift_number'];
                    $this->validator($checktarget, $targetList);
                    break;
                case CreditBr::EQUIPMENT_CHECK:
                    $checktarget = ['name', 'cell', 'id', 'event', 'af_swift_number'];
                    $this->validator($checktarget, $targetList);
                    break;
                default:
                    $this->pass = false;
                    break;
            }
            if ($this->pass) {
                $this->userList = $targetList;
            }
            return $this->pass;
        } else {
            return false;
        }

    }

    public function post($url, $data, $timeout = 30)
    {

        $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;
        $ch = curl_init();
        $opt = array(
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => $timeout,
        );
        if ($ssl) {
            $opt[CURLOPT_SSL_VERIFYHOST] = FALSE;
            $opt[CURLOPT_SSL_VERIFYPEER] = FALSE;
        }
        curl_setopt_array($ch, $opt);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}
