# JSON-RPC Server (Laravel 5.4-5.5, Lumen 5.4-5.5)
## Описание
JsonRpc сервер - реализация сервера по спецификации JsonRpc 2.0.
Поддерживает:
* вызов удаленных методов по нотификации имяКонтроллера_имяМетода
* вызов нескольких удаленных методов в одном запросе
* передача параметров в метод контроллера по имени, либо в порядке очереди
* аутентификация с помощью токена, переданного в заголовке (отключаемо)
* контроль доступа к методам для разных сервисов - ACL (отключаемо)
* автоматическая генерация SMD-схемы
* возможность настройки нескольких точек входа с разными настройками JsonRpc-сервера
## Установка
### Laravel
1. ``composer require tochka-developers/jsonrpc``
2. Добавьте `Tochka\JsonRpc\JsonRpcServiceProvider` в список сервис-провайдеров в `config/app.php`:
```php
'providers' => [
    //...
    \Tochka\JsonRpc\JsonRpcServiceProvider::class,
],
```
3. Опубликуйте конфигурацию:  
```
php artisan vendor:publish
```
### Lumen
1. ``composer require tochka-developers/jsonrpc``
2. Зарегистрируйте сервис-провайдер `Tochka\JsonRpc\JsonRpcServiceProvider` в `bootstrap/app.php`:
```php
$app->register(Tochka\JsonRpc\JsonRpcServiceProvider::class);
```
3. Скопируйте конфигурацию из пакета (`vendor/tochka-developers/jsonrpc/config/jsonrpc.php`) в проект (`config/jsonrpc.php`)
4. Подключите конфигурацию в `bootstrap/app.php`:
```php
$app->configure('jsonrpc');
```
5. Включите поддержку фасадов в `bootstrap/app.php`:
```php
$app->withFacades();
```

## Ручная настройка точек входа
При ручной найтройке вы сами контролируете процесс роутинга. 
Пропишите в вашем route.php:
### Laravel
```php
Route::post('/api/v1/jsonrpc', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server) {
    return $server->handle($request);
});
```
### Lumen 5.4
```php
$app->post('/api/v1/jsonrpc', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server) {
    return $server->handle($request);
});
```
### Lumen 5.5
```php
$router->post('/api/v1/jsonrpc', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server) {
    return $server->handle($request);
});
```

Если планируется передавать имя контроллера в адресе, после точки входа, роутинги дожны быть следующего вида:
### Laravel
```php
Route::post('/api/v1/jsonrpc/{endpoint}[/{action}]', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server, $endpoint, $action = null) {
    return $server->handle($request, ['endpoint' => $endpoint, 'action' => $action]);
});
```
### Lumen 5.4
```php
$app->post('/api/v1/jsonrpc/{endpoint}[/{action}]', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server, $endpoint, $action = null) {
    return $server->handle($request, ['endpoint' => $endpoint, 'action' => $action]);
});
```
### Lumen 5.5
```php
$router->post('/api/v1/jsonrpc/{endpoint}[/{action}]', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server, $endpoint, $action = null) {
    return $server->handle($request, ['endpoint' => $endpoint, 'action' => $action]);
});
```
Для установки уникальных параметров сервера необходимо передать массив с параметрами в метод `handle`:
```php
return $server->handle($request, $options);
```
### Описание массива $options
* `uri` ('/api/v1/jsonrpc') - точка входа
* `namespace` ('App\\Http\\Controllers\\') - Namespace для контроллеров. 
Если не указан - берется значение `jsonrpc.controllerNamespace`
* `controller` ('Api') - контроллер по умолчанию (для методов без указания контроллера - имяМетода). 
Если не указано - берется значение `jsonrpc.defaultController`
* `postfix` ('Controller') - суффикс контроллеров (для метода foo_bar будет выбран контроллер fooController). 
Если не указано - берется значение `jsonrpc.controllerPostfix`
* `middleware` (array) - список обработчиков запроса. 
В списке обработчиков обязательно должен быть `\Tochka\JsonRpc\Middleware\MethodClosureMiddleware::class`, 
данный обработчик отвечате за выбор контроллера и метода. 
Если список не указан - берется значение `jsonrpc.middleware`
* `description` ('JsonRpc server') - описание сервиса. Возвращается в SMD-схеме.
Если не указано - берется значение `jsonrpc.description`
* `auth` (true) - стандартная авторизация по токены в заголовке.
Если не указано - берется значение `jsonrpc.authValidate`
* `acl` (array) - список контроля доступа к методам. Заполняется в виде `имяМетода => [serviceName1, serviceName2]`.
Игнорируется, если не включен обработчик `AccessControlListMiddleware`.
Если не указано - берется значение `jsonrpc.acl`
* `endpoint` (string) - Имя контроллера содержащий вызываемый метод.
* `action` (string) - Суффикс к имени контроллера.

