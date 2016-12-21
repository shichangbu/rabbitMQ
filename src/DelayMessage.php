<?php

namespace Sunshine\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Sunshine\RabbitMQ\Contracts\DelayMessageInterface;

class DelayMessage implements DelayMessageInterface
{
    private $conn;
    private $channel;

    public function __construct(AMQPStreamConnection $conn)
    {
        $this->conn = $conn;
        $this->channel = $this->conn->channel();
    }

    /**
     * 发布延时消息.
     *
     * @param $msg_body
     * @param $exchange
     * @param $queue
     * @param $delayTime
     * @param string $exchangeType
     */
    public function delayPublish($msg_body, $exchange, $queue, $delayTime, $exchangeType = 'direct')
    {
        $this->channel->exchange_declare($exchange, 'x-delayed-message', false, true, false, false, false, new AMQPTable(array(
            'x-delayed-type' => 'direct',
        )));

        $this->channel->queue_declare($queue, false, true, false, false, false, new AMQPTable(array(
            'x-dead-letter-exchange' => 'delayed',
        )));

        $headers = new AMQPTable(array('x-delay' => $delayTime));

        $msg = new AMQPMessage(json_encode($msg_body), array('delivery_mode' => 2));

        $msg->set('application_headers', $headers);

        $this->channel->queue_bind($queue, $exchange, 'routing');
        $this->channel->basic_publish($msg, $exchange);
    }

    /**
     * 消费消息.
     *
     * @param $queue
     * @param string $consumer_tag
     * @param $exchange
     * @param string $exchangeType
     * @param $className
     * @param $funcName
     * @param string $msg
     */
    public function delayConsumer($queue, $consumer_tag, $exchange, $exchangeType, $className, $funcName, $msg = '')
    {
        $this->channel->exchange_declare($exchange, 'x-delayed-message', false, true, false, false, false, new AMQPTable(array(
            'x-delayed-type' => 'direct',
        )));

        $this->channel->queue_declare($queue, false, false, false, false, false, new AMQPTable(array(
            'x-dead-letter-exchange' => 'delayed',
        )));

        $this->channel->queue_bind($queue, $exchange);

        $consumer_tag = 'consumer'.$consumer_tag;

        $this->channel->basic_consume($queue, $consumer_tag, false, false, true, false, call_user_func_array(array($className, $funcName), [$msg]));

        //如果出现异常，自动关闭
        function shutdown($channel, $conn)
        {
            $channel->close();
            $conn->close();
        }
        register_shutdown_function('shutdown', $this->channel, $this->conn);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->conn->close();
    }
}
