<?php
namespace Queue\RabbitMQ\Providers;

use Illuminate\Support\ServiceProvider;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Queue\RabbitMQ\DelayMessage;
use Queue\RabbitMQ\Message;

/**
 * Created by PhpStorm.
 * User: wangyan
 * Date: 2016/12/21
 * Time: 13:54.
 */
class RabbitmqServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/rabbitmq.php' => config_path('rabbitmq.php'),
        ]);
    }

    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton(AMQPStreamConnection::class, function () {
            return new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost'));
        });

        $this->app->singleton(Message::class, function ($app) {
            return new Message($app[AMQPStreamConnection::class]);
        });

        $this->app->singleton(DelayMessage::class, function ($app) {
            return new DelayMessage($app[AMQPStreamConnection::class]);
        });
    }
}
