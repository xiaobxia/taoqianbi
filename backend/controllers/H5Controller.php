<?php
namespace backend\controllers;

use Yii;
use common\helpers\Url;
use backend\models\AuthorizeForm;
use common\models\LoanPerson;
use common\services\ZmopService;

class H5Controller extends BaseController
{
	//姓名+身份证
	public function actionTest()
	{
		$model = new AuthorizeForm();
		return $this->render('test',['model'=>$model]);
	}


	public function actionDoSubmit()
	{

		$user_id = 123;
		$loanPerson = LoanPerson::findOne($user_id);
		$postParams = Yii::$app->request->post();
		if(isset($postParams['AuthorizeForm']['name']) && isset($postParams['AuthorizeForm']['idnum']) && !empty($postParams['AuthorizeForm']['name']) && !empty($postParams['AuthorizeForm']['idnum']))
		{
			//如果合理
			if($this->is_idcard($postParams['AuthorizeForm']['idnum']))
			{
				$zmopService = Yii::$container->get('zmopService');
				$res = $zmopService->zmAuthorize($postParams['AuthorizeForm']['idnum'],$postParams['AuthorizeForm']['name'],$loanPerson->id,$source='1');
				$this->redirect($res);
			}
			else
			{
				$this->redirect(array('h5/error','message'=>'身份证格式不正确'));
			}
		}
		else
		{
			$this->redirect(array('h5/error','message'=>'缺少必要的参数'));
		}
	}

	//手机号
	public function actionMobile()
	{
		$model = new AuthorizeForm();
		return $this->render('txt',['model'=>$model]);
	}


	public function actionCheckMobile()
	{
		$user_id = 123;
		$loanPerson = LoanPerson::findOne($user_id);
		$postParams = Yii::$app->request->post();
		if(isset($postParams['AuthorizeForm']['phone'])  && !empty($postParams['AuthorizeForm']['phone']))
		{
			//如果合理
			if($this->is_phonenum($postParams['AuthorizeForm']['phone']))
			{
				$zmopService = Yii::$container->get('zmopService');
				$res = $zmopService->h5AuthorizeUrl($postParams['AuthorizeForm']['phone'],strval($loanPerson->id));
				$this->redirect($res);
			}
			else
			{
				$this->redirect(array('h5/error','message'=>'手机号码格式不正确'));
			}
		}
		else
		{
			$this->redirect(array('h5/error','message'=>'缺少必要的参数'));
		}
	}

	public function actionError()
	{
		return $this->render('error',['message'=>Yii::$app->request->get('message')]);
	}

	public function is_idcard($id)
	{
	  $id = strtoupper($id);
	  $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
	  $arr_split = array();
	  if(!preg_match($regx, $id))
	  {
	    return FALSE;
	  }
	  if(15==strlen($id)) //检查15位
	  {
	    $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

	    @preg_match($regx, $id, $arr_split);
	    //检查生日日期是否正确
	    $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
	    if(!strtotime($dtm_birth))
	    {
	      return FALSE;
	    } else {
	      return TRUE;
	    }
	  }
	  else      //检查18位
	  {
	    $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
	    @preg_match($regx, $id, $arr_split);
	    $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
	    if(!strtotime($dtm_birth)) //检查生日日期是否正确
	    {
	      return FALSE;
	    }
	    else
	    {
	      //检验18位身份证的校验码是否正确。
	      //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
	      $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
	      $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
	      $sign = 0;
	      for ( $i = 0; $i < 17; $i++ )
	      {
	        $b = (int) $id{$i};
	        $w = $arr_int[$i];
	        $sign += $b * $w;
	      }
	      $n = $sign % 11;
	      $val_num = $arr_ch[$n];
	      if ($val_num != substr($id,17, 1))
	      {
	        return FALSE;
	      } //phpfensi.com
	      else
	      {
	        return TRUE;
	      }
	    }
	  }
	}


	public function is_phonenum($num)
	{
		if(preg_match("/^1[34578]{1}\d{9}$/",$num))
		{
		    return TRUE;
		}
		else
		{
		   return FALSE;
		}
	}
}