<?php

namespace common\models\info;

use Yii;
use yii\base\Model;

/**
 *


 *
 */

class AlarmMonitor extends Model
{
	public $threshold = 0.05;
	public $totalNumberKey;
	public $errorNumberKey;
	public $startTimeKey;
	public $exceptionsKey;
	public $product;
	public $totalNumber;
	public $errorNumber;
	public $startTime;
	public $exceptions;
	public $redis;
	public $worstErrorRate;

    // public function _construct($product, $config = []){
    // 	$this->redis = Yii::$app->redis;

    // 	$this->totalNumberKey = "info_capture_number_total_".$product;
    // 	$this->errorNumberKey = "info_capture_number_error_".$product;
    // 	if(!$this->redis->exists($this->totalNumberKey)){
    // 		$this->redis->set($this->totalNumberKey, 0);
    // 	}
    // 	if(!$this->redis->exists($this->errorNumberKey)){
    // 		$this->redis->set($this->errorNumberKey, 0);
    // 	}
    // 	parent::__construct($config);
    // }

    public function setProduct($product){
    	$this->product = $product;
    	$this->redis = Yii::$app->redis;
    	$this->totalNumberKey = $this->product."_total_number";
    	$this->errorNumberKey = $this->product."_error_number";
    	$this->startTimeKey = $this->product."_start_time";
    	$this->exceptionsKey = $this->product."_exceptions";
    	if(!$this->redis->exists($this->totalNumberKey)){
    		$this->redis->set($this->totalNumberKey, 0);
    	}
    	if(!$this->redis->exists($this->errorNumberKey)){
    		$this->redis->set($this->errorNumberKey, 0);
    	}
		if(!$this->redis->exists($this->startTimeKey)){
    		$this->redis->set($this->startTimeKey, date("Y-m-d H:i:s"));
    	}
    	if(!$this->redis->exists($this->exceptionsKey)){
    		$this->exceptions = [];
    		$this->redis->set($this->exceptionsKey, json_encode($this->exceptions));
    	}
    }

    public function reset(){
		$this->redis->set($this->totalNumberKey, 0);
		$this->redis->set($this->errorNumberKey, 0);
		$this->redis->set($this->startTimeKey, date("Y-m-d H:i:s"));
		$this->exceptions = json_decode($this->redis->get($this->exceptionsKey), true);
		$keys = array_keys($this->exceptions);
		$l = count($this->exceptions);
		for($i = 0; $i < $l; $i++){
			$this->exceptions[$keys[$i]] = 0;
		}
		$this->redis->set($this->exceptionsKey, json_encode($this->exceptions));
	}

	public function getErrorNumber(){
		return $this->errorNumber = $this->redis->get($this->errorNumberKey);
	}

	public function getTotalNumber(){
		return $this->totalNumber = $this->redis->get($this->totalNumberKey);
	}

	public function getStartTime(){
		return $this->startTime = $this->redis->get($this->startTimeKey);
	}

	public function totalNumberCounter(){
		$this->redis->incr($this->totalNumberKey);

	}

	public function errorNumberCounter($exceptionName){
		$this->redis->incr($this->errorNumberKey);

		$this->exceptions = json_decode($this->redis->get($this->exceptionsKey), true);
		if(array_key_exists($exceptionName, $this->exceptions)){
			$this->exceptions[$exceptionName] = $this->exceptions[$exceptionName] + 1;
		} else{
			$this->exceptions[$exceptionName] = 1;
		}
		$this->redis->set($this->exceptionsKey, json_encode($this->exceptions));
	}

	public function getErrorRate(){
		if($this->getTotalNumber() == 0){
			return $rate = "0%";
		} else{
			$rate = $this->getErrorNumber()/$this->getTotalNumber()*100;
			$rate = round($rate, 2)."%";
			return $rate;
		}
	}

	public function overThreshold(){
		return ($this->getErrorNumber()/$this->getTotalNumber()>=$this->threshold)?true:false;

	}

	public function getExceptions(){
		return $this->exceptions = json_decode($this->redis->get($this->exceptionsKey));
	}

	public function getWorstError(){
		if($this->getErrorNumber() == 0){
			return "No errors.";
		} else{
			$this->exceptions = json_decode($this->redis->get($this->exceptionsKey), true);
			$keys = array_keys($this->exceptions);
			$l = count($this->exceptions);
			$name = $keys[0];
			$n = $this->exceptions[$name];
			for($i = 1; $i < $l; $i++){
				$k = $keys[$i];
				if($this->exceptions[$k] > $n){
					$name = $k;
					$n = $this->exceptions[$k];
				}
			}
			$this->worstErrorRate = round(($n/$this->getTotalNumber())*100, 2)."%";
			return $name;
		}
	}

	public function send($markerId){
		$totalErrorRate = $this->getErrorRate();
		$worstError = $this->getWorstError();
		Yii::$app->uniform_alarm->send([
            "markerId" => $markerId,
            "content" => "product: $this->product 告警超过阈值。 总异常率: $totalErrorRate 。 最严重异常: $worstError 。 最严重异常率: $this->worstErrorRate .",
        ]);
	}
}

?>
