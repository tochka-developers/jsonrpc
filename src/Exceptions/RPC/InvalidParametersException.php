<?php

namespace Tochka\JsonRpc\Exceptions\RPC;

use Tochka\JsonRpc\Exceptions\JsonRpcException;
use Illuminate\Support\MessageBag;

/**
 * Class WebServiceException
 * @package App\Exceptions
 */
class InvalidParametersException extends JsonRpcException
{
    /**
     * InvalidParametersException constructor.
     * @param MessageBag $messageBag
     * @param \Exception|null $previous
     */
    public function __construct(MessageBag $messageBag, \Exception $previous = null)
    {
        $error = [];

        foreach ($messageBag->toArray() as $code => $message) {
            $error[] = [
                'code' => $code,
                'message' => reset($message)
            ];
        }

        parent::__construct(self::CODE_INVALID_PARAMETERS, null, $error, $previous);
    }
}