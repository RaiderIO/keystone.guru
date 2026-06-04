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

