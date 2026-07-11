# ActB2bRegistration - Shopware Plugin

A Shopware 6 plugin that trims the storefront registration form to B2B: it forces the "company" account type and removes the guest/account choice, so shops that only sell to businesses get a clean, unambiguous sign-up. Every feature is toggleable per sales channel, and enforcement is applied both visually (Twig) and server-side (route decorator), so a manipulated or bot-submitted form cannot bypass it.

## Features

- **Force company account type**: hides the private/company selector, always registers as "company", and shows the company fields (company name, department, VAT ID) as required
- **Remove the guest/account choice**: hides the "create customer account" checkbox in checkout and enforces the outcome — either always create a real account or always order as guest
- Two independent toggles, fully separate — enable one, both, or neither
- **Server-side enforcement** via a `RegisterRoute` decorator: `accountType` and the guest flag are corrected in the request data before registration, independent of what the form submits
- Applies to both `/account/register` and `/checkout/register` (shared registration component)
- Multi-language support (German & English)
- Per-sales-channel configuration
- Compatible with Shopware 6.7.x

## Requirements

- Shopware 6.7.x
- PHP 8.4 or higher

## Installation

### Via Composer (recommended)

```bash
composer require actualize/act-b2b-registration
bin/console plugin:refresh
bin/console plugin:install --activate ActB2bRegistration
bin/console cache:clear
```

### Manual

1. Download or clone this plugin into your `custom/plugins/` directory
2. Install and activate the plugin via CLI:
   ```bash
   bin/console plugin:refresh
   bin/console plugin:install --activate ActB2bRegistration
   bin/console cache:clear
   ```

## Configuration

1. Go to Admin Panel → Settings → System → Plugins
2. Find "Actualize: B2B Registration" and click on the three dots
3. Click "Config" to access plugin settings (settings are saved per sales channel)

### Available Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Force company account type | Hide the private/company selector, always set "company", show the company fields | Disabled |
| Guest/account choice | `Default` (show choice) · `Hide choice – always create account` · `Hide choice – always guest` | Default |

## How it works

1. **Visual layer (Twig)**: `sw_extends` overrides of the registration component hide the account-type selector (using Shopware's native `onlyCompanyRegistration` path) and, in checkout, remove the "create customer account" checkbox. When "always create account" is active the password fields stay visible and required; when "always guest" is active they are removed.
2. **Server layer (route decorator)**: a decorator on `AbstractRegisterRoute` reads the plugin config for the current sales channel and corrects the submitted data before registration:
   - Force company → `accountType = business`
   - Always account → `guest = false` (a real account is created)
   - Always guest → `guest = true`
   Because the storefront controller derives the guest flag from `createCustomerAccount` *before* the route runs, the decorator's override is authoritative — a tampered POST (e.g. `guest=1` or `accountType=private`) is still corrected.
3. **Registration itself** is handled by Shopware's standard `RegisterRoute` and validation, so the customer is created exactly as it would be natively — only the incoming data has been normalized.

## Technical Details

### Configuration keys
- `ActB2bRegistration.config.forceBusinessAccount` (bool)
- `ActB2bRegistration.config.accountChoiceMode` (`off` | `forceAccount` | `forceGuest`)

### Service
- `Storefront\Route\B2bRegisterRoute` — decorates `Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute`; corrects `accountType`/`guest` in the `RequestDataBag` per sales-channel config, then delegates to the inner route

### Template Extensions
- `component/account/register.html.twig` — forces the company account type and renders the company fields (blocks `component_account_register_personal_address_fields`, `component_account_register_company_fields`)
- `page/checkout/address/register.html.twig` — removes the guest/account checkbox and, in guest mode, the password fields (blocks `page_checkout_register_personal_guest`, `component_account_register_personal_password`, `component_account_register_personal_password_confirmation`)

## Behavior Notes

- **"Always guest" and the standalone `/account/register` page**: the guest flag is enforced server-side on every registration route, but the password-field hiding only applies in the checkout flow. On the dedicated `/account/register` page the password fields stay visible in "always guest" mode (guest behaviour is still enforced). This mode is intended for the checkout; shops that force guest and also expose `/account/register` should be aware of this. See the config help text.
- **Whether the account-creation box is checked by default** is a separate Shopware core setting (`core.loginRegistration.createCustomerAccountDefault`) and is intentionally not managed by this plugin.

## Compatibility

- **Shopware Version**: 6.7.x
- **PHP Version**: 8.4+
- **Template Compatibility**: Uses Shopware 6.7+ template structure

## Support

For issues and feature requests, please use the GitHub issue tracker.

## License

This plugin is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Credits

Developed by Actualize

---

Made with ❤️ for the Shopware Community
