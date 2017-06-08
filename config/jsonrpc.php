<?php

/**
 * Настройки JsonRpc
 */
return [

    /**
     * Пространство имен для контроллеров
     */
    'controllerNamespace' => 'App\\Http\\Controllers\\Api\\',

    /**
     * Аутентификация сервиса по ключу
     */
    'authValidate' => true,

    /**
     * Заголовок идентификации сервиса
     */
    'accessHeaderName' => 'X-Tochka-Access-Key',

    /**
     * Обработчики запросов
     */
    'middleware' => [
        \Tochka\JsonRpc\Middleware\ValidateJsonRpcMiddleware::class,    // валидация на стандарты JsonRPC
        \Tochka\JsonRpc\Middleware\AccessControlListMiddleware::class,  // проверка доступа системы к методу
        \Tochka\JsonRpc\Middleware\MethodClosureMiddleware::class,      // возвращает контроллер и метод
        \Tochka\JsonRpc\Middleware\AssociateParamsMiddleware::class,    // ассоциативные параметры
    ],

    /**
     * Ключи доступа к API
     */
    'keys' => [],

    /**
     * Настройки логгирования
     */
    'log_max_files' => 20,
    'log_path' => 'logs/jsonrpc/activity.log',

    /**
     * Список контроля доступа
     * Ключи массива - методы, значения - массив с наименованием сервисов, которые имеют доступ к указанному методу
     */
    'acl' => [],

];