<?php namespace Simple;

/**
 * インスタンスの生成用の関数だけを登録できる単純なコンテナ
 */
class Container
{
    /**
     * @var callable[]
     */
    private $factories = [];

    /**
     * ファクトリ関数を登録
     * @param string $name 登録名
     * @param callable $func インスタンスのファクトリ関数
     */
    public function bind($name, callable $func)
    {
        $this->factories[$name] = $func;
    }

    /**
     * 登録名を解決してインスタンスを取得
     * @param string $name 登録名
     * @return mixed インスタンス
     */
    public function make($name)
    {
        return call_user_func($this->factories[$name], $this);
    }
}


/**
 * ログファサードの実体のロガー
 */
class Logger
{
    /**
     * ログファイルのパス
     *
     * @var string
     */
    private $filePath;

    /**
     * コンストラクタ
     * @param string $filePath ログファイルのパス
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * ログを書き出す
     * @param string $message ログに書き出す文字列
     */
    public function log($message)
    {
        $line = date('Y/m/d H:i:s') . ": " . $message . PHP_EOL;
        file_put_contents($this->filePath, $line, FILE_APPEND);
    }
}


/**
 * ログファサード
 */
class LogFacade
{
    /**
     * コンテナ
     * @var Container
     */
    private static $container;

    /**
     * 初期化 コンテナを設定
     * @param Container $container
     */
    public static function init(Container $container)
    {
        self::$container = $container;
    }

    /**
     * スタティックメソッドの呼び出しをコンテナから取得したインスタンスに移譲
     * @param string $name メソッド名
     * @param array $arguments 引数
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $instance = self::$container->make('log');
        return call_user_func_array([$instance, $name], $arguments);
    }
}

// ファサードを初期化
boot_strap: {

    // コンテナを生成
    $container = new Container();

    // コンテナにインスタンスの生成を登録する（サービスプロバイダクラス相当）
    $container->bind('log', function() {
        return new Logger(__DIR__ . '/log.txt');
    });

    //ファサードにコンテナを設定して初期化
    LogFacade::init($container);

    // エイリアスの設定
    class_alias(LogFacade::class, 'Log');
}

main: {
    \Log::log('Hello Laravel.Osaka!!');
}

