<?php declare(strict_types=1);

namespace Actualize\ActB2bRegistration\Storefront\Route;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class B2bRegisterRoute extends AbstractRegisterRoute
{
    public function __construct(
        private readonly AbstractRegisterRoute $inner,
        private readonly SystemConfigService $systemConfig,
    ) {
    }

    public function getDecorated(): AbstractRegisterRoute
    {
        return $this->inner;
    }

    public function register(
        RequestDataBag $data,
        SalesChannelContext $context,
        bool $validateStorefrontUrl = true,
        ?DataValidationDefinition $additionalValidationDefinitions = null
    ): CustomerResponse {
        $salesChannelId = $context->getSalesChannelId();

        if ($this->systemConfig->getBool('ActB2bRegistration.config.forceBusinessAccount', $salesChannelId)) {
            $data->set('accountType', CustomerEntity::ACCOUNT_TYPE_BUSINESS);
        }

        $mode = $this->systemConfig->getString('ActB2bRegistration.config.accountChoiceMode', $salesChannelId);
        if ($mode === 'forceAccount') {
            $data->set('guest', false);
        } elseif ($mode === 'forceGuest') {
            $data->set('guest', true);
        }

        return $this->inner->register($data, $context, $validateStorefrontUrl, $additionalValidationDefinitions);
    }
}
