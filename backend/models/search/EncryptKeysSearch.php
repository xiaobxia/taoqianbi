<?php

namespace backend\models\search;

use Yii;
use yii\data\ActiveDataProvider;

use common\models\encrypt\EncryptKeys;

class EncryptKeysSearch extends EncryptKeys
{

	public function search($params){
		$query = EncryptKeys::find()->where(['state'=>self::STATE_USABLE, 'status'=>EncryptKeys::STATUS_ACTIVE])->orderBy('id DESC');

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
            'type' => $this->type,
            'state' => $this->state
        ]);

        return $dataProvider;
	}

}