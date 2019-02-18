<?php
 use yii\helpers\Url;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>贷款超市</title>
    <script src="<?=$this->staticUrl('js/jquery.js',1); ?>"></script>
    <script src="<?=$this->staticUrl('js/flexible.js?v=2016110901',1); ?>"></script>
    <style>
        * {
            margin:0;
            padding:0;
        }
        .wrapper {
            width:10rem;
            height: 17rem;
            background: #f5f5f5;
            background-size:10rem auto;
        }
        ul {
            list-style: none;
        }
        a {
            text-decoration: none;
        }
        .hot {
            width:10rem;
            height: 9.706667rem;
            background: #fff;
            position: relative;
            overflow: scroll;
            margin-bottom: 0.133333rem;
        }
        .loan {
            width: 10rem;
            height: 1.133333rem;
        }
        .loan span{
            display: inline-block;
        }
        .logo {
            width:.8rem;
            height: .8rem;
        }
        .text {
            font-size: 0.426667rem;
            color:#666;
            margin-left: .333333rem; 
            position: absolute;
            top:0.35rem;
        }
        
        ul li {
            border-top: .013333rem solid #f5f5f5;
            margin-top:.2rem;
            width:10rem;
            height: 2.093333rem;
            position: relative;
        }
        img {
            width: 1.6rem;
            height: 1.6rem;
            border-radius: .8rem;
            margin-left: .426667rem;
            margin-top:0.24rem;
            margin-bottom: 0.213333rem;
        }
       
        .content {
            display: inline-block;
            position: absolute;
            left:2.293333rem;
        }
        .company-name {
            display: inline-block;
            color:#333;
            font-size:0.43rem;
            margin-top:0.35rem;
        }
        .pass {
            display: none;
            width: 1.8rem;
            height: .326667rem;
            background:  #EC7474;
            border-radius: .5rem;
            color: #fff;
            margin-left:.3rem;
            text-align: center;
            font-size:.266667rem;
            /*box-sizing:border-box;*/
            padding:.08rem .1rem .15rem .1rem;
        }
        .des {
            display: block;
            font-size:0.33rem;
            color:#999;
            margin-top:0.146667rem;
        }
        .people {
            display: inline-block;
            font-size:0.33rem;
            color:#666;
            margin-top: 0.013333rem;
        }
        .blue {
            font-size:.4rem;
            color:#3399ff;
            font-style: normal;
        }
        .rate {
            margin-left: .5rem;
            font-size:0.33rem;
            color:#666;
        }
        .rate i {
            font-size:0.4rem;
            color:#ff9933;
            font-style: normal;
        }
    </style>
</head>
<body>
    <div class="wrapper">
    
    <?php foreach($list as $value):?>
        <div class="hot">
            <div class="loan">
            	<img src="<?php echo $value['log_url'];?>" class="logo">
            	<span class="text"><?php echo $value['name']; ?></span>
            </div>
            <ul>
            <?php foreach($value['list'] as $v):?>
                <li onclick="window.open('<?php echo $v['url']; ?>','_self');">
                    <img src="<?php echo $v['log_url'];?>" alt="">
                    <div class="content">
                        <span class="company-name"><?php echo $v['name'];?></span>
                        <?php if($v['trait']):?>
                            <span class="pass" style="display: inline-block"><?php echo $v['trait'];?></span>
                        <?php else:?>
                            <span class="pass" style=""></span>
                        <?php endif;?>
                        <span class="des"><?php echo $v['remark'];?></span>
                        <span class="people">申请人数<i class="blue"><?php echo $v['number'];?></i></span>
                        <span class="rate">日利率<i><?php echo $v['rate'];?>%</i>起</span>
                    </div>
                </li>
             <?php endforeach; ?>
           </ul>
        </div>
     <?php endforeach;?>           
    </div>
    <script src="https://s19.cnzz.com/z_stat.php?id=1273203078&web_id=1273203078" language="JavaScript"></script>
      <script>
        $(function(){
        	var type = <?php  echo count($list) > 1 ? 2 : 1;?>;
	      	if(type == 1){
	      		$('.hot').css("height","18rem")
	      	}else if(type == 2){
	      		$('.hot').css("height","11rem")
	      	}
        })
      	
      </script>
</body>
</html>
