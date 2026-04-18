# Jardis Validation

![Build Status](https://github.com/jardisSupport/validation/actions/workflows/ci.yml/badge.svg)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](phpstan.neon)
[![PSR-12](https://img.shields.io/badge/Code%20Style-PSR--12-blue.svg)](phpcs.xml)
[![Coverage](https://img.shields.io/badge/Coverage-98.84%25-brightgreen.svg)](https://github.com/jardisSupport/validation)

> Part of the **[Jardis Business Platform](https://jardis.io)** — Enterprise-grade PHP components for Domain-Driven Design

Object graph validation with recursive traversal. Walks entire object hierarchies — including nested aggregates and collections — applying field-level rules with 21 built-in validators. Fluent composition, circular reference detection, and break-on-first-error mode.

---

## Features

- **Recursive Object Traversal** — validates nested objects and collections automatically, no manual wiring
- **21 Built-in Validators** — NotBlank, Email, Url, Uuid, Range, Length, Format, DateTime, Ip, Iban, CreditCard, PhoneNumber, Json, Alphanumeric, Contain, Count, Positive, Equals, UniqueItems, NotEmpty, Callback
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

## Documentation

Full documentation, guides, and API reference:

**[docs.jardis.io/en/support/validation](https://docs.jardis.io/en/support/validation)**

## License

This package is licensed under the [MIT License](LICENSE.md).

---

**[Jardis](https://jardis.io)** · [Documentation](https://docs.jardis.io) · [Headgent](https://headgent.com)

<!-- BEGIN jardis/dev-skills README block — do not edit by hand -->
## KI-gestützte Entwicklung

Dieses Package liefert einen Skill für Claude Code, Cursor, Continue und Aider mit. Installation im Konsumentenprojekt:

```bash
composer require --dev jardis/dev-skills
```

Mehr Details: <https://docs.jardis.io/skills>
<!-- END jardis/dev-skills README block -->
