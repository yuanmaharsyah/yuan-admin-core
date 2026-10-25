---
name: admin-audit
description: "Use when logging a non-model event to the audit log (Log Aktivitas), or when a field holds personal data that must be masked (NIK, NPWP, phone, address, account number)."
---

# Audit events and masked fields

Model changes are logged automatically by `LogsModuleActivity`. Everything else goes through `Audit::record()`:

```php
Audit::record(AuditEvent::SettingsUpdated, meta: ['keys' => ['app.name']]);   // core event
Audit::record('approve', $record, ['note' => $note]);                         // a custom action declared in module.php
Audit::batch(function () use ($ids) {                                         // many records, one batch
    {Name}::query()->whereKey($ids)->each->delete();
});
```

- Never call `activity()` directly, never queue audit writes, never update or delete `activity_log` rows. An undeclared event name throws; standard actions (`view`, `create`…) are not event names.
- A job writes as the user it was dispatched for: `Audit::actingAs($this->user, fn () => Audit::record(AuditEvent::Exported, meta: ['rows' => $count], module: '{slug}'));`
- Audit queries go through `ImmutableActivity::visibleTo($user)`.

## Masking personal data

Add the field to `config/admin-core.php` (if the project has none yet: `php artisan vendor:publish --tag=admin-core-config`) → `admin-core.audit.masked_fields` with `omit` (never stored), `partial` (keeps the first 4 and last 4 characters) or `full` (`••••`), in the same change that adds the field. The contract test (`ContractAssertions::assertMaskingHolds()`) checks every configured field for every event type. Never log passwords, tokens or unmasked personal data.
