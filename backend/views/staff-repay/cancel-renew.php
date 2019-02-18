<?php
/* @var $this \yii\web\View */
?>
<div>
    <form method="POST">

        <input name="_csrf" type="hidden" id="_csrf" value="<?php echo Yii::$app->request->csrfToken ?>">

        <div class="form-group">
            <label class="control-label">订单ID</label>
            <input type="number" class="form-control" value="<?php echo $order->id?>" disabled="disabled" />
        </div>

        <div class="form-group">
            <label class="control-label">用户ID</label>
            <input type="number" class="form-control" value="<?php echo $order->user_id?>" disabled="disabled" />
        </div>

        <div class="form-group">
            <label class="control-label">用户姓名</label>
            <input type="text" class="form-control" value="<?php echo $order->loanPerson ? $order->loanPerson->name : '无'?>" disabled="disabled" />
        </div>

        <div class="form-group">
            <label class="control-label">用户手机</label>
            <input type="number" class="form-control" value="<?php echo $order->loanPerson ? $order->loanPerson->phone : '无'?>" disabled="disabled" />
        </div>

        <div class="form-group">
            <label class="control-label">取消续期次数</label>
            <input type="number" class="form-control" name="times" value="" max="<?php echo $max_times?>" min="1"/>
            <div class="hint-block">
                该订单最多可取消 <?php echo $max_times?> 次续期
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn" >提交</button>
        </div>

    </form>
</div>
