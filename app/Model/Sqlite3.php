<?php

declare(strict_types=1);

namespace App\Model;

class Sqlite3{

    static public $instance ;//保存实例
    public $db;
    function __construct(){
        $this->db = new \SQLite3( env('DB_PATH',  BASE_PATH . '/db/sensor.db'));
        // 设置超时时间为 2 秒钟
        $this->db->busyTimeout(2000);
        // 实例化
    }
    //单例方法， 判断是否已经实例化，只实例化一次
    public static function getInstance (){
        if(!isset( self::$instance )){
            self ::$instance = new self();
        }
        return self::$instance;
    }
    //防止克隆对象
    private function __clone (){
        trigger_error ("not allow to clone.");
    }
    function getDb(){
        return $this->db;
    }
}
