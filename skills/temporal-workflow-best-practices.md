# Temporal Workflow Best Practices

> **Scope**: This document establishes code review standards for Temporal Workflow and Activity development in PHP.
> **Source**: Derived from [Temporal PHP SDK Documentation](https://docs.temporal.io/develop/php) and operational experience.

---

## Table of Contents

1. [Workflow Design](#workflow-design)
2. [Activity Design](#activity-design)
3. [Data Transfer Objects](#data-transfer-objects)
4. [Worker Configuration](#worker-configuration)
5. [Anti-Patterns](#anti-patterns)
6. [Code Review Checklist](#code-review-checklist)

---

## Workflow Design

### Interface Definition

Workflows **MUST** be defined as interfaces with the `#[WorkflowInterface]` attribute. The implementation class implements this interface.

```php
<?php

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;
use Temporal\Workflow\ReturnType;

#[WorkflowInterface]
interface OrderProcessingWorkflowInterface
{
    #[WorkflowMethod(name: "OrderProcessing")]
    #[ReturnType(OrderResult::class)]  // Required for proper client type hints
    public function process(OrderArgs $args): Generator;
}
```

**Requirements:**
- Use `#[WorkflowInterface]` on the interface, not the implementation
- Use `#[WorkflowMethod(name: "...")]` to specify a custom workflow type
- Use `#[ReturnType()]` attribute so client code knows what type to expect from `getResult()`
- Return type **MUST** be `Generator` (workflows are coroutines)

### Implementation Rules

```php
<?php

final readonly class OrderProcessingWorkflow implements OrderProcessingWorkflowInterface
{
    protected ActivityProxy|PaymentActivityInterface $paymentActivity;

    public function __construct()
    {
        // Initialize activity stubs in constructor
        $this->paymentActivity = Workflow::newActivityStub(
            PaymentActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(3)
                        ->withInitialInterval(CarbonInterval::seconds(1))
                )
        );
    }

    // DO NOT duplicate #[WorkflowMethod] here - it's already on the interface
    public function handle(OrderArgs $args): Generator
    {
        // Use yield for async operations (activity calls)
        $result = yield $this->paymentActivity->charge($args->amount);

        // Return plain value (NO yield) for final result
        return new OrderResult(
            success: true,
            transactionId: $result->id
        );
    }
}
```

**Critical Rules:**
1. **DO NOT** use `#[WorkflowMethod]` on the implementation class - only on the interface
2. **DO NOT** use `yield` when returning the final result - only for async operations
3. **MUST** initialize activity stubs in the constructor using `Workflow::newActivityStub()`
4. **MUST** configure ActivityOptions with timeouts (start-to-close or schedule-to-close)

### Parameters

**ALWAYS** use a single DTO parameter for Workflows:

```php
// ✅ CORRECT: Single DTO parameter
#[WorkflowMethod(name: "ProcessOrder")]
public function process(ProcessOrderArgs $args): Generator;

// ❌ INCORRECT: Multiple scalar parameters
#[WorkflowMethod]
public function process(string $orderId, float $amount, array $items): Generator;
```

**Rationale**: Using a single DTO allows adding new fields without breaking existing workflow executions or client code.

---

## Activity Design

### Interface Definition

Activities follow the same pattern as Workflows:

```php
<?php

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: "PaymentActivity.")]
interface PaymentActivityInterface
{
    #[ActivityMethod(name: "charge")]
    public function charge(ChargeArgs $args): ChargeResult;

    #[ActivityMethod(name: "refund")]
    public function refund(string $transactionId): RefundResult;
}
```

**Requirements:**
- Use `#[ActivityInterface(prefix: "...")]` to namespace activity types
- Use `#[ActivityMethod(name: "...")]` for each activity method
- Return types should be specific DTOs or scalar types (not `Generator`)

### Implementation Rules

```php
<?php

class PaymentActivity implements PaymentActivityInterface
{
    public function __construct(
        private readonly PaymentGateway $gateway
    ) {}

    #[ActivityMethod(name: "charge")]  // Must match interface
    public function charge(ChargeArgs $args): ChargeResult
    {
        // Activities are synchronous - no yield needed
        $response = $this->gateway->charge($args->amount, $args->currency);

        return new ChargeResult(
            id: $response->id,
            status: $response->status
        );
    }
}
```

**Activity Constraints:**
- Activities **MUST NOT** use `yield` - they are synchronous
- Activities **MUST** complete within their configured timeout
- Activities should be idempotent (safe to retry)

### Timeouts (Required)

Every activity stub **MUST** have timeouts configured:

```php
ActivityOptions::new()
    // Maximum time from first attempt to completion (includes retries)
    ->withStartToCloseTimeout(CarbonInterval::minutes(10))

    // OR: Maximum time from scheduling to completion (includes queue time)
    ->withScheduleToCloseTimeout(CarbonInterval::minutes(15))

    // Optional: How long to wait in queue before starting
    ->withScheduleToStartTimeout(CarbonInterval::minutes(5))

    // Optional: Retry policy
    ->withRetryOptions(
        RetryOptions::new()
            ->withMaximumAttempts(5)
            ->withInitialInterval(CarbonInterval::seconds(1))
            ->withBackoffCoefficient(2.0)
    )
```

**Rule**: Always set at least one of `StartToClose` or `ScheduleToClose` timeout.

---

## Data Transfer Objects

### Serialization

All workflow/activity parameters and return values **MUST** be serializable. Temporal uses JSON by default via the `DataConverter`.

**Serializable types:**
- Plain PHP objects with public properties
- Classes implementing `JsonSerializable`
- Arrays, scalars
- `spatie/laravel-data\Data` objects (optional, adds validation)

**Example - Plain PHP DTO:**
```php
<?php

declare(strict_types=1);

namespace App\Temporal\DataTransferObjects;

final readonly class OrderArgs
{
    public function __construct(
        public string $orderId,
        public float $amount,
        /** @var array<int, LineItem> */
        public array $items = []
    ) {}
}
```

### Backward Compatibility

When modifying DTOs, maintain backward compatibility:

```php
final readonly class OrderArgs
{
    public function __construct(
        public string $orderId,
        public float $amount,
        // Add new fields with default values
        public ?string $customerNotes = null,
        public bool $expressShipping = false,
    ) {}
}
```

---

## Worker Configuration

### Registration

Workers must register both workflow and activity implementations:

```php
<?php

use Temporal\WorkerFactory;

$factory = WorkerFactory::create();
$worker = $factory->newWorker('default');  // Task queue name

// Register workflow implementation (class, not interface)
$worker->registerWorkflowTypes(OrderProcessingWorkflow::class);

// Register activity implementation
$worker->registerActivity(
    PaymentActivity::class,
    fn() => new PaymentActivity(app(PaymentGateway::class))
);

$factory->run();
```

**Key Points:**
- Register the **implementation class**, not the interface
- Use closure factory for activity instantiation to support dependency injection
- Match task queue names between worker and workflow client

---

## Anti-Patterns

### ❌ DON'T: Yield on Return

```php
// WRONG - yield is only for async operations
return yield new WorkflowResult(...);

// CORRECT
return new WorkflowResult(...);
```

### ❌ DON'T: Duplicate Attributes

```php
// WRONG - WorkflowMethod on implementation
class MyWorkflow implements MyWorkflowInterface
{
    #[WorkflowMethod]  // ❌ Remove this
    public function handle(): Generator {}
}

// CORRECT - Only on interface
```

### ❌ DON'T: Multiple Parameters

```php
// WRONG - Brittle, breaks on signature changes
public function process(string $a, int $b, array $c): Generator;

// CORRECT - Extensible
public function process(ProcessArgs $args): Generator;
```

### ❌ DON'T: Forget Timeouts

```php
// WRONG - Activities will use default (infinite) timeout
$this->activity = Workflow::newActivityStub(MyActivity::class);

// CORRECT - Always specify timeout
$this->activity = Workflow::newActivityStub(
    MyActivity::class,
    ActivityOptions::new()
        ->withStartToCloseTimeout(CarbonInterval::minutes(5))
);
```

### ❌ DON'T: Non-Deterministic Code

```php
// WRONG - Non-deterministic
public function handle(): Generator
{
    $id = uniqid();  // ❌ Different on replay
    $result = yield $this->activity->process($id);
}

// CORRECT - Use workflow ID or side effect
public function handle(): Generator
{
    $id = Workflow::getInfo()->workflowExecution->id;
    // OR: $id = yield Workflow::sideEffect(fn() => uniqid());
    $result = yield $this->activity->process($id);
}
```

---

## Code Review Checklist

### Workflow Interface
- [ ] Has `#[WorkflowInterface]` attribute
- [ ] Has `#[WorkflowMethod(name: "...")]` with explicit name
- [ ] Has `#[ReturnType()]` attribute with correct type
- [ ] Single DTO parameter (no multiple scalars)
- [ ] Return type is `Generator`

### Workflow Implementation
- [ ] Implements the interface
- [ ] **NO** `#[WorkflowMethod]` attribute on implementation
- [ ] Activity stubs initialized in constructor
- [ ] Activity stubs have required timeouts
- [ ] Uses `yield` only for async calls (activity invocations)
- [ ] Returns plain value (not `yield`) for final result
- [ ] No non-deterministic code (random, time, external calls)

### Activity Interface
- [ ] Has `#[ActivityInterface(prefix: "...")]` attribute
- [ ] Has `#[ActivityMethod(name: "...")]` for each method
- [ ] Single DTO parameter
- [ ] Return type is specific (not `Generator`)

### Activity Implementation
- [ ] Implements the interface
- [ ] **NO** `yield` statements
- [ ] `#[ActivityMethod]` attributes match interface
- [ ] Idempotent logic (safe to retry)

### Data Transfer Objects
- [ ] All parameters/returns are serializable
- [ ] Public properties or `JsonSerializable` implementation
- [ ] New fields have default values (backward compatibility)

---

## References

- [Temporal PHP SDK Documentation](https://docs.temporal.io/develop/php)
- [Core Application Guide](https://docs.temporal.io/develop/php/core-application)
- [Temporal Client Guide](https://docs.temporal.io/develop/php/temporal-client)
- [Testing Guide](https://docs.temporal.io/develop/php/testing-suite)
- [SDK Source](https://github.com/temporalio/sdk-php)
