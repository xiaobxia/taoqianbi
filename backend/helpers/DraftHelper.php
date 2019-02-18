<?php

namespace backend\helpers;

/**
 * Created by PhpStorm.
 * User: user
 * Date: 2015/9/7
 * Time: 17:24
 */
class DraftHelper
{
    /**
     * 计算票据用户收益
     * 用户收益 = 票面金额 - 票面金额 / (天数 / 365 * 年利率 + 1)
     * 天数 = 到期日期 - 购买时间
     * @param $buy_time         票据购买日期（时间戳）
     * @param $expire_time      票据到期日期（时间戳）
     * @param $draft_account    票据票面金额
     * @param $apr              票据年利率（%）
     * @return float
     * @author hezhuangzhuang@kdqugou.com
     */
    public static function calUserProfits($buy_time, $expire_time, $draft_account, $apr) {
        $period = ($expire_time - $buy_time) / (24 * 3600);
        $apr = $apr / 100;
        $user_profits = $draft_account - $draft_account / ($period / 365 * $apr + 1);
        return $user_profits;
    }

    /**
     * 计算票据项目应发金额
     * 项目应发金额 = 票面金额 /（天数 / 365 * 年利率 + 1）
     * 天数 = 到期日期 - 创建时间
     * @param $created_at       票据创建时间(时间戳)
     * @param $expire_time      票据到期日期(时间戳)
     * @param $draft_account    票据票面金额
     * @param $apr              票据年利率(%)
     * @return float
     * @author hezhuangzhuang@kdqugou.com
     */
    public static function calProjectAccount($created_at, $expire_time, $draft_account, $apr) {
        $period = ($expire_time - $created_at) / (24 * 3600);
        $apr = $apr / 100;
        $project_account = $draft_account / ($period / 365 * $apr + 1);
        return $project_account;
    }

}