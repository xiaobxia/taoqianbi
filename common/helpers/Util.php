<?php
namespace common\helpers;

use yii\helpers\Url;
use common\models\LoanPerson;
use common\models\UserCreditTotal;
use common\exceptions\UserExceptionExt;

class Util {
    /**
     * 输出对象
     * @param all $rows     需要输出的对象
     * @param bool $type    以print_r还是var_dump来输出
     * @param bool $exit    输出后是否中止
     */
    public static function pr($rows, $type = false, $exit = false)
    {
        echo '<pre>';
        $type ? var_dump($rows) : print_r($rows);
        echo '</pre>';
        $exit && exit();
    }

    /**
     * 将一维数组转为 sql condition 字符串 (['a' => 1, 'b' => 2, 'c => '+1'] To: a=1, b=2, c=c+1)
     * @param array 需要转换的数组对象
     * @return string
     */
    public static function arrayToStr(array $datas = [])
    {
        $str = '';
        if(!empty($datas))
        {
            $s = array('like', 'update', 'id', 'key', 'starting');

            $i = 1;
            $dataCount = count($datas);
            foreach($datas as $key => $data)
            {
                $str .= (in_array($key, $s) ? '`' . $key . '`' : $key) . ($data && in_array($data, array('?+1', '?-1', '?+2', '?-2')) ? '=' . $key . strtr($data, array('?' => NULL)) : '=\'' . mysql_escape_string($data) . '\'') . ($i < $dataCount ? ', ' : NULL);
                $i++;
            }
        }
        return $str;
    }

    public static function exportXml($filename, $title, $data)
    {
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename={$filename}.xls");
        header('Pragma: no-cache');
        header('Expires: 0');

        echo iconv('utf-8', 'GB2312//IGNORE', implode("\t", $title)), "\n";
        foreach ($data as $value) {
            echo iconv('utf-8', 'GB2312//IGNORE', implode("\t", $value)), "\n";
        }

        exit();
    }

    public static function exportCsv($filename, $title, $data)
    {
        $str = '';
        $str .= iconv('utf-8', 'gb2312', implode(",", $title)). "\n";
        foreach ($data as $value) {
            $str .= iconv('utf-8', 'gb2312', implode(",", $value)). "\n";
        }
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        echo $str;
        exit();
    }

    /**
     * 用户IP
     */
    public static function getUserIP() {
        if (!\Yii::$app instanceof \yii\web\Application) {
            return "192.168.0.1";
        }

        $ip = \Yii::$app->getRequest()->getUserIP();
        if (\strstr($ip, ',')) {//含有逗号
            $a = \explode(',', $ip);
            return trim($a[1]);
        }
        else if (empty($ip) || strstr($ip, ":")) {
            return "192.168.0.1";
        }
        else {
            return $ip;
        }
    }

    /**
     * 用户类型
     */
    public static function getClientType() {
        if (@$_SERVER['SERVER_NAME'] == 'qbm.wzdai.com') {
            return 'wap';
        }
        //else
        if (\method_exists(\Yii::$app->request, 'getClient')) {
            $client = \Yii::$app->request->getClient();

            return $client->clientType;
        }

        return "pc";
    }

    /**
     * 验证手机
     */
    public static function verifyPhone($phone) {
        return preg_match('/^1[0-9]{10}$/', $phone);
    }

    public static $_config = [];

    /**
     * 载入配置文件
     * @param $name as (@mobile/test,@frontent/test,test)
     * 例子:Util::loadConfig('@frontend/test'),Util::loadConfig('test')
     */
    public static function loadConfig($name) {
        if (!isset(self::$_config[$name])) {
            if(!strncmp($name, '@', 1)){//use an alias
                $pos = strpos($name, '/');
                $root = \Yii::getAlias(substr($name,0, $pos)).'/config';
                $configFilePath = $root.substr($name, $pos);
            }else if(strpos($name, '/') || strpos($name, '\\')){
                $configFilePath = realpath($name);
            }else{
                $configFilePath = \Yii::$app->getBasePath() . DIRECTORY_SEPARATOR . 'config/'.$name;
            }
            $configFilePath = $configFilePath.'.php';
            if (!file_exists($configFilePath)) {
                throw new \yii\base\Exception('load config data '.$name.' failed');
            }
            self::$_config[$name] = require($configFilePath);
        }
        return self::$_config[$name];
    }

