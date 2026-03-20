<?php

namespace Tochka\JsonRpc\Tests\TestParams;

enum TestEnumString: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Canceled = 'canceled';
}
