<?php
if(empty($attachment_pic_arr)){
    echo "不存在图片记录！";
    return;
}

?>
<?php foreach($attachment_pic_arr as $k => $v):
    $k++;
    ?>
    <div style="text-align: center;margin: 0 auto; margin-top: 10px;">
        <img  src="<?php echo $v ?>">
        <hr style=" height: 5px; background-color: yellow;">
    </div>
<?php endforeach;?>
