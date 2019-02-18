<?php

namespace yii\notice;

use Yii;

class UniformAlarm extends Notice {

    public $ip;
    public $port;

    private $_connection = null;

    public function init() {
        parent::init();
    }

    public function __destruct() {
        if (!empty($this->_connection)) {
            \socket_close($this->_connection);
        }
    }

    private function getConnection() {
        if (empty($this->_connection)) {
            $this->_connection = \socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (empty($this->_connection)) {
                $code = \socket_last_error();
                $msg = \socket_strerror($code);
                \yii::error("Failed to create socket, error {$code} : {$msg}.");
            }
        }

        return $this->_connection;
    }

    public function send($params) {
        \yii::error( \sprintf('[%s] %s', $params['markerId'], $params['content']), __CLASS__ );
        return;

        $msg = \json_encode([
            'markerId' => $params['markerId'],
            'content' => $params['content']
        ]);
        $len = \strlen($msg);
        $connection = $this->getConnection();
        if (empty($connection)) {
            return false;
        }

        $ret = \socket_sendto($connection, $msg, $len, 0, $this->ip, $this->port);
        if (empty($ret)) {
            $code = \socket_last_error($connection);
            $msg = \socket_strerror($code);
            \yii::error("Failed to send data, error {$code} : {$msg}.");
            return false;
        }

        return true;
    }

}
