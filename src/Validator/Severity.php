<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

/**
 * How serious a mapping issue is: errors break loading or saving,
 * warnings are likely mistakes that may still work.
 *
 * @api
 */
enum Severity: string
{
    case Error   = 'error';
    case Warning = 'warning';
}
