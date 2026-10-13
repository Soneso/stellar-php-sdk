# Contributing to the PHP Stellar SDK

Thank you for helping to improve the SDK. This guide covers bug reports, feature proposals, setup and the pull request process. The commands, the definition of done and the repository rules are in `AGENTS.md`.

## Reporting a bug

Open a bug report issue; the template asks for what you did, what happened, what you expected, the SDK version, and the PHP version. A failing test or a short script that reproduces the behavior shortens the fix.

## Proposing a feature

Open a feature request issue and describe the use case and the proposed behavior. For a SEP or a protocol feature, name the specification. Discuss before writing a large change, so the design is settled before the code.

## Setting up

- PHP at or above the floor in `composer.json`, with the extensions it requires (bcmath, gmp, mbstring, sodium). The CI matrix in `.github/workflows/tests.yml` names the tested versions. The streaming integration tests need the pcntl extension.
- Composer 2. The Commands section of `AGENTS.md` has the install command and the test commands.
- For the XDR generator: Docker, Ruby, and Python 3. For the compatibility matrices: Python 3 with `requests` and network; a GitHub token raises the API rate limit. For the SEP-51 fixtures: Rust's cargo for the pinned reference CLI. Each tool's README under `tools/` names its requirements.

Run the unit suite as `AGENTS.md` describes before you change anything, so you know the baseline is green on your machine.

## Submitting a change

1. Fork the repository and branch from `main` with a short kebab-case name that names the change.
2. Make the change with its tests. A bug fix starts with a test that fails before the fix.
3. Add an entry under `[Unreleased]` in `CHANGELOG.md`, in the section the change belongs to.
4. Run the gates listed under "Definition of done" in `AGENTS.md`.
5. Commit with an imperative subject and a body that states the motivation and what the change does. Open the pull request against `main` and describe what you verified.

The user documentation under `docs/` and the skill under `skills/` describe the released SDK and are updated on the `release-docs` branch with the release; a pull request does not change them. Describe the needed documentation change in the pull request, and the maintainer adds it on that branch. `docs/documentation-strategy.md`, `AGENTS.md` and this file are process documents: change them in the pull request that changes a fact they state.

## What the review checks

- The change does what the pull request says, and nothing else.
- The tests fail without the change and pass with it; unit tests mock HTTP.
- The conventions in `AGENTS.md`: typed API, strict types, PHPDoc on public API.
- The CHANGELOG entry; no hand edits to generated files; no change to the user documentation or the skill.

CI runs the unit suite on the PHP versions of the CI matrix, static analysis, and the dependency audit; the Codecov project status must pass.

## Questions

Use GitHub Discussions for questions about using the SDK or about a change you plan.
