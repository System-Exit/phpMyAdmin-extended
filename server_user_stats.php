<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * ServerMessages page. This page is used as the harness for our messages page.
 *
 * There are three elements needed to create a page:
 *  - the top-level page (this)
 *  - the controller (libraries/classes/Controllers/Giganibbles/ServerMessagesController.php).
 *  - the twig(s) template (templates/Giganibbles/server_messages.twig)
 *
 * The top level page is this: it creates the controller which will then be
 * used to load the twigs. The controller is placed in the
 * 'libraries/classes/Controllers/Giganibbles directory'. The twig is placed in
 * the 'templates/Giganibbles' directory.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Controllers\Giganibbles\ServerUserStatsController;

/**
 * Does the common work
 */
require_once 'libraries/common.inc.php';

/* -- using Twig */
$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Giganibbles\ServerUserStatsController');
$container->alias('ServerUserStatsController', 'PhpMyAdmin\Controllers\Giganibbles\ServerUserStatsController');
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/** @var ServerUserStatsController $controller */
$controller = $container->get(
    'ServerUserStatsController',
    []
);
$controller->indexAction();