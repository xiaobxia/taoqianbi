<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/11
 * Time: 15:53
 */
use common\models\loanPerson;
use common\helpers\Url;
use common\models\CreditZmop;

?>
<table class="tb tb2 fixpadding">
    <tr><th class="partition" colspan="15">借款人信息</th></tr>
    <?php if(isset($loan_person) && !empty($loan_person)): ?>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'id'); ?></td>
            <td width="300"><?php echo $loan_person['id']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'uid'); ?></td>
            <td width="300"><?php echo $loan_person['uid']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['id_number']; ?></td>
            <td ><?php echo $loan_person['id_number']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'type'); ?></td>
            <td ><?php echo LoanPerson::$person_type[$loan_person['type']]; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['name']; ?></td>
            <td><?php echo $loan_person['name']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'phone'); ?></td>
            <td colspan="3"><?php echo $loan_person['phone']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['birthday']; ?></td>
            <td colspan="3"><?php echo date('Y-m-d',$loan_person['birthday']); ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['property']; ?></td>
            <td colspan="3"><?php echo $loan_person['property']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['contact_username']; ?></td>
            <td colspan="3"><?php echo $loan_person['contact_username']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $tittle['contact_phone']; ?></td>
            <td colspan="3"><?php echo $loan_person['contact_phone']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'attachment'); ?></td>
            <?php if(empty($loan_person['attachment'])) :?>
            <td colspan="3">--待上传--</td>
            <?php else: ?>
            <td colspan="3"><a href="<?php echo Url::toRoute(['loan/loan-person-pic', 'id' => $loan_person['id']])?>">查看</a></td>
            <?php endif;?>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'credit_limit'); ?></td>
            <td colspan="3"><?php echo $loan_person['credit_limit']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'open_id'); ?></td>
            <td colspan="3"><?php echo $loan_person['open_id']; ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'created_at'); ?></td>
            <td colspan="3"><?php echo date('Y-m-d H:i:s',$loan_person['created_at']); ?></td>
        </tr>
        <tr>
            <td class="td24"><?php echo $this->activeLabel($loan_person, 'updated_at'); ?></td>
            <td colspan="3"><?php echo date('Y-m-d H:i:s',$loan_person['updated_at']); ?></td>
        </tr>
    <?php else: ?>
        <tr>
            <td>暂无借款人相关信息</td>
        </tr>
    <?php endif; ?>
    <tr>
        <th class="partition" colspan="15">
            <span>查询芝麻信用记录</span>
            <?php if(!empty($loan_person['creditZmop']['status'])): ?>
                &nbsp;
                <a style="color:red" onclick="return confirm('确认获取')" href="<?php echo Url::toRoute(['zmop/get-all-info','person_id'=>$loan_person['id']]);?>">点击获取芝麻信用所有信息</a>
            <?php endif;?>
        </th>
    </tr>
    <?php if(!empty($loan_person['creditZmop']['open_id'])): ?>
        <tr>
            <td class="td24">
                芝麻信用评分</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-credit-score', 'id' => $loan_person['id'] , 'pid' => -1,'type' => $loan_person['type']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if (! empty($loan_person['creditZmop']['zm_score'])): ?>
                    信用评分：<?php echo $loan_person['creditZmop']['zm_score'] ?>
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        </tr>
        <tr>
            <td class="td24">
                获得手机RAIN分</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-credit-mobile-rain', 'id' => $loan_person['id'], 'pid' => -1,'type' => $loan_person['type']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if(! empty($loan_person['creditZmop']['rain_info'])): ?>
                    RAIN分(取值为0-100。得分越高，风险越高)：<?php echo $loan_person['creditZmop']['rain_score'] ?> </br>
                    <?php foreach (json_decode($loan_person['creditZmop']['rain_info']) as $val): ?>
                        <?php echo $val->name ?>: <?php echo $val->description ?> </br>
                    <?php endforeach ?>
                <?php else: ?>
                    暂无信息
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="td24">
                获得行业关注名单</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-credit-watch-list', 'id' => $loan_person['id'], 'pid' => -1,'type' => $loan_person['type']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if (! empty($loan_person['creditZmop']['watch_info'])): ?>
                    <?php foreach(json_decode($loan_person['creditZmop']['watch_info']) as $val ): ?>
                        风险信息行业：<?php echo CreditZmop::$iwatch_type[$val->biz_code] ?> <br/>
                        风险类型：<?php echo CreditZmop::$risk_type[$val->type] ?> <br/>
                        风险说明：<?php echo CreditZmop::$risk_code[$val->code] ?> <br/>
                        负面信息或者风险信息：<?php echo $val->level ?> (取值：1=有负面信息，2=有风险信息)<br/>
                        数据刷新时间：<?php echo $val->refresh_time ?> <br/>
                        <?php if(!empty($val->extend_info)) :?>
                            <?php foreach($val->extend_info as $v): ?>
                                芝麻信用申诉id: <?php echo $v->value;?><br/>
                            <?php endforeach ?>
                        <?php endif ?>
                        <br/>
                    <?php endforeach ?>
                <?php elseif($loan_person['creditZmop']['watch_matched'] == 0): ?>
                    行业关注未匹配
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        <tr>
            <td class="td24">
                获得IVS信息验证信息</br></br>
                        <a href="<?php echo Url::toRoute(['zmop/get-credit-ivs-detail', 'id' => $loan_person['id'], 'pid' => -1,'type' => $loan_person['type']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if(! empty($loan_person['creditZmop']['ivs_info'])): ?>
                    IVS评分(取值区间为0-100。分数越高，表示可信程度越高。0表示无对应数据)：<?php echo $loan_person['creditZmop']['ivs_score'] ?> </br>
                    <?php foreach(json_decode($loan_person['creditZmop']['ivs_info']) as $val): ?>
                        <?php echo $val->description ?> </br>
                    <?php endforeach ?>
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        </tr>
        <tr>
            <td class="td24">
                获得DAS认证信息</br></br>
                <a href="<?php echo Url::toRoute(['zmop/get-zmop-credit-das', 'id' => $loan_person['id'], 'pid' => -1,'type' => $loan_person['type']])?>">点击查询最新数据</a>
            </td>
            <td width="300">
                <?php if(! empty($loan_person['creditZmop']['das_info'])): ?>
                    <?php foreach(json_decode($loan_person['creditZmop']['das_info']) as $v): ?>
                        <?php echo CreditZmop::$das_keys[$v->key] . '：' . $v->value ?> </br>
                    <?php endforeach ?>
                <?php else: ?>
                    暂无信息
                <?php endif ?>
            </td>
        </tr>

    <?php else: ?>
        <tr>
            <td class="td24">暂时没有通过芝麻信用</td>
        </tr>
        <tr>
            <td class="td24"><a href="<?php echo Url::toRoute(['zmop/batch-feedback', 'id' => $loan_person['id'], 'type' => $loan_person['type'] ])?>">点击发送短信授权</a></td>
        </tr>
    <?php endif;?>
</table>

<table class="tb tb2 fixpadding">
    <tr>
        <th class="partition" colspan="15">
            查询聚信立记录
        </th>
    </tr>
    <?php if(!empty($loan_person['creditJxl']['status'])): ?>
        <tr>
            <td class="td24">更新时间：<?php echo date('Y-m-d H:i:s',$loan_person['creditJxl']['report_update']) ?></td>
            <td width="300"><a target="_BLANK" href="https://dev.juxinli.com/#/app/reports/4.1/<?php echo $loan_person['creditJxl']['token']?>">点击查看聚信立记录</a></td>
        </tr>
    <?php else: ?>
        <tr>
            <td class="td24">
                暂时没有聚信立信息
            </td>
        </tr>
        <tr>
            <td class="td24">
                <a href="<?php echo Url::toRoute(['jxl/get-user-jxl-status', 'id' => $loan_person['id']])?>">点击获取用户报表信息</a>
            </td>
        </tr>
    <?php endif; ?>
</table>



