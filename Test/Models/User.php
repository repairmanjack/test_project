<?php 
namespace Test\Models;

use Test\DataBase as db;
use Test\Models\Transaction;

class User {
	public function __construct() {
		$this->setLogged(false);
	}
	public static function auth(\Zend\Diactoros\ServerRequest $request) {
		session_start();
		if(isset($_SESSION['userid'])) {
			$user = self::getUserById($_SESSION['userid']);
		} else {
			$user = self::login($request->getParsedBody());
			$_SESSION['userid'] = $user->getId();
		}
		session_write_close();
		return $user;
	}
	public function getId() {
		return $this->id;
	}
	public function getLogin() {
		return $this->login;
	}
	public function isLogged() {
		return $this->logged;
	}
	public function getTransactionSum() {
		return $this->summ;
	}
	public function getTransactions() {
		return Transaction::getAllByUserId($this->getId());
	}
	public function addTransaction($sum) {
		(new Transaction())->fromArray([
			'summ' => $sum,
			'datetime' => date('Y-m-d H:i:s'),
			'user_id' => $this->getId()
		])->save();
		return $this->updateModel()->getTransactionSum();
	}
	public static function login(Array $authData) {
		$ret = new User;
		if(isset($authData['login']) && isset($authData['passw'])) {
			$login = mysqli_real_escape_string(db::inst()->getDb(), $authData['login']);
			$passw = md5($authData['passw']);
			if(mysqli_num_rows($resource = db::inst()->query("SELECT * FROM `users` WHERE `login`='{$login}' AND `passw`='{$passw}'"))) {
				$ret->fromArray(mysqli_fetch_assoc($resource))->setLogged(true);
			}
		}
		return $ret;
	}
	public static function getUserById($id) {
		return (new User)->setId($id)->updateModel();
	}
	private function updateModel() {
		$id = $this->getId();
		if(mysqli_num_rows($resource = db::inst()->query("SELECT * FROM `users` WHERE `id`='{$id}'"))) {
			$this->fromArray(mysqli_fetch_assoc($resource))->setLogged(true);
		}
		return $this;
	}
	private function setId($id) {
		$this->id=$id;
		return $this;
	}
	private function setLogin($login) {
		$this->login=$login;
	}
	private function setLogged($logged=false) {
		$this->logged=$logged;
		return $this;
	}
	private function setSum($sum) {
		$this->summ = $sum;
	}
	private function fromArray(Array $userRow) {
		if(isset($userRow['id'])) {
			$this->setId($userRow['id']);
		}
		if(isset($userRow['login'])) {
			$this->setLogin($userRow['login']);
		}
		if(isset($userRow['summ'])) {
			$this->setSum($userRow['summ']);
		}
		return $this;
	}
	private $id;
	private $login;
	private $passw;
	private $logged;
	private $summ;
}