<?php

namespace Tochka\JsonRpc\Attributes;

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Alias for field/parameter extraction/hydration. This alias will be used instead of the field/parameter name
 *
 * @psalm-api
 * @Annotation
 * @Target({"METHOD"})
 * @NamedArgumentConstructor
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_METHOD)]
class RequestToDto
{
    public function __construct(
        public readonly ?string $parameterName = null
    ) {
    }
}
