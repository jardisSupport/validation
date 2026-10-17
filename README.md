# Jardis Validation

![Build Status](https://github.com/jardisSupport/validation/actions/workflows/ci.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](phpstan.neon)
[![PSR-12](https://img.shields.io/badge/Code%20Style-PSR--12-blue.svg)](phpcs.xml)
[![Coverage](https://img.shields.io/badge/Coverage-98.84%25-brightgreen.svg)](https://github.com/jardisSupport/validation)

> Part of **[Jardis](https://jardis.io)** — the Domain-Driven Design platform for PHP. You model your domain; Jardis generates the production-ready hexagonal code (DTOs, Command/Query handlers, repositories, persistence). This package is part of the open-source foundation that generated code runs on.

Object graph validation for PHP with recursive traversal. Walks entire object hierarchies — including nested aggregates and collections — applying field-level rules with 22 built-in validators. Purpose-built for aggregate validation in domain models. Fluent composition, circular reference detection, and break-on-first-error mode.

---

## Features

- **Recursive Object Traversal** — validates nested objects and collections automatically, no manual wiring
- **22 Built-in Validators** — NotBlank, Email, Url, Uuid, Range, Length, Format, DateTime, Ip, Iban, CreditCard, PhoneNumber, Json, Alphanumeric, Contain, Count, Positive, Equals, UniqueItems, NotEmpty, ChildPresence, Callback
- **Fluent Field Rules** — `CompositeFieldValidator` composes per-field validators with a chainable `field()` API
- **Break Mode** — stop at first error for guard-style validation before deeper checks
- **ValidatorRegistry** — maps classes (and parent types) to their validators with exact and inheritance-based matching
- **Circular Reference Detection** — tracks visited objects to prevent infinite loops in cyclic graphs
- **Exclude Fields** — skip fields conditionally, supporting partial-update patterns
- **Depth Limiting** — `ValidationContext` tracks traversal levels to cap recursion depth

---

## Installation

```bash
composer require jardissupport/validation
```

## Quick Start

```php
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Email;
use JardisSupport\Validation\Validator\Length;

// Define rules for a single class
$userValidator = new CompositeFieldValidator();
$userValidator->field('name')->validates(NotBlank::class)->validates(Length::class, ['min' => 2, 'max' => 100]);
$userValidator->field('email')->validates(NotBlank::class)->validates(Email::class);

// Register and validate
$registry = new ValidatorRegistry();
$registry->register(User::class, $userValidator);

$validator = new ObjectValidator($registry);
$result = $validator->validate($user);

if (!$result->isValid()) {
    print_r($result->getErrors());
}
```

## Result: errors and kinds

`ValidationResult::getKinds()` mirrors `getErrors()` leaf for leaf (same keys, same nesting). Each leaf is
`ValidationResult::KIND_MISSING` (`'missing'`, the value is absent: reported by validators implementing
`MissingValueValidatorInterface`, currently `NotBlank` and `ChildPresence`) or `ValidationResult::KIND_INVALID` (`'invalid'`, every
other validator, including `NotEmpty`). Requires `jardissupport/contracts ^2.3`.

```php
$result->getErrors(); // ['user' => ['name' => ['Field can not be empty']]]
$result->getKinds();  // ['user' => ['name' => ['missing']]]
```

### Required child property

`ChildPresence` requires a non-null property on an object, or on every element of a list, held by the validated field:

```php
$validator->field('items')->validates(ChildPresence::class, ChildPresence::requiredChild('product'));
```

### Date and time

`DateTime` offers three static option helpers: `iso8601()` (`Y-m-d\TH:i:sP`, numeric offset only), `dateOnly()` (`Y-m-d`, calendar-strict: `2026-02-30` is rejected) and `rfc3339()` for the JSON/JavaScript wire format:

```php
$validator->field('occurredAt')->validates(DateTime::class, DateTime::rfc3339());
```

`rfc3339()` accepts `YYYY-MM-DDTHH:MM:SS` with optional fractional seconds and the offset `Z` or `±HH:MM` (`T`/`Z` may be lower case, RFC 3339 section 5.6), e.g. `2026-10-12T10:00:00Z`, `2026-10-12T10:00:00.123+02:00`. It rejects a missing offset, calendar-invalid values (`2026-02-30T10:00:00Z`), leap seconds (`:60`), other shapes and non-strings; `null` passes. `min`/`max` (any string `DateTimeImmutable` parses) and `message` work as with the other modes.

## Advanced Usage

```php
use JardisSupport\Validation\CompositeFieldValidator;
use JardisSupport\Validation\ValidatorRegistry;
use JardisSupport\Validation\ObjectValidator;
use JardisSupport\Validation\Validator\NotBlank;
use JardisSupport\Validation\Validator\Uuid;
use JardisSupport\Validation\Validator\Email;
use JardisSupport\Validation\Validator\Range;

// Break-mode: abort all validation if the id field is blank
$orderValidator = new CompositeFieldValidator();
$orderValidator->field('id')->breaksOn(Uuid::class);
$orderValidator->field('email')->validates(Email::class);
$orderValidator->field('amount')->validates(Range::class, ['min' => 0.01]);

// Partial updates: skip these fields unless id is present
$orderValidator->excludeFields(['createdAt', 'updatedAt']);

// ValidatorRegistry resolves by exact class or parent/interface match
$registry = new ValidatorRegistry();
$registry->register(Order::class, $orderValidator);
$registry->register(OrderLine::class, $lineValidator);

// ObjectValidator walks the entire graph — Order + nested OrderLine collection
$validator = new ObjectValidator($registry);
$result = $validator->validate($order);
// Errors keyed by short class name: ['order' => [...], 'orderLine' => [...]]
```

### List index in the error path (opt-in, since 1.4)

By default, objects inside lists are keyed by short class name and siblings are merged, so the
element index is lost. Pass `indexedLists: true` as third constructor argument to keep it:

```php
$validator = new ObjectValidator($registry, indexedLists: true);
$result = $validator->validate($order); // second order line invalid

$result->getErrors();
// ['order' => ['orderLine[1]' => ['quantity' => ['Must be at least 1']]]]
$result->getKinds();
// ['order' => ['orderLine[1]' => ['quantity' => ['invalid']]]]  (same keys as getErrors())
```

Key schema: `{shortName}[{index}]`, where `{index}` is the array key of the element in its list.
Nested lists extend the path by nesting (`'item[2]' => ['part[0]' => [...]]`); a list inside a list
appends segments (`item[0][1]`). Objects held directly in a property keep the plain `{shortName}` key.
Siblings are never merged; `getErrors()` and `getKinds()` stay structurally identical. Without the
flag (default `false`) the result is byte-identical to 1.3.

### Field paths in the error tree (opt-in, since 1.6)

`indexedLists` still keys nested objects by class name, so two properties of the same class collide
and the property name is lost. Pass `fieldPaths: true` to key every level by the **property name**
that holds it, list elements by `{property}[{index}]`, at any depth:

```php
$validator = new ObjectValidator($registry, fieldPaths: true);
$result = $validator->validate($order); // quantity of the second line invalid

$result->getErrors();
// ['order' => ['lines[1]' => ['quantity' => ['Must be at least 1']]]]
$result->toFields('order');
// [['field' => 'lines[1].quantity', 'reason' => 'invalid', 'message' => 'Must be at least 1']]
```

- Segment per level = property name; list element = `name[i]`, list in list = `name[i][j]`
  (`a[1].b.c[0].d` through `toFields()`); `getKinds()` carries the same keys.
- The root object keeps its `lcfirst` short name — strip it with `toFields($root)`.
- `fieldPaths` implies indexed lists. Without the flag the result is byte-identical to 1.5.
- `toFields()` needs `jardissupport/contracts` `^2.7` (required since 1.6).

### Validating every element of a list (since 1.6)

`each()` applies a field validator to **every element** of a list field. A failing element is
reported under `{field}[{index}]`, with the same kinds as a single value (`missing` for
presence validators, otherwise `invalid`). A field value that is not an array (e.g. `null`) is
skipped — guard the field itself with `validates()`. An empty list yields no finding.

```php
$tagValidator = (new CompositeFieldValidator())
    ->field('tags')->validates(Count::class, Count::min(1))
                   ->each(Length::class, Length::between(2, 30));

// tags = ['ok', 'x'] -> errors ['tags[1]' => ['...']], kinds ['tags[1]' => ['invalid']]
```

## Documentation

Full documentation, guides, and API reference:

**[docs.jardis.io/en/support/validation](https://docs.jardis.io/en/support/validation)**

## License

This package is licensed under the [MIT License](LICENSE.md).

---

**[Jardis](https://jardis.io)** · [Documentation](https://docs.jardis.io) · [Headgent](https://headgent.com)

<!-- BEGIN jardis/dev-skills README block — do not edit by hand -->
## AI-Assisted Development

This package ships with a skill for Claude Code, Cursor, Continue, and Aider. Install it in your consuming project:

```bash
composer require --dev jardis/dev-skills
```

More details: <https://docs.jardis.io/en/skills>
<!-- END jardis/dev-skills README block -->
