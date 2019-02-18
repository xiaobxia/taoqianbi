<?php
namespace backend\controllers;


use Yii;
use common\helpers\Url;
use yii\data\Pagination;
use common\models\AppBanner;
use common\models\risk\RuleVersion;
use common\models\Version;
use common\models\Setting;

/**
 * AdminUser controller
 */
class VersionController extends BaseController {

    /**
     * APP 版本控制
     * 101极速荷包
     */
    public function actionSetting($id=101) {
        $version_info = Version::findOne(['id'=>$id,'type'=>1]);
        if(false == $version_info){
            $version_info = new Version();
        }

        $version_info->id = $id;
        $version_info->type = 1;
        $version_info->has_upgrade = $version_info->has_upgrade;
        $version_info->is_force_upgrade = $version_info->is_force_upgrade;
        $version_info->new_version = $version_info->new_version;
        $version_info->new_ios_version = $version_info->new_ios_version;
        $version_info->new_features = $version_info->new_features;
//        $version_info->ios_url = $version_info->ios_url;
        $version_info->ard_url = $version_info->ard_url;
        $version_info->ard_size = $version_info->ard_size;

        if ($version_info->load($this->request->post()) && $version_info->validate()) {
            $version_info->id = $id;
            $version_info->type = 1;
            $version_info->has_upgrade = $version_info->has_upgrade;
            $version_info->is_force_upgrade = $version_info->is_force_upgrade;
            $version_info->new_version = $version_info->new_version;
            $version_info->new_ios_version = $version_info->new_ios_version;
            $version_info->new_features = $version_info->new_features;
//            $version_info->ios_url = $version_info->ios_url;
            $version_info->ard_url = $version_info->ard_url;
            $version_info->ard_size = $version_info->ard_size;
            $version_info->update_time = time();
            $version_info->operator_name = Yii::$app->user->identity->username;
            if(101 == $id){
                Setting::updateSetting('credit_app_time_stamp',$version_info->update_time);
            }else{
                Setting::updateSetting('app_time_stamp',$version_info->update_time);
            }
            if(!$version_info->save()){
                return $this->redirectMessage('保存失败', self::MSG_ERROR, Url::toRoute('version/setting'));
            }
            return $this->redirectMessage('保存成功', self::MSG_SUCCESS, Url::toRoute('version/setting'));
        }

        return $this->render('setting', [
            'version_info' => $version_info,
        ]);
    }

    /**
     * 配置列表
     * @return string
     * @name 财务管理 -小钱包打款扣款管理-支付宝还款列表/actionAlipayList
     */
    public function actionList()
    {
        $query = Version::find()->where(['<>', 'id', '101'])->orderBy('id DESC');
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;
        $list = $query->offset($pages->offset)->limit($pages->limit)->all();

        return $this->render('list', array(
            'list' => $list,
            'type_name' => '',
            'status_name' => '',
            'pages' => $pages,
        ));
    }

    // 添加配置
    /**
     * @return string
     * @name 配置管理 /actionVersionList
     */
    public function actionAdd()
    {
        $model = new Version();
        $data = $this->request->post();
        $version_rule = RuleVersion::find()->select(['name','id'])
            ->where(['status'=>1])->asArray()->all();
        if ($data && $model->load($data)) {
            if ($model->save() && $model->validate()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }
        return $this->render('add', [
            'version' => $version_rule,
            'model' => $model,
            'type' => 'edit',
        ]);
    }

    //修改配置
    /**
     * @return string
     * @name 财务管理 -小钱包打款扣款管理-支付宝还款列表/actionAlipayList
     */
    public function actionEdit(int $id)
    {
        $type = 'edit';
        $app_url = Version::$app_url;
        $model = Version::findOne(['id' => $id]);
        $data = $this->request->post();
        $version_rule = RuleVersion::find()->select(['name','id'])
            ->where(['status'=>1])->asArray()->all();
        if ($data && $model->load($data)) {
            if ($model->save() && $model->validate()) {
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }
        }
        return $this->render('add', [
            'model' => $model,
            'app_url' => $app_url,
            'version' => $version_rule,
            'type' => $type
        ]);
    }
    /**
     * @return string
     * @name 财务管理 -小钱包打款扣款管理-支付宝还款列表/actionAlipayList
     */
    //删除配置
    public function actionDel(int $id)
    {
        $model = Version::findOne($id);
        if (!$model) {
            return $this->redirectMessage('banner已删除', self::MSG_ERROR);
        }
        if ($model->delete($id)) {
            return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('list'));
        } else {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }
    }


    /**
     * wangwei
     * @name 获取对应的版本配置
     */

    public function actionListRule()
    {
        $Rule_res = RuleVersion::find()->orderBy('id desc');
        $countQuery = clone $Rule_res;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;
        $list = $Rule_res->offset($pages->offset)->limit($pages->limit)->all();
        return $this->render('list_rule', [
            'list' => $list,
            'pages' => $pages,
        ]);
    }

    /**
     * wangwei
     * @name 添加版本配置
     */
    public function actionAddRule()
    {
        $model = new RuleVersion();
        $data = $this->request->post();
        if ($data && $model->load($data)) {
            $model->created_by = \Yii::$app->user->identity->username;;
            if ($model->save() && $model->validate()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('添加失败', self::MSG_ERROR);
            }
        }
        $version = $this->getVersion();
        return $this->render('add_rule', [
            'version' => $version,
            'model' => $model,
            'type' => 'add',
        ]);
    }


    /**
     * wangwei
     * @name 添加版本配置
     */
    public function actionEditRule(int $id)
    {
        $type = 'edit';
        $model = RuleVersion::findOne(['id' => $id]);
        $data = $this->request->post();
        if ($data && $model->load($data)) {
            $model->created_by = \Yii::$app->user->identity->username;
            if ($model->save() && $model->validate()) {
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {
                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }
        }
        $version = $this->getVersion();
        return $this->render('add_rule', [
            'version' => $version,
            'model' => $model,
            'type' => $type
        ]);
    }

    /**
     * @return string
     * @name 财务管理 -小钱包打款扣款管理-支付宝还款列表/actionAlipayList
     */
    //删除配置
    public function actionDelRule(int $id)
    {
        $model = RuleVersion::findOne($id);
        if (!$model) {
            return $this->redirectMessage('banner已删除', self::MSG_ERROR);
        }
        if ($model->delete($id)) {
            return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('list-rule'));
        } else {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }
    }
}
