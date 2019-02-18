<?php

namespace backend\models\search;

use Yii;
use yii\data\ActiveDataProvider;

use common\models\risk\EscapeTemplate;

class EscapeTemplateSearch extends EscapeTemplate
{

	public function rules(){
		return [
			[['state','id'],'integer'],
            [['name'],'string', 'max' => 128]
		];
	}

	public function search($params){
		$query = EscapeTemplate::find()->where(['status'=>EscapeTemplate::STATUS_NORMAL]);

    	$dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

		if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        $query->andFilterWhere([
            'id' => $this->id,
            'state' => $this->state
        ]);
        $query->andFilterWhere(['like','name',$this->name]);
        return $dataProvider;
	}

}