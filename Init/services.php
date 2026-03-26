<?php

namespace Okay\Modules\Sviat\Ringostat;

use Okay\Core\Config;
use Okay\Core\EntityFactory;
use Okay\Core\ManagerMenu;
use Okay\Core\OkayContainer\Reference\ServiceReference as SR;
use Okay\Core\Request;
use Okay\Core\Settings;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatApiClient;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatCronHelper;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatHelper;
use Okay\Modules\Sviat\Ringostat\Helpers\RingostatSettingsHelper;
use Okay\Modules\Sviat\Ringostat\Extenders\BackendExtender;
use Okay\Modules\Sviat\Ringostat\Extenders\FrontendExtender;
use Psr\Log\LoggerInterface;

return [
    RingostatApiClient::class => [
        'class' => RingostatApiClient::class,
        'arguments' => [
            new SR(Settings::class),
            new SR(LoggerInterface::class),
        ],
    ],
    RingostatSettingsHelper::class => [
        'class' => RingostatSettingsHelper::class,
        'arguments' => [
            new SR(Settings::class),
        ],
    ],
    RingostatHelper::class => [
        'class' => RingostatHelper::class,
        'arguments' => [
            new SR(EntityFactory::class),
            new SR(RingostatApiClient::class),
            new SR(Settings::class),
            new SR(Request::class),
            new SR(RingostatSettingsHelper::class),
        ],
    ],
    BackendExtender::class => [
        'class' => BackendExtender::class,
        'arguments' => [
            new SR(RingostatHelper::class),
            new SR(Config::class),
            new SR(ManagerMenu::class),
            new SR(EntityFactory::class),
        ],
    ],
    FrontendExtender::class => [
        'class' => FrontendExtender::class,
        'arguments' => [
            new SR(RingostatHelper::class),
            new SR(Settings::class),
        ],
    ],
    RingostatCronHelper::class => [
        'class' => RingostatCronHelper::class,
        'arguments' => [
            new SR(RingostatHelper::class),
        ],
    ],
];
