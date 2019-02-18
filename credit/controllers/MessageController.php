<?php
namespace credit\controllers;

use Yii;
use yii\helpers\Url;
use common\models\ContentActivity;
use yii\data\Pagination;

/**
 * MessageController
 * @use 用于用户接受消息通知等信息的控制器 （包括：App推送、短信、App弹框、App红点消息等）
 */
class MessageController extends BaseController
{

    /**
     * 启动弹框
     *
     * @name 启动弹框 [noticePopBox]
     * @method post
     * @param integer $page 页数
     */
    public function actionGetMessage() {
        $now_page  = intval($this->request->post('page'));
        $now_page  = $now_page < 1 ? 1 : $now_page;

        // $condition = " 1=1 ";
        $condition = "status in (2,3)";
        $query = ContentActivity::find()->where($condition)->orderBy([
            'id' => SORT_DESC,
        ]);
        $pages = new Pagination(['totalCount' => $query->count()]);
        $totalCount = $pages->totalCount;
        $limit  = 5;
        $totalPage  = floor(($totalCount + $limit -1) / $limit);
        $offset = ($now_page - 1) * $limit;

        $data   = $query->offset($offset)->limit($limit)->asArray()->all();
        if (count($data) > 0) {
            foreach ($data as $k => $v) {
                $data[$k]["created_at"] = date("Y-m-d H:i",$v["created_at"]);
            }
        }

        return [
            'code' => 0,
            'message' => '获取成功',
            'data' => [
                "item"  => $data,
                "page"  => $now_page,
                "count" => $totalPage
            ]
        ];
    }
}
