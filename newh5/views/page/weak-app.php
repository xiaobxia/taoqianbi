<body>
<div id="show" style="display: none">223333333</div>
<button id="dianji">点击</button>
</body>
<script>
    $(function () {
        window.location="<?=$url;?>";
        setTimeout(function(){
            window.location="http://www.baidu.com";
        },5000);
    })
</script>