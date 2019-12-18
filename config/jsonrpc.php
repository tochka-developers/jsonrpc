<?php

/**
 * Настройки JsonRpc
 */

use Tochka\JsonRpc\Middleware\AccessControlListMiddleware;
use Tochka\JsonRpc\Middleware\LogMiddleware;
use Tochka\JsonRpc\Middleware\ServiceValidationMiddleware;
use Tochka\JsonRpc\Middleware\TokenAuthMiddleware;

return [
    // Описание сервиса (для SMD-схемы)
    'description' => 'JsonRpc Server',

    'default'          => [
        // Пространство имен для контроллеров по умолчанию
        'controllerNamespace' => 'App\\Http\\Controllers\\',

        //Суффикс для имен контроллеров по умолчанию
        'controllerPostfix'   => 'Controller',

        //Контроллер по умолчанию для методов без имени сервиса (ping)
        'defaultController'   => 'Api',

        /**
         * Обработчики запросов
         */
        'middleware'       => [
            LogMiddleware::class               => [
                // Канал лога, в который будут записываться все логи
                'channel' => 'default',

                /**
                 * Параметры, которые необходимо скрыть из логов
                 */
                //'hideParams' => [
                //    'App\\Http\\TestController1@method' => ['password', 'data.phone_number'],
                //    'App\\Http\\TestController2' => ['password', 'data.phone_number']
                //]
            ],
            TokenAuthMiddleware::class         => [
                'headerName' => 'X-Tochka-Access-Key',
                // Ключи доступа к API
                'tokens' => [
                    'all' => 'TOKEN',
                ],
            ],
            ServiceValidationMiddleware::class => [
                //Р азрешенные сервера, которые могут авторизовываться под указанными сервисами
                'servers' => [
                    //'service1' => ['192.168.0.1', '192.168.1.5'],
                    //'service2' => '*',
                ],
            ],
            AccessControlListMiddleware::class => [
                /**
                 * Список контроля доступа
                 * Ключи массива - методы, значения - массив с наименованием сервисов, которые имеют доступ к указанному методу
                 */
                'acl' => [
                    // '*' => '*',                               // доступ ко всем методам есть у всех систем
                    // 'App\\Http\\TestController1' => '*'       // доступ ко всем методам контроллера есть у всех систем
                    // 'App\\Http\\TestController1@method1' => ['system1', 'system2'], // к этому методу есть доступ только у system1 и system2
                    // 'App\\Http\\TestController1@method2' => 'system3',              // а к этому методу только у system3
                    // 'App\\Http\\TestController2' => '*',      // доступ ко всем методам контроллера есть у всех систем
                ],
            ],
        ],
    ],
];
