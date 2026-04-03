# Temporal Worker Deployment Plan

> **Purpose**: Production-safe deployment strategy for Temporal Workers using Worker Versioning and rainbow deployment pattern.

---

## Prerequisites

- Temporal Server v1.24.0+ (required for Worker Versioning API)
- Temporal CLI or Go SDK for versioning rule updates
- Container deployment solution (Kubernetes recommended)
- Ability to run multiple Worker versions simultaneously

---

## Deployment Strategy: Rainbow Deployment

Rainbow deployment allows multiple Worker versions to run concurrently, enabling Workflow Pinning and safe draining of old versions.

### Why Not Rolling Deployment?

Rolling deployments are **incompatible** with Worker Versioning because they:
- Upgrade Workers in place with little control
- Provide limited rollback capability
- Cannot support Workflow Pinning requirements

---

## Deployment Steps

### Step 1: Version Your Workers

Tag each Worker build with a unique version identifier:

```php
<?php

use Temporal\WorkerFactory;
use Temporal\Worker\WorkerOptions;

$factory = WorkerFactory::create();

// Get version from build/deployment metadata
$buildId = getenv('WORKER_BUILD_ID') ?: 'unknown';

$worker = $factory->newWorker(
    'default',  // Task queue name
    WorkerOptions::new()
        ->withBuildId($buildId)
        ->withUseWorkerVersioning(true)
);

$worker->registerWorkflowTypes(\App\Temporal\Workflows\HelloWorldWorkflow::class);
$worker->registerActivity(
    \App\Temporal\Activities\GreetingActivity::class,
    fn() => new \App\Temporal\Activities\GreetingActivity()
);

$factory->run();
```

**Set build ID via environment:**
```bash
# In your deployment pipeline
export WORKER_BUILD_ID=$(git rev-parse --short HEAD)-$(date +%s)
```

---

### Step 2: Deploy New Version Alongside Old

Deploy new Workers without stopping old ones. Both versions will poll the same task queue.

```yaml
# Kubernetes example - multiple deployments
apiVersion: apps/v1
kind: Deployment
metadata:
  name: temporal-worker-v1-2-3  # New version
spec:
  replicas: 5
  template:
    spec:
      containers:
      - name: worker
        env:
        - name: WORKER_BUILD_ID
          value: "1.2.3"
---
apiVersion: apps/v1
kind: Deployment
metadata:
  name: temporal-worker-v1-2-2  # Old version (still running)
spec:
  replicas: 5
  template:
    spec:
      containers:
      - name: worker
        env:
        - name: WORKER_BUILD_ID
          value: "1.2.2"
```

---

### Step 3: Configure Versioning Rules

Use Temporal CLI to set routing rules. New Workflows will use the new version, existing Workflows stay pinned to their original version.

```bash
# Set the new version as current (accepts all new Workflows)
temporal worker deployment set-current-version \
  --deployment-name "hello-world-workers" \
  --version "1.2.3"

# OR: Ramp gradually (10% of new Workflows to new version)
temporal worker deployment set-ramping-version \
  --deployment-name "hello-world-workers" \
  --version "1.2.3" \
  --ramp-percentage 10
```

---

### Step 4: Monitor Drainage of Old Version

Check when the old version is fully drained (no more running Workflows):

```bash
# Describe specific version
temporal worker deployment describe-version \
  --deployment-name "hello-world-workers" \
  --version "1.2.2"
```

**Expected output during drainage:**
```
Version: 1.2.2
DrainageStatus: draining
DrainageLastChangedTime: 2 minutes ago
DrainageLastCheckedTime: 30 seconds ago
Task Queues: hello-world
```

**When fully drained:**
```
Version: 1.2.2
DrainageStatus: drained
DrainageLastChangedTime: 15 minutes ago
DrainageLastCheckedTime: 1 minute ago
```

---

### Step 5: Shutdown Old Workers

Only after `DrainageStatus: drained`:

