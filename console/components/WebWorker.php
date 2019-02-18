<?php

namespace console\components;

use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Autoloader;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http;
use Workerman\Protocols\HttpCache;

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * skilly
 * 
 * app 路由服务端基类
 */
class WebWorker extends Worker
{

    /**
     * 版本
     * @var string
     */
    const VERSION = '0.1.0';

    private $conn = false;
    private $map = array();
    private $access_log = array();

    public $autoload = array();
    public $on404 = "";

    public $onAppStart = NULL;

    public $max_request = 0; //设置当前app实例中每个进程处理多少请求后重启 0则表示不自动重启
 
    public function __construct($socket_name, $context_option = array())
    {
        parent::__construct($socket_name, $context_option);
    }

    public function RegisterRouter($url,callable $callback) {
        if ( $url != "/" ) {
            $url = strtolower(trim($url,"/"));
	}
	if ( is_callable($callback) ) {
            if ( $callback instanceof \Closure ) {
                $callback = \Closure::bind($callback, $this, get_class());
            }
        } else {
	       throw new \Exception('can not RegisterRouter');
        }
        $this->map[] = array($url, $callback, 1);
    }

    public function AddRouter($url,callable $callback) {
        if ( $url != "/" ) {
            $url = strtolower(trim($url,"/"));
        }
        if ( is_callable($callback) ) {
            if ( $callback instanceof \Closure ) {
                $callback = \Closure::bind($callback, $this, get_class());
            }
        } else {
            throw new \Exception('can not AddRouter');
        }
        $this->map[] = array($url, $callback, 2);
    }
    
    private function show_404($connection) {
        if ( $this->on404 ) {
            Http::header("HTTP/1.1 404 Not Found");
            $callback = \Closure::bind($this->on404, $this, get_class());
            call_user_func($callback);
        } else {
            Http::header("HTTP/1.1 404 Not Found");
            $html = '<html>
                <head><title>404 Not Found</title></head>
                <body bgcolor="white">
                <center><h1>404 Not Found</h1></center>
                <hr><center>Workerman</center>
                </body>
                </html>';
            $connection->send($html);
        }
    }

    private function auto_close($conn) {
        if ( strtolower($_SERVER["SERVER_PROTOCOL"]) == "http/1.1" ) {
            if ( isset($_SERVER["HTTP_CONNECTION"]) ){
                if ( strtolower($_SERVER["HTTP_CONNECTION"]) == "close" ) {
                    $conn->close();
                }
            }
        } else {
            if ( $_SERVER["HTTP_CONNECTION"] == "keep-alive" ) {

            } else {
                $conn->close();
            }
        }
    	$this->access_log[7] = round(microtime_float() - $this->access_log[7], 4);
    	echo implode(" - ", $this->access_log)."\n";
    }

