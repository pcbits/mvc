<?php
namespace MVC;

use MVC\MVC;
use \PDO;

class DB
{
    const DB_PREFIX = 'mvc_db_pdo_';
    const TYPE_PREFIX = 'mvc_db_type_';
    protected $mvc;
    protected $cons = [];

    public function __construct(MVC $mvc)
    {
        $this->mvc = $mvc;
        $this->settings($mvc->get('dbs'));
    }

    public function settings($dbs = [])
    {
        foreach($dbs as $dbName => $dbConf) {
            $this->add($dbName, $dbConf['dsn'], $dbConf['user'], $dbConf['pass']);
        }
    }

    public function add($dbName, $dsn, $user = null, $pass = null)
    {
        $this->cons[$dbName] = [
            'dsn'=>$dsn,
            'user'=>$user,
            'pass'=> $pass
        ];
        if (!isset($GLOBALS[self::DB_PREFIX.$dbName])) {
            $GLOBALS[self::DB_PREFIX.$dbName] = new PDO(
                $this->cons[$dbName]['dsn'],
                $this->cons[$dbName]['user'],
                $this->cons[$dbName]['pass']
            );
            $GLOBALS[self::DB_PREFIX.$dbName]
                ->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $GLOBALS[self::TYPE_PREFIX.$dbName] =
                $GLOBALS[self::DB_PREFIX.$dbName]
                    ->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }
    }

    public static function pdo($dbName = 'db')
    {
        return $GLOBALS[self::DB_PREFIX.$dbName];
    }

    public static function type($dbName = 'db')
    {
        return $GLOBALS[self::TYPE_PREFIX.$dbName];
    }
}