## Автоматический роутинг
Данный метод более удобен. Для роутинга достаточно перечислить точки входа в параметре `jsonrpc.routes`.
```php
[
    '/api/v1/jsonrpc',                  // для этой точки входа будут использованы глобальные настройки
    'v2' => [                          // для этой точки входа задаются свои настройки. Если какой-то из параметров не указан - используется глобальный
        'uri' => '/api/v1/jsonrpc',                       // URI (обязательный)
        'namespace' => 'App\\Http\\Controllers\\V2\\',   // Namespace для контроллеров
        'controller' => 'Api',                           // контроллер по умолчанию
        'postfix' => 'Controller',                       // суффикс для имен контроллеров
        'middleware' => [],                              // список обработчиков запросов
        'auth' => true,                                  // аутентификация сервиса
        'acl' => [],                                     // Список контроля доступа
        'description' => 'JsonRpc server V2'             // описание для SMD схемы
    ]
]
```
Каждая точка входа может быть либо строкой с указанием адреса, либо массивом, аналогичном $options.

## Обработчики (Middleware)
Обработчики позволяют подготовить запрос, прежде чем вызывать указанный метод. Список обработчиков задается в параметре 
`jsonrpc.middleware`. Это массив, в котором необходимо перечислить в порядке очереди классы обработчиков.
По умолчанию доступны следующие обработчики:
`ValidateJsonRpcMiddleware`
Валидация запроса на соответствие спецификации JsonRpc2.0. Рекомендуется использовать.
`AccessControlListMiddleware`
Контроль доступа к методам.
`MethodClosureMiddleware`
Обработчик разбирает запрос и находит необходимый контроллер и метод в нем. Данный обработчик обязательно необходимо 
включать в список для работы сервера.
`AssociateParamsMiddleware`
Передача параметров из запроса в метод на основе имен.

Кроме того, вы можете использовать свои обработчики. 
Для этого просто реализуйте интерфейс `\Tochka\JsonRpc\Middleware\BaseMiddleware` и укажите обработчик в списке.

## Аутентификация
Если включена аутентификация (`jqonrpc.authValidate`), то в каждом запросе должен присутствовать заголовок (указанный в `jsonrpc.accessHeaderName`).
Значение заголовка - токен. Список токенов необходимо указать в параметре `jsonrpc.keys`:
```php
[
    'systemName1' => 'secretToken1',
    'systemName2' => 'secretToken2'
]
```
Если запрос был осуществлен без данного заголовка, либо с токеном, которого нет в списке - клиенту вернется ошибка.
Если аутентификация прошла успешно - клиент будет опознан как `systemName1` (`systemName2`), что позволит контролировать доступ к методам.
Если аутентификация отклбючена - клиент будет опознан как `guest`.

## Контроль доступа к методам
Если включен обработчик `AccessControlListMiddleware`, то будет осуществлен контроль доступа к методам.
Для описания доступа необходимо заполнить параметр `jsonrpc.acl`:
```php
[
'foo_bar' => ['systemName1', 'systemName2'],
'bar_foo' => ['*'],
]
```
В указанном примере в методу `foo_bar` будут иметь доступ только клиенты `systemName1` и `systemName2`, а к методу `bar_foo` - все клиенты.

