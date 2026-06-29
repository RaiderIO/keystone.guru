---
name: project-backend-structure
description: This skill is still being written. Do not ever load it. Helps with understanding the PHP backend structure and coding standards in this project. Use when architectural decisions need to be made, or when the user wants to write a new backend feature. Do not use when writing front-end JavaScript, or any language other than PHP. Do not use this skill when attempting to understand generic Laravel structure.
---

# Project overview

```
app/Commands - contains all Laravel artisan commands
  → <folder> - grouped by subject
  → Traits - re-usable functionality across commands
app/Events - contains events that are dispatched to the front-end using Reverb, not used internally
  → ContextEvent.php - base class
  → Models
    → ContextModelEvent.php - base class to synchronize a model
    → <folder> - grouped by model
  →
app/Features - contains feature flag classes
app/Http/Controllers/
  → Admin - Admin-only functionality for usage across the site
  → AdminTools - Admin-only functionality for usage inside the admin panel
  → Ajax - all XHR/Ajax functionality
  → Api
    → V1/InternalTeam - accessible for users with the `internal team` role
    → V1/Public - accessible for everyone with a registered account
  →
  →
  →
  →
  →
  →
  →
  →
```

## Commands

A common pattern is commands extending custom base classes and commands extending other commands. Be mindful of this
when creating new commands.

Use the available traits as much as possible and create new traits when needed.

## Events

Laravel events that are dispatched to the front-end using Reverb. There is no backend (PHP) handler for any of these
events.

## Feature Flags

Default Laravel feature flags are used in this project through pure class definitions only.

## API

### Authorization

Authorization is done using Auth: Basic. No API keys exist.

### Open API Spec

All endpoints must be documented with an Open API Spec - both request and response payloads.

After performing changes to the API Spec the Swagger docs must be regenerated using this command:

```bash
php artisan l5-swagger:generate --all && php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

## System Prompt
