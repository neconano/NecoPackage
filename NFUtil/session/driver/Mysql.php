<?php

namespace nfutil\session\driver;

use SessionHandler;
use think\Exception;

/**
 * 数据库方式Session驱动
 * CREATE TABLE `session` (
 *   `session_id` varchar(255) NOT NULL,
 *   `session_data` blob,
 *   `uid` int(11) unsigned NOT NULL COMMENT '用户ID',
 *   `update_time` int(11) unsigned NOT NULL COMMENT '更新时间',
 *   UNIQUE KEY `session_id` (`session_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='session存储表'; */

class Mysql extends SessionHandler
{
    protected $handler = null;
    protected $config  = [
        'expire'       => 3600, // session有效期
        'prefix'     => '', // Session前缀
        'table_name' => '', // 表名（不包含前缀）
        'db_config'  => '', //应用配置文件中配置的额外的数据库连接信息
    ];

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 打开Session
     * @access public
     * @param string $save_path
     * @param mixed $session_name
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        $this->handler = \think\Db::connect($this->config['db_config'])->name($this->config['table_name']);
        return true;
    }

    /**
     * 关闭Session
     * @access public
     */
    public function close()
    {
        $this->gc(ini_get('session.gc_maxlifetime'));
        $this->handler = null;
        return true;
    }

    /**
     * 读取Session
     * @access public
     * @param string $session_id
     * @return bool|string
     */
    public function read($session_id)
    {
        $map['session_id'] = ['eq', $this->config['prefix'] . $session_id];
        if ($this->config['expire'] != 0) {
            $map['update_time'] = ['gt', time() - $this->config['expire']];
        }
        return (string) $this->handler->where($map)->value('session_data');
    }

    /**
     * 写入Session
     * @access public
     * @param string $session_id
     * @param String $session_data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        // 反php序列化模式处理
        if($this->config['prefix']){
            $input_data = unserialize( str_replace($this->config['prefix'].'|','',$session_data) );
            $uid = $input_data['user_auth']['uid'];
        }
        // 更新
        $result = $this->handler->where('session_id', $this->config['prefix'] . $session_id)->find();
        $data   = ['session_id' => $this->config['prefix'] . $session_id, 'update_time' => time(), 'session_data' => $session_data, 'uid' => $uid ];
        try{
            if ($result) {
                $affect_rows = $this->handler->update($data);
            } else {
                $affect_rows = $this->handler->insert($data);
            }
        }catch (Exception $e) {   
            print $e->getMessage();   
            exit();   
        }   
        return $affect_rows ? true : false;
    }

    /**
     * 删除Session
     * @access public
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        $result = $this->handler->where('session_id', $this->config['prefix'] . $session_id)->delete();
        return $result ? true : false;
    }

    /**
     * Session 垃圾回收
     * @access public
     * @param string $sessMaxLifeTime
     * @return bool
     */
    public function gc($sessMaxLifeTime)
    {
        if ($this->config['expire'] != 0) {
            $map['update_time'] = ['lt', time() - $this->config['expire']];
        } else {
            $map['update_time'] = ['lt', time() - $sessMaxLifeTime];
        }

        $result = $this->handler->where($map)->delete();
        return $result ? true : false;
    }

}
