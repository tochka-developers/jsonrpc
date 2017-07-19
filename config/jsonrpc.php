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
     * Список контроля доступа
     * Ключи массива - методы, значения - массив с наименованием сервисов, которые имеют доступ к указанному методу
     */
    'acl' => [],

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
     * Настройки логгирования
     */
    'log_max_files' => 10,
    'log_path' => 'logs/jsonrpc/activity.log',

];
