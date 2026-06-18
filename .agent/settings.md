# settings.md

## Default Working Style
- Prefer small, reviewable changes.
- Keep modules cohesive.
- Prefer explicit naming over clever shortcuts.
- Avoid introducing new dependencies without clear need.

## Guardrails
- Do not change core business rules unless explicitly requested.
- Do not rename core database entities casually.
- Do not mix contracted vehicle service logic into fuel/garage eligibility logic.
- Do not implement access control only in the frontend.
- Do not skip audit logging for sensitive actions.

## Documentation Discipline
- Update context when system behavior changes.
- Update memory when decisions become stable.
- Create a new skill when a workflow becomes repeatable and specialized.
- Add ADRs for architecture or policy decisions that affect future implementation.
