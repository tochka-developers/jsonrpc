<?php

/**
 * Настройки JsonRpc
 */

use Tochka\JsonRpc\Middleware\AccessControlListMiddleware;
use Tochka\JsonRpc\Middleware\LogMiddleware;
use Tochka\JsonRpc\Middleware\ServiceValidationMiddleware;
use Tochka\JsonRpc\Middleware\TokenAuthMiddleware;
use Tochka\JsonRpc\Support\ServerConfig;

return [
    'default' => [
        // Точка входа в указанный сервер
        'endpoint' => '/api/v1/public/jsonrpc',
        
        /**
         * Тип формирования точки входа (получать или нет из конечного URI группу методов и метод
         *
         * ServerConfig::DYNAMIC_ENDPOINT_NONE - точка входа статична, все контроллеры располагаются в одном пространстве имен
         * Пример:
         * uri: /api/v1/public/jsonrpc
         * jsonrpc method: test_ping
         * controller@method: \Default\Controller\Namespace\TestController@ping
         *
         * ServerConfig::DYNAMIC_ENDPOINT_CONTROLLER_NAMESPACE - все, что отличается в URI от указанной точки входа -
         * является постфиксом к пространству имен контроллеров (group).
         * Пример:
         * uri: /api/v1/public/jsonrpc/foo/bar
         * jsonrpc method: test_ping
         * controller@method: \Default\Controller\Namespace\Foo\Bar\TestController@ping
         *
         * ServerConfig::DYNAMIC_ENDPOINT_FULL_CONTROLLER_NAME - последний элемент URI является именем контроллера (action),
         * предыдущие элементы до указанной точки входа - постфикс к пространству имен контроллеров.
         * Пример:
         * uri: /api/v1/public/jsonrpc/foo/bar
         * jsonrpc method: test_ping
         * controller@method: \Default\Controller\Namespace\Foo\BarController@test_ping
         */
        'dynamicEndpoint' => ServerConfig::DYNAMIC_ENDPOINT_NONE,
       
        // Краткое описание сервера
        'summary' => 'Основная точка входа',
        
        // Полное описание сервера
        'description' => 'JsonRpc Server',

        // Пространство имен, в котором находятся контроллеры
        'namespace'   => 'App\Http\Controllers',

        // Suffix для контроллеров
        'controllerSuffix' => 'Controller',

        // Разделитель для имен методов
        'methodDelimiter' => '_',
        
        // Использовать методы родителя при наследовании
        'allowParentMethods' => false,

        // Обработчики запросов
        'middleware'  => [
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
                'tokens'     => [
                    'all' => 'TOKEN',
                ],
            ],
            ServiceValidationMiddleware::class => [
                // Разрешенные сервера, которые могут авторизовываться под указанными сервисами
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
