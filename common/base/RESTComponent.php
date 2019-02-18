<?php
namespace common\base;

use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * RESTComponent
 * REST 组件
 * -------------
 * @author Verdient。
 */
class RESTComponent extends Component
{
    /**
     * @var $protocol
     * 协议
     * --------------
     * @author Verdient。
     */
    public $protocol = 'http';

    /**
     * @var $host
     * 主机
     * ----------
     * @author Verdient。
     */
    public $host = null;

    /**
     * @var $port
     * 端口
     * ----------
     * @author Verdient。
     */
    public $port = null;

    /**
     * @var $routes
     * 路由集合
     * ------------
     * @author Verdient。
     */
    public $routes = [];

    /**
     * @var $_requestUrl
     * 请求地址
     * -----------------
     * @author Verdient。
     */
    protected $_requestUrl = [];

    /**
     * init()
     * 初始化
     * ------
     * @inheritdoc
     * -----------
     * @author Verdient。
     */
    public function init(){
        if(!$this->host){
            throw new InvalidConfigException('host must be set');
        }
        if($this->protocol == 'http' && $this->port == 80){
            $this->port = null;
        }
        if($this->protocol == 'https' && $this->port == 443){
            $this->port = null;
        }
        foreach($this->routes as $name => $route){
            $this->_requestUrl[$name] = $this->protocol . '://' . $this->host . ($this->port ? (':' . $this->port) : '') . '/' . $route;
        }
    }
}