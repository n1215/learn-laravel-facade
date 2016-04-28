<?php namespace Step04;

/**
 * 04. サービスプロバイダ
 * サービスコンテナにサービスを登録するモジュール
 *
 * コンテナに登録するサービスが増えると、その登録時の管理が問題になってきます。
 * サービスプロバイダという単位を導入することで、関連のある機能の単位でサービスを登録するようにします。
 *
 * 今回作成するサービスプロバイダはサービスコンテナにサービスを登録するregister()メソッドだけを持つものです。
 * Laravelのサービスプロバイダにはbootメソッドが存在し、サービスの依存関係をコンテナに登録し終えたあとに、
 * アプリケーション全般に関わる設定を行うことができます。
 */

/**
 * Logファサード用にインスタンスの生成処理を登録
 */
class LogServiceProvider
{
    public function register(Container $container)
    {
        $container->bind('log', function () {
            return new Logger();
        });
    }
}

/**
 * [変更なし] 利便のためのファサード用共通クラス
 * コンテナへの登録名だけでインスタンスを取得し移譲できるようにする
 */
abstract class Facade
{
    /**
     * コンテナ
     * @var Container
     */
    private static $container = null;

    /**
     * 初期化 コンテナを設定
     *
     * @param Container $container
     */
    public static function init(Container $container)
    {
        self::$container = $container;
    }

    /**
     * 移譲するインスタンスのコンテナでの登録名を取得
     * @return string コンテナに登録されている名前
     */
    protected static function getFacadeAccessor()
    {
        throw new \RuntimeException('実装してね');
    }

    /**
     * スタティックメソッドの呼び出しをコンテナから取得したインスタンスに移譲
     * @param string $name メソッド名
     * @param array $arguments 引数
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $className = static::class;
        echo "static method {$className}::{$name}() is called." . PHP_EOL;
        $accessor = static::getFacadeAccessor();
        $instance = self::$container->make($accessor);
        return call_user_func_array([$instance, $name], $arguments);
    }
}

/**
 * [変更なし]ログファサード
 */
class LogFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'log';
    }
}

/**
 * [変更なし] ログファサードの実体のロガー
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


/**
 * [変更なし]インスタンスの生成用の関数だけを登録できる単純なコンテナ
 */
class Container
{
    /**
     * @var array|callable[]
     */
    private $factories = [];

    /**
     * ファクトリ関数を登録
     *
     * @param string $name 登録名
     * @param callable $func インスタンスのファクトリ関数
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

// ファサードを初期化
boot_strap: {

    // サービスプロバイダの設定
    $providerClasses = [
        LogServiceProvider::class,
    ];

    // エイリアスの設定
    $aliases = [
        'Log' => LogFacade::class,
    ];

    // サービスプロバイダによりコンテナにインスタンスの生成方法を登録
    $container = new Container();
    foreach ($providerClasses as $providerClass) {
        /**
         * @var LogServiceProvider $provider;
         */
        $provider = new $providerClass();
        $provider->register($container);
    }

    //ファサードにコンテナを設定して初期化
    Facade::init($container);

    //ファサードのエイリアス名解決のためのオートロード関数を登録
    spl_autoload_register(function ($alias) use ($aliases) {
        if (isset($aliases[$alias])) {
            return class_alias($aliases[$alias], $alias);
        }
        return false;
    }, true, true);
}

main: {
    \Log::log('Hello Service Provider!');
}

/* 出力
> php step04.php
static method Step04\LogFacade::log() is called.
instance method Step04\Logger::log() is called.
Hello Service Provider!
*/
