<?php
/**
 * ZHIMA API: zhima.credit.fraudulent.singlecharge request
 *
 * @author auto create
 * @since 1.0, 2016-01-15 14:11:58
 */
class ZhimaCreditFraudulentSinglechargeRequest
{
	/** 
	 * 业务发生时间,格式yyyyMMddHHmmss
	 **/
	private $bizTime;
	
	/** 
	 * 反欺诈老平台升级截至点
	 **/
	private $deadline;
	
	/** 
	 * 云产品Id
	 **/
	private $productCode;
	
	/** 
	 * 产品结果码，多值时用|分割
	 **/
	private $resultCode;
	
	/** 
	 * 交易单据号,标识一次请求11
	 **/
	private $transactionId;

	private $apiParas = array();
	private $fileParas = array();
	private $apiVersion="1.0";
	private $scene;
	private $channel;
	private $platform;
	private $extParams;

	
	public function setBizTime($bizTime)
	{
		$this->bizTime = $bizTime;
		$this->apiParas["biz_time"] = $bizTime;
	}

	public function getBizTime()
	{
		return $this->bizTime;
	}

	public function setDeadline($deadline)
	{
		$this->deadline = $deadline;
		$this->apiParas["deadline"] = $deadline;
	}

	public function getDeadline()
	{
		return $this->deadline;
	}

	public function setProductCode($productCode)
	{
		$this->productCode = $productCode;
		$this->apiParas["product_code"] = $productCode;
	}

	public function getProductCode()
	{
		return $this->productCode;
	}

	public function setResultCode($resultCode)
	{
		$this->resultCode = $resultCode;
		$this->apiParas["result_code"] = $resultCode;
	}

	public function getResultCode()
	{
		return $this->resultCode;
	}

	public function setTransactionId($transactionId)
	{
		$this->transactionId = $transactionId;
		$this->apiParas["transaction_id"] = $transactionId;
	}

	public function getTransactionId()
	{
		return $this->transactionId;
	}

	public function getApiMethodName()
	{
		return "zhima.credit.fraudulent.singlecharge";
	}

	public function setScene($scene)
	{
		$this->scene=$scene;
	}

	public function getScene()
	{
		return $this->scene;
	}
	
	public function setChannel($channel)
	{
		$this->channel=$channel;
	}

	public function getChannel()
	{
		return $this->channel;
	}
	
	public function setPlatform($platform)
	{
		$this->platform=$platform;
	}

	public function getPlatform()
	{
		return $this->platform;
	}

	public function setExtParams($extParams)
	{
		$this->extParams=$extParams;
	}

	public function getExtParams()
	{
		return $this->extParams;
	}	

	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function getFileParas()
	{
		return $this->fileParas;
	}

	public function setApiVersion($apiVersion)
	{
		$this->apiVersion=$apiVersion;
	}

	public function getApiVersion()
	{
		return $this->apiVersion;
	}

}
