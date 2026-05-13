<?php

    namespace Coco\logger;

    use Monolog\Formatter\LineFormatter;

    /**
     * 标准日志格式化器。
     *
     * 输出格式：
     * [2026-05-13 16:00:00] channel.LEVEL: message {"key":"value"}
     */
    class StandardFormatter extends LineFormatter
    {
        /**
         * 格式化 Monolog 2 的记录结构。
         *
         * 当前项目 composer 依赖为 Monolog ^2.0，
         * 这里先按 Monolog 2 稳定处理，避免为兼容 3 误改现有项目。
         */
        public function format(array $record): string
        {
            $datetime = $record['datetime'] ?? null;
            $channel = $record['channel'] ?? 'app';
            $levelName = $record['level_name'] ?? 'INFO';
            $message = (string)($record['message'] ?? '');
            $context = $record['context'] ?? [];

            $date = $datetime instanceof \DateTimeInterface
                ? $datetime->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s');

            $contextJson = json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($contextJson === false) {
                $contextJson = '{}';
            }

            return sprintf(
                "[%s] %s.%s: %s %s\n",
                $date,
                $channel,
                $levelName,
                $message,
                $contextJson
            );
        }
    }