<?php

    namespace Coco\logger;

    use Monolog\Handler\HandlerInterface;
    use Monolog\Handler\RedisHandler;
    use Monolog\Handler\RotatingFileHandler;
    use Monolog\Handler\StreamHandler;
    use Psr\Log\LoggerInterface;

    /**
     * Trait Logger
     *
     * 给业务类快速挂载 Monolog 日志能力。
     *
     * 设计原则：
     * 1. 保持现有调用方式兼容，不大改对外接口
     * 2. 未初始化 logger 时继续静默，避免影响旧项目
     * 3. handler 相关能力优先兼容 Monolog\Logger
     */
    trait Logger
    {

        /**
         * 当前 logger 实例。
         *
         * 这里保留 LoggerInterface 类型，兼容旧项目 setLogger() 的调用方式；
         * 但 handler 管理功能仅在 logger 为 Monolog\Logger 时生效。
         */
        protected ?LoggerInterface $logger = null;

        /**
         * 设置 logger。
         */
        public function setLogger(?LoggerInterface $logger): static
        {
            $this->logger = $logger;

            return $this;
        }

        /**
         * 获取 logger。
         */
        public function getLogger(): ?LoggerInterface
        {
            return $this->logger;
        }

        /**
         * 添加按日期滚动的文件日志处理器。
         *
         * 说明：
         * 1. RotatingFileHandler 是按日期滚动，不是按文件大小滚动
         * 2. $maxFiles = 0 表示不限制保留数量
         * 3. 当前 logger 不是 Monolog\Logger 时，将继续静默，不报错
         */
        public function addRotatingFileHandler(string $path, int $maxFiles = 0, ?callable $callback = null): static
        {
            $handler = new RotatingFileHandler($path, $maxFiles, \Monolog\Logger::DEBUG);

            if (is_callable($callback))
            {
                call_user_func_array($callback, [
                    $handler,
                    $this,
                ]);
            }

            $this->pushLoggerHandler($handler);

            return $this;
        }

        /**
         * 添加自定义时间分片文件日志处理器。
         *
         * 说明：
         * 1. 适合按小时、按分钟等粒度切日志文件
         * 2. 该方法在创建 handler 时根据当前时间生成真实文件名
         * 3. 对于普通 PHP 请求/CLI 脚本非常适合；如果是常驻进程，不会自动跨时段切新文件
         *
         * 示例：
         * - path=/data/logs/app.log, dateFormat=Y-m-d-H
         *   => /data/logs/app-2026-05-13-16.log
         * - path=/data/logs/app.log, dateFormat=Y-m-d-H-i
         *   => /data/logs/app-2026-05-13-16-35.log
         */
        public function addTimedFileHandler(string $path, string $dateFormat = 'Y-m-d-H', ?callable $callback = null): static
        {
            $realPath = $this->buildTimedLogPath($path, $dateFormat);
            $handler  = new StreamHandler($realPath, \Monolog\Logger::DEBUG);

            if (is_callable($callback))
            {
                call_user_func_array($callback, [
                    $handler,
                    $this,
                ]);
            }

            $this->pushLoggerHandler($handler);

            return $this;
        }

        /**
         * 向当前 logger 压入 handler。
         *
         * 注意：
         * 仅当 logger 为 Monolog\Logger 时才会生效；
         * 如果外部传入的是普通 PSR logger，则这里保持静默兼容。
         */
        public function pushLoggerHandler(HandlerInterface $handler): static
        {
            if ($this->logger instanceof \Monolog\Logger)
            {
                $this->logger->pushHandler($handler);
            }

            return $this;
        }

        /**
         * 创建标准 Monolog logger。
         */
        public function setStandardLogger(string $name, array $handlers = [], array $processors = [], ?\DateTimeZone $timezone = null): static
        {
            $this->setLogger(new \Monolog\Logger($name, $handlers, $processors, $timezone));

            return $this;
        }

        /**
         * 添加 Redis 日志处理器。
         *
         * 注意：
         * 1. 依赖 ext-redis
         * 2. 当前 logger 不是 Monolog\Logger 时，将继续静默，不报错
         * 3. Redis 连接失败时抛出运行时异常，方便定位
         */
        public function addRedisHandler(string $redisHost = '127.0.0.1', int $redisPort = 6379, string $password = '', int $db = 10, string $logName = 'redis_log', ?callable $callback = null): static
        {
            if (!class_exists(\Redis::class))
            {
                throw new \RuntimeException('ext-redis is required for addRedisHandler().');
            }

            $redis = new \Redis();

            try
            {
                $connected = $redis->connect($redisHost, $redisPort);

                if ($connected === false)
                {
                    throw new \RuntimeException(sprintf('Redis connect returned false. host=%s port=%d', $redisHost, $redisPort));
                }

                if ($password !== '')
                {
                    $authResult = $redis->auth($password);

                    if ($authResult === false)
                    {
                        throw new \RuntimeException('Redis auth failed.');
                    }
                }

                $selectResult = $redis->select($db);

                if ($selectResult === false)
                {
                    throw new \RuntimeException(sprintf('Redis select db failed. db=%d', $db));
                }
            }
            catch (\Throwable $e)
            {
                throw new \RuntimeException(sprintf('Failed to initialize redis log handler. host=%s port=%d db=%d logName=%s', $redisHost, $redisPort, $db, $logName), 0, $e);
            }

            $handler = new RedisHandler($redis, $logName, \Monolog\Logger::DEBUG);

            if (is_callable($callback))
            {
                call_user_func_array($callback, [
                    $handler,
                    $this,
                ]);
            }

            $this->pushLoggerHandler($handler);

            return $this;
        }

        /**
         * 添加普通文件日志处理器。
         */
        public function addFileHandler(string $path, ?callable $callback = null): static
        {
            $handler = new StreamHandler($path, \Monolog\Logger::DEBUG);

            if (is_callable($callback))
            {
                call_user_func_array($callback, [
                    $handler,
                    $this,
                ]);
            }

            $this->pushLoggerHandler($handler);

            return $this;
        }

        /**
         * 添加标准输出日志处理器。
         */
        public function addStdoutHandler(?callable $callback = null): static
        {
            $handler = new StreamHandler('php://stdout', \Monolog\Logger::DEBUG);

            if (is_callable($callback))
            {
                call_user_func_array($callback, [
                    $handler,
                    $this,
                ]);
            }

            $this->pushLoggerHandler($handler);

            return $this;
        }

        /**
         * 记录 error 日志。
         */
        public function logError(string $msg, array $context = []): static
        {
            $this->writeLog('error', $msg, $context);

            return $this;
        }

        /**
         * 记录 alert 日志。
         */
        public function logAlert(string $msg, array $context = []): static
        {
            $this->writeLog('alert', $msg, $context);

            return $this;
        }

        /**
         * 记录 info 日志。
         */
        public function logInfo(string $msg, array $context = []): static
        {
            $this->writeLog('info', $msg, $context);

            return $this;
        }

        /**
         * 记录 debug 日志。
         */
        public function logDebug(string $msg, array $context = []): static
        {
            $this->writeLog('debug', $msg, $context);

            return $this;
        }

        /**
         * 记录 emergency 日志。
         */
        public function logEmergency(string $msg, array $context = []): static
        {
            $this->writeLog('emergency', $msg, $context);

            return $this;
        }

        /**
         * 记录 notice 日志。
         */
        public function logNotice(string $msg, array $context = []): static
        {
            $this->writeLog('notice', $msg, $context);

            return $this;
        }

        /**
         * 记录 warning 日志。
         */
        public function logWarning(string $msg, array $context = []): static
        {
            $this->writeLog('warning', $msg, $context);

            return $this;
        }

        /**
         * 记录 critical 日志。
         */
        public function logCritical(string $msg, array $context = []): static
        {
            $this->writeLog('critical', $msg, $context);

            return $this;
        }

        /**
         * 执行日志写入。
         *
         * 这里继续保持兼容：没有 logger 时静默跳过。
         */
        private function writeLog(string $level, string $msg, array $context = []): void
        {
            if ($this->logger instanceof LoggerInterface)
            {
                $this->logger->{$level}($msg, $context);
            }
        }

        /**
         * 根据基础路径和时间格式生成真实日志文件路径。
         *
         * 规则：
         * 1. 如果有扩展名：app.log => app-2026-05-13-16.log
         * 2. 如果没有扩展名：app => app-2026-05-13-16
         */
        protected function buildTimedLogPath(string $path, string $dateFormat): string
        {
            $directory = dirname($path);
            $filename  = basename($path);
            $timestamp = date($dateFormat);

            if ($directory !== '' && $directory !== '.' && !is_dir($directory))
            {
                mkdir($directory, 0777, true);
            }

            $extension            = pathinfo($filename, PATHINFO_EXTENSION);
            $nameWithoutExtension = pathinfo($filename, PATHINFO_FILENAME);

            if ($extension !== '')
            {
                return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nameWithoutExtension . '-' . $timestamp . '.' . $extension;
            }

            return rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . '-' . $timestamp;
        }

        /**
         * 获取标准格式化器回调。
         *
         * 用法：
         * Demo::getStandardFormatter()
         */
        public static function getStandardFormatter(): \Closure
        {
            return function($handler, $_this) {
                if (method_exists($handler, 'setFormatter'))
                {
                    $handler->setFormatter(new StandardFormatter());
                }
            };
        }

    }