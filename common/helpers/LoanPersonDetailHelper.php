<?php
namespace common\helpers;

use Yii;
use yii\base\Exception;
use common\models\CardInfo;
use common\models\UserContact;
use common\models\LoanPerson;
use common\models\UserDetail;
use common\models\UserQuotaPersonInfo;
use common\models\UserQuotaWorkInfo;

class LoanPersonDetailHelper
{
    protected $loan_person_id;
    protected $verify;
    protected $loanPerson;
    protected $cardInfo = '';
    protected $userQuotaPersonInfo = '';
    protected $userDetail = '';
    protected $userQuotaWorkInfo = '';
    protected $userName, $userPhone, $idNumber, $userBirthday;
    protected $cardNo, $bankName, $bankId, $cardType;
    protected $userAddress, $userAddressDistinct, $userLiveTimeType, $userDegrees, $userMarriage, $userDegreesCode;
    protected $userCompanyId, $userCompanyName, $userCompanyEmail, $userContactsType, $userContactsName, $userContactsMobile;
    protected $userCompanyAddress, $userCompanyAddressDistinct;

    public function __construct($loan_person_id,$verify = false)
    {
        $this->verify = $verify;
        $this->loan_person_id = $loan_person_id;
        $this->getLoanPersonModel($this->loan_person_id);
    }

    public function __get($name)
    {
        $function_name = 'get'.ucwords($name);
        return is_null($this->$name) ? $this->$function_name() : $this->$name;
    }

    //用户信息
    protected function getLoanPersonModel($user_id){
        if(is_null($this->loanPerson)){
            if( ! $this->loanPerson = LoanPerson::findOne($user_id)){
                throw new Exception('借款人不存在');
            }
        }

    }
    protected function getUserName(){
        $this->userName = trim($this->loanPerson->name);
        return $this->userName;
    }
    protected function getUserPhone(){
        $this->userPhone = $this->loanPerson->phone;
        return $this->userPhone;
    }
    protected function getIdNumber(){
        $this->idNumber = $this->loanPerson->id_number;
        return $this->idNumber;
    }
    protected function getUserBirthday(){
        $this->userBirthday = $this->loanPerson->birthday;
        return $this->userBirthday;
    }

    //银行卡信息
    protected function getCardInfoModel(){
        if($this->cardInfo === ''){
            if(! $this->cardInfo = CardInfo::find()->where(['user_id'=>$this->loan_person_id,'main_card'=>CardInfo::MAIN_CARD])->one()){
                if($this->verify){
                    throw new Exception('借款人银行主卡信息不存在');
                }

            }
        }
    }
    protected function getCardNo(){
        $this->getCardInfoModel();
        if(is_null($this->cardInfo)){
            $this->cardNo = null;
        }else{
            $this->cardNo = $this->cardInfo->card_no;
        }
        return $this->cardNo;
    }
    protected function getBankName(){
        $this->getCardInfoModel();
        if(is_null($this->cardInfo)){
            $this->bankName = null;
        }else{
            $this->bankName = $this->cardInfo->bank_name;
        }
        return $this->bankName;
    }
    protected function getBankId(){
        $this->getCardInfoModel();
        if(is_null($this->cardInfo)){
            $this->bankId = null;
        }else{
            $this->bankId = $this->cardInfo->bank_id;
        }
        return $this->bankId;
    }
    protected function getCardType(){
        $this->getCardInfoModel();
        if(is_null($this->cardInfo)){
            $this->cardType = null;
        }else{
            $this->cardType = $this->cardInfo->type;
        }
        return $this->cardType;
    }

