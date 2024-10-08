<?php

    namespace Coco\logger;

    use Monolog\Handler\HandlerInterface;
    use Monolog\Handler\StreamHandler;
    use Psr\Log\LoggerInterface;
    use Monolog\Handler\RedisHandler;

trait Logger
{
    protected ?LoggerInterface $logger = null;

    public function setLogger(?LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setStandardLogger(string $name, array $handlers = [], array $processors = [], ?\DateTimeZone $timezone = null): static
    {
        $this->setLogger(new \Monolog\Logger($name, $handlers, $processors, $timezone));

        return $this;
    }

    public function addRedisHandler(string $redisHost = '127.0.0.1', int $redisPort = 6379, string $password = '', int $db = 10, string $logName = 'redis_log', ?callable $callback = null): static
    {
        $redis = new \Redis();
        $redis->connect($redisHost, $redisPort);

        if ($password) {
            $redis->auth($password);
        }

        $redis->select($db);
        $handler = new RedisHandler($redis, $logName, \Monolog\Logger::DEBUG);

        is_callable($callback) && call_user_func_array($callback, [
            $handler,
            $this,
        ]);

        $this->pushLoggerHandler($handler);

        return $this;
    }

    public function addFileHandler(string $path, ?callable $callback = null): static
    {
        $handler = new StreamHandler($path, \Monolog\Logger::DEBUG);

        is_callable($callback) && call_user_func_array($callback, [
            $handler,
            $this,
        ]);

        $this->pushLoggerHandler($handler);

        return $this;
    }

    public function addStdoutHandler(?callable $callback = null): static
    {
        $handler = new StreamHandler('php://stdout', \Monolog\Logger::DEBUG);

        is_callable($callback) && call_user_func_array($callback, [
            $handler,
            $this,
        ]);

        $this->pushLoggerHandler($handler);

        return $this;
    }

    public function pushLoggerHandler(HandlerInterface $handler): static
    {
        $this->logger && $this->logger->pushHandler($handler);

        return $this;
    }

    public function logError(string $msg, array $context = []): static
    {
        $this->writeLog('error', $msg, $context);

        return $this;
    }

    public function logAlert(string $msg, array $context = []): static
    {
        $this->writeLog('alert', $msg, $context);

        return $this;
    }

    public function logInfo(string $msg, array $context = []): static
    {
        $this->writeLog('info', $msg, $context);

        return $this;
    }

    public function logDebug(string $msg, array $context = []): static
    {
        $this->writeLog('debug', $msg, $context);

        return $this;
    }

    public function logEmergency(string $msg, array $context = []): static
    {
        $this->writeLog('emergency', $msg, $context);

        return $this;
    }

    public function logNotice(string $msg, array $context = []): static
    {
        $this->writeLog('notice', $msg, $context);

        return $this;
    }

    public function logWarning(string $msg, array $context = []): static
    {
        $this->writeLog('warning', $msg, $context);

        return $this;
    }

    private function writeLog(string $level, string $msg, array $context = []): void
    {
        $this->logger && $this->logger->{$level}($msg, $context);
    }

    public static function getStandardFormatter(): \Closure
    {
        return function (StreamHandler $handler, $_this) {
            $handler->setFormatter(new StandardFormatter());
        };
    }
}
