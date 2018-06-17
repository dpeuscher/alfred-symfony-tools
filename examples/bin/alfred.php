<?php

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;

if (!isset($alfredArgv)) {
    $alfredArgv = implode(' ', $argv);
}

$_SERVER['argv'] = explode(' ', iconv("UTF-8-MAC", "UTF-8", $alfredArgv));

$argv = $_SERVER['argv'];

set_time_limit(0);

if (version_compare(PHP_VERSION, '7.1') < 0) {
    echo '{"items": [
        {
            "title": "Please install at least PHP 7.1",
            "subtitle": "You need a PHP version of at least 7.1. You have ' . PHP_VERSION . ' installed. Please upgrade.",
            "valid": false,
        }
    ]}';
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

if (!class_exists(Application::class)) {
    throw new \RuntimeException('You need to add "symfony/framework-bundle" as a Composer dependency.');
}

if (!isset($_SERVER['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__ . '/../.env');
}

$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], $_SERVER['APP_ENV'] ?? 'dev', true);
$debug = (bool)($_SERVER['APP_DEBUG'] ?? ('prod' !== $env)) && !$input->hasParameterOption('--no-debug', true);

if ($debug) {
    umask(0000);

    if (class_exists(Debug::class)) {
        Debug::enable();
    }
}

$kernel = new Kernel($env, $debug);
$application = new Application($kernel);
$application->run($input);
