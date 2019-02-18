<?php
namespace BestSignSDK;

define('CONFIG_NAME', 'demo');

require(__DIR__ . '/initialize.php');
require(dirname(__DIR__) . '/src/SDK.php');

error_reporting(E_ALL);

$server_config = getServerConfig();
$mid = $server_config['mid'];
$pem = $server_config['pem'];
$host = $server_config['host'];

$sdk = SDK::getInstance($mid, $pem, $host);
$sdk->setDebugLevel(Logger::DEBUG_LEVEL_INFO);

$last_contract = null;

testAll();
echo "\nFinish!\n";

function testAll()
{
    outputInfo();
    
    sjdsendcontractdocUpload();
    
    sjdsendcontract();
    
    uploaduserimage();
    
    queryuserimage();
    
    getSignPageSignimagePc();
    
    AutoSignbyCA();
    
    ViewContract();
    
    contractDownload();
    
    contractDownloadMobile();
    
    certificateApply();
    
    certificateApply2();
}

//****************************************************************************************************
// demo functions
//****************************************************************************************************
//合同上传
function sjdsendcontractdocUpload()
{
    outputInfo();
    
    addToLog('');
    addToLog('测试合同上传...');
    $result = createContract();
    addToLog('测试合同上传结果:');
    dump($result);
}

//合同创建(通过模版)
function uploadcontractly()
{
    global $sdk;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试合同上传(模版)...');
    
    $senduser = SendUser::buildData("22345678@163.com", "Test2", "13912345678", 3, false, Constants::USER_TYPE_PERSONAL, false, "title", "");
    
    //以下的模版变量, 都跟具体模版有关, demo只是演示一下
    //具体 tid
    $tid = '254'; 
    
    //具体模版变量值
    $receive_user = ReceiveUser::buildData("1234567@qq.com", "Test1", '13812345678', Constants::USER_TYPE_PERSONAL, Constants::CONTRACT_NEEDVIDEO_NONE, false);
    $sign0_value = $receive_user;
    $t1_value = 'the template var - 1';
    
    $tempcontents = array();
    $tempcontents['sign0']['type'] = 'sign';
    $tempcontents['sign0']['value'] = $sign0_value;
    $tempcontents['t1']['type'] = 'text';
    $tempcontents['t1']['value'] = $t1_value;
    
    addToLog('合同上传(模版)...');
    $result = $sdk->uploadcontractly($tid, $senduser, $tempcontents);
    addToLog('测试合同上传(模版)结果:');
    dump($result);
}

//追加签署人
function sjdsendcontract()
{
    global $sdk;
    global $last_contract;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试追加签署人...');
    
    if (empty($last_contract)) {
        addToLog('测试追加签署人, 先创建一份合同...');
        createContract();
    }
    $contractInfo = $last_contract['response']['content']['contlist'][0]['continfo'];
    
    $signid = $contractInfo['signid'];
    $userlist = randomReceiveUser();
    $userlist = array($userlist);
    
    addToLog('追加签署人...');
    $result = $sdk->sjdsendcontract($signid, $userlist);
    addToLog('测试追加签署人结果:');
    dump($result);
}

//用户图片上传接口
function uploaduserimage()
{
    global $sdk;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试用户图片上传...');
    
    $useraccount = '';
    
    $usermobile = '13005271034';
    $usertype = Constants::USER_TYPE_PERSONAL;
    $username = 'Test13005271034';
    
    $image = getResource('demo.jpg');
    $imgType = 'jpg';
    $imgName = 'demo.jpg';
    
    $result = $sdk->uploaduserimage($useraccount, $usermobile, $imgType, $image, $imgName, $username);
    addToLog('测试用户图片上传结果:');
    dump($result);
}

//用户图片查询 
function queryuserimage()
{
    global $sdk;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试用户图片查询...');
    
    $usermobile = '13005271034';
    $result = $sdk->queryuserimage($usermobile);
    addToLog('测试用户图片查询结果:');
    dump($result);
}

