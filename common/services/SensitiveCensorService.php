<?php
/**
 * 敏感词检测
 * TrieTree
 */
namespace common\services;

use Yii;
use yii\base\Component;
use common\models\SensitiveDict;

class SensitiveCensorService extends Component
{
    const CACHE_DICT_KEY = 'wzd:sensitive_censor:dict';
    const CACHE_DICT_DURATION = 86400;

    private $dict = [];
    private $trieTree = null;

    public function __construct() {
        if (!$this->trieTree) {
            $this->dict = \yii::$app->cache->getOrSet(self::CACHE_DICT_KEY, function() {
                return SensitiveDict::find()
                    ->select(['name'])
                    ->indexBy("CONCAT(id, 'node')")
                    ->orderBy(['id' => SORT_DESC])
                    ->asArray()->column();
            }, self::CACHE_DICT_DURATION);
            $this->trieTree = new TrieTree;
            foreach ($this->dict as $dict) {
                $this->trieTree->addString($dict);
            }
        }
    }

    protected function getDict() {
        return SensitiveDict::find()
            ->select(['name'])
            ->indexBy("CONCAT(id, 'node')")
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->column();
    }

    /**
     * 敏感词搜索
     * @param string $str [description]
     * @return [boolean] [description]
     */
    public function censor($str) {
        if (empty($str)) return false;
        $str = $this->brushString($str);
        if (empty($str)) return false;

        $trieTree = $this->trieTree;
        $searchArray = $trieTree->searchFullString($trieTree, $str);
        if (empty($searchArray)) return false;
        foreach ($searchArray as $key => $val) {
            if (substr_count($str, $val)) {
                return $val; //匹配到敏感词
            }
        }
        return false;
    }

    /**
     * 过滤特殊字符串
     * @return [string] [description]
     */
    private function brushString($content) {
        $search = array('*','$','\\','/',"'",'"',"and","{","}");
        return str_replace($search, '', $content);
    }

    public function test() {
        $a = $this->brushString('阿斯达斯大师赛的');
        print_r($a);die;
        $trieTree = $this->trieTree;

        // 添加单词
        // $trieTree->addString('hewol');
        // $trieTree->addString('hemy');
        // $trieTree->addString('heml');
        // $trieTree->addString('you');
        // $trieTree->addString('yo');

        // 获取所有单词
        // $str_array = $trieTree->getChildString($trieTree);

        // print_r($this->dict);die;
        // 搜索
        // $search_array = $trieTree->searchString($trieTree, '爱女人');
        $search_array = $trieTree->searchFullString($trieTree, '你好啊地方爱女人包二奶');
        print_r($search_array);die;
        // 循环打印所有搜索结果
        foreach ($search_array as $key => $value) {
            echo '爱' . $value . '<br>' . PHP_EOL;  // 输出带上搜索前缀
        }

        // print_r($str_array);die;
    }

}

class TrieTree {

    public $value;                 // 节点值
    public $is_end = false;        // 是否为结束--是否为某个单词的结束节点
    public $childNode = array();   // 子节点

    /* 添加孩子节点--注意：可以不为引用函数，因为PHP对象赋值本身就是引用赋值 */
    public function &addChildNode($value, $is_end = false) {
        $node = $this->searchChildNode($value);
        if(empty($node)) {
            // 不存在节点，添加为子节点
            $node = new TrieTree();
            $node->value = $value;
            $this->childNode[] = $node;
        }
        if (!$node->is_end) { //单词会覆盖
            $node->is_end = $is_end;
        }
        return $node;
    }

    /* 查询子节点 */
    public function searchChildNode($value) {
        foreach ($this->childNode as $k => $v) {
            if($v->value == $value) {
                // 存在节点，返回该节点
                return $this->childNode[$k];
            }
        }
        return false;
    }

    /* 添加字符串 */
    public function addString($str) {
        $node = null;
        for ($i=0; $i < mb_strlen($str); $i++) {
            $strSub = trim(mb_substr($str, $i, 1));
            if ($strSub !== ' ') {
                $is_end = $i == (mb_strlen($str) - 1);
                if ($i == 0) {
                    $node = $this->addChildNode($strSub, $is_end);
                }
                else {
                    $node = $node->addChildNode($strSub, $is_end);
                }
            }
        }
    }

    /* 获取所有字符串--递归 */
    public function getChildString($node, $str_array = array(), $str = '') {
        // 如果到达节点 则加入数组
        if ($node->is_end == true) {
            $str_array[] = $str;
        }
        if (empty($node->childNode)) {
            return $str_array;
        } else {
            foreach ($node->childNode as $k => $v) {
                $str_array = $this->getChildString($v, $str_array, $str . $v->value);
            }
            return $str_array;
        }
    }

    //模糊搜索
    public function searchString($node, $str) {
        for ($i=0; $i < mb_strlen($str); $i++) {
            $strSub = trim(mb_substr($str, $i, 1));
            if(!empty($strSub)) {
                $node = $node->searchChildNode($strSub);
                if(empty($node)) {
                    // 不存在返回空
                    return false;
                }
            }
        }
        return $this->getChildString($node);
    }

    /**
     * 完全匹配
     * 搜索词库中存在的敏感词
     * @param  [type] $node [description]
     * @param  [type] $str  [description]
     * @return [type]       [description]
     */
    public function searchFullString($node, $str) {
        $nodeArr = [];
        for ($i=0; $i < mb_strlen($str); $i++) {
            $strSub = trim(mb_substr($str, $i, 1));
            if(!empty($strSub)) {
                $nodes = $node->searchChildNode($strSub);
                if(!empty($nodes)) { // 保存搜索到的节点
                    $nodeArr[] = $nodes;
                }
            }
        }

        $searchArr = [];
        foreach ($nodeArr as $k => $v) {
            $tmpArr = $this->getChildString($v);
            $head = $v->value;
            $tmpArr = array_map(function($val) use($head) {
                return $head . $val;
            }, $tmpArr);
            $searchArr = array_merge($searchArr, $tmpArr);
        }
        return $searchArr;
    }

    /**
     * 首字母匹配
     * @param  [type] $node [description]
     * @param  [type] $str  [description]
     * @return [type]       [description]
     */
    public function searchFullStringH($node, $str) {
        $i = 0;
        $strSub = trim(mb_substr($str, $i, 1));
        $searchValue = mb_substr($str, 1, mb_strlen($str) - 1);
        if(!empty($strSub)) {
            $node = $node->searchChildNode($strSub);
            if(empty($node)) {
                // 不存在返回空
                return false;
            }
        }
        $searchArr = $this->getChildString($node);
        return in_array($searchValue, $searchArr);
    }

}

