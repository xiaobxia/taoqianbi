<?php
namespace backend\controllers;

use common\exceptions\UserExceptionExt;
use common\helpers\CommonHelper;
use Yii;
use common\helpers\Url;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\Response;
use yii\data\Pagination;
use common\models\LoanBlackList;
use common\models\LoanPerson;
use common\models\LoanBlacklistDetail;
use common\models\UserQuotaPersonInfo;
use common\models\UserDetail;
use common\models\UserQuotaWorkInfo;
use common\helpers\StringHelper;
use yii\db\Query;


class LoanBlackListController extends BaseController
{
    /**
     * @name 加入黑名单 /actionAdd
     **/
    public function actionAdd()
    {
        $this->response->format = Response::FORMAT_JSON; //??
        try {
            $id = intval($this->request->get('id'));
            $black_remark = $this->request->get('mark');
            $loanPerson = LoanPerson::findOne($id);
            if (!$black_remark) {
                return [
                    'code' => -1,
                    'message' => '必须填写备注'
                ];
            }
            if (is_null($loanPerson)) {
                return [
                    'code' => -1,
                    'message' => '借款不存在'
                ];
            }
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            $blackList = LoanBlackList::findOne(['user_id' => $id]);
            if (is_null($blackList)) {
                $blackList = new LoanBlackList();  //??区别
            }
            $blackList->user_id = $id;
            $blackList->black_status = 1;
            $blackList->black_admin_user = Yii::$app->user->identity->username;
            $blackList->black_remark = $black_remark;
            if (!$blackList->save()) {
                throw new Exception('保存黑名单失败');
            }

            //身份证
            if (is_null(LoanBlacklistDetail::find()->where(['type' => LoanBlacklistDetail::TYPE_ID_NUMBER, 'content' => $loanPerson['id_number']])->one(Yii::$app->get('db_kdkj_rd')))) {
                $detail_id_number = new LoanBlacklistDetail();
                $detail_id_number->user_id = $loanPerson['id'];
                $detail_id_number->type = LoanBlacklistDetail::TYPE_ID_NUMBER;
                $detail_id_number->content = $loanPerson['id_number'];
                $detail_id_number->source = LoanBlacklistDetail::SOURCE_MATCH;
                $detail_id_number->admin_username = Yii::$app->user->identity->username;
                if (!$detail_id_number->save()) {
                    throw new Exception('保存身份证规则失败');
                }
            }

            //手机号
            if (is_null(LoanBlacklistDetail::find()->where(['type' => LoanBlacklistDetail::TYPE_PHONE, 'content' => $loanPerson['phone']])->one(Yii::$app->get('db_kdkj_rd')))) {
                $detail_phone = new LoanBlacklistDetail();
                $detail_phone->user_id = $loanPerson['id'];
                $detail_phone->type = LoanBlacklistDetail::TYPE_PHONE;
                $detail_phone->content = $loanPerson['phone'];
                $detail_phone->source = LoanBlacklistDetail::SOURCE_MATCH;
                $detail_phone->admin_username = Yii::$app->user->identity->username;
                if (!$detail_phone->save()) {
                    throw new Exception('保存手机号规则失败');
                }
            }

            //家庭地址
            $person_relation = UserQuotaPersonInfo::find()->where(['user_id' => $loanPerson['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if (!is_null($person_relation)) {
                if (!empty($person_relation['address_distinct']) && !empty($person_relation['address'])) {
                    if (is_null(LoanBlacklistDetail::find()->where(['type' => LoanBlacklistDetail::TYPE_HOME_ADDRESS, 'content' => $person_relation['address_distinct'] . ' ' . $person_relation['address']])->one(Yii::$app->get('db_kdkj_rd')))) {
                        $detail_home_address = new LoanBlacklistDetail();
                        $detail_home_address->user_id = $loanPerson['id'];
                        $detail_home_address->type = LoanBlacklistDetail::TYPE_HOME_ADDRESS;
                        $detail_home_address->content = $person_relation['address_distinct'] . ' ' . $person_relation['address'];
                        $detail_home_address->source = LoanBlacklistDetail::SOURCE_MATCH;
                        $detail_home_address->admin_username = Yii::$app->user->identity->username;
                        if (!$detail_home_address->save()) {
                            throw new Exception('保存家庭地址规则失败');
                        }
                    }
                }
            }

            $equipment = UserDetail::find()->where(['user_id' => $loanPerson['id']])->one(Yii::$app->get('db_kdkj_rd'));
            if (!is_null($equipment)) {
                //公司名称
                if (!empty($equipment['company_name'])) {
                    if (is_null(LoanBlacklistDetail::find()->where(['type' => LoanBlacklistDetail::TYPE_COMPANY_NAME, 'content' => $equipment['company_name']])->one(Yii::$app->get('db_kdkj_rd')))) {
                        $detail_company_name = new LoanBlacklistDetail();
                        $detail_company_name->user_id = $loanPerson['id'];
                        $detail_company_name->type = LoanBlacklistDetail::TYPE_COMPANY_NAME;
                        $detail_company_name->content = $equipment['company_name'];
                        $detail_company_name->source = LoanBlacklistDetail::SOURCE_MATCH;
                        $detail_company_name->admin_username = Yii::$app->user->identity->username;
                        if (!$detail_company_name->save()) {
                            throw new Exception('保存家庭地址规则失败');
                        }
                    }
                }

                //公司邮箱
                $mail = explode('@', $equipment['company_email']);
                if (isset($mail[1])) {
                    if (is_null(LoanBlacklistDetail::find()->where(['type' => LoanBlacklistDetail::TYPE_COMPANY_EMAIL, 'content' => $mail[1]])->one(Yii::$app->get('db_kdkj_rd')))) {
                        $detail_company_email = new LoanBlacklistDetail();
                        $detail_company_email->user_id = $loanPerson['id'];
                        $detail_company_email->type = LoanBlacklistDetail::TYPE_COMPANY_EMAIL;
                        $detail_company_email->content = $mail[1];
                        $detail_company_email->source = LoanBlacklistDetail::SOURCE_MATCH;
                        $detail_company_email->admin_username = Yii::$app->user->identity->username;
                        if (!$detail_company_email->save()) {
                            throw new Exception('保存公司邮箱规则失败');
                        }
                    }
                }
            }

            $work = UserQuotaWorkInfo::findOne(['user_id' => $loanPerson['id']]);
            if (!is_null($work)) {
                //公司地址
                if (!empty($work['work_address'])) {
                    if (is_null(LoanBlacklistDetail::find()->where(['type' => LoanBlacklistDetail::TYPE_COMPANY_ADDRESS, 'content' => $work['work_address']])->one(Yii::$app->get('db_kdkj_rd')))) {
                        $detail_company_address = new LoanBlacklistDetail();
                        $detail_company_address->user_id = $loanPerson['id'];
                        $detail_company_address->type = LoanBlacklistDetail::TYPE_COMPANY_ADDRESS;
                        $detail_company_address->content = $work['work_address'];
                        $detail_company_address->source = LoanBlacklistDetail::SOURCE_MATCH;
                        $detail_company_address->admin_username = Yii::$app->user->identity->username;
                        if (!$detail_company_address->save()) {
                            throw new Exception('保存公司地址规则失败');
                        }
                    }
                }
            }

            $transaction->commit();
            return [
                'code' => 0,
                'message' => '添加成功'
            ];
        } catch (Exception $e) {
            $transaction->rollBack();
            return [
                'code' => -1,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @name 取消黑名单 /actionDel
     **/
    public function actionDel()
    {
        $this->response->format = Response::FORMAT_JSON;
        try {
            $id = intval($this->request->get('id'));
            $loanPerson = LoanPerson::findOne($id);
            if (is_null($loanPerson)) {
                return [
                    'code' => -1,
                    'message' => '借款不存在'
                ];
            }

            $blackList = LoanBlackList::findOne(['user_id' => $id]);
            if (is_null($blackList)) {
                return [
                    'code' => -1,
                    'message' => '借款人不在黑名单中'
                ];
            }
            $transaction = Yii::$app->db_kdkj->beginTransaction();
            $blackList->black_status = 0;
            $blackList->black_admin_user = Yii::$app->user->identity->username;;

            if (!$blackList->save()) {
                throw new Exception('黑名单保存失败');
            }

            LoanBlacklistDetail::deleteAll(['user_id' => $loanPerson['id']]);

            $transaction->commit();
            return [
                'code' => 0,
                'message' => '删除成功'
            ];
        } catch (Exception $e) {
            $transaction->rollBack();
            return [
                'code' => -1,
                'message' => $e->getMessage()
            ];
        }

    }

    protected function getFilter()
    {
        $condition = '1 = 1 and a.id>0 ';
        $search = $this->request->get();
        if (isset($search['user_id']) && !empty($search['user_id'])) {
            $condition .= " AND a.user_id = " . intval($search['user_id']);
        }

        if (isset($search['status']) && !empty($search['status'])) {
            $condition .= " AND a.black_status = '" . $search['status'] . "'";
        }
        if (isset($search['status']) && $search['status'] == '0') {
            $condition .= " AND a.black_status = '" . $search['status'] . "'";
        }

        if (isset($search['remark']) && !empty($search['remark'])) {
            $condition .= " and a.black_remark = '{$search['remark']}'";
        }

        if (isset($search['admin_user']) && !empty($search['admin_user'])) {
            $condition .= " and a.black_admin_user = '{$search['admin_user']}'";
        }

        if (isset($search['add_start']) && !empty($search['add_start'])) {
            $condition .= " AND a.created_at >= " . strtotime($search['add_start']);
        }
        if (isset($search['add_end']) && !empty($search['add_end'])) {
            $condition .= " AND a.created_at < " . strtotime($search['add_end']);
        }
        if (isset($search['phone']) && !empty($search['phone'])) {
            $condition .= " and p.phone = {$search['phone']}";
        }

        return $condition;
    }

    /**
     * @name 系统黑名单列表
     * @return string
     */
    public function actionShowList()
    {
        $condition = $this->getFilter();
        $query = LoanBlackList::find()->from(LoanBlackList::tableName() . ' as a')->leftJoin(LoanPerson::tableName() . ' as p', 'a.user_id = p.id')->where($condition)->orderBy('a.id DESC');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*', Yii::$app->get('db_kdkj_rd'))]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->all(Yii::$app->get('db_kdkj_rd'));

        return $this->render('show-list', array(
            'info' => $info,
            'pages' => $pages,
        ));
    }

    /**
     * @name 添加用户到黑名单
     * @return string
     */
    public function actionAddUser()
    {
        if ($this->getRequest()->isPost) {

            $user_id = trim($this->getRequest()->post('user_id'));
            $remark = trim($this->getRequest()->post('remark', ''));

            if (!$loan_person = LoanPerson::findOne($user_id)) {
                return $this->redirectMessage('用户不存在', self::MSG_ERROR);
            }

            if (LoanBlackList::findOne(['user_id' => $user_id])) {
                return $this->redirectMessage('已存在于黑名单', self::MSG_ERROR);
            }

            if (strlen($remark) > 255) {
                return $this->redirectMessage('备注长度超限', self::MSG_ERROR);
            }

            $black_list = new LoanBlackList();
            $black_list->user_id = $user_id;
            $black_list->phone = $loan_person->phone;
            $black_list->id_number = $loan_person->id_number;
            $black_list->black_status = LoanBlackList::STATUS_YES;
            $black_list->black_remark = $remark;
            $black_list->black_admin_user = Yii::$app->user->identity->username;
            try {
                if ($black_list->save()) {
                    if ($black_list->save()) {
                        return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('loan-black-list/show-list'));
                    }
                } else {
                    throw new Exception;
                }
            } catch (\Exception $e) {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }

        }

        return $this->render('add-user');
    }


    /**
     * @name 改变黑名单状态
     * @return array
     */
    public function actionChangeStatus()
    {
        if ($this->getRequest()->isAjax) {
            $this->getResponse()->format = Response::FORMAT_JSON;
            $id = trim($this->getRequest()->post('id'));
            if (!$black_list = LoanBlackList::findOne($id)) {
                return UserExceptionExt::throwCodeAndMsgExt('找不到该记录');
            }

            if ($black_list->black_status == LoanBlackList::STATUS_NO) {
                $black_list->black_status = LoanBlackList::STATUS_YES;
            } else {
                $black_list->black_status = LoanBlackList::STATUS_NO;
            }

            if ($black_list->save()) {
                return CommonHelper::resp();
            } else {
                return UserExceptionExt::throwCodeAndMsgExt('保存失败');
            }
        }
    }

}
