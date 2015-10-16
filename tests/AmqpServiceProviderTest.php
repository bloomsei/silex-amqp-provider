<?php

namespace IztokSvetik\SilexAmqp\Tests;

use IztokSvetik\SilexAmqp\Provider\AmqpServiceProvider;
use Silex\Application;

/**
 * Simple test cases
 */
class AmqpServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function testConnections()
    {
        $app = $this->getApp();

        $this->assertInstanceOf('AMQPConnection', $app['amqp.connection']['default']);
    }

    /**
     * @test
     */
    public function testProducers()
    {
        $app = $this->getApp();

        $this->assertInstanceOf('M6Web\Bundle\AmqpBundle\Amqp\Producer', $app['amqp.producer']['test_producer']);
    }

    /**
     * @test
     */
    public function testConsumers()
    {
        $app = $this->getApp();

        $this->assertInstanceOf('M6Web\Bundle\AmqpBundle\Amqp\Consumer', $app['amqp.consumer']['test_consumer']);
    }

    /**
     * @return Application
     */
    private function getApp()
    {
        $app = new Application();

        $app->register(new AmqpServiceProvider(), [
            'amqp.connections' => [
                'default' => [
                    'host'      => 'localhost',
                    'port'      => 5672,
                    'user'      => 'guest',
                    'password'  => 'guest',
                    'vhost'     => '/'
                ],
            ],
            'amqp.producers' => [
                'test_producer' => [
                    'connection' => 'default',
                    'exchange_options' => [
                        'name' => 'first_test',
                        'type' => 'direct',
                    ],
                    'queue_options' => [
                        'name' => 'silex',
                    ]
                ]
            ],
            'amqp.consumers' => [
                'test_consumer' => [
                    'connection' => 'default',
                    'exchange_options' => [
                        'name' => 'first_test'
                    ],
                    'queue_options' => [
                        'name' => 'a_queue',
                    ]
                ]
            ],
        ]);

        return $app;
    }
}
