<?php namespace Step02;

/**
 * 02. サービスコンテナ
 * オブジェクトの生成を管理する
 *
 * サービスコンテナはオブジェクト（インスタンス）の組み立て方を記憶し、取り出せるようにすることで、
 * アプリケーション全体に渡ってインスタンスの生成を一元管理するためのオブジェクトです。
 * サービスの利用者は、その生成方法を気にすることなく利用することができます。
 *
 * ここで言う"サービス"とは何らかの機能を提供するオブジェクト、またはその機能を指します。
 *
 * Laravelのコンテナはインスタンスのライフサイクルの管理（ex. シングルトン）など多くの機能を持ちますが、
 * 今回作成するコンテナはインスタンスの生成用の関数を名前付きで登録するだけの単純なものです。
 */

/**
 * インスタンス生成用の関数だけを登録できる単純なコンテナ
 */
class Container
{
    /**
     * @var array|callable[]
     */
    private $factories = [];

    /**
     * インスタンスの生成関数を名前つきで登録
     *
     * @param string $name 登録名
     * @param callable $func インスタンスの生成関数
     */
    public function bind($name, callable $func)
    {
        $this->factories[$name] = $func;
    }

    /**
     * 登録名を解決してインスタンスを取得
     *
     * @param string $name 登録名
     * @return mixed
     */
    public function make($name)
    {
        if (isset($this->factories[$name])) {
            return call_user_func($this->factories[$name], $this);
        }

        throw new \RuntimeException('クラス名を解決できません');
    }
}

/**
 * [変更なし] Logger
 */
class Logger
{
    public function log($message)
    {
        $className = get_called_class();
        echo "instance method {$className}::log() is called." . PHP_EOL;
        echo $message . PHP_EOL;
    }
}

// コンテナにインスタンスの生成方法を登録
boot_strap: {
    $container = new Container();
    $container->bind('log', function () {
        return new Logger();
    });
}

main: {
    $container->make('log')->log('Hello Service Container!');
}

/* 出力
> php step02.php
instance method Step02\Logger::log() is called.
Hello Service Container!
Hello Service Container!
*/
