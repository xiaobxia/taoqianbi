<?php

namespace common\models;

use Yii;
use common\exceptions\RedisException;

/**
 * TODO: 如果有多个需要这样做hash的话 可以把下面多个方法弄成基类，子类只需要配置$hashConfigs即可
 * @author yakehuang
 */
class UserRedis
{
	/**
	 * 哈希配置
	 * 为了约束字段规范，必须在此配置字段集合，否则抛异常
	 */
	public static $hashConfigs = [
		'name' => 'user',
		'fields' => [
			'profits_list',					// （暂时弃用）累计收益列表
			'trade_lock',					// （暂时弃用）交易锁，同一个用户不能同时执行多种交易
			'fin_proj_invest_count',		// 已结投资记录总数
            'invest_project_red_point', 	// 投资项目所得的红包红点
            'receive_voucher_red_point', 	// 有新的口袋券生成红点
            'phone_change_list',   			// 更改手机号后需要清除登录态的用户
            'yirute_register_tid',	
            'exp_money_red_point',		// PC落地页yirute来源注册用户对应tid标识码
		],
	];
	
	/**
	 * 读取哈希域的的值
	 * @param integer $id
	 * @param string $field
	 */
	public static function HGET($id, $field)
	{
		self::checkFieldName($field);
		$key = static::$hashConfigs['name'] . ':' . $id;
		return Yii::$app->redis->executeCommand('HGET', [$key, $field]);
	}
	
	/**
	 * 设置hash里面一个字段的值
	 * @param integer $id
	 * @param string $field
	 * @param string $value
	 */
	public static function HSET($id, $field, $value)
	{
		self::checkFieldName($field);
		$key = static::$hashConfigs['name'] . ':' . $id;
		return Yii::$app->redis->executeCommand('HSET', [$key, $field, $value]);
	}
	
	/**
	 * 从哈希集中读取全部的域和值
	 * @param integer $id
	 */
	public static function HGETALL($id)
	{
		$key = static::$hashConfigs['name'] . ':' . $id;
		return Yii::$app->redis->executeCommand('HGETALL', [$key]);
	}
	
	public static function checkFieldName($field)
	{
		if (!in_array($field, static::$hashConfigs['fields'])) {
			throw new RedisException('Invalid field name.');
		}
	}
}