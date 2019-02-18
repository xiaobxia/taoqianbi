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
        <tr>
            <th class="partition" colspan="15">
                <span>查询芝麻信用记录</span>
                <?php if(!empty($loan_person['creditZmop']['status']) && $loan_person['creditZmop']['status'] == CreditZmop::STATUS_1): ?>
                    &nbsp;
                    <a style="color:red" onclick="return confirm('确认获取')" href="<?php echo Url::toRoute(['zmop/get-all-info','person_id'=>$loan_person['id']]);?>">点击获取芝麻信用所有信息</a>
                <?php else:?>
                    &nbsp;
                    <a href="<?php echo Url::toRoute(['zmop/batch-feedback', 'id' => $loan_person['id']])?>">用户已取消授权，点击发送短信，让用户重新授权</a>
                <?php endif;?>
            </th>
        </tr>
        <?php if(!empty($loan_person['creditZmop']['status'])): ?>
            <tr>
                <td class="td24">
                    芝麻信用评分</br></br>
                    <a href="<?php echo Url::toRoute(['zmop/get-zmop-info', 'id' => $loan_person['id'] , 'type' =>CreditZmop::ZM_TYPE_SCORE ])?>">点击查询最新数据</a>
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
                    <a href="<?php echo Url::toRoute(['zmop/get-zmop-info', 'id' => $loan_person['id'], 'type' =>CreditZmop::ZM_TYPE_RAIN])?>">点击查询最新数据</a>
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
                    <a href="<?php echo Url::toRoute(['zmop/get-zmop-info', 'id' => $loan_person['id'], 'type' =>CreditZmop::ZM_TYPE_WATCH])?>">点击查询最新数据</a>
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
                    <a href="<?php echo Url::toRoute(['zmop/get-zmop-info', 'id' => $loan_person['id'], 'type' =>CreditZmop::ZM_TYPE_IVS])?>">点击查询最新数据</a>
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
                <a href="<?php echo Url::toRoute(['zmop/get-zmop-info', 'id' => $loan_person['id'], 'type' =>CreditZmop::ZM_TYPE_DAS])?>">点击查询最新数据</a>
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





