---
name: pennant-development
description: "Use when working with Laravel Pennant or feature flags/toggles in a Laravel project — defining/checking flags, class-based features in `app/Features`, Blade `@feature`, scoping to users/teams, custom storage drivers, protecting routes, testing flags, A/B or gradual rollouts. Not for generic Laravel configuration, authorization, or non-Pennant feature systems."
license: MIT
metadata:
  author: laravel
---

# Pennant Features

## Documentation

Use `search-docs` for detailed Pennant patterns and documentation.

## Basic Usage

### Defining Features

<!-- Defining Features -->
```php
use Laravel\Pennant\Feature;

Feature::define('new-dashboard', function (User $user) {
    return $user->isAdmin();
});
```

### Checking Features

<!-- Checking Features -->
```php
if (Feature::active('new-dashboard')) {
    // Feature is active
}

// With scope
if (Feature::for($user)->active('new-dashboard')) {
    // Feature is active for this user
}
```

### Blade Directive

<!-- Blade Directive -->
```blade
@feature('new-dashboard')
    <x-new-dashboard />
@else
    <x-old-dashboard />
@endfeature
```

### Activating / Deactivating

<!-- Activating Features -->
```php
Feature::activate('new-dashboard');
Feature::for($user)->activate('new-dashboard');
```

## Verification

1. Check feature flag is defined
2. Test with different scopes/users

## Common Pitfalls

- Forgetting to scope features for specific users/entities
- Not following existing naming conventions
