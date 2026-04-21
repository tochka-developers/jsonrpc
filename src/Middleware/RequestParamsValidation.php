<?php

namespace Tochka\JsonRpc\Middleware;


use Illuminate\Support\Facades\Validator;
use Tochka\JsonRpc\Exceptions\RPC\InvalidParametersException;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class RequestParamsValidation
{
    /**
     * @throws InvalidParametersException
     */
    public function handle(JsonRpcRequest $request, callable $next)
    {
        $rules = $request->getRoute()?->getValidation() ?? [];
        if (count($rules) > 0) {
            $validator = Validator::make((array) $request->params, $rules);
            $errorsBag = $validator->errors();
            
            if ($errorsBag->any()) {
                throw new InvalidParametersException($errorsBag);
            }
        }
        
        return $next($request);
    }
}
