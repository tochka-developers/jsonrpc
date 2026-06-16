# JSON-RPC Server (Laravel/Lumen)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=alert_status)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=bugs)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=code_smells)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=coverage)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)

# Описание
JsonRpc сервер - реализация сервера по спецификации JsonRpc 2.0.

Поддерживаемые версии:
* Laravel >= 10.0
* PHP >= 8.2

Поддерживает:
* вызов удаленных методов по нотификации имяКонтроллера_имяМетода, либо с разделением логики на несколько ресурсных 
точек входа
* вызов нескольких удаленных методов в одном запросе
* передача параметров в метод контроллера по имени
* маппинг параметров в DTO
* аутентификация с помощью токена, переданного в заголовке
* контроль доступа по IP-адресам
* контроль доступа к методам для разных сервисов - ACL
* возможность поддержки нескольких серверов в рамках одно сервиса 
* расширение возможностей с помощью механизма Middleware
* кеширование всех роутов для увеличения быстродействия

# Установка
Установка через composer:
```shell script
composer require tochka-developers/jsonrpc
```
### Laravel
Для Laravel есть возможность опубликовать конфигурацию для всех пакетов:  
```shell script
php artisan vendor:publish
```

Для того чтобы опубликовать только конфигурацию данного пакета, можно воспользоваться опцией tag
```shell script
php artisan vendor:publish --tag="jsonrpc-config"
```

# Настройка точек входа
Пропишите в вашем route.php:
```php
\Route::post('/v1/public/jsonrpc', function (Request $request) {
    $server = new JsonRpcServer(ServerConfig::makeFromConfigFile('default'));
    return $server->handle($request->getContent());
});
```

# Конфигурация
```php
return [
    // можно настроить несколько разных конфигурация для разных точек входа,
    // чтобы указать в маршруте, какая именно конфигурация должна быть использована - используйте имя при создании сервера
    'default' => [    
        // Краткое описание сервера
        'summary' => 'Основная точка входа',
        
        // Полное описание сервера
        'description' => 'Основная точка входа',

        // Namespace, в котором находятся контроллеры
        'namespace'   => 'App\\Http\\Controllers\\Api\\',
        
        // Suffix для контроллеров
        'controllerSuffix' => 'Controller',

        // Разделитель для имен методов
        'methodDelimiter' => '_',
        
        // Использовать методы родителя при наследовании
        'allowParentMethods' => false,
        
        // список Middleware, обрабатывающих запросы
        // описание middleware ниже
        'middleware' => [ //
            Tochka\JsonRpc\Middleware\LogMiddleware::class               => [
                // Канал лога, в который будут записываться все логи
                'channel' => 'default',
                // Заголовки которые нужно писать в лог
                'headers' => ['accept', 'encoding']
            ],
             Tochka\JsonRpc\Middleware\TokenAuthMiddleware::class         => [
                 'headerName' => 'X-Tochka-Access-Key',
                 // Ключи доступа к API
                 'tokens'     => [
                     'troll' => env('JSONRPC_KEY_TROLL', ''),
                 ],
             ],
             Tochka\JsonRpc\Middleware\AccessControlListMiddleware::class => [
                'acl' => [
                    '*'                              => '*',
                    FooController::class             => ['service'],
                    FooController::class . '@method' => ['service2'],
                ],
            ],
        ],
    ],
];

```
# Обработчики (Middleware)
Обработчики позволяют подготовить запрос, прежде чем вызывать указанный метод. Список обработчиков задается в параметре 
`jsonrpc.middleware`. Это массив, в котором необходимо перечислить в порядке очереди классы обработчиков.
По умолчанию доступны следующие обработчики:
* `Tochka\JsonRpc\Middleware\LogMiddleware` - логирование входящих запросов
* `Tochka\JsonRpc\Middleware\TokenAuthMiddleware` - авторизация сервиса по токену в заголовке
* `Tochka\JsonRpc\Middleware\ServiceValidationMiddleware` - валидация сервиса по его IP-адресу
* `Tochka\JsonRpc\Middleware\AccessControlListMiddleware` - ACL - правила доступа разных сервисов к разным методам и 
группам Jsonrpc-сервера

Кроме того, вы можете использовать свои Middleware для любых других целей (авторизация через BasicAuth, 
дополнительная фильтрация/валидация данных, etc) 

