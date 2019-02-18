<?php
use yii\helpers\Url;
use mobile\components\ApiUrl;
?>
<style>
*{
    margin: 0;
    padding: 0;
}
html,body{
    width: 100%;
    height: 100%;
}
.layout{
    width: 100%;
    height: 88.0rem;
    position: relative;
    background:url("<?=$this->absBaseUrl;?>/image/page/huodong/yemian.png?t=2017061912") no-repeat top left;
    -webkit-background-size: 100%;
    background-size: 100%;
}
.first-arrow,.second-arrow{
    position: absolute;
    width: 0.586667rem;
    height: 0.733333rem;
    left: 50%;
    margin-left: -0.2933rem;
    animation: arr linear infinite 1s;
    -webkit-animation:arr linear infinite 1s; /* Safari 和 Chrome */
}
.first-arrow{
    top: 7.7rem;
    background:transparent url("<?=$this->absBaseUrl;?>/image/page/huodong/jiantou1.png") no-repeat center center /100% 100%;
}
.second-arrow{
    top:58.7rem;
    background:transparent url("<?=$this->absBaseUrl;?>/image/page/huodong/jiantou2.png") no-repeat center center/100% 100%;
}
@keyframes arr{
    0%{transform: translateY(0px);}
    50%{transform: translateY(10px);}
    100%{transform: translateY(0px);}
}
/*firefox*/
@-moz-keyframes arr{
    0%{transform: translateY(0px);}
    50%{transform: translateY(10px);}
    100%{transform: translateY(0px);}
}
/*safari and chrome*/
@-webkit-keyframes arr{
    0%{transform: translateY(0px);}
    50%{transform: translateY(10px);}
    100%{transform: translateY(0px);}
}
/*opera*/
@-o-keyframes arr{
    0%{transform: translateY(0px);}
    50%{transform: translateY(10px);}
    100%{transform: translateY(0px);}
}
</style>
</head>
<body>
<div class="layout">
    <div class="first-arrow"></div>
    <div class="second-arrow"></div>
</div>
<script>
    window.onload=function(){
        var ifr=document.querySelectorAll('iframe');
        ifr.forEach( function(v) {
            v.parentNode.parentNode.parentNode.removeChild(v.parentNode.parentNode);
        });
        console.log(ifr)
    }

</script>
</body>
<script src="<?=$this->absBaseUrl;?>/js/flexable.js"></script>
