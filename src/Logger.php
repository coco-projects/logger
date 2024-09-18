<?php

    namespace Coco\logger;

    use Monolog\Handler\HandlerInterface;
    use Monolog\Handler\StreamHandler;
    use Psr\Log\LoggerInterface;
    use Monolog\Handler\RedisHandler;

trait Logger
{
    protected ?LoggerInterface $logger = null;

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function addRedisLogger(string $redisHost='127.0.0.1', int $redisPort=6379, string $password = '', int $db = 10, string $logName = 'redis_log'): void
    {
        $redis = new \Redis();
        $redis->connect($redisHost, $redisPort);

        if ($password) {
            $redis->auth($password);
        }

        $redis->select($db);
        $handler = new RedisHandler($redis, $logName, \Monolog\Logger::DEBUG);
        $this->pushLoggerHandler($handler);
    }

    public function addFileLogger(string $path): void
    {
        $handler = new StreamHandler($path, \Monolog\Logger::DEBUG);
        $this->pushLoggerHandler($handler);
    }

    public function addStdoutLogger(): void
    {
        $handler = new StreamHandler('php://stdout', \Monolog\Logger::DEBUG);
        $this->pushLoggerHandler($handler);
    }

    public function pushLoggerHandler(HandlerInterface $handler): static
    {
        $this->logger && $this->logger->pushHandler($handler);

        return $this;
    }

    public function logError(string $msg): static
    {
        $this->writeLog('error', $msg);

        return $this;
    }

    public function logAlert(string $msg): static
    {
        $this->writeLog('alert', $msg);

        return $this;
    }

    public function logInfo(string $msg): static
    {
        $this->writeLog('info', $msg);

        return $this;
    }

    public function logDebug(string $msg): static
    {
        $this->writeLog('debug', $msg);

        return $this;
    }

    public function logEmergency(string $msg): static
    {
        $this->writeLog('emergency', $msg);

        return $this;
    }

    public function logNotice(string $msg): static
    {
        $this->writeLog('notice', $msg);

        return $this;
    }

    public function logWarning(string $msg): static
    {
        $this->writeLog('warning', $msg);

        return $this;
    }

    private function writeLog(string $level, string $msg): void
    {
        $this->logger && $this->logger->{$level}($msg);
    }
}
