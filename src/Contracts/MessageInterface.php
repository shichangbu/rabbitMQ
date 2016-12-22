<?php
namespace Queue\RabbitMQ\Contracts;

interface MessageInterface
{
    public function publish($msg_body, $exchange, $queue, $exchangeType = 'direct');

    public function consumer($queue, $consumer_tag, $exchange, $funcName, $exchangeType  = 'direct');

    public function ackMessage($message);
}
