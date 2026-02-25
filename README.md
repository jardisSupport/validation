# Jardis Validation

![Build Status](https://github.com/jardisSupport/validation/actions/workflows/ci.yml/badge.svg)
[![License](https://img.shields.io/badge/license-PolyForm%20Noncommercial-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4.svg)](https://www.php.net/)
[![PSR-4](https://img.shields.io/badge/autoload-PSR--4-blue.svg)](https://www.php-fig.org/psr/psr-4/)
[![PSR-12](https://img.shields.io/badge/code%20style-PSR--12-blue.svg)](https://www.php-fig.org/psr/psr-12/)
[![Coverage](https://img.shields.io/badge/coverage-87.65%25-green)](https://github.com/jardisSupport/validation)

> Part of the **[Jardis Ecosystem](https://jardis.io)** — A modular DDD framework for PHP

Validate complex object graphs without annotations, without interfaces, without magic. Use type-safe, reusable validators with an elegant fluent API — perfect for DDD aggregates, value objects, and nested entities.

---

## Features

- **Recursive Object Graph Validation** — Automatically traverses nested objects and collections
- **20+ Production-Ready Validators** — Email, IBAN, UUID, PhoneNumber, CreditCard, Range, and more
- **Fluent API with Static Factories** — Type-safe configuration via `Email::strict()`, `Uuid::v4()`, `Iban::sepa()`
- **Circular-Reference Protection** — Prevents infinite loops in cyclic object structures
- **No Interface Constraints** — Validates arbitrary domain objects without interface requirements
- **Minimal Dependencies** — Only PSR interfaces, optimized for performance

---

## Installation

```bash
composer require jardissupport/validation
```

## Quick Start

```php
use JardisSupport\Validation\{ObjectValidator, ValidatorRegistry, CompositeFieldValidator};
use JardisSupport\Validation\Validator\{NotBlank, Email, Range};

class User
{
    public function __construct(
        private ?string $email = null,
        private ?int $age = null
    ) {}

    public function Email(): ?string { return $this->email; }
    public function Age(): ?int { return $this->age; }
}

$userValidator = (new CompositeFieldValidator())
    ->field('email')->validates(NotBlank::class)
                    ->validates(Email::class, Email::withDnsCheck())
    ->field('age')->validates(Range::class, Range::between(18, 120));

$validator = new ObjectValidator(
    (new ValidatorRegistry())->register(User::class, $userValidator)
);

$result = $validator->validate(new User('invalid', 15));

if (!$result->isValid()) {
    print_r($result->getErrors());
}
```

## Documentation

Full documentation, examples and API reference:

**-> [jardis.io/docs/support/validation](https://jardis.io/docs/support/validation)**

## Jardis Ecosystem

This package is part of the Jardis Ecosystem — a collection of modular, high-quality PHP packages designed for Domain-Driven Design.

| Category | Packages |
|----------|----------|
| **Core** | Kernel, Entity, Workflow |
| **Support** | DotEnv, Cache, Logger, Messaging, DbConnection, DbQuery, DbSchema, Validation, Factory, ClassVersion |
| **Generic** | Auth |
| **Tools** | Builder, Migration, Faker |

**-> [Explore all packages](https://jardis.io/docs)**

## License

This package is licensed under the [PolyForm Noncommercial License 1.0.0](LICENSE).

For commercial use, see [COMMERCIAL.md](COMMERCIAL.md).

---

**[Jardis Ecosystem](https://jardis.io)** by [Headgent Development](https://headgent.com)
