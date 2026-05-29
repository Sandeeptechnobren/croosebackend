---
name: teach-impeccable
description: One-time setup — gather design context for this project
---
I need to learn about this project's design language so all future `/impeccable audit`, `/impeccable polish`, `/impeccable critique` commands are project-aware. Interview me with these questions one at a time using AskUserQuestion:

1. What are your brand colors? (primary, secondary, accent — hex codes if you have them)
2. What fonts does this project use? (or what style: modern, serif, playful, technical?)
3. What is the overall design tone? (minimal, bold, corporate, playful, luxury, editorial, brutalist?)
4. Who is the target audience? (age range, technical level, context of use)
5. Are there any existing design references, competitor sites, or mood boards to follow?
6. Light mode, dark mode, or both?

After gathering answers, save them to .claude/skills/impeccable/brand-context.md in this format:

# Brand Context for [Project Name]
## Colors
(answers)
## Typography
(answers)
## Design Tone
(answers)
## Target Audience
(answers)
## References
(answers)
## Theme
(answers)

This file will be read automatically by all Impeccable commands going forward.

> NOTE: Impeccable v3.5+ also ships a native `/impeccable init` command that runs
> its own discovery interview and writes PRODUCT.md / DESIGN.md. Prefer that for
> the canonical setup; this command is a lightweight project-specific supplement.
