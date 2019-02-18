<?php
namespace console\soa;

class UserLoanOrder extends \common\models\UserLoanOrder
{

	public function isWhitelist()
	{
		return false;
	}

	public function isBlacklist()
	{
		return true;
	}

}
