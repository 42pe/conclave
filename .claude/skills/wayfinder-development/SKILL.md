---
name: wayfinder-development
description: "Activates whenever referencing backend routes in frontend components. Use when importing from @/actions or @/routes, calling Laravel routes from TypeScript, working with Wayfinder route functions, or using generated TypeScript types for models, enums, shared data, or broadcast channels."
license: MIT
metadata:
  author: laravel
---

# Wayfinder Development

## When to Apply

Activate whenever:
- Importing from `@/actions/` or `@/routes/`
- Calling Laravel routes from TypeScript/JavaScript
- Creating links or navigation to backend endpoints
- Using generated TypeScript types for models, enums, shared data, broadcast channels, or environment variables

## Documentation

Use `search-docs` for detailed Wayfinder patterns and documentation.

## Setup

This project uses the Vite plugin (`@laravel/vite-plugin-wayfinder`) with `formVariants: true` in `vite.config.ts`. Routes and types are auto-generated during development — no manual `wayfinder:generate` needed when Vite is running.

Configuration is in `config/wayfinder.php`.

### Manual Generation

If Vite is not running, regenerate manually:
```bash
php artisan wayfinder:generate --no-interaction
```

## Quick Reference

### Route Import Patterns

<!-- Controller Action Imports -->
```typescript
// Named imports for tree-shaking (preferred)...
import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

// Named route imports...
import { show as postShow } from '@/routes/post'
```

### Common Route Methods

<!-- Wayfinder Methods -->
```typescript
// Get route object...
show(1) // { url: "/posts/1", method: "get" }

// Get URL string...
show.url(1) // "/posts/1"

// Specific HTTP methods...
show.get(1)
store.post()
update.patch(1)
destroy.delete(1)

// Form attributes for HTML forms...
store.form() // { action: "/posts", method: "post" }

// Query parameters...
show(1, { query: { page: 1 } }) // "/posts/1?page=1"

// Query merging (merge with current URL, null removes params)...
show(1, { mergeQuery: { page: 2, sort: null } })
```

## Wayfinder + Inertia

Use Wayfinder with the `<Form>` component:
<!-- Wayfinder Form (React) -->
```typescript
<Form {...store.form()}><input name="title" /></Form>
```

Or with `useForm`:
```typescript
import { store } from '@/actions/App/Http/Controllers/PostController'

const form = useForm({ title: '' })
form.submit(store())
```

## Generated TypeScript Types

Wayfinder generates TypeScript types beyond routes. These are configured in `config/wayfinder.php`:

| Feature | Config Key | Description |
|---------|-----------|-------------|
| Route Actions | `generate.route.actions` | Controller action functions (`@/actions/`) |
| Named Routes | `generate.route.named` | Named route functions (`@/routes/`) |
| Form Variants | `generate.route.form_variant` | `.form()` method on route functions |
| Models | `generate.models` | TypeScript types for Eloquent models |
| Inertia Shared Data | `generate.inertia.shared_data` | Types for Inertia shared props |
| Broadcast Channels | `generate.broadcast.channels` | Types for broadcast channel subscriptions |
| Broadcast Events | `generate.broadcast.events` | Types for broadcast events |
| Environment Variables | `generate.environment_variables` | Types for environment variables |
| Enums | `generate.enums` | TypeScript equivalents of PHP enums |

## Verification

1. Ensure Vite is running (`npm run dev`) for auto-generation
2. Check TypeScript imports resolve correctly
3. Verify route URLs match expected paths

## Common Pitfalls

- Using default imports instead of named imports (breaks tree-shaking)
- Forgetting to run Vite or `wayfinder:generate` after route/model changes
- Not using type-safe parameter objects for route model binding
- Ignoring generated types for models and enums in favor of manual type definitions