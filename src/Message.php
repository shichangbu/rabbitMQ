<?php
namespace Queue\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Queue\RabbitMQ\Contracts\MessageInterface;

class Message implements MessageInterface
{
    private $conn;
    private $channel;

    public function __construct(AMQPStreamConnection $conn)
    {
        $this->conn = $conn;
        $this->channel = $this->conn->channel();
    }

    /**
     * 发布消息.
     *
     * @param $msg_body
     * @param $exchange
     * @param $queue
     * @param string $exchangeType
     */
    public function publish($msg_body, $exchange, $queue, $exchangeType = 'direct')
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->exchange_declare($exchange, $exchangeType, false, true, false);

        $msg = new AMQPMessage(json_encode($msg_body), array('delivery_mode' => 2));
        $this->channel->queue_bind($queue, $exchange);
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
    public function consumer($queue, $consumer_tag, $exchange, $exchangeType, $className, $funcName, $msg = '')
    {
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->exchange_declare($exchange, $exchangeType, false, true, false);
        $this->channel->queue_bind($queue, $exchange);

        $consumer_tag = 'consumer'.$consumer_tag;

        $this->channel->basic_consume($queue, $consumer_tag, false, false, true, false, call_user_func_array($funcName, [$msg]));

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

    /**
     * 消息确认
     * @param $message
     * @return bool
     */
    public function ackMessage($message)
    {
        if (!($message instanceof AMQPMessage))
        {
            return false;
        }
        $message->delivery_info['channel']
            ->basic_ack($message->delivery_info['delivery_tag']);
        return true;
    }
}
