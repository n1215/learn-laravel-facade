<?php namespace Step01;

/**
 * 01. ファサード
 * スタティックメソッドをインスタンスに委譲する
 *
 * 語弊を恐れずに言えば、Laravelのファサードは
 * スタティックメソッドの手軽さとインスタンスメソッドの柔軟さのイイトコどりを目指したものです。
 *
 * ファサードのクラスに対してスタティックメソッドを呼び出すと、
 * ファサードに対応する実体のクラスのオブジェクトが代理でインスタンスメソッドの処理を行うようになっています。
 * グローバル空間にエイリアス（クラスの短縮名）が登録され、手軽に使えるのも初心者に嬉しいポイントです。
 */

/**
 * 超簡易版ファサード
 */
class LogFacade
{
    /**
     * スタティックメソッドの呼び出しをLoggerのインスタンスに移譲
     * 実際のLaravelのコードではサービスコンテナからのインスタンス取得処理が挟まる
     *
     * @param string $name メソッド名
     * @param array $arguments メソッドの引数の配列
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $className = get_called_class();
        echo "static method {$className}::{$name}() is called." . PHP_EOL;
        $instance = new Logger();
        return call_user_func_array([$instance, $name], $arguments);
    }
}

/**
 * ファサードの実体となるクラス
 * PSR-3？ 知らない規格ですね
 */
class Logger
{
    /**
     * @param string $message
     */
    public function log($message)
    {
        $className = get_called_class();
        echo "instance method {$className}::log() is called." . PHP_EOL;
        echo $message . PHP_EOL;
    }
}

// ファサードのエイリアス設定
boot_strap: {
    $aliases = [
        'Log' => 'Step01\LogFacade',
    ];

    foreach ($aliases as $alias => $class) {
        class_alias($class, $alias);
    }
}

main: {
    \Log::log('Hello Facade!');
}

/* 出力
> php step01.php
static method Step01\LogFacade::log() is called.
instance method Step01\Logger::log() is called.
Hello Facade!
*/
