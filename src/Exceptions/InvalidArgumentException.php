<?php declare(strict_types=1);

namespace Payment\Exceptions;

use GuzzleHttp\Exception\GuzzleException;

class InvalidArgumentException extends \InvalidArgumentException implements WeChatPayException, GuzzleException
{
}
