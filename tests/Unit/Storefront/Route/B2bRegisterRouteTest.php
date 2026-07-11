<?php declare(strict_types=1);

namespace Actualize\ActB2bRegistration\Tests\Unit\Storefront\Route;

use Actualize\ActB2bRegistration\Storefront\Route\B2bRegisterRoute;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\CustomerResponse;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class B2bRegisterRouteTest extends TestCase
{
    private function context(): SalesChannelContext
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getSalesChannelId')->willReturn('sc-1');

        return $context;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function decorator(array $config, ?RequestDataBag &$captured = null): B2bRegisterRoute
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->method('getBool')->willReturnCallback(
            static fn (string $key): bool => (bool) ($config[$key] ?? false)
        );
        $systemConfig->method('getString')->willReturnCallback(
            static fn (string $key): string => (string) ($config[$key] ?? '')
        );

        $inner = $this->createMock(AbstractRegisterRoute::class);
        $inner->method('register')->willReturnCallback(
            function (RequestDataBag $data) use (&$captured): CustomerResponse {
                $captured = $data;

                return $this->createMock(CustomerResponse::class);
            }
        );

        return new B2bRegisterRoute($inner, $systemConfig);
    }

    public function testForceBusinessAccountSetsAccountType(): void
    {
        $route = $this->decorator(
            ['ActB2bRegistration.config.forceBusinessAccount' => true],
            $captured
        );
        $data = new RequestDataBag(['accountType' => 'private']);

        $route->register($data, $this->context());

        static::assertSame(CustomerEntity::ACCOUNT_TYPE_BUSINESS, $captured->get('accountType'));
    }

    public function testForceAccountModeSetsGuestFalse(): void
    {
        $route = $this->decorator(
            ['ActB2bRegistration.config.accountChoiceMode' => 'forceAccount'],
            $captured
        );
        $data = new RequestDataBag(['guest' => true]);

        $route->register($data, $this->context());

        static::assertFalse($captured->getBoolean('guest'));
    }

    public function testForceGuestModeSetsGuestTrue(): void
    {
        $route = $this->decorator(
            ['ActB2bRegistration.config.accountChoiceMode' => 'forceGuest'],
            $captured
        );
        $data = new RequestDataBag(['guest' => false]);

        $route->register($data, $this->context());

        static::assertTrue($captured->getBoolean('guest'));
    }

    public function testDisabledLeavesDataUntouched(): void
    {
        $route = $this->decorator([], $captured);
        $data = new RequestDataBag(['accountType' => 'private', 'guest' => true]);

        $route->register($data, $this->context());

        static::assertSame('private', $captured->get('accountType'));
        static::assertTrue($captured->getBoolean('guest'));
    }

    public function testUsesSalesChannelIdForConfigAndForwardsExtraArguments(): void
    {
        $systemConfig = $this->createMock(SystemConfigService::class);
        $systemConfig->expects(static::once())
            ->method('getBool')
            ->with('ActB2bRegistration.config.forceBusinessAccount', 'sc-1')
            ->willReturn(false);
        $systemConfig->expects(static::once())
            ->method('getString')
            ->with('ActB2bRegistration.config.accountChoiceMode', 'sc-1')
            ->willReturn('off');

        $definition = new DataValidationDefinition('test-definition');
        $validateStorefrontUrl = false;

        $inner = $this->createMock(AbstractRegisterRoute::class);
        $inner->expects(static::once())
            ->method('register')
            ->with(static::anything(), static::anything(), $validateStorefrontUrl, $definition)
            ->willReturn($this->createMock(CustomerResponse::class));

        $route = new B2bRegisterRoute($inner, $systemConfig);
        $data = new RequestDataBag(['accountType' => 'private']);

        $route->register($data, $this->context(), $validateStorefrontUrl, $definition);
    }
}
