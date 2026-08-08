---
name: blade-expert
description: Helps writing Blade templates in a project-compliant way. Use when the user wants to write or edit a Blade template or anything related to the front-end.
---

# Blade Expert

A skill for writing or editing Blade templates.

## System Prompt

You are a helpful assistant that writes Blade templates in a project-compliant way. You understand the project's coding
standards and best practices for integrating Blade templates into the application. You can write clean, efficient, and
maintainable Blade code that works seamlessly with the rest of the application.

## Views folder structure

resources/views/
├── common (re-usable blade components)
├── app (internal application blade files)
├── layouts (blade files for extending to adopt a certain look of the website)
└── others (public-facing website blade files, one for each page)

### Re-usable blade components

These blade files must be used as much as possible to avoid code duplication. If you duplicate code to create your view,
consider creating a blade component for it.

### Internal application blade files

Theese are not meant to be used for the public-facing website.
Examples include a blade file for constructing Discord messages or formatting application releases for other platforms.

### Layout blade files

These are blade files that you can extend to adopt a certain look of the website, such as a regular website page, with a
header and a footer, or a full-screen map view.

### Other/public-facing website blade files

These should be kept simple and focused on displaying content to users.

Nested folders may be used for grouping related pages together or creating re-usable components for a specific page of
the site.

### Javascript in blade files

To understand how to write JavaScript for blade files, include the `javascript-in-blade-files` skill in your prompt and follow the instructions there.


## Theming: never use the `bg-body-*` / `text-bg-*` Bootstrap utilities

This site ships **one class-scoped stylesheet per bootswatch theme** (`<html class="theme darkly">`,
switched server-side from the user's `theme` column) rather than using Bootstrap 5.3's
`data-bs-theme` attribute. Bootswatch therefore never overrides the `--bs-*-bg` custom properties:
under **darkly** and **vapor**, `--bs-tertiary-bg` is still its light default `#f8f9fa`.

So `class="bg-body-tertiary"` renders a **white panel with white text** on the dark themes — it
looks fine in a light theme and is invisible in a dark one. Found in #1772 by screenshotting the
page; no test catches it.

- **For panels/cards**: use a Bootstrap `.card` (`<div class="card"><div class="card-body">`), which
  bootswatch *does* theme per stylesheet.
- **For borders/text**: `border`, `text-body-secondary` and `text-muted` are safe —
  `--bs-secondary-color` *is* themed.
- **For icon buttons on a themed surface**: inherit `currentColor` rather than picking
  `btn-outline-secondary`. `$secondary` is a dark grey under darkly/vapor (invisible on a dark card)
  and picking a light variant instead fails the same way under lux. See
  `resources/assets/css/sections/creator-profile.css`.

**Always verify a new surface under both a dark and a light theme.** Because every theme lives in
the one stylesheet, swapping the `html` class is a faithful preview and needs no session change:

```sh
docker compose exec -T -e PRE_EVAL='document.documentElement.className = "theme lux"' app \
    sh -c 'cd /var/www && node .chrome-tmp/authshot.js ...'
```