## Валидация параметров
Для валидации входных параметров внутри контроллера можно использовать готовый trait: `Tochka\JsonRpc\Traits\JsonRpcController`
Подключив данный trait в своем контроллере вы сможете проверить данные с помощью средств валидации Laravel:
```php
public function store($title, $body)
{
    $validatedData = $this->validate([
        'title' => 'required|unique:posts|max:255',
        'body' => 'required',
    ]);

    // The blog post is valid...
}
```

## Скрытие конфиденциальной информации в логах системы

Для того, чтобы убрать конфиденциальную информацию (логины, пароли, токены и пр.) из логов системы нужно в контроллере перепределить массив $hideDataLog.
```php
public $hideDataLog = [
    type => [
        'method' => [key|bindName]
    ],
    type => [
        'method' => [key|bindName]
    ],
];
```
*type* - определяет где скрываем данные. Имеет 4 значения:
LogHelper::TYPE_REQUEST - убрать данные из HTTP запроса
LogHelper::TYPE_SQL - убрать данные из SQL запроса
LogHelper::TYPE_EXCEPTION - убрать данные при вызове ошибки
LogHelper::TYPE_RESPONSE - убрать данные из HTTP ответа

*method* - метод контроллера

*[key|bindName]* - определяет, что именно нужно скрыть при вызове вышеуказанного метода.
Для LogHelper::TYPE_SQL нужно перечислить названия меток в sql запросе.
Для остальных номера входных переменных (начиная в нуля)

```php
class TestController extends ApiController
{

    public $hideDataLog = [
        LogHelper::TYPE_REQUEST => [
            'm1' => [0, 2],
            'm2' => [0]
        ],
        LogHelper::TYPE_SQL => [
            'm2' => ['bindName1', 'bindName2']
        ],
        LogHelper::TYPE_EXCEPTION => [
            'm1' => [0, 1],
            'm2' => [0]
        ],
        LogHelper::TYPE_RESPONSE => [
            'm1' => [4],
            'm2' => [0]
        ]
    ];

    public function m1($p0, $p1, $p2, $p3, $p4){}
    
    public function m2($p0){}
}   
```

## Как это работает
Клиент послает валидный JsonRpc2.0-запрос:
```json
{
  "jsonrpc": "2.0", 
  "method": "client_getInfoById",
  "params": {
    "clientCode": "100500",
    "fromAgent" : true
  },
  "id": 15
 }
```
JsonRpc сервер пытается найти указанный метод `client_getInfoById`.
Имя метода разбивается на части: `имяКонтроллера_имяМетода`.
Класс контроллера ищется по указанному пространству имен (параметр `jsonrpc.controllerNamespace`) с указанным суффиксом (по умолчанию `Controller`).
Для нашего примера сервер попытается подключить класс 'App\Http\Controller\ClientController'.
Если контроллер не существует - клиенту вернется ошибка `Method not found`.
В найденном контроллере вызывается метод `getInfoById`.
Далее возможно два варианта. 

Если подключен обработчик `AssociateParamsMiddleware`, то все переданные параметры будут переданы в метод по именам.
То есть в контроллере должен быть метод `getInfoById($clientCode, $fromAgent)`. 
Все параметры будут отвалидированы по типам (если типы указаны). Кроме того, таким способом можно указывать необязательные 
параметры в методе - в таком случае их необязательно передавать в запросе, вместо непереданных параметров будут 
использованы значения по умолчанию из метода.
Если же не будет передан один из обязательных параметров - клиенту вернется ошибка.

Если обработчик `AssociateParamsMiddleware` не подключен - то все параметры из запроса будут переданы в метод по порядку.
В таком случае указанному запросу аналогичен следующий:
```json
{
  "jsonrpc": "2.0", 
  "method": "client_getInfoById",
  "params": ["100500", true],
  "id": 15
 }
```

