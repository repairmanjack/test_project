<?php 
namespace Test\Models;

use Test\DataBase as db;

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
		$sum = 0;
		foreach($this->getTransactions() as $transaction) {
			$sum += $transaction->getSum();
		}
		return round($sum,2);
	}
	public function getTransactions() {
		return Transaction::getAllByUserId($this->getId());
	}
	public static function login(Array $authData) {
		$ret = new User;
		if(isset($authData['login']) && isset($authData['passw'])) {
			$login = mysqli_real_escape_string(db::inst()->getDb(), $authData['login']);
			$passw = md5($authData['passw']);
			if(mysqli_num_rows($resource = db::inst()->query("SELECT * FROM `users` WHERE `login`='{$login}' AND `passw`='{$passw}'"))) {
				$userRow = mysqli_fetch_assoc($resource);
				$ret->setId($userRow['id']);
				$ret->setLogin($userRow['login']);
				$ret->setLogged(true);
			}
		}
		return $ret;
	}
	public static function getUserById($id) {
		$ret = new User;
		$id = intval($id);
		if(mysqli_num_rows($resource = db::inst()->query("SELECT * FROM `users` WHERE `id`='{$id}'"))) {
			$userRow = mysqli_fetch_assoc($resource);
			$ret->setId($userRow['id']);
			$ret->setLogin($userRow['login']);
			$ret->setLogged(true);
		}
		return $ret;
	}
	private function setId($id) {
		$this->id=$id;
	}
	private function setLogin($login) {
		$this->login=$login;
	}
	private function setLogged($logged=false) {
		$this->logged=$logged;
	}
	private $id;
	private $login;
	private $passw;
	private $logged;
}