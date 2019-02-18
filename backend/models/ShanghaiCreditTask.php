<?php

namespace backend\models;

use Yii;
use yii\base\Model;

/**
 *
 */
class ShanghaiCreditTask extends Model{
	const ALL = '全部';
	const STATUS_INITIAL = '待处理';
    const STATUS_PROCESS = '处理中';
    const STATUS_FINISH = '已完成';
    const KIND_USER_INFO = '用户个人信息';
    const KIND_LOAN_BUSINESS = '用户贷款业务';

    protected $taskNumberKey = "ShanghaiCreditTaskNumber";
	protected $processingTasksKey = "ShanghaiCreditProcessingTasks";
	protected $finishedTasksKey = "ShanghaiCreditFinishedTasks";
	protected $redis;

	public function taskNumberCounter(){
		$this->redis = Yii::$app->redis;
		if(!$this->redis->exists($this->taskNumberKey)){
			$this->redis->set($this->taskNumberKey, 0);
		}
		$this->redis->incr($this->taskNumberKey);
		return $this->redis->get($this->taskNumberKey);
	}

	public function setID(){
		$id = $this->taskNumberCounter();
		return $id = str_pad($id, 8, '0', STR_PAD_LEFT);
	}

	public function hasTask(){
		$this->redis = Yii::$app->redis;
		$processingTasks = $this->getProcessingTasks();
		return empty($processingTasks)?false:true;
	}

	public function pickOneTaskToProcess(){
		$this->redis = Yii::$app->redis;
		$data = $this->redis->lpop($this->processingTasksKey);
		$task = json_decode($data, true);
		$task['status'] = self::STATUS_PROCESS;
		$data = json_encode($task);
		$this->redis->lpush($this->processingTasksKey, $data);
		return $task;
	}

	public function addProcessingTask($taskName, $fileDate, $fileName, $fileKind){
		$this->redis = Yii::$app->redis;
		$creator = Yii::$app->user->identity->username;
		$creatTime = date("Y-m-d H:i:s");
		if($fileKind == "user-info"){
			$kind = self::KIND_USER_INFO;
		} else if($fileKind == "loan-business"){
			$kind = self::KIND_LOAN_BUSINESS;
		}
		$task = ["id" => $this->setID(), 'task_name' => $taskName, "date" => $fileDate, "file_name" => $fileName, "kind" => $kind, "creator" => $creator, "create_time" => $creatTime, "status" => self::STATUS_INITIAL];
		$task = json_encode($task);
		return $this->redis->rpush($this->processingTasksKey, $task);
	}

	public function addFinishedTask($task){
		$this->redis = Yii::$app->redis;
		$referring = $this->redis->lindex($this->processingTasksKey, 0);
		$referring = json_decode($referring, true);
		if($task == $referring){
			$task['status'] = self::STATUS_FINISH;
			$this->removeOneProcessingTask();
			$task = json_encode($task);
			return $this->redis->rpush($this->finishedTasksKey, $task);
		}
		return false;
	}

	public function removeOneProcessingTask(){
		$this->redis = Yii::$app->redis;
		return $this->redis->lpop($this->processingTasksKey);
	}

	public function removeFinishedTask($id){
		$task = [];
		$this->redis = Yii::$app->redis;
		$finishedTasks = $this->getFinishedTasks();

		if(!empty($finishedTasks)){
			$length = count($finishedTasks);
			while(empty($task) && $length > 0){
				$tmp = $this->redis->lpop($this->finishedTasksKey);
				$data = json_decode($tmp, true);
				if($data['id'] != $id){
					$this->redis->rpush($this->finishedTasksKey, $tmp);
				} else if($data['id'] == $id){
					$task = $data;
				}
				$length--;
			}
		}
		$times = 0;
		if(!empty($task)){
			foreach($finishedTasks as $value){
				if($value['id'] != $task['id'] && $value['date'] == $task['date'] && $value['kind'] == $task['kind']){
					$times++;
				}
			}
		}
		if($times == 0){
			unlink($task['file_name']);
		}
		return $task;

	}

	public function removeProcessingTask($id){
		$task = [];
		$this->redis = Yii::$app->redis;
		$processingTasks = $this->getProcessingTasks();
		if(!empty($processingTasks)){
			$length = count($processingTasks);
			while(empty($task) && $length > 0){
				$tmp = $this->redis->lpop($this->processingTasksKey);
				$data = json_decode($tmp, true);
				if($data['id'] != $id){
					$this->redis->rpush($this->processingTasksKey, $tmp);
				} else if($data['id'] == $id){
					$task = $data;
				}
				$length--;
			}
		}
		if(empty($task)){
			$task = $this->removeFinishedTask($id);
		}
		return $task;

	}

	public function getProcessingTasks(){
		$this->redis = Yii::$app->redis;
		$data = $this->redis->lrange($this->processingTasksKey, 0, -1);
		$processingTasks = [];
		foreach($data as $task){
			$processingTasks[] = json_decode($task, true);
		}
		return $processingTasks;
	}

	public function getFinishedTasks(){
		$this->redis = Yii::$app->redis;
		$data = $this->redis->lrange($this->finishedTasksKey, 0, -1);
		$finishedTasks = [];
		foreach($data as $task){
			$finishedTasks[] = json_decode($task, true);
		}
		return $finishedTasks;
	}

	public function removeAllProcessingTasks(){
		$processingTasks = $this->getProcessingTasks();
		if(!empty($processingTasks)){
			$length = $this->redis->llen($this->processingTasksKey);
			while($length > 0){
				$this->redis->lpop($this->processingTasksKey);
				$length--;
			}
		}
	}

	public function removeAllFinishedTasks(){
		$finishedTasks = $this->getFinishedTasks();
		if(!empty($finishedTasks)){
			$length = $this->redis->llen($this->finishedTasksKey);
			while($length > 0){
				$this->redis->lpop($this->finishedTasksKey);
				$length--;
			}
		}
	}

	public static $status = [
        self::ALL =>'全部',
        self::STATUS_INITIAL=>'待处理',
        self::STATUS_PROCESS=>'处理中',
        self::STATUS_FINISH=>'已完成',
    ];

    public static $kind = [
        self::ALL =>'全部',
        self::KIND_USER_INFO=>'用户个人信息',
        self::KIND_LOAN_BUSINESS=>'用户贷款业务',
    ];
}

?>