    /**
     * 信用卡app根据借款天数和金额,计算服务费
     * @param integer $period
     * @param float $money
     */
    public static function calcLoanInfo($period, $money, $card_type=\common\models\BaseUserCreditTotalChannel::CARD_TYPE_ONE, $user_id = null){
        if(\common\models\BaseUserCreditTotalChannel::CARD_TYPE_TWO == $card_type && $period >= 7){
            return self::calcMultiLoanInfo($period, $money);
        }else{
            $config = \Yii::$app->params['amount_days_list'];
        }
        if(!$config){
            UserExceptionExt::throwMsg('系统错误');
        }
        if(!in_array($period, $config['days']) || !$config['days']){
            UserExceptionExt::throwMsg('借款天数无效');
        }
        // if(!in_array($money, $config['amounts']) || !$config['amounts']){
        //     UserExceptionExt::throwMsg('借款金额无效');
        // }
        $interests =  $config['interests'];
        $baseMoney = end($config['amounts']);
        $rate = $money/$baseMoney;
        $ret = [];
        if ($user_id || $user_id = \yii::$app->user->identity->id) {
            $user_credit_total = UserCreditTotal::findOne(['user_id' => $user_id]);
            $config_counter_fee_rate = $user_credit_total ? $user_credit_total->counter_fee_rate: \Yii::$app->params['counter_fee_rate'];
        } else {
            $config_counter_fee_rate = \Yii::$app->params['counter_fee_rate'];
        }

        $counter_fee = $money * $config_counter_fee_rate /100;
        foreach($config['days'] as $idx => $day){
            if($day == $period){
                if(isset($interests[$idx])){
                    $ret = [
                        'money' => $money,
                        'period' => $period,
                        'counter_fee' => round($counter_fee), # round($interests[$idx]*$rate),
                    ];
                }
                break;
            }
        }
        if(!$ret){
            UserExceptionExt::throwMsg('系统错误');
        }
        return $ret;
    }

