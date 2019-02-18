<?php

namespace backend\models\search;

use Yii;
use yii\data\ActiveDataProvider;

use common\models\credit_line\CreditLine;

class CreditLineSearch extends CreditLine
{

	public function search($params){
		$query = CreditLine::find()->where(['status'=>CreditLine::STATUS_ACTIVE])->orderBy('id DESC');

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
            'user_id' => $this->user_id,
            'credit_line' => $this->credit_line,
            'time_limit' => $this->time_limit,
        ]);

        return $dataProvider;
	}

}