<?php
// 将配置文件载入读取
namespace neco\Tools;

class ConfigBag
{
    private static $configs = [];

    public static function setConfigs($configs){
        self::$configs = array_change_key_case($configs);
    }

    public static function addConfig($k, $v, $replace = true){
        if (!$replace && isset(self::$configs[$k])) {
            return false;
        }
        self::$configs[$k] = $v;
        return true;
    }

    public static function getConfigs(){
        return self::$configs;
    }

    /**
     * 根据键值获取配置
     * @param  string $key 要获取配置的键值 可以用. 分多个配置  e.g. a.b.c.d
     */
    public static function getConfigByKey($key){
        $configs = self::$configs;
        if (isset($configs[strtolower($key)])) {
            return $configs[strtolower($key)];
        }

        $key = explode('.', $key);
        $hit = false;
        $exist = false;
        while (($k = array_shift($key)) && ($exist = isset($configs[$k]) || isset($configs[strtolower($k)]))) {
            $configs = $configs[$k];
            $hit = true;
        }

        if (!$hit || !$exist) {
            return null;
        }
        return $configs;
    }

}
