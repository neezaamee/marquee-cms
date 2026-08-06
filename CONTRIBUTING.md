# Contributing Guidelines

Thank you for contributing to MarqueeCMS! Follow these guidelines to maintain enterprise-grade code quality and clean Git history.

## Development Workflow

### 1. Branching Strategy
We follow a feature-branch model. All development is done on separate topic branches.

* **`main`**: Production-ready branch. Must remain stable and tested at all times.
* **`feature/...`**: New features (e.g., `feature/staff-attendance`).
* **`bugfix/...`**: Bug fixes (e.g., `bugfix/stripe-webhook-failure`).
* **`chore/...`**: Technical debt, package updates, or configuration cleanups.

---

## Code Quality Standards

### 1. PHP Coding Standards (PSR-12)
All PHP files must adhere to the PSR-12 coding standard.
* Use 4 spaces for indentation.
* Always declare strict types `declare(strict_types=1);` when appropriate.
* Document class methods using phpdoc blocks where applicable.

### 2. Laravel Best Practices
* Keep controllers thin and extract complex operations into dedicated Service classes (under `app/Services`).
* Use Eloquent Scopes for data isolation (like the `BelongsToTenant` and `BelongsToBranch` traits).
* Validate inputs using custom Form Request classes.

### 3. Automated Tests
Every feature branch must include corresponding automated unit/feature tests. Run tests locally before opening a pull request:
```bash
php artisan test
```

---

## Git Best Practices

* **Commit Messages**: Write descriptive, imperative commit messages (e.g., `feat: implement recipe calculation service`).
* **Pull Requests**: Pull requests must pass all automated status checks (e.g., PHPUnit tests, lint checks) before being merged into the `main` branch.