### Аутентификация по токену (TokenAuthMiddleware)
Необходима, если вы хотите ограничить доступ к сервису и использовать в качестве аутентификации доступ по токену в 
заголовке. В конфигурацию обработчика передаются следующие параметры:
```php
[
    'headerName' => 'X-Tochka-Access-Key',
    'tokens'     => [
        'service_foo' => env('JSONRPC_KEY_SERVICE_FOO', \Str::uuid()->toString()),
        'service_bar' => env('JSONRPC_KEY_SERVICE_BAR', \Str::uuid()->toString()),
]
```
* `headerName` - имя заголовка, в котором клиент должен передать токен
* `tokens` - список сервисов-клиентов и их токены

Если запрос был осуществлен без данного заголовка, либо с токеном, которого нет в списке - клиенту вернется ошибка.
Если аутентификация прошла успешно - клиент будет опознан как `service_foo` (`service_bar`), что позволит 
контролировать доступ к методам. Если аутентификация отключена - клиент будет опознан как `guest`.

### Валидация сервиса по IP (ServiceValidationMiddleware)
Подключайте, если необходимо ограничить список IP-адресов, с которых сервис-клиент может осуществлять запросы.
В конфигурацию обработчика передаются следующие параметры:
```php
[
    'servers' => [
        'service_foo' => ['192.168.0.1', '192.168.1.5'],
        'service_bar' => '*',
    ],
]
```
В указанном примере авторизоваться с ключом доступа сервиса `service_foo` могут только клиенты с IP адресами 
`192.168.0.1` и `192.168.1.5`. Сервис `service_bar` может авторизоваться с любых IP адресов.

### Контроль доступа к методам (AccessControlListMiddleware)
Если включен обработчик `AccessControlListMiddleware`, то будет осуществлен контроль доступа к методам.
В конфигурацию обработчика передаются следующие параметры:
```php
[
    'acl' => [
        '*'                                           => ['service1', 'service2'],
        'App\Http\TestController'                     => ['service1'],
        'App\Http\TestController@isActivationAllowed' => ['service2'],
    ]
]
```

### Логирование - LogMiddleware
Для логирования входящих запросов используется LogMiddleware. В конфигурацию обработчика передаются следующие параметры:
```php
[
    'channel'    => 'jsonrpc',
    'hideParams' => [
        UserController::class => ['password', 'old_password', 'new_password'],
    ],
]
```
* `channel` - канал логирования. Любой из каналов, описанных в конфигурации `logging.php`
* `hideParams` - скрывает из логирования указанные параметры (в случае конфиденциальных данных). 

Описывается с помощью 
ассоциативного массива, в котором ключами выступает целевой метод, в котором необходимо скрыть параметры, а значения - 
имена параметров, которые необходимо скрыть. В качестве ключей может быть:
* `*` - правило распространяется для всего сервера (для всех контроллеров и методов)
* `App\Http\Controllers\UserController` - правило распространяется для всех методов указанного контроллера
* `App\Http\Controllers\UserController@auth` - правило распространяется для указанного метода в указанном контроллере

# Маршрутизация (роутинг)
Для ускорения поиска нужного метода и правильной просадки параметров пакет генерирует список всех маршрутов с описанием 
этих параметров. 

Для вывода всех доступных маршрутов вы можете воспользоваться командой artisan `jsonrpc:route:list {?server}`:
```
> php artisan jsonrpc:route:list
+---------------------------+-----------------------------------------+----------------------+
| Method                    | Controller                              | Call                 |
+---------------------------+-----------------------------------------+----------------------+
| user_test1                | App\Http\Controllers\Api\UserController | test1                |
| user_test2                | App\Http\Controllers\Api\UserController | test2                |
| user_test4                | App\Http\Controllers\Api\UserController | test4                |
+--------------------------------------------------------------------------------------------+
```

При каждом входящем запросе схема маршрутов заново пересчитывается. Чтобы этого не происходило, необходимо закешировать 
все маршруты с помощью команды `jsonrpc:route:cache {?serverName}`. Построенная схема сохранится в файл и будет использоваться каждый 
раз при следующих запросах. 

Учтите это при разработке - пока есть построенная закешированная схема маршрутов - любые 
изменения маршрутов в коде не будут отражаться на выполнении запросов. 

Рекомендуется выполнять команду кеширования маршрутов сразу после деплоя на рабочие инстансы (по аналогии с командой 
Laravel `config:cache`).

