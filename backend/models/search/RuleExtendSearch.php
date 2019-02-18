<?php

namespace backend\models\search;

use Yii;
use yii\data\ActiveDataProvider;

use common\models\risk\RuleExtendMap;

class RuleExtendSearch extends RuleExtendMap
{
	public function rules(){
		return [];
	}

	public function search($params,$post_params=[]){
		$query = RuleExtendMap::find()->where(['status'=>RuleExtendMap::STATUS_NORMAL]);
        if (array_key_exists('rule_id', $post_params)) {
            $query->andWhere([
                'rule_id' => $post_params['rule_id'],
            ]);
        }else{
            $query->andWhere('1=2');
        }
    	$dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);

		if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        $query->andFilterWhere([
            'order' => $this->order,
            'state' => $this->state
        ]);
        $query->andFilterWhere(['like','expression',$this->expression]);
        $query->andFilterWhere(['like','result',$this->result]);
        return $dataProvider;
	}

}