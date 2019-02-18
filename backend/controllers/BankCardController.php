<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2016/5/7
 * Time: 16:24
 */
namespace backend\controllers;

use common\models\CardInfo;
use common\models\LoanPerson;
use common\models\UserRealnameVerify;
use yii\data\Pagination;
use Yii;
use yii\db\Query;
use common\helpers\Url;

class BankCardController extends  BaseController
{
    /**
     * @return string
     * @name 用户管理-用户管理-银行卡列表/actionCardList
     */
    public function actionCardList()
    {
        $condition = $this->getCardListFilter();
        $query = CardInfo::find()->where($condition)->orderBy("id desc");
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->with([
            'loanPerson' => function(Query $query) {
                $query->select(['id', 'name']);
            },
        ])->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }


    /**
     * @return string
     * @name 用户管理-用户管理-银行卡添加/actionCardAdd
     */
    public function actionCardAdd()
    {
        /*银行卡列表*/
        $cardListInfo = CardInfo::getCardConfigList();
        $cardList =array_column($cardListInfo, 'bank_name', 'bank_id');

        $model = new CardInfo();
        $model->type = 2;

        if($this->request->isPost)
        {
            $post = $this->request->post()['CardInfo'];

            if(!$post['user_id'])
                return $this->redirectMessage('请填写用户id', self::MSG_ERROR);
            if(!$post['card_no'])
                return $this->redirectMessage('请填写卡号', self::MSG_ERROR);
            if(!$post['phone'])
                return $this->redirectMessage('请填写手机号', self::MSG_ERROR);

            if(CardInfo::checkCardIsUsed($post['card_no']))
                return $this->redirectMessage('该卡已被绑定', self::MSG_ERROR);

            $model->bank_name = $cardList[$post['bank_id']];
            $model->bank_id = $post['bank_id'];
            $model->user_id = $post['user_id'];
            $model->card_no = $post['card_no'];
            $model->type = $post['type'];
            $model->phone = $post['phone'];
            $model->status = CardInfo::STATUS_SUCCESS;
            $model->bank_id = $post['bank_id'];

            if($model->save())
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute(['bank-card/card-list']));
            else
                return $this->redirectMessage('添加失败', self::MSG_ERROR);

        }
        return $this->render('add', array(
            'model' => $model,
            'card_list' => $cardList
        ));
    }

    /**
     * 用户储蓄卡列表过滤
     */
    protected function getCardListFilter()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['card_no']) && !empty($search['card_no'])) {
                $condition .= " AND card_no = '" . $search['card_no']."'";
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $person = LoanPerson::find()->where(["name" => $search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                if(empty($person)) {
                    $condition .= " AND user_id = 0";
                } else {
                    $condition .= " AND user_id = " . $person['id'];
                }
            }
            if (isset($search['phone']) && !empty($search['phone'])) {
                $condition .= " AND phone = " . $search['phone'];
            }
            if (isset($search['add_start']) && !empty($search['add_start'])) {
                $condition .= " AND created_at >= " . strtotime($search['add_start']);
            }
            if (isset($search['add_end']) && !empty($search['add_end'])) {
                $condition .= " AND created_at < " . strtotime($search['add_end']);
            }

            if (isset($search['status']) && $search['status'] != NULL) {
                $condition .= " AND status = '" . $search['status']."'";
            }
        }
        return $condition;
    }
    /**
     * @name 用户管理-银行卡列表-切换主卡
     */
    public function actionChangeCard($id){
        $model = CardInfo::findOne(intval($id));
        if(is_null($model)){
            return $this->redirectMessage('银行卡不存在',self::MSG_ERROR);
        }
        $model->main_card = CardInfo::MAIN_CARD;
        if(!CardInfo::checkCanRebind($model['user_id'])){
            return $this->redirectMessage('此卡有未完成订单',self::MSG_ERROR);
        }
        if($model->save()){
            \common\models\UserVerification::saveUserVerificationInfo(['user_id'=>$model['user_id'],'real_bind_bank_card_status'=>1]);
            CardInfo::updateAll(['main_card'=>CardInfo::MAIN_CARD_NO],'user_id='.$model['user_id'].' and id<>'.$id);
            return $this->redirectMessage('操作成功', self::MSG_SUCCESS);
        }
        return $this->redirectMessage('操作失败', self::MSG_ERROR);
    }
    /**
     * @name 用户管理-用户实名列表
     */
    public function actionCardRealName()
    {
        $condition = $this->getCardRealNameFilter();
        $query=UserRealnameVerify::find()->andwhere($condition)->orderBy("id desc");
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*',Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $real_name_info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));
        return $this->render('card-real-name',array(
            'real_name_info'=>$real_name_info,
            'pages' =>$pages,
        ));
    }
    /**
     * 银行卡实名过滤
     */
    public function GetCardRealNameFilter()
    {
        $condition = '1 = 1 ';
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
            if (isset($search['user_id']) && !empty($search['user_id'])) {
                $condition .= " AND user_id = " . intval($search['user_id']);
            }
            if (isset($search['name']) && !empty($search['name'])) {
                $person = LoanPerson::find()->where(["name" => $search['name']])->one(Yii::$app->get('db_kdkj_rd'));
                if(empty($person)) {
                    $condition .= " AND user_id = 0";
                } else {
                    $condition .= " AND user_id = " . $person['id'];
                }
            }
            if (isset($search['id_card']) && !empty($search['id_card'])) {
                $condition .= " AND id_card = " . "\"".$search['id_card']."\"";
            }
        }
        return $condition;
    }
}