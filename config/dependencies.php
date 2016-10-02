<?php

use Monolog\Logger;
use Slim\Views\PhpRenderer;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use League\CommonMark\CommonMarkConverter;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Interop\Container\ContainerInterface as Container;

use Sihae\PostRepository;

$container = $app->getContainer();

$container[PostRepository::class] = function (Container $container) : PostRepository {
    return new PostRepository($container->get('entity-manager'));
};

$container[CommonMarkConverter::class] = function (Container $container) : CommonMarkConverter {
    $settings = $container->get('settings')['markdown'];

    return new CommonMarkConverter($settings);
};

$container['entity-manager'] = function (Container $container) : EntityManager {
    $settings = $container->get('settings')['doctrine'];

    $config = Setup::createAnnotationMetadataConfiguration(
        $settings['entity_path'],
        $settings['auto_generate_proxies'],
        $settings['proxy_dir'],
        $settings['cache'],
        false
    );

    return EntityManager::create($settings['connection'], $config);
};

// view renderer
$container['renderer'] = function (Container $container) : PhpRenderer {
    $settings = $container->get('settings')['renderer'];

    return new PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function (Container $container) : LoggerInterface {
    $settings = $container->get('settings')['logger'];

    $logger = new Logger($settings['name']);
    $logger->pushProcessor(new UidProcessor());
    $logger->pushHandler(new StreamHandler($settings['path'], $settings['level']));

    return $logger;
};

$container['foundHandler'] = function (Container $container) : InvocationStrategyInterface {
    return new RequestResponseArgs();
};
