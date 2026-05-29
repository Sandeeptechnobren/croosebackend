# Lessons Learned

A running list of rules distilled from real mistakes. After ANY correction or
mistake, add a rule here that would prevent it next time. Prune entries older
than 30 days into an archive file (see CLAUDE.md → Context Window Budget).

## Code Patterns

### Frontend Design
- All AI models default to generic templates (Inter font, purple gradients, cards-in-cards). Always challenge the first design output with `/impeccable critique` before accepting.
- Animations should have purpose. Never animate just because you can. Every motion must communicate state change, guide attention, or provide feedback.
- Dark mode is not "invert colors". It requires separate consideration for contrast, shadows, and surface hierarchy.

## Common Pitfalls
(none yet)

## Testing
(none yet)

## Bulk Operations
- NEVER run a bulk find-and-replace without first excluding `.claude/skills/`, `node_modules/`, `vendor/`, `.git/`, `dist/`, `build/`, and lock files.
- ALWAYS show the full list of files that will be affected BEFORE running any bulk operation.
- ALWAYS ask for confirmation before executing bulk changes.
- When in doubt, operate on a single file first and verify the result before scaling up.
