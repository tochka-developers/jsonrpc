# JSON-RPC Server (Laravel/Lumen)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=alert_status)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=bugs)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=code_smells)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=tochka-developers_jsonrpc&metric=coverage)](https://sonarcloud.io/dashboard?id=tochka-developers_jsonrpc)

## Описание
JsonRpc сервер - реализация сервера по спецификации JsonRpc 2.0.

Поддерживаемые версии:
* Lumen >= 5.7
* Laravel >= 5.7 

Поддерживает:
* вызов удаленных методов по нотификации имяКонтроллера_имяМетода
* вызов нескольких удаленных методов в одном запросе
* передача параметров в метод контроллера по имени
* аутентификация с помощью токена, переданного в заголовке
* контроль доступа по IP-адресам
* контроль доступа к методам для разных сервисов - ACL
* возможность настройки нескольких точек входа с разными настройками JsonRpc-сервера

## Установка
Установка через composer:
```shell script
composer require tochka-developers/jsonrpc
```
### Laravel
Для Laravel есть возможность опубликовать конфигурацию для всех пакетов:  
```shell script
php artisan vendor:publish
```

Для того, чтобы опубликовать только конфигурацию данного пакета, можно воспользоваться опцией tag
```shell script
php artisan vendor:publish --tag="jsonrpc-config"
```

### Lumen
В Lumen отсутствует команда _vendor:publish_, поэтому делается это вручную. 
Если в проекте еще нет директории для конфигураций - создайте ее:
```shell script
mkdir config
```
Скопируйте в нее конфигрурацию jsonrpc:
```shell script
cp vendor/tochka-developers/jsonrpc/config/jsonrpc.php config/jsonrpc.php
```
Вместо _config/jsonrpc.php_ нужно указать любую другую директорию, где хранятся ваши конфиги и название будущего конфига.
Далее необходимо прописать скопированный конфиг в _bootstrap/app.php_
```php
$app->configure('jsonrpc');
```
Так же прописать провайдер:
```php
$app->register(\Tochka\JsonRpc\JsonRpcServiceProvider::class);
```
Где _jsonrpc_ - имя файла конфига

Для корректной работы так же необходимы фасады:
```php
$app->withFacades();
```
## Настройка точек входа
Пропишите в вашем route.php:
```php
\Route::post('/api/v1/public/jsonrpc', function (\Illuminate\Http\Request $request) {
    return \Tochka\JsonRpc\Facades\JsonRpcServer::handle($request->getContent());
});
```
Если планируется передавать имя контроллера в адресе, после точки входа, роутинги дожны быть следующего вида:
```php
Route::post('/api/v1/jsonrpc/{group}[/{action}]', function (Illuminate\Http\Request $request, $group, $action = null) {
    return \Tochka\JsonRpc\Facades\JsonRpcServer::handle($request->getContent(), 'default', $group, $action);
});
```

## Конфигурация
```php
return [
    // можно настроить несколько разных конфигурация для разных точек входа
    // чтобы указать в роутинге, какая именно конфигурация должна быть использована - передавайте ключ конфига вторым 
    // параметром в \Tochka\JsonRpc\Facades\JsonRpcServer::handle
    'default' => [ 
        // namespace, в котором располагаются контроллеры, обрабатывающие запросы
        'namespace'  => 'App\Http\Controllers\Api', 
        // список Middleware, обрабатывающих запросы
        // описание middleware ниже
        'middleware' => [ //
            Tochka\JsonRpc\Middleware\LogMiddleware::class               => [
                // Канал лога, в который будут записываться все логи
                'channel' => 'default',
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

## Обработчики (Middleware)
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

## Аутентификация по токену (TokenAuthMiddleware)
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

## Валидация сервиса по IP (ServiceValidationMiddleware)
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

## Контроль доступа к методам (AccessControlListMiddleware)
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

## Логирование - LogMiddleware
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
Кроме того, в указанном trait доступны следующие методы:
```php
/**
 * Возвращает массив с переданными в запросе параметрами
 */
protected function getArrayRequest(): array;

/**
 * Возвращает экземпляр класса с текущим запросом
 */
protected function getRequest(): JsonRpcRequest;

/**
 * Валидация переданных в контроллер параметров
 *
 * @param array $rules Правила валидации
 * @param array $messages Сообщения об ошибках
 * @param bool  $noException Если true - Exception генерироваться не будет
 *
 * @return bool|MessageBag Прошла валидация или нет
 */
protected function validate($rules, array $messages = [], $noException = false);

 /**
 * Валидирует и фильтрует переданные в контроллер параметры. Возвращает отфильтрованный массив с параметрами
 *
 * @param array $rules Правила валидации
 * @param array $messages Сообщения об ошибках
 * @param bool  $noException Если true - Exception генерироваться не будет
 */
protected function validateAndFilter($rules, array $messages = [], $noException = false): array;
```

## Как это работает
Клиент посылает валидный JsonRpc2.0-запрос:
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

Если настроено получение имени группы и метода из роута, то логика следующая:
Клиент посылает валидный JsonRpc2.0-запрос на адрес `/api/v1/public/jsonrpc/client`:
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
JsonRpc сервер пытается найти указанный метод `getInfoById` в контроллере: `Сlient`. Если запрос идет на 
адрес `\client\action` то имя контроллера будет иметь вид `СlientAction`.
Класс контроллера ищется по указанному пространству имен (параметр `jsonrpc.namespace`) с указанным суффиксом 
(по умолчанию `Controller`). Для нашего примера сервер попытается подключить класс 'App\Http\Controller\ClientController' 
или 'App\Http\Controller\ClientActionController' соответственно.
Если контроллер не существует - клиенту вернется ошибка `Method not found`.
В найденном контроллере вызывается метод `getInfoById`.


## Несколько вызовов в одном запросе
По спецификации JsonRpc разрешено вызывать несколько методов в одном запросе. Для этого необходимо валидные 
JsonRpc2.0-вызовы передать в виде массива. Каждый вызываемый метод будет вызван из соответствующего контроллера, а 
вернувшиеся результаты будут возвращены клиенту в том порядке, в котором пришли запросы. 

В ответе клиенту всегда присутствует параметр id, если таковой был передан клиентом.
Данный параметр также позволяет идентифицировать ответы на свои запросы на стороне клиента.

## SMD-схема
В текущей версии (>=3.0) формирование SMD схемы было полностью убрано. Планируется развитие описания методов 
сервера с помощью OpenRPC.
