<?php

namespace Tochka\JsonRpc\Middleware;


use Illuminate\Support\Facades\Validator;
use Tochka\JsonRpc\Exceptions\RPC\InvalidParametersException;
use Tochka\JsonRpc\Helpers\ArrayHelper;
use Tochka\JsonRpc\Support\JsonRpcRequest;

class RequestParamsValidation
{
    /**
     * @throws InvalidParametersException
     */
    public function handle(JsonRpcRequest $request, callable $next)
    {
        $routeValidation = $request->getRoute()?->getValidation();
        if (count($routeValidation->rules) > 0) {
            $validator = Validator::make(
                ArrayHelper::fromObject($request->params),
                $routeValidation->rules,
                $routeValidation->messages,
                $routeValidation->attributes
            );
            $errorsBag = $validator->errors();
            
            if ($errorsBag->any()) {
                throw new InvalidParametersException($errorsBag);
            }
        }
        
        return $next($request);
    }
}
