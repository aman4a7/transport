# hooks.md

## Before Finalizing Any Task
- Run lint.
- Run relevant tests.
- Check whether architecture changes require updating `context.md`.
- Check whether major decisions require updating `memory.md`.
- Check whether changed UI still follows `DESIGN.md`.
- Verify no core business rule was bypassed.

## When Editing Backend Logic
- Verify authorization and policy checks.
- Verify server-side validation exists.
- Verify transactions are used for multi-step state changes.
- Verify audit log hooks exist for sensitive actions.

## When Editing Frontend Logic
- Verify role-based visibility.
- Verify blocked-state messaging for denied actions.
- Verify loading, empty, and error states.
- Verify status badges and alerts are understandable.

## When Editing File Upload Flows
- Verify file size/type validation.
- Verify secure storage path handling.
- Verify document status and expiry fields are supported.
- Verify replacement and re-verification workflows still work.
