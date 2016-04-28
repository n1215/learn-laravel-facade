<?php
exit('IDEの解析専用');

class Log {
    public static function log($message) {
        (new Step01\Logger())->log($message);
    }
}