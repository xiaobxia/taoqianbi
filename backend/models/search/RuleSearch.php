<?php

namespace backend\models\search;

use Yii;
use yii\data\ActiveDataProvider;

use common\models\risk\Rule;

class RuleSearch extends Rule
{

	public function rules(){
		return [
			[['type','state','id','tree_root'],'integer'],
            [['name', 'tree_description'],'safe']
		];
	}

	public function search($params){
		$query = Rule::find()->where(['status'=>Rule::STATUS_NORMAL])->orderBy('id DESC');

    	$dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

		if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        $query->andFilterWhere([
            'id' => $this->id,
            'type' => $this->type,
            'state' => $this->state,
            'tree_root' => $this->tree_root
        ]);
        $query->andFilterWhere(['like','name',$this->name]);
        $query->andFilterWhere(['like','tree_description',$this->tree_description]);
        return $dataProvider;
	}

}