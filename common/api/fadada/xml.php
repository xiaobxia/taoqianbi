<?php
namespace common\api\fadada;

/*
* example 读取数据： 
* 
* $xml = new xml("dbase.xml",'table'); 
* 
* $data=$xml->xml_fetch_array(); 
* 
* echo "<pre style="font-size:12px;">"; 
* 
* print_r($data); 
*/
class xml 
{ 
var $dbase; //数据库,要读取的XML文件 
var $dbname; //数据库名称,顶层元素,与数据库文件名称一致 
var $dbtable; //数据表,要取得的节点 
var $parser; //剖析器 
var $vals; //属性 
var $index; //索引 
var $dbtable_array;//节点数组 
var $array; //下级节点的数组 
var $result; //返回的结果 
var $querys; 
function xml($dbase,$dbtable) 
{ 
$this->dbase=$dbase; 
$this->dbname=substr($dbase,strrpos($dbase,"/"),-4); 
$this->dbtable=$dbtable; 
$data=$this->ReadXml($this->dbase); 
if(!$data){ 
die("无法读取 $this->dbname.xml"); 
} 
$this->parser = xml_parser_create(); 
xml_parser_set_option($this->parser,XML_OPTION_CASE_FOLDING,0); 
xml_parser_set_option($this->parser,XML_OPTION_SKIP_WHITE,1); 
xml_parse_into_struct($this->parser,$data,$this->vals,$this->index); 
xml_parser_free($this->parser); 
//遍历索引，筛选出要取值的节点 节点名:$dbtable 
foreach ($this->index as $key=>$val) { 
if ($key == $this->dbtable) { 
//取得节点数组 
$this->dbtable_array = $val; 
} else { 
continue; 
} 
} 
for ($i=0; $i < count($this->dbtable_array); $i+=2) { 
$offset = $this->dbtable_array[$i] + 1; 
$len = $this->dbtable_array[$i + 1] - $offset; 
//array_slice() 返回根据 offset 和 length 参数所指定的 array 数组中的一段序列。 
//所取节点下级数组 
$value=array_slice($this->vals,$offset,$len); 
//取得有效数组，合并为结果数组 
$this->array[]=$this->parseEFF($value); 
} 
return true; 
} 
//将XML文件读入并返回字符串 
function ReadXml($file) 
{ 
return file_get_contents($file); 
} 
//取得有效数组 
function parseEFF($effective) { 
for ($i=0; $i < count($effective); $i++){ 
$effect[$effective[$i]["tag"]] = $effective[$i]["value"]; 
} 
return $effect; 
} 
//xml_query(方法,条件,多条件时逻辑运算符and or or,插入或更新的数组) 
function xml_query($method,$condition,$if='and',$array=array()) 
{ 
if(($method=='select')||($method=='count')){ 
return $this->xml_select($method,$condition,$if); 
} elseif($method=='insert') { 
return $this->xml_insert($condition,$if,$array); 
} elseif($method=='update') { 
return $this->xml_update($condition,$if,$array); 
} 
} 
//取得xml数组 
function xml_fetch_array($condition,$if) 
{ 
//$this->querys++; 
$row = $this->array; //初始化数据数组 
if($condition) { 
//是否有条件,如有条件则生成符合条件的数组 
//生成条件数组,条件格式 field,operator,match 
$condition=explode(",",$condition);//条件数组 
$cs=count($condition)/3; //条件数 
for($i=0;$i<$cs;$i++){ 
$conditions[]=array("field"=>$condition[$i*3],"operator"=>$condition[$i*3+1],"match"=>$condition[$i*3+2]); 
} 
//echo count($row); 
for($r=0;$r<count($row);$r++){ 
for($c=0;$c<$cs;$c++){ 
//$i++; 
$condition=$conditions[$c]; //当前条件 
$field=$condition['field']; //字段 
$operator=$condition["operator"];//运算符 
$match=$condition['match']; //匹配 
if(($operator=='=')&&($row[$r][$field]==$match)){ 
$true++;//若条件符合,符合数加1 
} elseif(($operator=='!=')&&($row[$r][$field]!=$match)){ 
$true++;//若条件符合,符合数加1 
} elseif(($operator=='<')&&($row[$r][$field]<$match)){ 
$true++;//若条件符合,符合数加1 
} elseif(($operator=='<=')&&($row[$r][$field]<=$match)){ 
$true++;//若条件符合,符合数加1 
} elseif(($operator=='>')&&($row[$r][$field]>$match)){ 
$true++;//若条件符合,符合数加1 
} elseif(($operator=='>')&&($row[$r][$field]>=$match)){ 
$true++;//若条件符合,符合数加1 
} 
} 
//根据条件取值 
if($if=='and'){ 
//如果多条件为and,当符合数等于条件数时,生成数组 
if($true==$cs){ 
$result[]=$row[$r]; 
} 
} else { 
//如果多条件为or,当有符合纪录时,生成数组 
if($true!=0){ 
$result[]=$row[$r]; 
} 
} 
//echo $true; 
//echo "<pre style="font-size:12px;text-align:left">"; 
//print_r($true); 
$true=0;//符合条件数归零,进入下一轮循环 
} 
} else { 
$result=$this->array; 
} 
//echo "<pre style="font-size:12px;text-align:left">"; 
//print_r($this->result); 
return $result; 
} 
//筛选或统计 
function xml_select($method,$condition,$if) 
{ 
$result=$this->xml_fetch_array($condition,$if); 
if($method=='select'){ 
return $result; 
} else { 
return count($result); 
} 
} 
//插入数据 
function xml_insert($condition,$if,$array) 
{ 
$data=$this->xml_fetch_array($condition,$if);//总数据数组 
$data[]=$array; //插入后的总数据数组 
$this->array=$data; //更新总数组 
$this->WriteXml($data); 
} 
//得到更新的XML并改写 
function xml_update($condition,$if,$array) 
{ 
$datas=$this->array; //总数据数组 
$subtract=$this->xml_fetch_array($condition,$if);//要更新的数组 
//echo "<pre style="font-size:12px;text-align:left">"; 
//print_r($data); 
//print_r($datas); 
//echo "每条记录中有".count($datas[0])."个值<br>"; 
for($i=0;$i<count($datas);$i++){ 
$data=$datas[$i]; 
//echo "原始记录中的第".$i."条<br>"; 
foreach($data as $k=>$v){ 
//echo "-第".$i."条的".$k."值为".$v."<br>"; 
//echo "--要查找的数组".$k."值为".$subtract[0][$k]."<br>"; 
if($v==$subtract[0][$k]){ 
$is++; 
} 
} 
if($is==count($data)){ 
//echo "----与第".$i."条符合<br>"; 
$datas[$i]=$array; 
//array_splice($datas,$i,$i+1); 
} 
//echo "原始记录中的第".$i."条与要查找的有".$is."匹配<br>"; 
//echo "原始记录中的第".$i."条结束<br>"; 
$is=0; 
} 
//array_splice($datas,2,2+1,$array); 
//echo "<pre style="font-size:12px;text-align:left">"; 
//print_r($datas); 
$this->array=$datas; 
$this->WriteXml($datas); 
} 
//写入XML文件(全部写入) 
function WriteXml($array) 
{ 
if(!is_writeable($this->dbase)){ 
die("无法写入".$this->dbname.".xml"); 
} 
$xml.="<?xml version='1.0' encoding='utf-8'?>\r"; 
$xml.="<$this->dbname>\r"; 
for($i=0;$i<count($array);$i++){ 
$xml.="<$this->dbtable>\r"; 
foreach($array[$i] as $k=>$s){ 
$xml.="<$k>$s</$k>\r"; 
} 
$xml.="</$this->dbtable>\r"; 
} 
$xml.="</$this->dbname>"; 
$fp=@fopen($this->dbase,"w"); 
flock($fp, LOCK_EX); 
rewind($fp); 
fputs($fp,$xml); 
fclose($fp); 
} 
//逐行写入xml(我试着写入10000行,感觉没一次写入快，所以没用这种写入方式) 
function WriteLine($array) 
{ 
if(!is_writeable($this->dbase)){ 
die("无法写入".$this->dbname.".xml"); 
} 
$fp=@fopen($this->dbase,"w"); 
rewind($fp); 
flock($fp, LOCK_EX); 
fputs($fp,"<?xml version='1.0' encoding='utf-8'?>\r"); 
fputs($fp,"<$this->dbname>\r"); 
for($i=0;$i<count($array);$i++){ 
fputs($fp,"<$this->dbtable>\r"); 
$xml.="<$this->dbtable>\r"; 
foreach($array[$i] as $k=>$s){ 
fputs($fp,"<$k>$s</$k>\r"); 
} 
fputs($fp,"</$this->dbtable>\r"); 
} 
fputs($fp,"</$this->dbname>"); 
fclose($fp); 
} 
} 


/**
 * 
 * 
 * example 读取数据： 
* 
* $xml = new xml("dbase.xml",'table'); 
* 
* $data=$xml->xml_fetch_array(); 
* 
* echo "<pre style="font-size:12px;">"; 
* 
* print_r($data); 
 * 
 * require_once('xml.class.php'); 
$xml = new xml("exemple.xml","item"); 
$newarray = array( 
"title"=>"XML标题", 
"text"=>"PHP的XML类测试！" 
); 
$insert=$xml->xml_query('insert','','',$newarray);//第二及第三个变量位置是条件，留空表示在最后插入 
 * 
 * 
require_once('xml.class.php'); 
$xml = new xml("exemple.xml","item"); 
$array = array( 
"title"=>"XML标题", 
"text"=>"PHP的XML类测试！" 
); 
$insert=$xml->xml_query('update','title,=,20年后世界将会怎样？','and',$array);//title标签等于xxx的用$array替换（可以建唯一属性的标签，比如id，这样就可以修改某一条记录） 
 
 
 
require_once('xml.class.php'); 
$xml = new xml("exemple.xml","item"); 
$array = array(); 
$insert=$xml->xml_query('update','title,=,20年后世界将会怎样？','and',$array);//数组留空 
 *
 *
 */


?> 