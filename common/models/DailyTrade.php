<?php
namespace common\models;

use Yii;
/**

 * date 2017-05-11
 *
 * Channel model
 * @property integer $status
 * @property integer $channel_rank
 */
class DailyTrade extends \yii\db\ActiveRecord{


    /**
     * @inheritdoc
     */
    public static function tableName(){
        return '{{%daily_trade_data}}';
    }




}
