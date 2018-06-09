<?php

$root = dirname(dirname(__DIR__));

return [
    'pathToEnv'  => $root . '/tmp/.env.new',
    'backupPath' => $root . '/tmp/',
];
