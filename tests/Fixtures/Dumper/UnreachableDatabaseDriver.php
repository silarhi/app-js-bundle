<?php

declare(strict_types=1);

/*
 * This file is part of the App JS Bundle package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\AppJsBundle\Tests\Fixtures\Dumper;

use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use LogicException;

/**
 * A driver that fails to connect: the dump must never read the database.
 */
final class UnreachableDatabaseDriver extends AbstractDriverMiddleware
{
    public function __construct()
    {
        parent::__construct(new Driver());
    }

    public function connect(array $params): never
    {
        throw new LogicException('The dump connected to the database.');
    }
}