//手动签合同
function getSignPageSignimagePc()
{
    global $sdk;
    global $last_contract;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试手动签合同...');
    
    addToLog('测试手动签合同, 先创建一份合同...');
    createContract();
    $contractInfo = $last_contract['response']['content']['contlist'][0]['continfo'];
    
    $fsid = $contractInfo['signid'];
    //$fsid = '1456194891314YI8P2';
    $email = '1234567@qq.com';
    $pagenum = 1;
    $signx = '0.001';
    $signy = '0.001';
    $returnurl = 'http://www.baidu.com/';
    $typedevice = Constants::DEVICE_TYPE_PC;
    $openflagString = 0;
    
    $result = $sdk->getSignPageSignimagePc($fsid, $email, $pagenum, $signx, $signy, $returnurl, $typedevice, $openflagString);
    addToLog('测试手动签合同结果:');
    dump($result);
}

//自动签合同
function AutoSignbyCA()
{
    global $sdk;
    global $last_contract;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试自动签合同...');
    
    addToLog('测试自动动签合同, 先创建一份合同...');
    createContract();
    $contractInfo = $last_contract['response']['content']['contlist'][0]['continfo'];
    
    //自动签这份合同
    $signid = $contractInfo['signid'];
    $email = '1234567@qq.com';
    $pagenum = 1;
    $signx = '0.001';
    $signy = '0.001';
    $openflag = 0;
    
    $result = $sdk->AutoSignbyCA($signid, $email, $pagenum, $signx, $signy, $openflag);
    addToLog('测试自动签合同结果:');
    dump($result);
}

//合同预览
function ViewContract()
{
    global $sdk;
    global $last_contract;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试合同预览...');
    
    if (empty($last_contract))
    {
        addToLog('测试合同预览, 先创建一份合同...');
        createContract();
    }
    $contractInfo = $last_contract['response']['content']['contlist'][0]['continfo'];
    
    $signid = $contractInfo['signid'];
    $docid = $contractInfo['docid'];
    $status = 3;
    
    $result = $sdk->ViewContract($signid, $docid, $status);
    addToLog('测试合同预览结果:');
    dump($result);
}

//合同下载
function contractDownload()
{
    global $sdk;
    global $last_contract;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试合同下载...');
    
    if (empty($last_contract))
    {
        addToLog('测试合同下载, 先创建一份合同...');
        createContract();
    }
    $contractInfo = $last_contract['response']['content']['contlist'][0]['continfo'];
    
    //自动签这份合同
    $signid = $contractInfo['signid'];
    $status = 3;
    
    $result = $sdk->contractDownload($signid, $status);
    addToLog('测试合同下载结果:');
    dump($result);
}

//合同pdf下载
function contractDownloadMobile()
{
    global $sdk;
    global $last_contract;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试合同pdf下载...');
    
    if (empty($last_contract))
    {
        addToLog('测试合同pdf下载, 先创建一份合同...');
        createContract();
    }
    $contractInfo = $last_contract['response']['content']['contlist'][0]['continfo'];
    
    //自动签这份合同
    $signid = $contractInfo['signid'];
    $status = 3;
    
    $result = $sdk->contractDownloadMobile($signid, $status);
    addToLog('测试合同pdf下载结果:');
    dump($result);
}

//模版创建
function templateCreate()
{
    global $sdk;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试模版创建...');
    
    $rtick = time() . rand(1000, 9999);
    $uid = "user1";
    
    $result = $sdk->templateCreate($rtick, $uid);
    addToLog('测试模版创建结果:');
    dump($result);
}

//CFCA证书申请
function certificateApply()
{
    global $sdk;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试cfca证书申请...');
    
    $ca_type = Constants::CA_TYPE_CFCA;
    $name = '张三';
    $password = 'zs@zhangshan@123456';
    $link_mobile = "130" . rand(10000000, 99999999);
    $email = $link_mobile . "@mobile.cn";
    $link_id_code = randomIDCard();
    //String identNo = "220465197106285386";
    $address = '北京市';
    
    $result = $sdk->certificateApply($ca_type, $name, $password, $name, $link_mobile, $email, $address, "浙江省", "杭州市", $link_id_code);
    addToLog('测试cfca证书申请结果:');
    dump($result);
}

