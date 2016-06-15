<?php
require_once __DIR__.'/../vendor/autoload.php';

use Workshop\DatadogCollector;

$app = new Silex\Application();
$app['debug'] = true;

$app['datadog_host'] = 'metrics'; // should be linked to datadog's container
$app['datadog_port'] = 8125;
$app['datadog_prefix'] = 'workshop.';

/** @var DatadogCollector */
$app['collector'] = function($app) {
    return new DatadogCollector($app['datadog_host'], $app['datadog_port'], $app['datadog_prefix']);
};

$app->get('/hello/{name}', function ($name) use ($app) {
    /** @var DatadogCollector */
    $collector = $app['collector'];

    $collector->increment('hello.called');
    $collector->flush();
    return 'Hello '.$app->escape($name);
});

$app->get('/scan', function () use ($app) {
    /** @var DatadogCollector */
    $collector = $app['collector'];

    $start = microtime(true);
    $collector->increment('scan.called');

    // Simulating calling an scan API, or running exec(./my_favorite_scanner)
    usleep(rand(100, 1500));
    $collector->timing('scan.execution', microtime(true) - $start);

    $status = rand(0, 9);
    if ($status !== 0) {
        $collector->increment('scan.success');
    } else {
        $collector->increment('scan.failed');
    }

    $collector->flush();

    return $status ? 'Success!' : 'Failed :(';
});

$app->get('/queue/add', function() use ($app) {
    /** @var $collector DatadogCollector */
    $collector = $app['collector'];

    // Fakely querying a message queue about how much is left to do
    $addedToQueue = rand(1000, 1500);
    $collector->gauge('queue.treat.messages', '+'.$addedToQueue);

    $collector->flush();

    return sprintf('Added %d messages to queue', $addedToQueue);
});


$app->get('/queue/treat', function() use ($app) {
    /** @var $collector DatadogCollector */
    $collector = $app['collector'];

    $collector->increment('queue.treat.called');

    //Fakely treating a bunch of stuff
    $treated = rand(100, 350);
    $collector->gauge('queue.treat.messages', '-'.$treated);

    $collector->flush();

    return sprintf('Treated %d', $treated);
});

$app->run();