    /**
     * 大写金额转换
     */
    // 阿拉伯数字转中文大写金额
    public static function numToMoney($num) {
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        //精确到分后面就不要了，所以只留两个小数位
        $num = round($num, 2);
        //将数字转化为整数
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "金额太大，请检查";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                //获取最后一位数字
                $n = substr($num, strlen($num) - 1, 1);
            } else {
                $n = $num % 10;
            }
            //每次将最后一位数字转化为中文
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            //去掉数字最后一位了
            $num = $num / 10;
            $num = (int) $num;
            //结束循环
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            //utf8一个汉字相当3个字符
            $m = substr($c, $j, 6);
            //处理数字中很多0的情况,每次循环去掉一个汉字“零”
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j - 3;
                $slen = $slen - 3;
            }
            $j = $j + 3;
        }
        //这个是为了去掉类似23.0中最后一个“零”字
        if (substr($c, strlen($c) - 3, 3) == '零') {
            $c = substr($c, 0, strlen($c) - 3);
        }
        //将处理的汉字加上“整”
        if (empty($c)) {
            return "零元整";
        } else {
            return $c . "整";
        }
    }

    /**
     * 多卡产品费率计算
     */
    public static function calcMultiLoanInfo($period,$money){
        $config = \Yii::$app->params['amount_days_list_multi'];
        if(!$config){
            UserExceptionExt::throwMsg('系统错误');
        }
        $interests = $config['interests'];
        $baseMoney = end($config['amounts']);
        $rate = $money/$baseMoney;
        $ret  = [];
        foreach($config['days'] as $idx => $day){
            if($day >= $period){
                break;
            }
        }
        if(isset($interests[$idx])){
            $ret = [
                'money' => $money,
                'period' => $period,
                'counter_fee' => round($period/$day*$interests[$idx]*$rate),
            ];
        }
        if(!$ret){
            UserExceptionExt::throwMsg('系统错误');
        }
        return $ret;
    }

    /**
     * 获取活动配置
     * @param unknown $key
     * @return mixed
     */
    public static function getActInfos($key){
        $info = \Yii::$app->params[$key];
        $time = time();
        if($info && isset($info['start_time']) &&  isset($info['end_time'])
                && $time >= strtotime($info['start_time']) && $time <= strtotime($info['end_time'])){
            return \Yii::$app->params[$key];
        }
        return [];
    }

    /**
     * 计算零钱包服务费
     * @param unknown $period
     * @param unknown $money
     * @param unknown $pocket_apr
     */
    public static function calcLqbLoanInfo($period, $money, $pocket_apr, $card_type = 1) {
        if ($card_type == \common\models\BaseUserCreditTotalChannel::CARD_TYPE_TWO) {//金卡
            if ($period == 21) {
                return round(16000*$money*$period/30/100000);
            }else{
                return round(15000*$money*$period/30/100000);
            }
        }

        if ($period == 14) {
            return round(15000 * $money / 100000);
        }else if($period == 21){
            return round(16000*$money/100000);
        }

        return round($money*$pocket_apr/100*$period);
    }

    /**
     * 获取零钱包贷款期限
     */
    public static function getLqbLoanPeriod(){
        $info = self::getActInfos('ygd_act_national_day');
        $now = time();
        if ($now >= strtotime("2017-01-11 00:00:00") && $now <= strtotime("2017-01-16 23:59:59")) {
            return 7;
        }elseif ($now >= strtotime("2017-01-17 00:00:00") && $now <= strtotime("2017-01-19 23:59:59")) {
            return 21;
        }elseif ($now >= strtotime("2017-01-20 00:00:00") && $now <= strtotime("2017-01-27 23:59:59")) {
            return 14;
        }
        if($info && isset($info['day'])){
            return $info['day'];
        }
        return 7;
    }

    /**
     * 生成页面顶部面包屑
     * @param array $menu
     * @param int $select_idx 高亮选项
     * @param boolean $with_get 附加上get参数
     * @return array
     */
    public static function getBreadCrumbsMenu(array $menu, $select_idx=NULL, $with_get = false) {
        $param = $with_get ? \Yii::$app->request->get() : [];

        if (NULL === $select_idx) { //根据 route 计算
            foreach($menu as $_idx => $_item) {
                if (\is_array($_item[1]) ) { #array
                    if (isset($_item[1][0]) && \yii::$app->requestedRoute == $_item[1][0]) {
                        $select_idx = $_idx;
                        break;
                    }
                }
                else { #string
                    if (isset($_item[1]) && \yii::$app->requestedRoute == $_item[1]) {
                        $select_idx = $_idx;
                        break;
                    }
                }
            }
        }

        foreach ($menu as &$_item) {
            // $_item[0] 是页面title
            if (! \is_array($_item[1])) { //convert array
                $_item[1] = [ $_item[1] ];
            }

            $_item[1] = Url::toRoute(\array_merge($_item[1], $param));
            $_item[2] = 0;
        }

        $select_idx = $select_idx ?: 0; #仍未算出该该高亮哪个.
        $menu[$select_idx][2] = \intval( isset($menu[$select_idx][2]) );

        return $menu;
    }

    /**
     * 根据不同的渠道商，返回不同的不同的文字
     * @param string $key
     * @param string $channel
     */
    public static function t($key, $channel='') {
        $name = 'm';
        if (!$channel) {
            if (\Yii::$app instanceof \yii\web\Application) {
                $channel = 'xqb'; # 沿用
            }
            if (YII_ENV_TEST) {
                $channel = 'xqb_test';
            }
        }
        if ($channel) {
            $name = $name.'_'.$channel;
        } else {
            $name = $name . '_xqb';
        }

        $config = self::loadConfig('@common/message/'.$name);
        if (isset($config[$key])) {
            return $config[$key];
        }

        return '';
    }

    /**
     * 获得对应的 view 文件夹
     * @param string $controller_name
     * @return string
     */
    public static function getViewPath($controller_name) {
        $view_folder = \preg_replace_callback('/([A-Z])/', function($m) {
            return \strtolower( "-{$m[0]}" );
        }, \str_replace('Controller', '', $controller_name));
        $view_folder = trim($view_folder, '-');

        return \yii::$app->basePath . "/views/{$view_folder}";
    }

    /**
     * convert memory_get_usage / memory_get_peak_usage ...
     * @param int $size
     * @return string
     */
    public static function convertMem($size) {
        $unit = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];
        return @round( $size / pow( 1024, ($i = floor( log( $size, 1024 ) )) ), 2 ) . ' ' . $unit[$i];
    }

    /**
     * php-cli 公共设置修改
     * @param int $mem
     */
    public static function cliLimitChange($mem=512) {
        \set_time_limit(0);
        \ini_set('memory_limit', "{$mem}m");
    }

    /**
     * 尝试释放内存
     */
    public static function freeMem() {
        $enabled = \gc_enabled();
        if (! $enabled) {
            \gc_enable();
        }
        $ret = \gc_collect_cycles();
        if (! $enabled) {
            \gc_disable();
        }

        return $ret;
    }

    /**
     * 简单实现了通过一个sql, 过滤全表
     *
     * @param \yii\db\Connection $db
     * @param array              $config
     * 'sql_tpl' => $sql_tpl,
     * 'start' => $last_id,
     * 'step' => $step,
     * 'max_id' => $max_id,
     *
     * @param callable           $callback
     *
     * @return array
     * @throws \ErrorException
     */
    public static function goThroughTable(\yii\db\Connection $db, array $config, callable $callback = NULL) {
        $ret = [];

        $sql_tpl = $config['sql_tpl'] ?? NULL;
        $start = $config['start'] ?? 0;
        $step = $config['step'] ?? (YII_ENV_PROD ? 50000 : 10);
        $max_id = $config['max_id'] ?? NULL;
        if (empty($sql_tpl) || empty($max_id)) {
            throw new \ErrorException('sql_tpl or max_id missing.');
        }

        foreach(['{id}', '{step}'] as $_tag) {
            if ( \strpos($sql_tpl, $_tag) === FALSE ) {
                throw new \ErrorException('sql_tpl tag missing.');
            }
        }
        if (! \is_callable($callback)) {
            throw new \ErrorException('callback missing.');
        }

        do {
            CommonHelper::info(\sprintf('[%s] go through %s, %s', __FUNCTION__, $start, $step));

            $sql = \str_replace(['{id}', '{step}'], [$start, $step], $sql_tpl);
            $records = $db->createCommand($sql)->queryAll();
            if (empty($records) && ($start + $step) > $max_id) {
                break;
            }

            $ids = ArrayHelper::getColumn($records, 'id');
            if (empty($ids)) {
                throw new \ErrorException('column id missing.');
            }

            $vals = empty($col) ? $records : ArrayHelper::getColumn($records, $col);
            $_ret = call_user_func($callback, $vals);
            if ($_ret === false) {
                return $ret; #exit!
            }

            if (is_array($_ret)) {
                $ret = \array_merge($ret, $_ret); #php56这里会耗尽
            }

            $start = \max($ids);
            if ($start > $max_id) {
                break;
            }
        }
        while (! empty($records));

        return $ret;
    }

    /**
     * 简称
     * @param string $class
     * @param string $func
     * @return string
     */
    public static function className($class) {
        if (\strpos($class, '\\') !== false) {
            $_tmp = \explode('\\', $class);
            $class = \array_pop($_tmp);
        }

        return $class;
    }

    /**
     * 简称
     * @param string $class
     * @param string $func
     * @return string
     */
    public static function short($class, $func) {
        if (\strpos($class, '\\') !== false) {
            $_tmp = \explode('\\', $class);
            $class = \array_pop($_tmp);
        }

        if (\strpos($func, 'action') !== false) {
            $func = \str_replace('action', '', $func);
        }

        return \sprintf('%s::%s', $class, $func);
    }

    /**
     * 通过身份证号码获得年龄
     * @param $id_number
     * @return int
     */
    public static function getAgeFromIdNumber($id_number)
    {
        $this_yeal = intval(date('Y', time()));

        if (strlen($id_number) == 18) {
            $year = (int)substr($id_number, 6, 4);
            $month = substr($id_number, 10, 2);
            $day = substr($id_number, 12, 2);
        } elseif (strlen($id_number) == 15) {
            $year = "19" . substr($id_number, 6, 2);
            $month = substr($id_number, 8, 2);
            $day = substr($id_number, 10, 2);
        } else {
            return 0;
        }

        $age = $this_yeal - $year;
        if (date('m') < $month) {
            $age -= 1;
        } else {
            if (date('m') < $month) {
                $age -= 1;
            } elseif (date('m') == $month && date('d') < $day) {
                $age -= 1;
            }
        }

        return $age;
    }

    /**
     * app端用来判断appMarket参数
     * @return string
     */
    public static function getMarket(){
        $header = \Yii::$app->request->headers;
        $appMarket = strtolower( $header->get('appmarket') );
        $clientType = strtolower( $header->get('clientType') );

        //兼容老包
        $info = \Yii::$app->controller->getClientInfo();
        if (!$clientType) {
            $clientType = $info->clientType;
        }
        if (!$appMarket) {
            $appMarket = $info->appMarket;
        }

        $appname = 'xybt';
        if ($clientType == 'android'){
            if(stristr($appMarket,'xybt_fuli')){//三级渠道
                $appname = 'xybt_fuli';
            }elseif (stristr($appMarket,'xybt_fund')){
                $appname = 'xybt_fund';
            }elseif (stristr($appMarket,'xybt_xjbtfuli')){
                $appname = LoanPerson::APPMARKET_XJBT;
            }elseif (stristr($appMarket,'hbqb')){
                $appname = 'hbqb';
            }elseif (stristr($appMarket,'sxdai')){
                $appname = 'sxdai';
            }elseif (stristr($appMarket,'wzdai_loan')){
                $appname = 'wzdai_loan';
            }elseif (stristr($appMarket,'xybt_professional')){
                $appname = 'xybt_professional';//极速荷包专业版
            }elseif (stristr($appMarket,LoanPerson::APPMARKET_KXJIE)){
                $appname = 'kxjie';//开心借
            }elseif (stristr($appMarket,LoanPerson::APPMARKET_XH)){
                $appname = 'xh'; //享花
            }else{
                $appname = 'xybt';
            }


        }else if($clientType == 'ios'){
            switch ($info->appMarket){
                case 'AppStore':$appname = 'xybt';break;
                case 'AppStoreWelfare':$appname = 'xybt_fuli';break;
                case 'AppStoreWZD':$appname = 'wzdai_loan';break;
                case 'AppStorehbqb':$appname = 'hbqb';break;
                case 'AppStoreFund':$appname = 'xybt_fund';break;
                case 'AppStoreSxd':$appname = 'sxdai';break;
                case 'AppStoreXjbt':$appname = LoanPerson::APPMARKET_XJBT;break;
                case 'AppStoreXYBTPro':$appname = 'xybt_professional';break;//极速荷包专业版
                case 'AppStoreKXJie':$appname = 'kxjie';break;//开心借
                default: $appname = 'xybt';break;
            }
        }
        return $appname;
    }

    /**
     * 用来代替 redis 的 keys 命令
     * @param \yii\redis\Connection $redis
     * @param string $pattern
     * @throws \ErrorException
     * @return multitype:
     */
    public static function redisKeys($redis, $pattern, $callback=NULL) {
        if (\strpos($pattern, '*') === false) {
            throw new \ErrorException('none * in pattern');
        }

        $step = 5000;

        $ret = [];
        $cursor = 0;
        do {
            $redis_query = $redis->executeCommand('SCAN', [$cursor, 'match', $pattern, 'count', $step]);
            if (! empty($redis_query[1])) {
                if (\is_callable($callback)) {
                    $_ret = call_user_func($callback, $redis_query[1]);
                    if ((! empty($_ret)) && \is_array($_ret)) {
                        $ret = \array_merge($ret, $_ret);
                    }
                }
                else {
                    $ret = \array_merge($ret, $redis_query[1]);
                }
            }

            $cursor = $redis_query[0];
        } while ($cursor != 0);

        return $ret;
    }
    /**
     * 判断电话长度并截取11位
     */
    public static function getLenPhone($len,$phone){
        $phone_res = $phone;
        if(strlen($phone) > $len){
            $phone_res = substr($phone,-11,11);
        }
        return $phone_res;
    }

}
