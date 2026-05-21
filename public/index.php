<?php

use App\Kernel;

$projectDir = dirname(__DIR__);
if (!is_file($projectDir.'/.env') && ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV')) === 'prod') {
    $_SERVER['APP_RUNTIME_OPTIONS'] = ['disable_dotenv' => true];
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
