<?php

declare(strict_types=1);

namespace Nusje2000\FeatureToggleBundle\Exception;

use LogicException;

final class FeatureNotSupported extends LogicException implements Throwable
{
}
