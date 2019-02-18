<?php
use common\helpers\Url;

/**
 * @var backend\components\View $this
 */
?>
<!doctype html>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, minimal-ui">
    <title><?php echo APP_NAMES;?></title>
    <meta name="format-detection" content="telephone=no">
    <link rel="stylesheet" type="text/css" href="<?php echo Url::toStatic('/css/style.css'); ?>?v=2016121214" />

</head>
<style>
.bank_list{list-style: none;
    border-bottom: 1px solid #E6E6E6;
    height: 9vh;
    width: 100%;
    line-height: 9vh;
}
.bank_list img{list-style: none;
    margin-left: 2vh;
    margin-right: 2vh;
    width: 4vh;
    margin-bottom: 1.2vh;
}
.bank_list span{list-style: none;
   font-size: 2.5vh;
    color: #3A3A3A;
    font-family: 'Microsoft YaHei', 微软雅黑, sans-serif;
}
.flag{height: 2vh;
 background-color:#F2F2F2 ;
}
.container{
    padding: 0 0 0;

}
</style>
<code class="hljs coffeescript has-numbering" style="display: block; padding: 0px; color: inherit; box-sizing: border-box; font-family: 'Source Code Pro', monospace;font-size:undefined; white-space: pre; border-radius: 0px; word-wrap: normal; background: transparent;"><span class="hljs-built_in" style="color: rgb(102, 0, 102); box-sizing: border-box;">window</span>.alert = <span class="hljs-reserved" style="box-sizing: border-box;">function</span>(name){ <span class="hljs-reserved" style="box-sizing: border-box;">var</span> iframe = <span class="hljs-built_in" style="color: rgb(102, 0, 102); box-sizing: border-box;">document</span>.createElement(<span class="hljs-string" style="color: rgb(0, 136, 0); box-sizing: border-box;">"IFRAME"</span>); iframe.style.display=<span class="hljs-string" style="color: rgb(0, 136, 0); box-sizing: border-box;">"none"</span>; iframe.setAttribute(<span class="hljs-string" style="color: rgb(0, 136, 0); box-sizing: border-box;">"src"</span>, <span class="hljs-string" style="color: rgb(0, 136, 0); box-sizing: border-box;">'data:text/plain,'</span>); <span class="hljs-built_in" style="color: rgb(102, 0, 102); box-sizing: border-box;">document</span>.documentElement.appendChild(iframe); <span class="hljs-built_in" style="color: rgb(102, 0, 102); box-sizing: border-box;">window</span>.frames[<span class="hljs-number" style="color: rgb(0, 102, 102); box-sizing: border-box;">0</span>].<span class="hljs-built_in" style="color: rgb(102, 0, 102); box-sizing: border-box;">window</span>.alert(name); iframe.parentNode.removeChild(iframe); };</code><ul class="pre-numbering" style="box-sizing: border-box; position: absolute; width: 50px; top: 0px; left: 0px; margin: 0px; padding: 6px 0px 40px; border-right-width: 1px; border-right-style: solid; border-right-color: rgb(221, 221, 221); list-style: none; text-align: right; opacity: 0.0452538; background-color: rgb(238, 238, 238);"><li style="box-sizing: border-box; padding: 0px 5px;">1</li><li style="box-sizing: border-box; padding: 0px 5px;">2</li><li style="box-sizing: border-box; padding: 0px 5px;">3</li><li style="box-sizing: border-box; padding: 0px 5px;">4</li><li style="box-sizing: border-box; padding: 0px 5px;">5</li><li style="box-sizing: border-box; padding: 0px 5px;">6</li><li style="box-sizing: border-box; padding: 0px 5px;">7</li><li style="box-sizing: border-box; padding: 0px 5px;">8</li></ul><ul class="pre-numbering" style="box-sizing: border-box; position: absolute; width: 50px; top: 0px; left: 0px; margin: 0px; padding: 6px 0px 40px; border-right-width: 1px; border-right-style: solid; border-right-color: rgb(221, 221, 221); list-style: none; text-align: right; background-color: rgb(238, 238, 238);"><li style="box-sizing: border-box; padding: 0px 5px;">1</li><li style="box-sizing: border-box; padding: 0px 5px;">2</li><li style="box-sizing: border-box; padding: 0px 5px;">3</li><li style="box-sizing: border-box; padding: 0px 5px;">4</li><li style="box-sizing: border-box; padding: 0px 5px;">5</li><li style="box-sizing: border-box; padding: 0px 5px;">6</li><li style="box-sizing: border-box; padding: 0px 5px;">7</li><li style="box-sizing: border-box; padding: 0px 5px;">8</li></ul>

<body>
<div class="flag"></div>
<ul>
    <?php foreach($bank_info as $k=> $value):?>

    <a href="<?php echo Url::toRoute(['payroll-card/apply','item'=>$value,'user_id'=>$user_id,'bank_id'=>$k,'bank_name'=>$value['bank_name']]);?>"><li class="bank_list">
            <img src="<?php echo Url::toStatic('/image/bank/bank' . $k . '.png'); ?>"><span><?php echo $value['bank_name'];?></span>
        </li></a>

    <?php endforeach ?>

</ul>



</body>