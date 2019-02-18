<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/29
 * Time: 13:45
 */
namespace common\models;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use common\models\UserProofMateria;
/**
 * This is the model class for table "{{%province}}".
 */
class AlipayDataOrg extends ActiveRecord
{
    const STATUS_NO_REMARK = 0;
    const STATUS_ALREAD_REMARK = 1;



    public static function AddZfbReportData($user_id, $name, $amount)
    {

        $name = trim($name, '');
        $report = self::find()->where(['name' => $name])->one();
        if (empty($report)) {
            $report = new AlipayDataOrg();
            $report->name = $name;
            $report->user_ids = json_encode([$user_id => ['count' => 1, 'amount' => $amount]]);
            $report->count = 1;
            $report->count_person = 1;
            $report->status = AlipayDataOrg::STATUS_NO_REMARK;
            $report->category = '';
        }

        $user_ids = $report->user_ids;
        $user_ids = json_decode($user_ids,true);
        if (array_key_exists($user_id, $user_ids)) {
            $a = $user_ids[$user_id];
            if (isset($a['amount'])) {
                $a['amount'] = $a['amount'] + $amount;
            }
            if (isset($a['count'])) {
                $a['count']++;
            }
            $user_ids[$user_id] = $a;
        } else {
            $report->count_person += 1;
            $user_ids[$user_id] = [$user_id => ['count' => 1, 'amount' => $amount]];
        }
        $user_ids = json_encode($user_ids);
        $report->user_ids = $user_ids;
        $report->count += 1;

        $report->save();
    }


}