<?php

/**
 * Настройки JsonRpc
 */
return [

    /**
     * Пространство имен для контроллеров по умолчанию
     */
    'controllerNamespace' => 'App\\Http\\Controllers\\',

    /**
     * Суффикс для имен контроллеров по умолчанию
     */
    'controllerPostfix' => 'Controller',

    /**
     * Контроллер по умолчанию для методов без имени сервиса (ping)
     */
    'defaultController' => 'Api',

    /**
     * Аутентификация сервиса по ключу
     */
    'authValidate' => false,

    /**
     * Заголовок идентификации сервиса
     */
    'accessHeaderName' => 'X-Tochka-Access-Key',

    /**
     * Обработчики запросов
     */
    'middleware' => [
        \Tochka\JsonRpc\Middleware\ValidateJsonRpcMiddleware::class,     // валидация на стандарты JsonRPC
        //\Tochka\JsonRpc\Middleware\ServiceValidationMiddleware::class,   // проверка возможности авторизации под указанным сервисом
        //\Tochka\JsonRpc\Middleware\AccessControlListMiddleware::class,   // проверка доступа системы к методу
        \Tochka\JsonRpc\Middleware\MethodClosureMiddleware::class,       // возвращает контроллер и метод !!REQUIRED!!
        \Tochka\JsonRpc\Middleware\AssociateParamsMiddleware::class,     // ассоциативные параметры
    ],

    /**
     * Ключи доступа к API
     */
    'keys' => [
        'all' => 'TOKEN'
    ],

    /**
     * Разрешенные сервера, которые могут авторизовываться под указанными сервисами
     * Работает только при использовании ServiceValidationMiddleware
     */
    'servers' => [
        //'service1' => ['192.168.0.1', '192.168.1.5'],
        //'service2' => '*',
    ],

    /**
     * Список контроля доступа
     * Ключи массива - методы, значения - массив с наименованием сервисов, которые имеют доступ к указанному методу
     * Работает только при использовании AccessControlListMiddleware
     */
    'acl' => [
        //'App\\Http\\TestController1' => [
        //  '*' => '*'                              // доступ ко всем методам контроллера по умолчанию есть у всех систем
        //  'method1' => ['system1', 'system2'],    // но к этому методу есть доступ только у system1 и system2
        //  'method2' => 'system3',                 // а к этому методу только у system3
        //]
        //'App\\Http\\TestController2' => '*',      // доступ ко всем методам контроллера есть у всех систем
    ],

    /**
     * Правила роутинга
     * Позволяет иметь на одном хосте несколько JsonRpc-серверов со своими настройками
     *
     * 1. Можно указать URI, по которому будет доступен сервер. В этом случае берутся глобальные настройки сервера
     * [
     *   '/api/v1/jsonrpc', 'api/v2/jsonrpc
     * ]
     *
     * 2. Можно задать свои настройки для каждой точки входа
     * [
     *   '/api/v1/jsonrpc'                  // для этой точки входа будут использованы глобальные настройки
     *
     *   'v2' => [                          // для этой точки входа задаются свои настройки. Если какой-то из параметров не указан - используется глобальный
     *     'uri' => '/api/v1/jsonrpc,                       // URI (обязательный)
     *     'namespace' => 'App\\Http\\Controllers\\V2\\',   // Namespace для контроллеров
     *     'controller' => 'Api',                           // контроллер по умолчанию
     *     'postfix' => 'Controller',                       // суффикс для имен контроллеров
     *     'middleware' => [],                              // список обработчиков запросов
     *     'auth' => true,                                  // аутентификация сервиса
     *     'acl' => [],                                     // Список контроля доступа
     *     'description' => 'JsonRpc server V2'             // описание для SMD схемы
     *   ]
     * ]
     */
    'routes' => [],

    /**
     * Описание сервиса (для SMD-схемы)
     */
    'description' => 'JsonRpc Server',

    /**
     * Настройки логирования
     */
    'log' => [
        /**
         * Канал лога, в который будут записываться все логи
         */
        'channel' => 'default',

        /**
         * Параметры, которые необходимо скрыть из логов
         */
        //'hideParams' => [
        //    'App\\Http\\TestController1@method' => ['password', 'data.phone_number'],
        //    'App\\Http\\TestController2' => ['password', 'data.phone_number']
        //]
    ]

];