    public function onClientMessage($connection, $data) {
    	$this->access_log[0] = $_SERVER["REMOTE_ADDR"];
    	$this->access_log[1] = date("Y-m-d H:i:s");
    	$this->access_log[2] = $_SERVER['REQUEST_METHOD'];
    	$this->access_log[3] = $_SERVER['REQUEST_URI'];
    	$this->access_log[4] = $_SERVER['SERVER_PROTOCOL'];
    	$this->access_log[5] = "NULL";
    	$this->access_log[6] = 200;
    	$this->access_log[7] = microtime_float();
        if ( empty($this->map) ) {
            $str = <<<'EOD'
<div style="margin: 200px auto;width:600px;height:800px;text-align:left;">基于<a href="http://www.workerman.net/" target="_blank">Workerman</a>实现的自带http server的web开发框架.没有添加路由，请添加路由!
<pre>$WebWorker->RegisterRouter("/",function($conn,$data) use($WebWorker){
    $conn->send("默认页");
});</pre>
</div>
EOD;
            $connection->send($str);
            return;
        }
        $this->conn = $connection;
        $url= $_SERVER["REQUEST_URI"];
        $pos = stripos($url,"?");
        if ($pos != false) {
            $url = substr($url,0,$pos);
        }
    	if ( $url != "/"){
            $url = strtolower(trim($url,"/"));
    	}
        $url_arr = explode("/",$url);
        $class = empty($url_arr[0]) ? "_default" : $url_arr[0];
        $method = empty($url_arr[1]) ? "_default" : $url_arr[1];
        
        $success = false;
        foreach($this->map as $route) {
    	    if ( $route[2] == 1) { //正常路由
        		if ( $route[0] == $url ) {
        		    $callback[] = $route[1];
        		    $success = true;
        		}
    	    } else if ( $route[2] == 2 ) {//中间件
        		if ( $route[0] == "/" ) {
        		    $callback[] = $route[1];
        		} else if ( stripos($url,$route[0]) === 0 ) {
        		    $callback[] = $route[1];
        		}
            }
    	}
        if ( isset($callback) && $success ) {
            try {
                foreach($callback as $cl) {
                    if ( call_user_func($cl) === true){
                        break;
                    }
                }
            } catch (\Exception $e) {
                if ($e->getMessage() != 'jump_exit') {
                    $this->access_log[5] = $e;
                }
                $code = $e->getCode() ? $e->getCode() : 500;
        		$this->access_log[6] = 500;
            }
        } else {
            $this->show_404($connection);
            // $msg = "class $class not found";
        }
        $this->auto_close($connection);
	
        // 已经处理请求数
        static $request_count = 0;
        // 如果请求数达到1000
        if( ++$request_count >= $this->max_request && $this->max_request > 0 ){
            Worker::stopAll();
        }
    }

    public function ServerJson($data) {
        Http::header("Content-type: application/json");
        $this->conn->send(json_encode($data));
    }

    public function ServerHtml($data) {
        $this->conn->send($data);
    }

    /**
     * Ajax响应，以json格式输出到浏览器端
     *
     * @param  bool         $status 消息状态
     * @param  string|array $info  返回的消息，如果为数组，则会用换行符转换为字符串
     * @param  mixed               任意类型的数据
     */
    public function asynReturn($info = '', $data = null, $status)
    {
        Http::header("Content-type: application/json");
        $data = [
            'status' => $status,
            'info' => $info,
            'data' => $data
        ];

        $this->conn->send(json_encode($data));
    }

    /**
     * 
     * @param  string|array $info 返回的消息
     * @param  mixed        $data 附加的数据
     */
    public function success($info = '', $data = null)
    {
        $this->asynReturn($data, $info, true);
    }

    /**
     * 临时放入公共方法
     * @return [type] [description]
     */
    public function checkParams()
    {
        $get = $_GET;
        if (!isset($get['phone'])) {
            $this->error('手机号必须传入');
        }
        if (!isset($get['msg'])) {
            $this->error('短信消息比如传入');
        }
        return $get;
    }

    /**
     * Ajax的成功返回
     *
     * @param string|array|yii\base\model $info 如果info传入的是 yii\base\model 则表示输出该模型的错误信息
     * @param  mixed                      $data 附加的数据
     */
    public function error($info = '', $data = null)
    {
        $this->asynReturn($data, $info, false);
    }
   
    public function Header($str) {
	   Http::header($str);
    }

    public function Setcookie($name, $value = '', $maxage = 0, $path = '', $domain = '', $secure = false, $HTTPOnly = false) {
	   Http::setcookie($name, $value, $maxage, $path, $domain, $secure, $HTTPOnly);
    }

    public function run()
    {
    	$this->reusePort = true;
    	$this->onWorkerStart = $this->onAppStart;
        $this->onMessage = array($this, 'onClientMessage');
        parent::run();
    }


//    public static function runAll()
//    {
//    	$this->reusePort = true;
//    	$this->onWorkerStart = $this->onAppStart;
//        $this->onMessage = array($this, 'onClientMessage');
//        parent::runAll();
//    }

}

function autoload_dir($dir_arr) {
    extract($GLOBALS);
    foreach($dir_arr as $dir ) {
        foreach(glob($dir.'*.php') as $start_file)
        {
            require_once $start_file;
        }
    }
}
