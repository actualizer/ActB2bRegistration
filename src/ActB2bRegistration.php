<?php declare(strict_types=1);

namespace Actualize\ActB2bRegistration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class ActB2bRegistration extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $container = $this->container;
        if ($container === null) {
            return;
        }

        $connection = $container->get(Connection::class);
        if (!$connection instanceof Connection) {
            return;
        }

        $connection->executeStatement(
            "DELETE FROM system_config WHERE configuration_key LIKE 'ActB2bRegistration.config.%'"
        );
    }
}
