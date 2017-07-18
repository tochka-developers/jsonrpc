# JSON-RPC Server for Laravel 5 (Lumen compatible)
## Quick How-To
1. Install with composer
2. Add 'Tochka\JsonRpc\JsonRpcServiceProvider' to the service providers list
3. Publish config. 
For Laravel:
```
php artisan vendor:publish
```
For Lumen:
Copy `config/jsonrpc.php` from package to `config/jsonrpc.php` in project
4. Just for Lumen. Enable facades in app. In file `bootstrap/app.php` add:
```php
$app->withFacades();
```
5. Configure (`config/jsonrpc.php`) as you need
6. Use JsonRpcServer in your routes.php:
For Laravel:
```php
Route::any('/v1/public/jsonrpc', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server) {
    return $server->handle($request);
});
```
For Lumen:
```php
$app->post('/api/v1/jsonrpc', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server) {
    return $server->handle($request);
});
```
## Controllers naming rules and routing logic
Controllers namespace is `App\Http\Controllers\App\` by default. 
So, your controllers should be placed in `app/Http/Controllers/App` folder. 
Controllers namespace may be changed in your `config/jsonrpc.php` config file.
Controllers should have `Controller` suffix(for example `ClientController`). 
Imagine that `ClientController` has `getInfoById()` method with param `$clientCode`, your request could be like:
```
{"jsonrpc":"2.0", "method":"client_getInfoById","id":15,"params":{"clientCode":"100500"}}
```