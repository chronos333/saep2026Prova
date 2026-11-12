<?php
declare(strict_types=1);

$composerAutoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($composerAutoloader)) {
    require_once $composerAutoloader;
} else {
    require_once dirname(__DIR__) . '/src/Core/autoload.php';
}
