<?php

namespace backend\models\search;

use Yii;
use yii\data\ActiveDataProvider;

use common\models\risk\EscapeRule;

class EscapeRuleSearch extends EscapeRule
{

	public function rules(){
		return [
			[['state','value','id'],'integer'],
            [['sign'],'string', 'max' => 128]
		];
	}

	public function search($params,$post=[]){
		$query = EscapeRule::find()->where(['status'=>EscapeRule::STATUS_NORMAL]);
        if(array_key_exists('template_id', $post)){
            $query ->andWhere(['template_id'=>$post['template_id']]);
        }
    	$dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

		if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        $query->andFilterWhere([
            'id' => $this->id,
            'value' => $this->value,
            'state' => $this->state
        ]);
        $query->andFilterWhere(['like','sign',$this->sign]);
        return $dataProvider;
	}

}