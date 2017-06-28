# JSON-RPC Server for Laravel 5

## Quick How-To

- Install with composer
- Add 'Tochka\JsonRpc\JsonRpcServiceProvider' to the service providers list
- Publish config
```php
php artisan vendor:publish
```
- Configuration (`config/jsonrpc.php`)
- Use JsonRpcServer in your routes.php, like this:
```php
Route::any('/v1/public/jsonrpc', function (Illuminate\Http\Request $request, \Tochka\JsonRpc\JsonRpcServer $server) {
    return $server->handle($request);
});
```