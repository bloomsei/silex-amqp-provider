<?php

namespace IztokSvetik\SilexAmqp\Provider;

use M6Web\Bundle\AmqpBundle\Factory\ConsumerFactory;
use M6Web\Bundle\AmqpBundle\Factory\ProducerFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Service provider for AMQP producers and consumers
 */
class AmqpServiceProvider implements ServiceProviderInterface
{
    private static $defaultConnection = 'default';

    private static $defaultProducer = '\M6Web\Bundle\AmqpBundle\Amqp\Producer';

    private static $defaultConsumer = '\M6Web\Bundle\AmqpBundle\Amqp\Consumer';

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $this->loadConnections($app);
        $this->loadProducers($app);
        $this->loadConsumers($app);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app) {}

    /**
     * @param Application $app
     * @param array       $options
     *
     * @return \AMQPConnection
     */
    private function getConnection(Application $app, $options)
    {
        $connectionName = self::$defaultConnection;
        if (isset($options['connection'])) {
            $connectionName = $options['connection'];
        }

        if (!isset($app['amqp.connection'][$connectionName])) {
            throw new \InvalidArgumentException("Connection $connectionName was not defined");
        }

        return $app['amqp.connection'][$connectionName];
    }

    /**
     * @param Application $app
     */
    private function loadConnections(Application $app)
    {
        $app['amqp.connection'] = $app->share(function ($app) {
            if (!isset($app['amqp.connections'])) {
                throw new \InvalidArgumentException("You need to configure at least one AMQP connection");
            }

            $connections = [];

            foreach ($app['amqp.connections'] as $name => $options) {
                $connection = new \AMQPConnection($app['amqp.connections'][$name]);
                if (!$this->isLazy($app, $name)) {
                    $connection->connect();
                }

                $connections[$name] = $connection;
            }

            return $connections;
        });
    }

    /**
     * @param Application $app
     */
    private function loadProducers(Application $app)
    {
        $app['amqp.producer'] = $app->share(function($app) {
            if(!isset($app['amqp.producers'])){
                return;
            }

            $producerFactory = new ProducerFactory('AMQPChannel', 'AMQPExchange', 'AMQPQueue');

            $producers = [];
            foreach($app['amqp.producers'] as $name => $options) {
                $connection = $this->getConnection($app, $options);
                $lazy = $this->isLazy($app, $options['connection']);

                $exchangeOptions = $this->addDefaultOptions($options, 'exchange_options', [
                    'passive'            => false,
                    'durable'            => true,
                    'auto_delete'        => false,
                    'arguments'          => [],
                    'routing_keys'       => [],
                    'publish_attributes' => [],
                ], false);

                $queueOptions = $this->addDefaultOptions($options, 'queue_options', [
                    'name'        => '',
                    'passive'     => false,
                    'durable'     => true,
                    'auto_delete' => false,
                ]);

                $producerClass = isset($options['class']) ? $options['class'] : self::$defaultProducer;

                $producers[$name] = $producerFactory->get($producerClass, $connection, $exchangeOptions, $queueOptions, $lazy);
            }

            return $producers;
        });
    }

    /**
     * @param Application $app
     */
    private function loadConsumers(Application $app)
    {
        $app['amqp.consumer'] = $app->share(function($app) {
            if(!isset($app['amqp.consumers'])){
                return;
            }

            $consumerFactory = new ConsumerFactory('AMQPChannel', 'AMQPQueue');
            $consumers = [];
            foreach($app['amqp.consumers'] as $name => $options) {
                $connection = $this->getConnection($app, $options);

                $queueOptions = $this->addDefaultOptions($options, 'queue_options', [
                    'passive'      => false,
                    'durable'      => true,
                    'exclusive'    => false,
                    'auto_delete'  => false,
                    'arguments'    => [],
                    'routing_keys' => [],
                ], false);

                $qosOptions = $this->addDefaultOptions($options, 'qus_options', [
                    'prefetch_size'  => 0,
                    'prefetch_count' => 0,
                ]);

                $consumerClass = isset($options['class']) ? $options['class'] : self::$defaultConsumer;

                $consumers[$name] = $consumerFactory->get(
                    $consumerClass, $connection, $options['exchange_options'], $queueOptions, $qosOptions
                );
            }

            return $consumers;
        });
    }

    /**
     * @param Application $app
     * @param string      $connection
     * @return bool
     */
    private function isLazy(Application $app, $connection)
    {
        if (isset($app['amqp.connections'][$connection]['lazy']) && $app['amqp.connections'][$connection]['lazy']) {
            return true;
        }

        return false;
    }

    /**
     * @param array     $options
     * @param string    $option
     * @param array     $default
     * @param bool|true $optional
     * @return array
     */
    private function addDefaultOptions(array $options, $option, array $default, $optional = true)
    {
        if (!isset($options[$option]) && $optional) {
            return [];
        }

        $configuration = $options[$option];

        foreach ($default as $key => $value) {
            if (!isset($configuration[$key])) {
                $configuration[$key] = $value;
            }
        }

        return $configuration;
    }
}
