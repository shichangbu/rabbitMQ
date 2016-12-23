## Usage

在`config/app.php`中加入：

```php
 Queue\RabbitMQ\Providers\RabbitmqServiceProvider::class
```

执行命令：

```php
php artisan vendor:publish
```

### Publish

```php
### 发布普通消息
$message = app(Message::class);
$message->publish([
    'name' => 'sunshine',
    'age' => 22,
], 'exchange', 'queue', 'direct');
return 'success';

### 发布延时消息
$delayMessage = app(DelayedMessage::class);
$delayMessage->delayPublish([
    'name' => 'Tang',
    'age' => 22,
], 'delay-exchange', 'delay-queue', 5000, 'direct');

return 'success';
```


### Consume

```php
### 消费普通消息
$message = app(Message::class);
$message->consumer('delay-queue', 'consumer_tag', 'delay-exchange', 'callbackFunc', 'direct');


### 消费延时消息
$delayMessage = app(DelayedMessage::class);
$delayMessage->delayConsumer('delay-queue', 'consumer_tag', 'delay-exchange', 'callbackFunc', 'direct');


function testConsume($message)
{
   var_dump(json_decode($message->body, true));
}

```