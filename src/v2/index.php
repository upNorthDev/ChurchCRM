<?php
require '../Include/Config.php';
require '../Include/Functions.php';

// This file is generated by Composer
require_once __DIR__.'/../vendor/autoload.php';

use ChurchCRM\dto\SystemConfig;
use ChurchCRM\Slim\Middleware\AuthMiddleware;
use ChurchCRM\Slim\Middleware\VersionMiddleware;
use Slim\App;
use Slim\Container;

$container = new Container;
if (SystemConfig::debugEnabled()) {
    $container["settings"]['displayErrorDetails'] = true;
}
$container["settings"]['displayErrorDetails'] = true;
$container["settings"]['logger']['name'] = "slim-app";
$container["settings"]['logger']['path'] = __DIR__ . '/logs/slim-app.log';

// Add middleware to the application
$app = new App($container);

$app->add(new VersionMiddleware());
$app->add(new AuthMiddleware());

// Set up
require __DIR__.'/../Include/slim/error-handler.php';

require __DIR__.'/routes/common/mvc-helper.php';

// admin routes
require __DIR__.'/routes/admin/admin.php';
require __DIR__.'/routes/user.php';

// people routes
require __DIR__.'/routes/root.php';

require __DIR__.'/routes/people.php';
require __DIR__.'/routes/family.php';
require __DIR__.'/routes/person.php';

require __DIR__.'/routes/email.php';
require __DIR__.'/routes/calendar.php';
require __DIR__.'/routes/cart.php';

require __DIR__.'/routes/user-current.php';

// Run app
$app->run();
