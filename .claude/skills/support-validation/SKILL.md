---
name: support-validation
description: Object graph validation with composite validators, fluent API. Use for Validation, field validators, jardissupport/validation.
user-invocable: false
---

# VALIDATION_COMPONENT_SKILL
> jardissupport/validation v1.0.0 | NS: `JardisSupport\Validation` | PHP 8.2+

## ARCHITECTURE
```
ObjectValidator (exception-safe via try/finally)
  └─ ValidatorRegistry (class → validator mapping, parent/interface matching)
       └─ CompositeFieldValidator (fluent API, singleton validator instances)
            └─ Internal\FieldBuilder (chains validators, $options parameter)
                 └─ ValueValidators (singletons)
Internal\ValidationContext — tracks spl_object_id() for circular refs
  Constructor: new ValidationContext(maxDepth: 100)
```

## CORE API
```php
use JardisSupport\Validation\{ObjectValidator, ValidatorRegistry, CompositeFieldValidator};
use JardisSupport\Validation\Validator\{NotBlank, Email, Range};

$userValidator = (new CompositeFieldValidator())
    ->field('email')->validates(NotBlank::class)
                    ->validates(Email::class, Email::strict())
    ->field('age')->validates(Range::class, Range::between(18, 120))
    ->field('id')->breaksOn(NotBlank::class)     // early exit; finalizes pending validates() first
    ->excludeFields(['meta']);                     // skipped in create mode (id=null); validated on update

$validator = new ObjectValidator(
    (new ValidatorRegistry())->register(User::class, $userValidator)
);
$result = $validator->validate(new User('invalid', 15));
$result->isValid();    // bool
$result->getErrors();  // ['order' => ['customer' => ['email' => ['Invalid email']]]]
```

## FIELD RESOLUTION ORDER
```
1. get{Field}()   PSR getter        (getEmail, getId)
2. is{Field}()    boolean getter    (isActive, isPublished)
3. has{Field}()   existence getter  (hasPermission, hasRole)
4. {Field}()      ucfirst fallback  (Email, Id)
5. Reflection     direct property   ($email, $id)
```

## VALIDATORS (21)
| Validator | Factory Methods |
|-----------|----------------|
| `NotBlank` | `required($msg)` |
| `NotEmpty` | `trimmed()`, `strict()` |
| `Email` | `basic()`, `withDnsCheck()`, `strict()` |
| `Length` | `between()`, `min()`, `max()`, `exact()`, `zipCode()`, `phoneNumber()` |
| `Range` | `between()`, `min()`, `max()` |
| `Uuid` | `any()`, `v1()`, `v3()`, `v4()`, `v5()` |
| `Iban` | `sepa()`, `forCountry()` |
| `PhoneNumber` | `german()`, `us()`, `international()` |
| `Format` | `pattern()`, `slug()`, `hexColor()` |
| `Count` | `min()`, `max()`, `exact()`, `between()` |
| `Url` | `httpsOnly()`, `noLocalhost()`, `secure()` |
| `CreditCard` | `visa()`, `mastercard()`, `amex()`, `discover()`, `diners()`, `jcb()` |
| `Ip` | `v4()`, `v6()`, `noPrivate()`, `publicV4()` |
| `Json` | `object()`, `array()`, `maxDepth()` |
| `DateTime` | `iso8601()`, `between()`, `dateOnly()` |
| `Contain` | `oneOf()` |
| `Equals` | `value(mixed)`, `strict(mixed)`, `loose(mixed)` |
| `Positive` | `allowZero()`, `strict()` |
| `Alphanumeric` | `withDashes()`, `withSpaces()`, `withUnderscores()` |
| `UniqueItems` | `strict()`, `loose()` |
| `Callback` | `new Callback(Closure(mixed): ?string)` (readonly) |

## CUSTOM VALIDATOR
```php
use JardisSupport\Contract\Validation\ValueValidatorInterface;

class UniqueSku implements ValueValidatorInterface {
    public function validateValue(mixed $value, array $options = []): ?string {
        if ($value === null) return null;  // null-safe convention

        $hasCustomMessage = array_key_exists('message', $options);
        $message = $options['message'] ?? 'SKU already exists';

        if ($this->repo->exists($value)) {
            return $hasCustomMessage ? $message : sprintf('SKU "%s" already exists', $value);
        }
        return null;
    }
}
```

## CONVENTIONS
- **Null-safe:** All validators return `null` when value is `null` — except `NotBlank` and `NotEmpty`
- **Custom messages:** When `$options['message']` is set → return it on EVERY error; detail messages only as fallback
- **Interface:** `ValueValidatorInterface::validateValue(mixed $value, array $options = []): ?string`
- **FieldBuilder param:** named `$options` (not `$args`)
- **`breaksOn()`:** finalizes pending `validates()` before registration; respects `excludeFields()`
- **`excludeFields()`:** create mode (id=null) → skipped; update mode (id set) → all fields validated
- **`withIdentityField(string $fieldName)`** — configures identity field for `excludeFields()` (default: `'id'`)

## NESTED OBJECTS
```php
$registry = (new ValidatorRegistry())
    ->register(Order::class, $orderValidator)
    ->register(Customer::class, $customerValidator);
$validator = new ObjectValidator($registry);
$result = $validator->validate($order);  // recursively validates graph
```

## LAYER
- **Application:** validate Commands/DTOs before Domain
- **Domain:** NEVER imports Validation