    protected function getUserQuotaPersonInfoModel(){
        if($this->userQuotaPersonInfo === ''){
            if(! $this->userQuotaPersonInfo = UserQuotaPersonInfo::find()->where(['user_id'=>$this->loan_person_id])->one()){
                if($this->verify){
                    throw new Exception('借款人个人补充信息不存在');
                }

            }
        }
    }
    protected function getUserAddress(){
        $this->getUserQuotaPersonInfoModel();
        if(is_null($this->userQuotaPersonInfo)){
            $this->userAddress = null;
        }else{
            $this->userAddress = $this->userQuotaPersonInfo->address;
        }
        return $this->userAddress;
    }
    protected function getUserAddressDistinct(){
        $this->getUserQuotaPersonInfoModel();
        if(is_null($this->userQuotaPersonInfo)){
            $this->userAddressDistinct = null;
        }else{
            $this->userAddressDistinct = $this->userQuotaPersonInfo->address_distinct;
        }
        return $this->userAddressDistinct;
    }
    protected function getUserLiveTimeType(){
        $this->getUserQuotaPersonInfoModel();
        if(is_null($this->userQuotaPersonInfo)){
            $this->userLiveTimeType = null;
        }else{
            $this->userLiveTimeType = UserQuotaPersonInfo::$live_time_type[$this->userQuotaPersonInfo->live_time_type];
        }
        return $this->userLiveTimeType;
    }
    protected function getUserDegrees(){
        $this->getUserQuotaPersonInfoModel();
        if(is_null($this->userQuotaPersonInfo)){
            $this->userDegrees = null;
        }else{
            $this->userDegrees = UserQuotaPersonInfo::$degrees[$this->userQuotaPersonInfo->degrees];
        }
        return $this->userDegrees;
    }
    protected function getUserDegreesCode(){
        $this->getUserQuotaPersonInfoModel();
        if(is_null($this->userQuotaPersonInfo)){
            $this->userDegreesCode = null;
        }else{
            $this->userDegreesCode = $this->userQuotaPersonInfo->degrees;
        }
        return $this->userDegreesCode;
    }
    protected function getUserMarriage(){
        $this->getUserQuotaPersonInfoModel();
        if(is_null($this->userQuotaPersonInfo)){
            $this->userMarriage = null;
        }else{
            $this->userMarriage = UserQuotaPersonInfo::$marriage[$this->userQuotaPersonInfo->marriage];
        }
        return $this->userMarriage;
    }

    //用户详情
    protected function getUserDetailModel(){
        if($this->userDetail === ''){
            if(! $this->userDetail = UserDetail::find()->where(['user_id'=>$this->loan_person_id])->one()){
                if($this->verify){
                    throw new Exception('借款人详情表不存在');
                }
            }
        }
    }
    protected function getUserCompanyId(){
        $this->getUserDetailModel();
        if(is_null($this->userDetail)){
            $this->userCompanyId = null;
        }else{
            $this->userCompanyId = $this->userDetail->company_id;
        }
        return $this->userCompanyId;
    }
    protected function getUserCompanyName(){
        $this->getUserDetailModel();
        if(is_null($this->userDetail)){
            $this->userCompanyName = null;
        }else{
            $this->userCompanyName = $this->userDetail->company_name;
        }
        return $this->userCompanyName;
    }
    protected function getUserCompanyEmail(){
        $this->getUserDetailModel();
        if(is_null($this->userDetail)){
            $this->userCompanyEmail = null;
        }else{
            $this->userCompanyEmail = $this->userDetail->company_email;
        }
        return $this->userCompanyEmail;
    }
    protected function getUserContactsType(){
        $this->getUserDetailModel();
        if(is_null($this->userDetail)){
            $this->userContactsType = null;
        }else{
            $this->userContactsType = UserContact::$relation_types[$this->userDetail->contacts_type];
        }
        return $this->userContactsType;
    }
    protected function getUserContactsName(){
        $this->getUserDetailModel();
        if(is_null($this->userDetail)){
            $this->userContactsName = null;
        }else{
            $this->userContactsName = $this->userDetail->contacts_name;
        }
        return $this->userContactsName;
    }
    protected function getUserContactsMobile(){
        $this->getUserDetailModel();
        if(is_null($this->userDetail)){
            $this->userContactsMobile = null;
        }else{
            $this->userContactsMobile = $this->userDetail->contacts_mobile;
        }
        return $this->userContactsMobile;
    }

    //公司信息
    protected function getUserQuotaWorkInfoModel(){
        if($this->userQuotaWorkInfo === ''){
            if(! $this->userQuotaWorkInfo = UserQuotaWorkInfo::find()->where(['user_id'=>$this->loan_person_id])->one()){
                if($this->verify){
                    throw new Exception('借款人公司信息不存在');
                }
            }
        }
    }
    protected function getUserCompanyAddress(){
        $this->getUserQuotaWorkInfoModel();
        if(is_null($this->userQuotaWorkInfo)){
            $this->userCompanyAddress = null;
        }else{
            $this->userCompanyAddress = $this->userQuotaWorkInfo->work_address;
        }
        return $this->userCompanyAddress;
    }
    protected function getUserCompanyAddressDistinct(){
        $this->getUserQuotaWorkInfoModel();
        if(is_null($this->userQuotaWorkInfo)){
            $this->userCompanyAddressDistinct = null;
        }else{
            $this->userCompanyAddressDistinct = $this->userQuotaWorkInfo->work_address_distinct;
        }
        return $this->userCompanyAddressDistinct;
    }
}
