<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%loan_task}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $data
 * @property integer $task_id
 * @property string $file
 * @property integer $status
 * @property string $operator_name
 * @property integer $created_at
 * @property integer $updated_at
 */
class LoanTask extends BaseActiveRecord
{
    //1未开始2已开始3已完成4删除5执行失败
    const STATUS_NOT = 1;
    const STATUS_ING = 2;
    const STATUS_FINISH = 3;
    const STATUS_DELETE = 4;
    const STATUS_FAILD = 5;

    public static $status = [
        self::STATUS_NOT => '未开始',
        self::STATUS_ING => '处理中',
        self::STATUS_FINISH => '已完成',
        self::STATUS_DELETE => '删除',
        self::STATUS_FAILD => '执行失败',
    ];

    const TYPE_1 = 1;
    const TYPE_2 = 2;
    const TYPE_3 = 3;
    const TYPE_4 = 4;

    public static $type = [
        self::TYPE_1 => '维度筛选用户',
        self::TYPE_2 => '还款日志列表',
        self::TYPE_3 => '打款日志列表',
        self::TYPE_4 => '扣款列表',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%loan_task}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['data'], 'string', 'max' => 500],
            [['file'], 'string', 'max' => 200],
            [['operator_name'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '任务名称',
            'data' => 'json数据',
            'task_id' => '任务id',
            'file' => '结果文件路径',
            'status' => '状态(1未开始2已开始3已完成4删除',
            'operator_name' => '操作人',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function getDb()
    {
        return Yii::$app->get('db_stats');
    }

}
