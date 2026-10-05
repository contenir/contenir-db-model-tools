<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken;

use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * "audit_log", which has no primary key.
 */
#[Table('audit_log')]
final class AuditLog
{
    #[Id]
    public string $message;
}