//ZJCA证书申请
function certificateApply2()
{
    global $sdk;
    
    outputInfo();
    
    addToLog('');
    addToLog('测试zjca证书申请...');
    
    $ca_type = Constants::CA_TYPE_ZJCA;
    $name = '张三';
    $password = 'zs@zhangshan@123456';
    $link_mobile = "130" . rand(10000000, 99999999);
    $email = $link_mobile . "@mobile.cn";
    $link_id_code = randomIDCard();
    //String identNo = "220465197106285386";
    $address = '北京市';
    
    $result = $sdk->certificateApply($ca_type, $name, $password, $name, $link_mobile, $email, $address, "浙江省", "杭州市", $link_id_code);
    addToLog('测试zjca证书申请结果:');
    dump($result);
}

//****************************************************************************************************
// helper functions
//****************************************************************************************************
//创建一份新合同用来进行测试
function createContract()
{
    global $sdk;
    global $last_contract;
    
    $file_data = getResource('demo.pdf');
    
    $receive_user = ReceiveUser::buildData("1234567@qq.com", "Test1", '13812345678', Constants::USER_TYPE_PERSONAL, Constants::CONTRACT_NEEDVIDEO_NONE, false);
    $userlist = array($receive_user);
    
    $senduser = SendUser::buildData("22345678@163.com", "Test2", "13912345678", 3, false, Constants::USER_TYPE_PERSONAL, false, "title", "");
    $senduser = array($senduser);
    
    $last_contract = $sdk->sjdsendcontractdocUpload($userlist, $senduser, $file_data);
    return $last_contract;
}

//随机ReceiveUser
function randomReceiveUser()
{
    $mobile = "1300" . rand(1000000, 9999999);
    $email = $mobile . "@qq.com";
    $name = "Test" . $mobile;
    $user = ReceiveUser::buildData($email, $name, $mobile, Constants::USER_TYPE_PERSONAL, Constants::CONTRACT_NEEDVIDEO_NONE, false);
    return $user;
}

//随机身份证
function randomIDCard()
{
    $a = rand(110000, 650999);
    
    $yy = rand(1950, 2000);
    $mm = rand(3, 12);
    $dd = rand(1, 30);
    if (strlen($mm) != 2)
    {
        $mm = '0' . $mm;
    }
    if (strlen($dd) != 2)
    {
        $dd = '0' . $dd;
    }
    
    $b = rand(0, 999);
    if (strlen($b) == 1)
    {
        $b = '00' . $b;
    }
    else if (strlen($b) == 2)
    {
        $b = '0' . $b;
    }
    
    $s = $a . $yy . $mm . $dd . $b;
    //System.out.println(s);
    
    $nums = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
    $m = 0;
    for ($i = 0; $i < strlen($s); $i++)
    {
        $c = substr($s, $i, 1);
        $n = (int)$c;
        $n2 = $nums[$i];
        $m += $n * $n2;
    }
    $m = $m % 11;
    
    $codes = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
    $code = $codes[$m];
    $s .= $code;
    return $s;
}

function getResource($file_name)
{
    $path = __DIR__ . '/resources/' . $file_name;
    return file_get_contents($path);   
}

function dump($value)
{
    $output = print_r($value, true);
    Logger::addToLog($output);
    if (Logger::getDebugLevel() == Logger::DEBUG_LEVEL_INFO && strlen($output) > 1030)
    {
        $output = substr($output, 0, 1024) . "......";
    }
    echo "{$output}\n";
}

function addToLog($message)
{
    Logger::addToLog($message);
    echo print_r($message, true) . "\n";
}

function outputInfo()
{
    global $is_output_info;
    global $mid;
    global $host;
    
    if ($is_output_info)
    {
        return;
    }
    $is_output_info = true;
    $output = 'Test mid: ' . $mid . '; host: ' . $host;
    Logger::addToLog($output);
    echo "{$output}\n";
}

function getServerConfig()
{
    require(__DIR__ . '/conf/config.php');
    if (!array_key_exists(CONFIG_NAME, $SERVERS_CONFIG))
    {
        die('ERROR: $SERVERS_CONFIG[' . CONFIG_NAME . "] not exists\n");
    }
    return $SERVERS_CONFIG[CONFIG_NAME];
}
