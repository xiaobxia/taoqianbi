<?php
/*
 * +----------------------------------------------------------------------
 * | 口袋理财
 * +----------------------------------------------------------------------
 * | Copyright (c) 2016 All rights reserved.
 * +----------------------------------------------------------------------
 * | Author: lujingfeng <lujingfeng@xinjincard.com>
 * +----------------------------------------------------------------------
 * | 渠道额度表基础服务类
 */
namespace common\services;

use yii\base\Component;
use common\helpers\Util;
use common\models\UserLoanOrder;
use common\models\UserCreditTotal;

class BaseCreditChannelService extends Component
{

    //额度对应db对象
    public $creditDBInstance;

    //额度表名称
    protected $creditTableName;

    protected $sub_order_type;

    private $creditDBConfig = [];

    //各个渠道额度表对应常量标识
    const USER_CREDIT_CHANNEL_XJK = 1;
    const USER_CREDIT_CHANNEL_RONG360 = 2;
    const USER_CREDIT_CHANNEL_BAIRONG = 14;

    /**
     * 构造函数
     */
    public function __construct(){
        //根据渠道进行判断
        $credit_db_channel_name = Util::t('credit_db_channel_name');
        if (!empty($credit_db_channel_name) && class_exists($credit_db_channel_name)) {
            $this->creditDBInstance = \Yii::createObject($credit_db_channel_name);
            $this->creditTableName = $this->creditDBInstance->tableName();
        } else {
            $this->creditDBInstance = new UserCreditTotal();
            $this->creditTableName = UserCreditTotal::tableName();
        }

        //保存配置文件信息
        $this->creditDBConfig[self::USER_CREDIT_CHANNEL_XJK] = Util::loadConfig('@common/message/m_xqb')['credit_db_channel_name'];

        $this->sub_order_type = \common\helpers\Util::t('sub_order_type');

        parent::__construct();
    }

    /**
     * 获取额度表名称
     */
    public function getCreditTableName(){
        return $this->creditTableName;
    }

    /**
     * 根据订单ID获取对应渠道额度Model
     * @param int $order_id
     * @return model对象
     */
    public function getCreditTotalChannelByOrderId($order_id){
        $order_info = UserLoanOrder::find()
                        ->select('sub_order_type')
                        ->where(['id' => $order_id])
                        ->asArray()
                        ->one();
        $class = null;
        switch ($order_info['sub_order_type']) {
            case UserLoanOrder::SUB_TYPE_XJD :
                $class = $this->creditDBConfig[self::USER_CREDIT_CHANNEL_XJK];
                break;
            default :
                $class = $this->creditDBConfig[self::USER_CREDIT_CHANNEL_XJK];
                break;
        }
        if (!empty($class) && class_exists($class)) {
            return new $class();
        } else {
            \yii::error( \sprintf('[%s][%s] class %s missing.', __FILE__, __LINE__, $class) );
            return new UserCreditTotal();
        }
    }

    /**
     * 根据channel值(service有常量对应值)获取对应model对象
     * @param int $channel
     * @return unknown|\common\models\UserCreditTotal
     */
    public function getCreditTotalChannelByChannel($channel = 0){
        if (isset($this->creditDBConfig[$channel]) && class_exists($this->creditDBConfig[$channel])) {
            return new $this->creditDBConfig[$channel];
        } else {
            return new UserCreditTotal();
        }
    }
}
