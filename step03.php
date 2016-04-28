<?php namespace Step03;

/**
 * 03. ファサードとサービスコンテナ
 * スタティックメソッドの委譲先のインスタンスをサービスコンテナを通じて取得する
 *
 * ファサードが処理を任せるインスタンスは、サービスコンテナを通じて取得されます。
 * サービスコンテナに登録するオブジェクトの精製方法を変更することで、
 * 簡単に実装を入れ替えたり、テスト時にモックに差し替えたり、より柔軟性が増します。
 */

/**
 * 利便性のためのファサード用共通クラス
 * コンテナへの登録名を指定するだけでインスタンスを解決できるようにする
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
     * 移譲先のインスタンスの登録名を取得
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
 * ログファサード
 */
class LogFacade extends Facade
{
    /**
     * logという名称でコンテナに登録されているインスタンスに処理を委譲する
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'log';
    }
}

/**
 * [変更なし] ログファサードの実体
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

    // エイリアスの設定
    $aliases = [
        'Log' => LogFacade::class,
    ];

    // サービスコンテナ設定
    $container = new Container();

    // logという名前でLoggerのインスタンスの生成を登録
    $container->bind('log', function () {
        return new Logger();
    });

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
    \Log::log('Facade meets Service Container!');
}

/* 出力
> php step03.php.php
static method Step03\LogFacade::log() is called.
instance method Step03\Logger::log() is called.
Facade meets Service Container!
*/