Если настроено получение имени контроллера из точки входа то логика следующая:
Клиент послает валидный JsonRpc2.0-запрос:
```json
{
  "jsonrpc": "2.0", 
  "method": "getInfoById",
  "params": {
    "clientCode": "100500",
    "fromAgent" : true
  },
  "id": 15
 }
```
На адрес `\client`.
JsonRpc сервер пытается найти указанный метод `getInfoById`.
В контроллере: `Сlient`. Если запрос идет на адрес `\client\action` то имя контроллера будет иметь вид `СlientAction`.
Класс контроллера ищется по указанному пространству имен (параметр `jsonrpc.controllerNamespace`) с указанным суффиксом (по умолчанию `Controller`).
Для нашего примера сервер попытается подключить класс 'App\Http\Controller\ClientController' или 'App\Http\Controller\СlientActionController' соответственно.
Если контроллер не существует - клиенту вернется ошибка `Method not found`.
В найденном контроллере вызывается метод `getInfoById`.


## Несколько вызовов в одном запросе
По спецификации JsonRpc разрешено вызывать несколько методов в одном запросе. Для этого необходимо валидные JsonRpc2.0-вызовы 
передать в виде массива. Каждый вызываемый метод будет вызван из соответствующего контроллера, а вернувшиеся результаты
будут возвращены клиенту в том порядке, в котором пришли запросы. 

В ответе клиенту всегда присутствует параметр id, если таковой был передан клиентом.
Данный параметр также позволяет идентифицировать ответы на свои запросы на стороне клиента.

## SMD-схема
Если на настроенную точку входу сделать запрос с параметром `?smd`, то сервер проигнорирует запрос и вернет полную
SMD-схему для указанной точки входа. SMD-схема строится автоматически на основании настроек указанной точки входа.
Список методов формируется исходя из доступных контроллеров в указанном для точки входа пространстве имен.
В SMD-схеме кроме стандартных описаний присутствуют дополнительные параметры, которые позволяют более точно сгенерировать 
JsonRpc-клиента и документацию по серверу.

## Дополнительное описание для SMD-схемы
SMD-схема генерируется на основе доступных публичных методов в доступных в указанном пространстве имен контроллеров.
По умолчанию этой информации достаточно для генерации на основе схемы прокси-клиента. Но для генерации понятной и полной 
документации также можно использовать расширенное описание контроллеров и методов в блоках `PhpDoc`.

### Описание группы методов
По умолчанию в качестве названия группы методов используется описание класса из PhpDoc:
```php
/**
 * Методы для работы с чем-то
 */
class SomeController {
```
Также название можно указать с помощью тега `@apiGroupName`. В таком случае само описание класса будет проигнорировано:
```php
/**
 * Class DossierController
 * @package App\Http\Controllers\Api
 *
 * @apiGroupName
 * Методы для работы с чем-то
 */
class SomeController {
```

### Доступные методы
По умолчанию генератор SMD-схемы собирает все доступные публичные методы из контроллера. Если необходимо скрыть из 
схемы какие-либо методы, можно воспользоваться тегом `@apiIgnoreMethod`:
```php
/**
 * Class DossierController
 * @package App\Http\Controllers\Api
 *
 * @apiGroupName
 * Методы для работы с чем-то
 * @apiIgnoreMethod someMethod
 * @apiIgnoreMethod otherMethod
 */
class SomeController {
```

### Описание метода
```php
/**
 * Основное описание метода.
 * @apiName Название метода. Если не указан этот тег - название метода формируется автоматически по правилам
 * @apiDescription
 * Описание метода. Если не указан - то берется основное описание метода.
 * @apiNote Замечание к методу
 * @apiWarning Предупреждение к методу
 *
 * @param string $param1 Описание параметра 1
 * @param int $param2 Описание параметра 2
 * @param string $param3 Описание параметра 3
 * @return bool Описание возвращаемого ответа
 * 
 * @apiRequestExample
 * Пример запроса
 * @apiResponseExample
 * Пример ответа
 */
```
