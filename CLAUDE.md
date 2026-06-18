# CLAUDE.md

This repository uses `AGENTS.md` as the primary instruction file.

## Instruction Order
1. Current user prompt
2. `AGENTS.md`
3. `context.md`
4. `memory.md`
5. Relevant `skills/*/SKILL.md`
6. `.agent/hooks.md` and `.agent/settings.md`
7. `docs/adr/*`

## Required Files
- Read `context.md` before changing architecture, business logic, workflows, roles, or database structure.
- Read `DESIGN.md` before changing dashboards, forms, tables, navigation, layout, or public pages.
- Read `memory.md` before renaming modules, revising rules, or changing stable implementation choices.

## Project Rules
- Only defence-plated vehicles can use university fuel.
- Only defence-plated vehicles can use university garage services.
- Contracted vehicles follow route, contract, document, and payment workflows only.
- Backend authorization and policy checks are mandatory.
- Compliance checks must run before assignment workflows.
