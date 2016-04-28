<?php namespace Step05;

/**
 * 05. さよならファサード
 *
 * 高速にコーディングを行う上では強力なファサードですが、
 * マジックメソッドを利用するがゆえにコードを追いにくくIDEのコード補完との相性が悪かったり、
 * どこでも利用できるがゆえに設計が疎かになりがちだったりという弱点も存在します。
 * 特にコードの保守が重要になる場合は、ファサードを使わないことを検討するべきです。
 * ※ IDEヘルパーやファサードのモック機能など、ファサードの悩ましさをカバーするための機能はありますが。
 *
 * 解決方法のひとつにはLaravel5系で増え続けているファサードの代替、ヘルパ関数を利用する方法があります。
 *
 * 今回のコードで扱うもうひとつの解決策は、
 * ファサードを通して移譲先のインスタンスを呼び出す代わりに、
 * 依存性の注入（いわゆるDI / コンストラクタインジェクション、セッタインジェクションなど）パターンを
 * 利用することでファサードで行っていた処理を代替する方法です。
 *
 * 依存性の注入とは、あるオブジェクトが利用する（依存する）オブジェクトを
 * ハードコーディングするのではなく、外部から指定して与えることができるようにすることです。
 *
 * さらにサービスコンテナにインターフェースから実装の解決を登録し、
 * インターフェースを通じて依存オブジェクトを扱うことで、
 * 疎結合かつ依存関係の明瞭なコードを書くことができます。
 */


/**
 * Class GoodBye
 * LoggerInterfaceを利用するクラス
 * コンストラクタでLoggerInterfaceを実装したインスタンスを注入する
 */
class GoodBye
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GoodBye constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $name
     */
    public function to($name)
    {
        $this->logger->log("Good Bye {$name}!");
    }
}


/**
 * Loggerのインターフェース
 */
interface LoggerInterface
{
    public function log($message);
}

/**
 * LoggerInterfaceの実装
 */
class Logger implements LoggerInterface
{
    public function log($message)
    {
        $className = get_called_class();
        echo "instance method {$className}::log() is called." . PHP_EOL;
        echo $message . PHP_EOL;
    }
}


/**
 * サービスプロバイダのインターフェース
 */
interface ServiceProviderInterface
{
    public function register(Container $container);
}

/**
 * LoggerInterfaceに対しインスタンスの生成処理を登録
 */
class LogServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->bind(LoggerInterface::class, function () {
            return new Logger();
        });
    }
}

/**
 * GoodByeクラスの生成処理の登録
 */
class GoodByeServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->bind(GoodBye::class, function (Container $container) {
            $logger = $container->make(LoggerInterface::class);
            return new GoodBye($logger);
        });
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


boot_strap: {
    // サービスプロバイダの設定
    $providerClasses = [
        LogServiceProvider::class,
        GoodByeServiceProvider::class,
    ];

    // サービスプロバイダによりコンテナにインスタンスの生成方法を登録
    $container = new Container();
    foreach ($providerClasses as $providerClass) {
        /**
         * @var ServiceProviderInterface $provider
         */
        $provider = new $providerClass();
        $provider->register($container);
    }
}


main: {
    $goodBye = $container->make(GoodBye::class);
    $goodBye->to('Facade');
}

/* 出力
> php step05.php
instance method Step05\Logger::log() is called.
Good Bye Facade!
*/
