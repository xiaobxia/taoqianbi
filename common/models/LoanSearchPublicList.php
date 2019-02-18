<?php

namespace common\models;

use Yii;

class LoanSearchPublicList extends BaseActiveRecord
{
    //1初始化，2删除
    const STATUS_INIT = 1;
    const STATUS_DELETE = 2;

    public static $status = [
        self::STATUS_INIT => '初始化',
        self::STATUS_DELETE => '已删除',
    ];

    // 5个key
    const KEY_SEARCH_LIST_ONE = 'loan_search_public_list_01';
    const KEY_SEARCH_LIST_TWO = 'loan_search_public_list_02';
    const KEY_SEARCH_LIST_THREE = 'loan_search_public_list_03';
    const KEY_SEARCH_LIST_FOUR = 'loan_search_public_list_04';
    const KEY_SEARCH_LIST_FIVE = 'loan_search_public_list_05';

    public static $keys = [
        1 => self::KEY_SEARCH_LIST_ONE,
        2 => self::KEY_SEARCH_LIST_TWO,
        3 => self::KEY_SEARCH_LIST_THREE,
        4 => self::KEY_SEARCH_LIST_FOUR,
        5 => self::KEY_SEARCH_LIST_FIVE,
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_search_public_list}}';
    }

    public static function getDb()
    {
        return Yii::$app->get('db_stats');
    }

}
