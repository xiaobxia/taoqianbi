<?php
use backend\components\widgets\ActiveForm;
use backend\models\AdminUserRole;

?>

<style type="text/css">
    .item{ float: left; width: 300px; line-height: 25px; margin-left: 5px; border-right: 1px #deeffb dotted; }
</style>
<script type="text/JavaScript">
function permcheckall(obj) {
    $(obj).parents('tbody').find('.J_item').val(1)
}
function checkclk(obj,id,type) {
    var myobj=obj;
    var obj = obj.parentNode.parentNode;
    obj.className = obj.className == 'J_item' ? 'J_item checked' : 'J_item';
    if(type.toString()=='big'){
        var checked=myobj.checked;
        $('.small'+id.toString()+',bigmenu'+id.toString()).each(function () {
            this.checked=checked;
        });
        $('.bigmenu'+id.toString()).each(function () {
            this.checked=checked;
        });
    }else{
        //判断是否需要选中大类
        var checked=false;
        $('.small'+id.toString()).each(function () {
            if(this.checked){
                checked=true;
                return false;
            }
        });
        $('.bigmenu'+id.toString()).each(function () {
            this.checked=checked;
        });
    }
}
</script>


<?php $form = ActiveForm::begin(['id' => 'role-form']); ?>
<table class="tb tb2">

<?php foreach ($permissionChecks as $controller => $permission): ?>

      <?php $i=1;$j=1;?>
    <?php foreach($permission as $k=>$vvv):?>

    <table class="tb2" id="<?php echo $controller; ?>" attr="<?php echo $i ?>"<?php if($i==1 && $controller!=0): echo 'style="margin-top:10px;"'; endif; ?>>
        <tbody><?php if($i==1):?>
        <tr>
            <th class="partition" colspan="5">
                 <label> <?php echo $k; ?> - <?php echo $vvv; ?></label>
		   </th>
        </tr><?php endif ?>
		<?php if($i==2):?>
		 <?php
        $index = 0;
        $line_cnt = 5;
        ?>
		 <?php foreach($vvv as $kkkk=>$vvvv):?>

            <?php

            //判断菜单
            if(strstr($kkkk,'_begin') || strstr($kkkk,'_end')):
                $style=" style='display:none;'";
                if(strstr($kkkk,'_begin')):
                    $j++;
                    $style='';
                endif;
                $arrs=$k.'/'.$kkkk;
                echo '<tr'.$style.'><td colspan="5"><div class="J_item"><label class="txt" style="font-weight: bold;"><input type="checkbox" onclick="checkclk(this,\''.$controller.'_'.$j.'\',\'big\')" class="checkbox bigmenu'.$controller.'_'.$j.'" value="'.$k.'/'.$kkkk.'" name="permissionChecks[]"'.(in_array($arrs,$permissions) ? ' checked' : '').'>'.$vvvv.'</label></div>';
                echo '<div style="color:#999;margin-left: 5px;font-weight: bold;">'.$kkkk.'</div>';
                echo '</td></tr>';
            else:

                ?>

		    <?php if( intval($index % $line_cnt) == 0):?>
		      <tr>
			  <?php endif ?>

			    <td width="200px" >
                <div class="J_item">
                    <label class="txt">
                        <?php $arrs=$k.'/'.$kkkk;?>
                        <input type="checkbox" onclick="checkclk(this,'<?php echo $controller.'_'.$j?>','small')" class="checkbox small<?php echo $controller.'_'.$j?>" value="<?php echo $k;?>/<?php echo $kkkk;?>" name="permissionChecks[]"<?php echo in_array($arrs,$permissions) ? ' checked' : ''; ?>/>
                        <?php echo $vvvv; ?>
                    </label>
                </div>
                <div style="color:#999;margin-left: 5px">
                   <?php echo $kkkk; ?>
                </div>
            </td>
			  <?php if( intval($index++ % $line_cnt) == $line_cnt - 1):

                //不足5列表补充5列表
                $sy=$index % $line_cnt;
                if($sy>0){
			      for($row=0;$row<=($line_cnt-$sy);++$row){
			          echo '<td width="200px"></td>';
                  }
                }

                ?>
			  </tr>
			  <?php endif ?>
			  <?php endif; ?>
         <?php endforeach;?>
		 <?php endif?>

        </tbody>
    </table>
     <?php $i++;?>
  <?php endforeach; ?>
<?php endforeach; ?>

    <tr>
        <td colspan="5">
            <input type="submit" value="提交" name="submit_btn" class="btn" />
        </td>
    </tr>
</table>
<?php ActiveForm::end(); ?>
