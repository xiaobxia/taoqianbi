<?php
namespace backend\controllers;

use ALIOSS;
use common\helpers\Curl;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\WeixinMenu;
use common\models\WeixinUser;
use Green\Request\V20170112\VideoAsyncScanRequest;
use Yii;
use common\models\AppBanner;
use yii\db\Exception;
use yii\db\Query;
use common\helpers\Url;
use yii\data\Pagination;
use yii\web\UploadedFile;
use yii\validators\FileValidator;
use common\models\UserProofMateria;

require_once Yii::getAlias('@common/api/oss') . '/sdk_wzd.class.php';
/**
 * Banner controller
 */
class WeixinContractController extends BaseController
{
    public $enableCsrfValidation = false;
    private $source_list = [
        LoanPerson::PERSON_SOURCE_MOBILE_CREDIT ,
    ];
    /**
     * @name 显示已有的微信菜单
     */
    public function actionMenu(){
        $menu = WeixinMenu::getMenuInfo();

        return $this->render('list',[
            'list'=>$menu
        ]);
    }
    /**
     * @name banner列表
     */
    public function actionList()
    {
        $query = WeixinMenu::find();
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;
        $list = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        foreach ($list as $k=>$v){

        }
        return $this->render('list', array(
            'list' => $list,
            'pages' => $pages,
        ));
    }

    /**
     * @name 添加banner
     */
    public function actionAdd()
    {
        $model = new WeixinMenu();
        // 有提交则装载post值并验证
        $data = Yii::$app->getRequest()->post();
        $fathers = [];
        $fathers[0]= '父按钮';
        $father = WeixinMenu::find()->where(['pid'=>0])->select('id,name')->asArray()->all();
        if(is_array($father)){
            foreach ($father as $k => $v){
                $fathers[$v['id']] = $v['name'];
            }
        }
        if (isset($data) &&  $model->load($data)) {
            //var_dump($model);die;
            if(isset($model->pid) && $model->pid == 0){
                $count = $model->find()->where(['pid' => 0])->count();
                if($count == 3){
                    return $this->redirectMessage('父按钮添加失败,最多只能有三个', self::MSG_ERROR);
                }
            }
            if(isset($model->pid) && $model->pid != 0){
                $pid_count = $model->find()->where(['pid' => $model->pid])->count();
                if($pid_count == 5){
                    return $this->redirectMessage('子按钮添加失败,最多只能有5个', self::MSG_ERROR);
                }
            }
            if ($model->validate() && $model->save()) {
                return $this->redirectMessage('添加成功', self::MSG_SUCCESS, Url::toRoute('menu'));
            } else {
                $message = $model->getErrors();
                $val = array_keys($message);
                $message_res = $message[$val['0']];
                $msg = '添加失败';
                if($message_res){
                    $msg = $val['0'].$message_res[0];
                }
                return $this->redirectMessage($msg, self::MSG_ERROR);
            }
        }

        return $this->render('add', [
            'model' => $model,
            'type'  => 'add',
            'fathers' => $fathers,
            'award' => [],
            'platform' => []
        ]);
    }

    /**
     * @name 编辑banner
     */
    public function actionEdit( $id)
    {
        $model = WeixinMenu::findOne($id);
        if(!$model) {
            return $this->redirectMessage('按钮已删除', self::MSG_ERROR);
        }
        $fathers = [];
        $fathers[0]= '父按钮';
        $father = WeixinMenu::find()->where(['pid'=>0])->select('id,name')->asArray()->all();
        if(is_array($father)){
            foreach ($father as $k => $v){
                $fathers[$v['id']] = $v['name'];
            }
        }//TODO 后面改不是数组的情况

        $data = Yii::$app->getRequest()->post();
        if ($model->load($data) && $model->validate()) {
            if ($model->save()) {
                return $this->redirectMessage('修改成功', self::MSG_SUCCESS, Url::toRoute('list'));
            } else {

                return $this->redirectMessage('修改失败', self::MSG_ERROR);
            }
        }
        return $this->render('add', array(
            'model' => $model,
            'fathers' => $fathers,
            'type'  => 'edit',
            'award' => [],
            'platform' => []
        ));
    }


