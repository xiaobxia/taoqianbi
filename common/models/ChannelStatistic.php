<?php
namespace common\models;


class ChannelStatistic extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%channel_statistic}}';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => '一级渠道id',
            'subclass_id' => '二级渠道id',
            'pre_pv' => '注册总量(扣量前)',
            'pv' => '注册量',
            'pv_rate' => '批量率',
            'apply_all' => '申请借款量',
            'loan_all' => '成功借款量',
            'withhold_pv' => '代扣注册量',
            'link' => '地址',
            'time' => '当天0点的时间',
            'created_at' => '创建时间',
            'updated_at' => '修改时间'
        ];
    }
}