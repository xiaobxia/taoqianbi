<?php

namespace backend\models\search;

use Yii;
use yii\data\ActiveDataProvider;

use common\models\card_qualification\CardQualificationResult;

class CardQualificationSearch extends CardQualificationResult
{

	public function search($params){
		$query = CardQualificationResult::find()->where(['status'=>CardQualificationResult::STATUS_ACTIVE])->orderBy('id DESC');

    	$dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

		if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        
        $query->andFilterWhere([
            'loan_person_id' => $this->loan_person_id,
            'type' => $this->type,
            'qualification' => $this->qualification
        ]);

        return $dataProvider;
	}

}