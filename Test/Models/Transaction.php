<?php 
namespace Test\Models;

use Test\DataBase as db;

class Transaction {
	public function getId() {
		return $this->id;
	}
	public function getUserId() {
		return $this->user_id;
	}
	public function getSum() {
		return sprintf("%.2f", $this->summ);
	}
	public function getDateTime() {
		return date("d.m.Y H:i:s", strtotime($this->datetime));
	}
	public function getDateTimeDb() {
		return date("Y-m-d H:i:s", strtotime($this->datetime));
	}
	public function setSum($sum) {
		$this->summ = floatval($sum);
	}
	public function setUserId($user_id) {
		$this->user_id = intval($user_id);
	}
	public function setDateTime($dt) {
		$this->datetime = date("Y-m-d H:i:s", strtotime($dt));
	}
	public function fromArray(Array $data = []) {
		if(isset($data['id'])) {
			$this->setId($data['id']);
		}
		if(isset($data['summ'])) {
			$this->setSum($data['summ']);
		}
		if(isset($data['datetime'])) {
			$this->setDateTime($data['datetime']);
		}
		if(isset($data['user_id'])) {
			$this->setUserId($data['user_id']);
		}
		return $this;
	}
	public static function getAllByUserId($user_id) {
		$user_id = intval($user_id);
		$ret = [];
		if($user_id) {
			$resource = db::inst()->query("SELECT * FROM `transactions` WHERE `user_id`='{$user_id}'");
			while($transactionRow = mysqli_fetch_assoc($resource)){
				$ret[] = (new Transaction())->fromArray($transactionRow);
			}
		}
		return $ret;
	}
	public function save() {
		$sum = $this->getSum();
		$link = db::inst()->getDb();
		$userId = $this->getUserId();
		$targetUserId = 2;
		$time = $this->getDateTimeDb();

		if(!floatval($sum)) {
            throw new \Exception("Сумма транзакции не может быть равна 0.");
		}
		
		mysqli_begin_transaction($link);

		if(!($ret = db::inst()->query( 
			"UPDATE `users` SET `summ`=IF((@res:=`summ`-'{$sum}')<0,`summ`,@res) WHERE `id`='{$userId}'"
		) && mysqli_affected_rows($link))) {
			mysqli_rollback($link);
            throw new \Exception("На вашем счёте не достаточно средств для списания суммы {$sum} руб.");
		}

		$ret = $ret && db::inst()->query( 
			"UPDATE `users` SET `summ`=`summ`+'{$sum}' WHERE `id`='{$targetUserId}'"
		) && db::inst()->query( 
			"INSERT INTO `transactions` (`user_id`,`summ`,`datetime`) values ('{$userId}', '-{$sum}', '{$time}');"
		) && db::inst()->query( 
			"INSERT INTO `transactions` (`user_id`,`summ`,`datetime`) values ('{$targetUserId}', '{$sum}', '{$time}');"
		);

		if(!$ret) {
			$error = mysqli_error($link);
            mysqli_rollback($link);
            throw new \Exception("Ошибка выполнения транзакции: {$error}");
        }

		mysqli_commit($link);
		return $ret;
	}
	private function setId($id) {
		$this->id = intval($id);
	}
	private $id;
	private $summ;
	private $datetime;
	private $user_id;
}