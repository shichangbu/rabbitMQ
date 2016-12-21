<?php

namespace Queue\RabbitMQ\Contracts;

interface DelayMessageInterface
{
    public function delayPublish($msg_body, $exchange, $queue, $delayTime, $exchangeType = 'direct');

    public function delayConsumer($queue, $consumer_tag, $exchange, $exchangeType, $className, $funcName, $msg = '');

    public function ackMessage($message);
}
