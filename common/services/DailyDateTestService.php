<?php
namespace common\services;

use Yii;
use yii\base\Component;
use common\models\LoanPerson;
use common\models\UserLoanOrder;
use common\models\UserLoanOrderRepayment;
use common\models\UserRegisterInfo;
use common\models\CreditJsqb;

/**
 * Class 数据报表统计
 * @package common\services
 */
class DailyDateTestService extends Component
{
    const IS_WHITE = 1;//白名单
    const NOT_WHITE = 0;//非白名单
    const PERSON_NEW  = 0;//新客
    const PERSON_OLD  = 1;//老客

    public $today_start;//开始时间
    public $today_end;//结束时间


    /**
     * @name 返回 全部注册 白/非名单
     * @param $type int 白/非名单
     * @return $array
     */
    public function getRegisterInfo($time_st,$time_res){
        $where = ['between','r.created_at',$time_st,$time_res];
        $where2 = ['between','created_at',$time_st,$time_res];
        $andwhere = ['w.is_white'=>self::IS_WHITE];

        $count_white = UserRegisterInfo::find()
            ->from(UserRegisterInfo::tableName(). 'as r')
            ->leftJoin(CreditJsqb::tableName() . 'as w', ' r.user_id = w.person_id')
            ->where($where)->andWhere($andwhere)->count();

        $all = UserRegisterInfo::find()->where($where2)->count();
        $count_no_white = $all - $count_white;
        return [
            'all'=>$all,
            'white'=>$count_white,
            'no_white'=>$count_no_white,
        ];
    }

    /**
     * @name 返回 全部申请 白/非名单 新老客
     * @param $type int 新老客
     * $param $data 借款的数据
     */
    public function getLoanOrderInfo($time_st,$time_res){
        $where2 = ['between','created_at',$time_st,$time_res];
        //非白名单 新客
        $count_no_white  =  $this->getData(1,$time_st,$time_res);
        //白新客
        $count_white_new = 0;
        $count_no_white_new = 0;
        /* $count_white_new = $count_no_white['all'];
         $count_no_white_new = $count_no_white['all'];*/
        //非白名单 老客
        $count_no_white_old =  $this->getData(2,$time_st,$time_res);
        $count_white_old = $count_no_white_old['w_all'];
        $count_no_white_old_all = $count_no_white_old['all'];
        //全部的
        $all = UserLoanOrder::find()->where($where2)->count();
        return [
            'all'=>$all,
            'white_loan_new'=>$count_white_new,
            'white_loan_old'=>$count_white_old,
            'no_white_loan_new'=>$count_no_white_new,
            'no_white_loan_old'=>$count_no_white_old_all,
        ];

    }

    private function getData($type,$time_st,$time_res){
        $where = ['between','r.created_at',$time_st,$time_res];
        if($type == 1){
            $andwhere = ['l.customer_type'=>self::PERSON_NEW];
        }else if ($type == 2){
            $andwhere = ['l.customer_type'=>self::PERSON_OLD];
        }

        $count_no_white_old = UserLoanOrder::find()
            ->from(UserLoanOrder::tableName(). 'as r')
            ->leftJoin(LoanPerson::tableName() . 'as l', ' r.user_id = l.id')
            ->where($where)->andWhere($andwhere)->select(['user_id'])->asArray()->all();
        $res_all = 0;
        if(!empty($count_no_white_old)){
            foreach ($count_no_white_old as $v){
                $data[] = $v['user_id'];
            }
            $all = count($data);
            $res_all = CreditJsqb::find()->where(['in','person_id',$data])->andwhere(['is_white'=>1])->count();
            $res = $all - $res_all;
        }else{
            $res = 0;
        }
        return [
            'w_all'=>$res_all,
            'all'=>$res,
        ];
    }

    /**
     * @name 返回 全部通过 白/非名单 新老客
     * @param $type int 新老客
     * $param $data 借款的数据
     */
    public function getLoanPassInfo($time_st,$time_res)
    {
        $where2 = ['between','created_at',$time_st,$time_res];
        //非白名单 新客
        $count_no_white  =  $this->getDatas(1,$time_st,$time_res);
        //白新客
        $count_white_new = $count_no_white['w_all'];
        $count_no_white_new = $count_no_white['all'];
        //非白名单 老客
        $count_no_white_old =  $this->getDatas(2,$time_st,$time_res);
        $count_white_old = $count_no_white_old['w_all'];
        $count_no_white_old_all = $count_no_white_old['all'];
        //全部的
        $all = UserLoanOrderRepayment::find()->where($where2)->count();
        return [
            'all'=>$all,
            'white_loan_new'=>$count_white_new,
            'white_loan_old'=>$count_white_old,
            'no_white_loan_new'=>$count_no_white_new,
            'no_white_loan_old'=>$count_no_white_old_all,
        ];
    }

    private function getDatas($type,$time_st,$time_res){
        $where = ['between','r.created_at',$time_st,$time_res];
        if($type == 1){
            $andwhere = ['l.customer_type'=>self::PERSON_NEW];
        }else if ($type == 2){
            $andwhere = ['l.customer_type'=>self::PERSON_OLD];
        }

        $count_no_white_old = UserLoanOrderRepayment::find()
            ->from(UserLoanOrderRepayment::tableName(). 'as r')
            ->leftJoin(LoanPerson::tableName() . 'as l', ' r.user_id = l.id')
            ->where($where)->andWhere($andwhere)->select(['user_id'])->asArray()->all();
        $res_all = 0;
        if(!empty($count_no_white_old)){
            foreach ($count_no_white_old as $v){
                $data[] = $v['user_id'];
            }
            $all = count($data);
            $res_all = CreditJsqb::find()->where(['in','person_id',$data])->andwhere(['is_white'=>1])->count();
            $res = $all - $res_all;
        }else{
            $res = 0;
        }
        return [
            'w_all'=>$res_all,
            'all'=>$res,
        ];
    }



}