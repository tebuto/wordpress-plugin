# CI (GitHub Actions)

Runner: `ubuntu-latest` for tebuto org repos. Cross-project tooling: Artus portal wiki **Repository Tooling (SonarQube, CI, Cursor Agents)**.

## Workflows

| File | Purpose |
| --- | --- |
| `.github/workflows/branch.yaml` | Build, lint (JS + PHP) → SonarQube scan → quality gate |

## Required checks

After rollout, enable on `main`:

- **Build & Lint**
- **SonarQube Scan**
- **SonarQube Quality Gate**

Secrets: `SONAR_TOKEN`, `SONAR_HOST_URL`.
