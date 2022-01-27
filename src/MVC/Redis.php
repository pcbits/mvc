<?php
namespace MVC;

class Redis
{
    const REDIS_KEY = 'mvc_redis_';

    public static function connect()
    {
        $GLOBALS[self::REDIS_KEY] = new \Redis();
        $GLOBALS[self::REDIS_KEY]->pconnect(
            (getenv('REDIS_HOST') ?: '127.0.0.1'),
            (getenv('REDIS_PORT') ?: 6379));
    }

    public static function obj()
    {
        return $GLOBALS[self::REDIS_KEY];
    }
}
