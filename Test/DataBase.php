<?php
namespace Test;

class DataBase {
    public static function inst() {
        if (self::$instance === null) {
			self::$instance = new self;
			self::$instance->setDb();
        }
        return self::$instance;
    }
	public function getDb() {
		return $this->db;
	}
	public function query($sql) {
		return mysqli_query($this->db, $sql);
	}
    private function __construct() {        
    }
    private function __clone() {
    }
    private function __wakeup() {
    }    
    private static $instance;
    private $db;
	private function setDb() {
		if(!($this->db = @mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME))) {
			throw new \Exception('Can\'t connect to DB!');
		}
	}
}