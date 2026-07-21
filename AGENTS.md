# jardissupport/validation

Object graph validation via Reflection — no annotations, no interfaces on domain classes, `ObjectValidator` + `ValidatorRegistry` + `CompositeFieldValidator` compose 21 stateless `ValueValidator` singletons.

## Usage essentials

- **Three-class entry point:** `new ObjectValidator(ValidatorRegistry)` → `validate($root)` traverses the graph recursively (exception-safe via `try/finally`). `ValidatorRegistry::register($class, $validator)` matches by class string with parent/interface fallback, `CompositeFieldValidator` builds rules via fluent API `->field('x')->validates(Class, $options)`. `ValidationContext` protects against circular refs with `spl_object_id()` + `maxDepth: 100`.
- **Field resolution in strict order:** `get{Field}()` → `is{Field}()` → `has{Field}()` → `{Field}()` (ucfirst) → Reflection on property. PSR getters always first, Reflection only as last-resort fallback — no `__get`/magic method support.
- **Null-safe convention (with exactly 2 exceptions):** All `ValueValidator`s return `null` when the value is `null` — **except** `NotBlank` and `NotEmpty`, which explicitly validate against `null`. Custom validators with `implements ValueValidatorInterface` (`jardissupport/contract`, `validateValue(mixed, array $options = []): ?string`) must include `if ($value === null) return null;` at the top.
- **Custom message pattern is required for every error return:** `$hasCustomMessage = array_key_exists('message', $options); $message = $options['message'] ?? 'Default';` — on every error return `$hasCustomMessage ? $message : 'Detail-Message'`. When `message` is set in the `$options` array, it takes precedence over any detail message, regardless of which rule fails.
- **Factory methods return `$options` arrays, parameter is named `$options` (not `$args`):** `Email::strict()`, `Uuid::v4()`, `Range::between(18, 120)`, `Length::zipCode()`. In `FieldBuilder`, `validates($class, $options)` and `breaksOn($class, $options)` are the two public methods — `breaksOn()` automatically finalizes pending `validates()` calls before registration.
- **`excludeFields()` distinguishes Create vs. Update:** When `id === null` (Create), listed fields are skipped; when `id` is set (Update), ALL fields are validated — including excluded ones. `withIdentityField('customId')` changes the identity field name (default `'id'`). `breaksOn()` respects `excludeFields()` — excluded fields are not evaluated for break conditions. The domain layer **never** imports Validation (Application validates Commands/DTOs before Domain).

## Full reference

https://docs.jardis.io/en/support/validation
