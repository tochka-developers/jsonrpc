<?php

namespace Tochka\JsonRpc\Exceptions;

use Exception;

/**
 * Class WebServiceException
 * @package App\Exceptions
 */
class JsonRpcException extends Exception
{
    public const CODE_PARSE_ERROR = -32700;
    public const CODE_INVALID_REQUEST = -32600;
    public const CODE_METHOD_NOT_FOUND = -32601;
    public const CODE_INVALID_PARAMS = -32602;
    public const CODE_INTERNAL_ERROR = -32603;
    public const CODE_INVALID_PARAMETERS = 6000;
    public const CODE_VALIDATION_ERROR = 6001;
    public const CODE_UNAUTHORIZED = 7000;
    public const CODE_FORBIDDEN = 7001;
    public const CODE_EXTERNAL_INTEGRATION_ERROR = 8000;
    public const CODE_INTERNAL_INTEGRATION_ERROR = 8001;

    public $jsonrpc_messages = [
        self::CODE_PARSE_ERROR                => 'Parse error',
        self::CODE_INVALID_REQUEST            => 'Invalid Request',
        self::CODE_METHOD_NOT_FOUND           => 'Method not found',
        self::CODE_INVALID_PARAMS             => 'Invalid params',
        self::CODE_INTERNAL_ERROR             => 'Internal error',
        self::CODE_INVALID_PARAMETERS         => 'Invalid parameters',
        self::CODE_VALIDATION_ERROR           => 'Validation error',
        self::CODE_UNAUTHORIZED               => 'Unauthorized',
        self::CODE_FORBIDDEN                  => 'Forbidden',
        self::CODE_EXTERNAL_INTEGRATION_ERROR => 'External integration error',
        self::CODE_INTERNAL_INTEGRATION_ERROR => 'Internal integration error',
    ];

    protected $data;

    public function __construct($code = 0, $message = null, $data = null, Exception $previous = null)
    {
        if ($message === null && !empty($this->jsonrpc_messages[$code])) {
            $message = $this->jsonrpc_messages[$code];
        }

        $this->data = $data;

        parent::__construct($message, $code, $previous);
    }

    public function getData()
    {
        return $this->data;
    }

    public function __toString()
    {
        return $this->getMessage();
    }

}