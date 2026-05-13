<?php

    use Coco\logger\Logger;
    use Monolog\Handler\StreamHandler;

    require __DIR__ . '/../vendor/autoload.php';

    class Demo
    {
        use Logger;
    }

    $demo = new Demo();
    $demo->setStandardLogger('test');

    $filePath = __DIR__ . '/logs';
    is_dir($filePath) or mkdir($filePath, 0777, true);

    /**
     * 标准输出日志。
     */
    $demo->addStdoutHandler(callback: function (StreamHandler $handler, Demo $_this) {
        $handler->setFormatter(new \Coco\logger\StandardFormatter());
    });

    /**
     * Redis 日志。
     *
     * 注意：
     * 1. 需要本机 Redis 可连接
     * 2. 如果你当前环境没有启动 Redis，先把这行注释掉再测其他输出
     */
    $demo->addRedisHandler();

    /**
     * 普通文件日志。
     */
    $demo->addFileHandler(
        path: $filePath . '/file_log.log',
        callback: Demo::getStandardFormatter()
    );

    /**
     * 按日期滚动文件日志。
     *
     * 说明：
     * 1. Monolog 自带 RotatingFileHandler 默认按天滚动
     * 2. maxFiles=7 表示最多保留 7 份历史日志
     */
    $demo->addRotatingFileHandler(
        path: $filePath . '/rotating.log',
        maxFiles: 7,
        callback: Demo::getStandardFormatter()
    );

    /**
     * 按小时切分日志文件。
     *
     * 示例文件名：
     * hourly-2026-05-13-16.log
     */
    $demo->addTimedFileHandler(
        path: $filePath . '/hourly.log',
        dateFormat: 'Y-m-d-H',
        callback: Demo::getStandardFormatter()
    );

    /**
     * 按分钟切分日志文件。
     *
     * 示例文件名：
     * minute-2026-05-13-16-35.log
     */
    $demo->addTimedFileHandler(
        path: $filePath . '/minute.log',
        dateFormat: 'Y-m-d-H-i',
        callback: Demo::getStandardFormatter()
    );

    $demo->logDebug('test log1');

    $demo->logAlert('test log2', [
        'key' => 'value',
    ]);

    $demo->logCritical('test critical log', [
        'module' => 'demo1',
        'status' => 'ok',
    ]);