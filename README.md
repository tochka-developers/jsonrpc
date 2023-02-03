# JSON-RPC Server (Laravel/Lumen)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=alert_status)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=bugs)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=code_smells)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=coverage)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)

# Описание
JsonRpc сервер - реализация сервера по спецификации JsonRpc 2.0.

Поддерживаемые версии:
* Laravel >= 9.0
* PHP 8.0 | 8.1 | 8.2

Поддерживает:
* вызов удаленных методов по нотификации имяКонтроллера_имяМетода, либо с разделением логики на несколько ресурсных 
точек входа
* вызов нескольких удаленных методов в одном запросе
* передача параметров в метод контроллера по имени
* маппинг параметров в DTO
* аутентификация с помощью токена, переданного в заголовке
* контроль доступа по IP-адресам
* контроль доступа к методам для разных сервисов - ACL
* возможность настройки нескольких точек входа с разными настройками JsonRpc-сервера
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
\Route::post('/api/v1/public/jsonrpc', function (\Illuminate\Http\Request $request) {
    return \Tochka\JsonRpc\Facades\JsonRpcServer::handle($request->getContent());
});
```
Если планируется передавать имя контроллера в адресе, после точки входа, роут должен быть следующего вида:
```php
Route::post('/api/v1/jsonrpc/{group}[/{action}]', function (Illuminate\Http\Request $request, $group, $action = null) {
    return \Tochka\JsonRpc\Facades\JsonRpcServer::handle($request->getContent(), 'default', $group, $action);
});
```

# Конфигурация

```php
return [
    // можно настроить несколько разных конфигурация для разных точек входа
    // чтобы указать в роуте, какая именно конфигурация должна быть использована - передавайте ключ конфига вторым 
    // параметром в \Tochka\JsonRpc\Facades\JsonRpcServer::handle
    'default' => [
        // корневой путь к точке входа (т.е. без указания group и action, если они используются)
        // необходим для корректного формирования и кеширования списка роутов
        'endpoint' => '/api/v1/public/jsonrpc',
        
        // Тип формирования точки входа (получать или нет из конечного URI группу методов и метод
        'dynamicEndpoint' => ServerConfig::DYNAMIC_ENDPOINT_NONE,
    
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
        
        // список Middleware, обрабатывающих запросы
        // описание middleware ниже
        'middleware' => [ //
            Tochka\JsonRpc\Middleware\LogMiddleware::class               => [
                // Канал лога, в который будут записываться все логи
                'channel' => 'default',
            ],
             Tochka\JsonRpc\Middleware\TokenAuthMiddlewareInterface::class         => [
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
# Dynamic Endpoint
Позволяет получать из конечного URI группу методов и сам метод.

Настраивается с помощью параметра `dynamicEndpoint` в конфигурации.

Все константы для настройки параметра находятся в классе `\Tochka\JsonRpc\Support\ServerConfig`
Далее перечислены возможные значения этого параметра

#### ServerConfig::DYNAMIC_ENDPOINT_NONE
Точка входа статична, все контроллеры располагаются в одном пространстве имен

Пример:
* uri: `/api/v1/public/jsonrpc`
* jsonrpc method: `test_ping`
* controller@method: `\Default\Controller\Namespace\TestController@ping`

#### ServerConfig::DYNAMIC_ENDPOINT_CONTROLLER_NAMESPACE
Все, что отличается в URI от указанной точки входа - является постфиксом к пространству имен контроллеров (group).

Пример:
* uri: `/api/v1/public/jsonrpc/foo/bar`
* jsonrpc method: `test_ping`
* controller@method: `\Default\Controller\Namespace\Foo\Bar\TestController@ping`

#### ServerConfig::DYNAMIC_ENDPOINT_FULL_CONTROLLER_NAME
Последний элемент URI является именем контроллера (action), предыдущие элементы до указанной точки входа - постфикс 
к пространству имен контроллеров (group).

Пример:
* uri: `/api/v1/public/jsonrpc/foo/bar`
* jsonrpc method: `test_ping`
* controller@method: `\Default\Controller\Namespace\Foo\BarController@test_ping`

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

Для вывода всех доступных маршрутов вы можете воспользоваться командой artisan `jsonrpc:route:list`:
```
> php artisan jsonrpc:route:list
+---------+--------------+-----------------------------+----------------------------------------------------------------+
| Server  | Group:Action | JsonRpc Method              | Controller@Method                                              |
+---------+--------------+-----------------------------+----------------------------------------------------------------+
| default | @:@          | check_arrearsOfTaxes        | App\Http\Controllers\Api\CheckController@arrearsOfTaxes        |
| default | @:@          | check_bankrupt              | App\Http\Controllers\Api\CheckController@bankrupt              |
| default | @:@          | check_crime                 | App\Http\Controllers\Api\CheckController@crime                 |
+---------+--------------+-----------------------------+----------------------------------------------------------------+
```

При каждом входящем запросе схема маршрутов заново пересчитывается. Чтобы этого не происходило, необходимо закешировать 
все маршруты с помощью команды `jsonrpc:route:cache`. Построенная схема сохранится в файл и будет использоваться каждый 
раз при следующих запросах. 

Учтите это при разработке - пока есть построенная закешированная схема маршрутов - любые 
изменения маршрутов в коде не будут отражаться на выполнении запросов. 

Рекомендуется выполнять команду кеширования маршрутов сразу после деплоя на рабочие инстансы (по аналогии с командой 
Laravel `config:cache`).

Чтобы очистить кеш - выполните команду `jsonrpc:route:clear`. После очистки кеша маршруты снова будут строиться каждый 
раз при входящем запросе.

Также вы можете динамически добавлять новые маршруты в список:

```php
$route = new \Tochka\JsonRpc\DTO\JsonRpcRoute('default', 'my_dynamic_method');
$route->controllerClass = MyController::class;
$route->controllerMethod = 'methodName';

\Tochka\JsonRpc\Facades\JsonRpcRouter::add($route);
```

# Маппинг параметров в DTO и получение полного запроса
По умолчанию все параметры из JsonRpc-запроса прокидываются в параметры метода контроллера по их имени. 
При этом рекомендуется использовать типизацию параметров для того, чтобы JsonRpc-сервер мог отвалидировать эти параметры
на входе и ответить ошибкой валидации в случае несовпадения типов.

Если в качестве типа параметра будет указан какой-либо класс - JsonRpc-сервер попытается создать экземпляр этого класса
и присвоить всем публичным полям значения аналогичных полей из объекта запроса. При этом также будут работать правила
валидации типов.

Также вы можете в качестве типа использовать array. Если при этом в PhpDoc указать конкретный тип элементов внутри
массива - то JsonRpc-сервер также попытается привести все элемента массива в запросе к указанному типу (в том числе, 
если таким типом будет другой класс).

Стоит учесть, что JsonRpc-сервер не приводит типы из запроса к указанным типам в понимании приведения типов в PHP.
Т.е. если в запросе передать значение с типом int, а в параметре будет указан тип string - то будет выброшено исключение 
и клиенту вернется ответ с ошибкой. Приведением в текущем случае мы называем попытка JsonRpc-сервера правильно 
"наложить" свойства объекта из JsonRpc-запроса на поля конкретного класса.

Кроме всего, вы можете указать JsonRpc-серверу на необходимость приведения всего JsonRpc-запроса к объекту, не пытаясь
прокидывать верхний уровень параметров запроса на параметры метода контроллера. Это может быть полезно в случае, если 
в запросе очень много параметров верхнего уровня, либо если один и тот же набор параметров используется в нескольких 
методах. Пример использования:
```php
use Tochka\JsonRpc\Annotations\ApiMapRequestToObject;

class ApiController extends BaseController
{
    /**
     * @ApiMapRequestToObject(parameterName="request")
     */
     #[ApiMapRequestToObject('request')]
    public function testMethod(MyDTOForRequest $request): bool
    {
        // ...
    }
}

```
В данном случае мы аннотацией/атрибутом указали, что весь JsonRpc-запрос необходимо привести к классу MyDTOForRequest и 
передать в качестве значения в качестве параметра метода `$request`.

Учтите, что в случае использования аннотации/аттрибута `ApiMapRequestToObject` для метода - во все остальные параметры 
метода больше не будут передаваться значения параметров из JsonRpc-запроса. Вместо этого будет отрабатывать стандартное
получение экземпляров из DI-контейнера Laravel и передача их в качестве параметров.

# Игнорирование публичных методов контроллеров
По умолчанию в схему маршрутизации попадают все публичные методы всех контроллеров, найденных в указанном в конфигурации 
пространстве имен. Если вам необходимо исключить из маршрутизации часть методов - воспользуйтесь аннотацией/атрибутом
`ApiIgnore` и `ApiIgnoreMethod`:
```php
use Tochka\JsonRpc\Annotations\ApiIgnore;
use Tochka\JsonRpc\Annotations\ApiIgnoreMethod;

/**
 * Использование аннотации/атрибута @ApiIgnore для класса исключает все методы класса из маршрутизации
 * @ApiIgnore()
 */
 #[ApiIgnore]
class ApiController extends BaseController
{
    // ...
}

/**
 * Использование аннотации/атрибута @ApiIgnoreMethod для класса исключает указанные методы из маршрутизации
 * @ApiIgnoreMethod(name="methodNameFoo")
 * @ApiIgnoreMethod(name="methodNameBar")
 */
 #[ApiIgnoreMethod('methodNameFoo')]
 #[ApiIgnoreMethod('methodNameBar')]
class ApiController extends BaseController
{
    /**
    * Использование аннотации/атрибута @ApiIgnore для метода исключает указанный метод из маршрутизации
    * @ApiIgnore()
    */
    #[ApiIgnore]
    public function fooMethod()
    {
        // ...
    }
    
    public function barMethod()
    {
        // ...
    }
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

# Обновление с v3 до v4
1. Исправьте версию в вашем composer.json на `"tochka-developers/jsonrpc": "^4.0"` и обновите пакет.

2. Дополните конфигурацию `jsonrpc.php` следующими атрибутами:
* endpoint
* dynamicEndpoint
* summary
* description
* controllerSuffix (по умолчанию: `Controller`)
* methodDelimiter (по умолчанию: `_`)

Описание параметров и возможные значения смотрите выше в разделе описания конфигурации
3. Поправьте скрипты сборки/деплоя prod-версии приложения, добавив в них команду сборки и кеширования маршрутов:
`php artisan jsonrpc:route:cache`
