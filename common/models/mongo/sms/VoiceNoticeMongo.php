<?php
namespace common\models\mongo\sms;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 *
 *
 * RuleCheckReport model
 *
 */
class VoiceNoticeMongo extends ActiveRecord{


    public static function getDb(){
        return Yii::$app->get('mongodb_log');
    }

    /**
     * @inheritdoc
     */
    public static function collectionName(){
        return 'voice_notice_'.date('ym');
    }

    public function attributes()
    {
        return [
            '_id',
            'channel',
            'mobile',
            'request',
            'response',
            'callback',
            'time'
        ];
    }
    
    public static function addNoticeLog($data,$id=''){
        
        $report = self::find()->where(['_id' =>$id ])->one();
        if (empty($report)) {
            $report = new self(['_id' =>$id]);
        }
        
        $report->channel = $data['channel'];
        $report->mobile = $data['phone'];
        $report->request = $data['request'];
        $report->response = $data['response'];
        $report->callback =$data['callback'];
        $report->time = time();

        return $report->save();
    }

}