```bash
# Scale down or delete old deployment
kubectl scale deployment temporal-worker-v1-2-2 --replicas=0
# OR
kubectl delete deployment temporal-worker-v1-2-2
```

---

## Version Lifecycle

```
┌──────────┐     ┌─────────┐     ┌──────────┐     ┌─────────┐
│ Inactive │ ──▶ │ Active  │ ──▶ │ Draining │ ──▶ │ Drained │
└──────────┘     └─────────┘     └──────────┘     └─────────┘
    │                 │               │               │
    │                 │               │               └─ Safe to shutdown
    │                 │               └─ No new workflows
    │                 │                  Existing workflows running
    │                 └─ Current/Ramping
    │                    Accepts new workflows
    └─ Version exists but never became Current
```

---

## Rollback Procedure

If issues are detected with new version:

```bash
# Instant rollback - make old version current again
temporal worker deployment set-current-version \
  --deployment-name "hello-world-workers" \
  --version "1.2.2"

# New Workflows now route to 1.2.2
# Pinned Workflows on 1.2.3 continue until complete
```

Then monitor drainage of the problematic version and shutdown when drained.

---

## Automated Deployment (Kubernetes)

Consider using the [Temporal Worker Controller](https://docs.temporal.io/production-deployment/worker-deployments/kubernetes-controller) for automated rainbow deployments:

```yaml
apiVersion: temporal.io/v1alpha1
kind: WorkerDeployment
metadata:
  name: hello-world-workers
spec:
  taskQueue: hello-world
  deployment:
    image: my-registry/temporal-worker:latest
    buildId: "1.2.3"
  versioning:
    enabled: true
```

The controller handles:
- Version management
- Routing rules
- Drainage monitoring
- Automatic cleanup of drained versions

---

## Best Practices

### 1. Always Use DTOs for Workflow Parameters
Changes to DTOs with backward-compatible defaults allow version flexibility:

```php
final readonly class OrderArgs
{
    public function __construct(
        public string $orderId,
        public float $amount,
        // New fields with defaults for backward compatibility
        public ?string $promoCode = null,
        public bool $expressShipping = false,
    ) {}
}
```

### 2. Handle Non-Deterministic Code Carefully

```php
// ❌ Wrong: Non-deterministic
$randomId = uniqid();

// ✅ Correct: Deterministic alternatives
$randomId = Workflow::getInfo()->workflowExecution->id;
// OR for truly random within workflow:
$randomId = yield Workflow::sideEffect(fn() => uniqid());
```

### 3. Version-Specific Database Migrations

If your Workflow code requires database schema changes:
- Ensure migrations are backward-compatible
- Old Workers must work with new schema
- Or use separate data stores per version (complex)

### 4. Testing Before Production

Add a pre-deployment test to verify new Workers can process workflows:

```bash
# Deploy to staging first
temporal worker deployment set-ramping-version \
  --deployment-name "hello-world-workers-staging" \
  --version "1.2.3" \
  --ramp-percentage 100

# Run integration tests
php artisan test --filter=TemporalWorkflowTest

# Then proceed to production
```

---

## Checklist

Before deploying a new Workflow version:

- [ ] New Worker build has unique `build_id` tag
- [ ] Worker code passes all tests
- [ ] Database migrations are backward-compatible (if any)
- [ ] New Workers deployed alongside old Workers
- [ ] Versioning rules updated (set-current or set-ramping)
- [ ] Monitoring in place for drainage status
- [ ] Rollback plan documented
- [ ] Old version will be shutdown only after `drained` status

---

## Emergency Contacts / Resources

- [Temporal Worker Versioning Docs](https://docs.temporal.io/production-deployment/worker-deployments/worker-versioning)
- [PHP SDK Versioning Guide](https://docs.temporal.io/develop/php/versioning)
- [Temporal Worker Controller (Kubernetes)](https://docs.temporal.io/production-deployment/worker-deployments/kubernetes-controller)