Чтобы очистить кеш - выполните команду `jsonrpc:route:clear {?serverName}`. После очистки кеша маршруты снова будут строиться каждый 
раз при входящем запросе.

# Маппинг параметров
При запросе, сервер берём все параметры указанные в методе и пытается просадить их в метод.
Все параметры которые не указаны в методе будут проигнорированы. 
При создании списка маршрутов для каждого параметра определяется тип и в зависимости от типа
входной параметр будет проверен и подставлен в метод. 

### Тип параметра Mixed
Тип параметра не указан или указан как mixed. Просто подставляем.
```php
/**
* В этом примере два входных параметра, оба могут быть любого типа
*
*/
public function test1($a, mixed $b)
{
    //....
}
```

### Тип параметра Primitive
Тип параметра примитив, так же может быт UnionType из примитивов (int|string|bool)
```php
/**
* Пример простых типов
*
*/
public function test1(int $a, string $b, ?string $c, bool|string|int|float|object $d)
{
    //....
}
```

### Тип параметра Enum
Будет создан экземпляр указанного Enum`a
```php
/**
* Будет создан экземпляр MyEnum
*
*/
public function test1(MyEnum $a)
{
    //....
}
```

###  Тип параметра Object
Параметром является объект который будет создан сервером.
При этом тип должен быть задан явно и не содержать перечисления типов. В таком случае создаётся неоднозначность того что должно быть
создано. Если вам нужно создавать разные объекты в зависимости от какой-то логики, то лучше обернуть это в объект обёртку.
```php
/**
* Тут сервер попытается создать объект указанного класса.
*
*/
public function test1(SomeObject $a)
{
    //....
}
```
```php
/**
* Такое работать не будет
* @deprecated 
*/
public function test1(SomeObject|SomeObject2 $a)
{
    //....
}
```

###  Тип параметра DI
Параметр, который создаётся из DI, при этом от входных параметров он не зависит.
Создать такой параметр можно указав аттрибут ApiDi.
```php
/**
* В этом примере входных параметров у апи нет, но при вызове будет создан AuthManager через контейнер.
*
*/
public function test1(#[ApiDI] AuthManager $b)
{
    //....
}
```

###  Тип параметра RequestObject
В этом варианте все входные параметры будут представлены в виде параметров одного объекта
```php
/**
* Все параметры будут преобразованы в параметры объекта AllParamsRequestObject
*
*/
public function test1(#[ApiParams] AllParamsRequestObject $a)
{
//....
}

class AllParamsRequestObject {
    public string $a;
    public string $b;
    public bool $c;
}
```

### Дополнительные возможности для RequestObject и Object
#### WithValidation
Объекту можно добавить трейт WithValidation, если переопределить методы то можно настроить валидациия для 
данного объекта.
```php

    /** Если метод вернёт пустой массив, то валидация будет проигнорирована */
    public static function rules(): array
    {            
        return [
            'date' => ['nullable', 'date:Y-m-d'],
        ];
    }
    
    /**
     * Можно переопределить сообщения об ошибках
     */
    public static function messages(): array
    {
        return [];
    }
    
    /**
     * Можно переопределить атрибуты передаваемые в Validator   
     */
    public static function attributes(): array
    {
        return [];
    }
```

#### WithDataMap
Объекту можно добавить трейт WithDataMap, если переопределить метод dataMap, то можно управлять
созданием объекта.
```php
    // В случае если трейт подключён, но метод не переопределён, то запустится
    // стандартное создание объекта, как если бы трейт не был указан   
    public static function dataMap(RouteParam $param, array|object $data): self
    {        
        return new self($data)
    }
```

# Игнорирование публичных методов контроллеров
По умолчанию в схему маршрутизации попадают все публичные методы всех контроллеров, найденных в указанном в конфигурации 
пространстве имен. Если вам необходимо исключить из маршрутизации часть методов - воспользуйтесь аннотацией
`ApiIgnore`:
```php

/**
 * Использование аннотации/атрибута @ApiIgnore для класса исключает все методы класса из маршрутизации
 * @ApiIgnore
 */
class ApiController extends BaseController
{
    // ...
}
```

# Валидация данных
Можно проверить все параметры запроса с помощью объекта запроса.
```php
public function testValidation(#[ApiDi] \Tochka\JsonRpc\Support\JsonRpcRequest $request, object $data)
{
    // в validated попадут только те поля для которых указаны правила валидации
    $validated = $request->validate([
        'object.field' => 'int|string',
        'object.field2' => 'required|bool',
    ]);    
}
```
Стандартный метод $request->validate при ошибках валидации выбрасывает InvalidParametersException.
Если по какой-то причине вы хотите обработать ошибки самостоятельно, можно вызвать validateSilent
```php
public function testValidation(#[ApiDi] \Tochka\JsonRpc\Support\JsonRpcRequest $request, object $data)
{
    // В этом случае не будет исключений при провале валидации
    $validated = $request->validateSilent([
        'object.field' => 'int|string',
        'object.field2' => 'required|bool',
    ]);
    // получить список ошибок
    $request->getValidationErrors();
    // получить список полей что не прошли валидацию
    $request->getValidationFailed();
}
```

# Как это работает
Клиент посылает валидный JsonRpc2.0-запрос:
```json
{
    "jsonrpc": "2.0", 
    "method": "client_getInfoById",
    "params": {
        "clientCode": "100500",
        "fromAgent" : true
    },
    "id": "service-ab34f8290cfa367dacb"
 }
```
JsonRpc сервер пытается найти указанный метод `client_getInfoById`.
Имя метода разбивается на части: `имяКонтроллера_имяМетода`.
Класс контроллера ищется по указанному пространству имен (параметр `jsonrpc.controllerNamespace`) с указанным суффиксом 
(по умолчанию `Controller`). Для нашего примера сервер попытается подключить класс 'App\Http\Controller\ClientController'.
Если контроллер не существует - клиенту вернется ошибка `Method not found`.
В найденном контроллере вызывается метод `getInfoById`.

Все переданные параметры будут переданы в метод по именам.
То есть в контроллере должен быть метод `getInfoById($clientCode, $fromAgent)`. 
Все параметры будут отвалидированы по типам (если типы указаны). Кроме того, таким способом можно указывать необязательные 
параметры в методе - в таком случае их необязательно передавать в запросе, вместо непереданных параметров будут 
использованы значения по умолчанию из метода.
Если не будет передан один из обязательных параметров - клиенту вернется ошибка.

# Несколько вызовов в одном запросе
По спецификации JsonRpc разрешено вызывать несколько методов в одном запросе. Для этого необходимо валидные 
JsonRpc2.0-вызовы передать в виде массива. Каждый вызываемый метод будет вызван из соответствующего контроллера, а 
вернувшиеся результаты будут возвращены клиенту в том порядке, в котором пришли запросы. 

В ответе клиенту всегда присутствует параметр id, если таковой был передан клиентом.
Данный параметр также позволяет идентифицировать ответы на свои запросы на стороне клиента.

# Документация JsonRpc
Для документирования JsonRpc используется спецификация OpenRpc (https://spec.open-rpc.org/). 

Для генерации OpenRpc-схемы можете использовать совместимый с текущей версией jsonrpc-server пакет 
[tochka-developers/openrpc](https://github.com/tochka-developers/openrpc)


# Обновление с v4 до v5

- Если будете использовать tochka-developers/openrpc. Перед установкой убедитесь что версия tochka-developers/jsonrpc-client больше чем 3.11 
(в более старых несовместимость с phpdocumentor/reflection-docblock v6)
- Удалить из config/jsonrpc строки endpoint, dynamicEndpoint
- Если используете атрибут #[ApiIgnore], замените на @ApiIgnore (это временное решение для переезда,
в дальнейшем методы апи будут указываться явно через #[ApiMethod])
- Обновляем версию
```
composer r tochka-developers/jsonrpc:^v5.0
// если испльзуете tochka-developers/openrpc, её тоже нужно обновить
composer r tochka-developers/jsonrpc:^v5.0 tochka-developers/openrpc:^v2.0
```
- Замените вызов сервера
```php
// до
Route::post('/api/v1/public/jsonrpc', function (Request $request) {
    return JsonRpcServer::handle($request->getContent(), 'default');
});

// после
Route::post('/api/v1/public/jsonrpc', function (Request $request) {
    $config = ServerConfig::makeFromConfigFile('default');
    $server = new JsonRpcServer($config);
    return $server->handle($request->getContent());
});
```
- Если стоит tochka-developers/openrpc, обновите его конфигурацию

Breaking changes
- убрана зависимость bensampo/laravel-enum, если она вам нужна, поставьте самостоятельно, если этот
тип объект используется как параметр апи, вам нужно сделать свой PropertyCaster
