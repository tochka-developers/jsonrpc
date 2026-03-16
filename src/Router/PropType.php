<?php

namespace Tochka\JsonRpc\Router;

enum PropType: string
{
    case DI = 'DI';
    case Enum = 'Enum';
    case Primitive = 'Primitive';
    case Object = 'Object';
    case RequestObject = 'RequestObject';
    case Mixed = 'Mixed';
}
