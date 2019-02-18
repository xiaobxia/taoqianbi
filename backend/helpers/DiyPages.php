<?php

namespace backend\helpers;


    class DiyPages {
          private $total;      //总记录
          private $pagesize;    //每页显示多少条
          private $limit;          //limit
          public $page;           //当前页码
          private $pagenum;      //总页码
          private $url;           //地址
          private $bothnum;      //两边保持数字分页的量
          private $type;

      //构造方法初始化
      public function __construct($_total, $_pagesize,$type=1) {
         $this->total = $_total ? $_total : 1;
         $this->pagesize = $_pagesize;
          $this->pagenum = ceil($this->total / $this->pagesize);
         $this->page = $this->setPage();
         //$this->limit = "LIMIT ".($this->page-1)*$this->pagesize.",$this->pagesize";
         $this->url = $this->setUrl();
         $this->bothnum = 2;
         $this->type = $type;
      }


      //获取当前页码
      private function setPage() {
         if (!empty($_GET['page'])) {
                if ($_GET['page'] > 0) {
                   if ($_GET['page'] > $this->pagenum) {
                          return $this->pagenum;
                   } else {
                          return $_GET['page'];
                   }
                } else {
                   return 1;
                }
         } else {
                return 1;
         }
      }

      //获取地址
      private function setUrl() {
         $_url = $_SERVER["REQUEST_URI"];
         $_par = parse_url($_url);
         if (isset($_par['query'])) {
                parse_str($_par['query'],$_query);
                unset($_query['page']);
                $_url = $_par['path'].'?'.http_build_query($_query);
         }
         return $_url;
      }     //数字目录
      private function pageList() {
      	$_pagelist = '';
         for ($i=$this->bothnum;$i>=1;$i--) {
            $_page = $this->page-$i;
            if ($_page < 1) continue;
                $_pagelist .= ' <a href="'.$this->url.'&page='.$_page.'" data-page="'.$_page.'" type="'.$this->type.'">'.$_page.'</a> ';
         }
         $_pagelist .= ' <span  href="'.$this->url.'&page='.$_page.'" data-page="'.$_page.'" style="background:#3325ff;color:#fff;border: 1px solid #ddd;line-height: 1.42857;margin-left: -1px;padding: 4px 10px;position: relative;text-decoration: none;border-radius: 1px;" type="'.$this->type.'">'.$this->page.'</span> ';
         for ($i=1;$i<=$this->bothnum;$i++) {
            $_page = $this->page+$i;
                if ($_page > $this->pagenum) break;
                $_pagelist .= ' <a href="'.$this->url.'&page='.$_page.'" data-page="'.$_page.'" type="'.$this->type.'">'.$_page.'</a> ';
         }
         return $_pagelist;
      }

      //首页
      private function first() {
         if ($this->page > $this->bothnum+1) {
                return ' <a href="'.$this->url.'" data-page="1" type="'.$this->type.'">1</a> ...';
         }
      }

      //上一页
      private function prev() {
         if ($this->page == 1) {
                return '<span class="disabled">«</span>';
         }
         return ' <a href="'.$this->url.'&page='.($this->page-1).'" data-page="'.($this->page-1).'" type="'.$this->type.'">«</a> ';
      }

      //下一页
      private function next() {
         if ($this->page == $this->pagenum) {
                return '<span class="disabled">»</span>';
         }
         return ' <a href="'.$this->url.'&page='.($this->page+1).'" data-page="'.($this->page+1).'" type="'.$this->type.'">»</a> ';
      }

      //尾页
      private function last() {
         if ($this->pagenum - $this->page > $this->bothnum) {
                return ' ...<a href="'.$this->url.'&page='.$this->pagenum.'" data-page="'.$this->pagenum.'" type="'.$this->type.'">'.$this->pagenum.'</a> ';
         }
      }

      private function total(){
      	return "<span>共{$this->total}条记录</span>";
      }
    public $_page;
      //分页信息
      public function showpage() {
         $this->_page .= $this->prev();
         $this->_page .= $this->first();
         $this->_page .= $this->pageList();
         $this->_page .= $this->last();
         $this->_page .= $this->next();
         $this->_page .= $this->total();
         return $this->_page;
      }
 }

?>
