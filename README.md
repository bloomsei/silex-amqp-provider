#AMQP Silex service provider

##About

This Silex service provider registers the [M6Web/AmqpBundle](https://github.com/M6Web/AmqpBundle) as a service in Silex. It uses the [php-amqp extension](https://pecl.php.net/package/amqp), and can communicate with any AMQP 0-9-1 compatible server, such as RabbitMQ, OpenAMQP and Qpid.

Publishing messages to AMQP server from a Silex application is easy
```php
$app->post('/message', function(Request $request) use ($app){
    $producer = $app['amqp.producer']['producer_name'];
    $producer->publish('Some message');

    return new Response($msg_body);
});
```

And likewise consuming
```php
$consumer = $app['amqp.consumer']['consumer_name'];
$message  = $consumer->getMessage()->getBody();
```

##Instalation

Require in your composer.json
```json
{
    "require": {
        "iztoksvetik/silex-amqp-provider": "~1.0",
    }
}
```

Register the service
```php
use Silex\Application;
use IztokSvetik\SilexAmqp\Provider;

$app = new Application();
$app->register(new AmqpServiceProvider());
```

Install the provider
```
$ composer update iztoksvetik/silex-amqp-provider
```

##Configuration

```php
$app->register(new AmqpServiceProvider(), [
    'amqp.connections' => [
        'default' => [
            'host'      => 'localhost', // optional - default "localhost"
            'port'      => 5672,        // optional - default 5672
            'login'     => 'guest',     // optional - default "guest"
            'password'  => 'guest',     // optional - default "guest"
            'vhost'     => '/',         // optional - default "/"
            'lazy'      => false        // optional - default false
        ],
    ],
    'amqp.producers' => [
        'my_producer' => [
            'class'            => 'My\Producer\Class', // optional - overload default class
            'connection'       => 'default',           // required
            'queue_options'    => [
                'name'        => 'my-queue',           // optional
                'passive'     => false,                // optional - default false
                'durable'     => true,                 // optional - default true
                'auto_delete' => false,                // optional - default false
            ],
            'exchange_options' => [
                'name'               => 'my-exchange',      // required
                'type'               => 'direct',           // required - possible direct/fanout/topic/headers
                'passive'            => false,              // optional - default false
                'durable'            => true,               // optional - default true
                'auto_delete'        => false,              // optional - default false
                'arguments'          => ['key' => 'value'], // optional - default []
                'routing_keys'       => ['key1', 'key2'],   // optional - default []
                'publish_attributes' => ['key' => 'value'], // optional - default []
            ],
        ]
    ],
    'amqp.consumers' => [
        'my_consumer' => [
            'class'            => 'My\Consumer\Class', // optional - overload default class
            'connection'       => 'default',          // required
            'exchange_options' => [
                'name' => 'my-exchange'               // required
            ],
            'queue_options'    => [
                'name'         => 'my-queue',         // required
                'passive'      => false,              // optional - default false
                'durable'      => true,               // optional - default true
                'exclusive'    => false,              // optional - default false
                'auto_delete'  => false,              // optional - default false
                'arguments'    => ['key' => 'value'], // optional - default []
                'routing_keys' => ['key1', 'key2'],   // optional - default []
            ],
            'qos_options'      => [
                'prefetch_size'  => 0, // optional - default 0
                'prefetch_count' => 0  // optional - default 0
            ]
        ]
    ],
]);
```

In this example your service container will have services `$app['amqp.consumer']['my_consumer']` and `$app['amqp.producer']['my_producer']`