    /**
     * @name 删除banner
     */
    public function actionDel( $id)
    {
        $model = WeixinMenu::findOne($id);
        if ($model->delete()) {
            return $this->redirectMessage('删除成功', self::MSG_SUCCESS, Url::toRoute('list'));
        } else {
            return $this->redirectMessage('删除失败', self::MSG_ERROR);
        }
    }
    /**
     * @name 更新微信菜单
     */
    public function actionUpdate(){
        //组装
        $data = WeixinMenu::find()->asArray()->all();
        $menuList = [];
        foreach ($data as $key=>$item)
        {
            if($item['pid'] == 0)
            {
                $menuList[] = $item;
                unset($data[$key]);
            }

        }


        foreach ($data as $data_item)
        {
            foreach ($menuList as $menuKey=>$list)
            {

                if($list['type'] == 'click')
                {
                    $menuList[$menuKey]['name'] = $list['name'];
                    $menuList[$menuKey]['type'] = 'click';
                    $menuList[$menuKey]['key'] = $list['key'];
                }
                else if($list['type'] == 'view')
                {
                    $menuList[$menuKey]['name'] = $list['name'];
                    $menuList[$menuKey]['type'] = 'view';
                    $menuList[$menuKey]['url'] = $list['url'];
                }
                else
                {
                    if($data_item['pid'] == $list['id'])
                    {
                        $subTmp['name'] = $data_item['name'];
                        $subTmp['type'] = $data_item['type'];
                        if($data_item['type'] == 'click')
                        {
                            $subTmp['key'] = $data_item['key'];
                        }
                        else if($data_item['type'] == 'view')
                        {
                            $subTmp['url'] = $data_item['url'];
                        }

                        $menuList[$menuKey]['sub_button'][] = $subTmp;
                        unset($subTmp);
                    }
                }

            }
        }

        foreach ($menuList as &$list)
        {
            unset($list['id']);
            unset($list['pid']);
            unset($list['id']);

            if($list['type'] == 'click')
            {
                $menuList[$menuKey]['name'] = $list['name'];
                $menuList[$menuKey]['type'] = 'click';
                $menuList[$menuKey]['key'] = $list['key'];

                unset($list['url']);
            }
            else if($list['type'] == 'view')
            {
                $menuList[$menuKey]['name'] = $list['name'];
                $menuList[$menuKey]['type'] = 'view';
                $menuList[$menuKey]['url'] = $list['url'];

                unset($list['key']);
            }
            else
            {
                unset($list['type']);
                unset($list['url']);
                unset($list['key']);
            }

            unset($list['created_at']);
            unset($list['updated_at']);
        }

        $buttons['button'] = $menuList;


        $data_res = json_encode($buttons,JSON_UNESCAPED_UNICODE);
        $wxservice = Yii::$app->weixinService;
        $res = $wxservice->getMenu($data_res);

        if($res === true)
        {
            return $this->redirectMessage('微信菜单保存成功！', self::MSG_SUCCESS);
        }
        else
        {
            return $this->redirectMessage('微信菜单保存失败，请联系联系管理员, 错误码：' . $res, self::MSG_ERROR);
        }
    }
    /**
     * 用户储蓄卡列表过滤
     */
    protected function getCardListFilter()
    {
        //查询还有14天的用户
        $condition = 1;
        if ($this->getRequest()->getIsGet()) {
            $search = $this->request->get();
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
     * 用户列表
     */
    public function actionMsgList(){
        $temp_list = WeixinMenu::$temp_func;//模板列表
        $data = Yii::$app->request->get();
        if(isset($data)){
            $beginTime=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endTime=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $type = $data['type']??1;
            $where = '';
            if($type == 1){
                $where = "plan_fee_time >= ".$beginTime .' AND plan_fee_time <='.$endTime;//还款当一天//未还款//还款失败的
            }elseif($type == 2){//还款当天
                $where = ".plan_fee_time = $beginTime";
            }
            $andWhere = "l.user_id = w.uid";
        }
        //var_dump($where);die;
        $query = UserLoanOrderRepayment::find()->from(UserLoanOrderRepayment::tableName(). 'as l')
            ->leftJoin(WeixinUser::tableName() . 'as w', 'l.user_id = w.uid ')
            ->leftJoin(LoanPerson::tableName() . 'as u', 'l.user_id = u.id ')
            ->where(['in', 'l.status', [
                UserLoanOrderRepayment::STATUS_NORAML,
                UserLoanOrderRepayment::STATUS_DEBIT_FALSE
            ]
            ])->andwhere($where)->andWhere($andWhere)->select(
                'l.id,l.user_id,l.principal,l.true_total_money,l.plan_fee_time,w.nickname,u.name'
            )
            ->orderBy(['l.id'=>SORT_DESC]);
        $countQuery = clone $query;
        $pages = new Pagination(['totalCount' => $countQuery->count('*')]);
        $pages->pageSize = 15;
        $info = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
        //var_dump($info);die;
        return $this->render('info', array(
            'infos' => $info,
            'pages' => $pages,
            'temp_list' =>$temp_list,
        ));
    }


    /**
     * @name 获取微信菜单信息
     */
    public function actionWxMenu()
    {
        $weixinService = Yii::$app->weixinService;
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token=' . $weixinService->get_access_token();

        $curl = new Curl();
        $res = $curl->get($url);

        $menu = json_decode($res, true);
        print_r($menu);
    }
}