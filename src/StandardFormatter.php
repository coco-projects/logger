<?php

    namespace Coco\logger;

    use Monolog\Formatter\LineFormatter;

class StandardFormatter extends LineFormatter
{
    public function format(array $record): string
    {
        $date = (new \DateTime())->setTimestamp($record['datetime']->getTimestamp())->format('Y-m-d H:i:s');

        return sprintf("[%s] %s.%s: %s %s\n", $date, $record['channel'], $record['level_name'], $record['message'], json_encode($record['context']));
    }
}
