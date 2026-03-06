# Project Folder Structure

This project follows a **feature-based structure** combined with **Nuxt's file-based routing**. This approach helps organize files by domain (feature) and improves scalability as the project grows.

The project is built with Nuxt, which automatically generates routes based on files inside the `pages/` directory.

---

# Directory Overview

```
app/
├── pages/
│   ├── Users/
│   │   └── index.vue
│   └── users.vue
│
├── components/
│   └── Users/
│       ├── UserHeader.vue
│       ├── UserTable.vue
│       └── ...
```

---

# Pages (`app/pages`)

The `pages` directory defines the **application routes**. In Nuxt, each `.vue` file inside this directory automatically becomes a route.

### Example

```
app/pages/users.vue
```

Creates the route:

```
/users
```

Similarly:

```
app/pages/Users/index.vue
```

Also generates:

```
/users
```

### Notes

Because both files generate the same route (`/users`), **only one should normally be used** to avoid conflicts.

Recommended structure:

```
pages/
└── users/
    └── index.vue
```

This keeps the route scalable if more user-related pages are added later.

Example future structure:

```
pages/
└── users/
    ├── index.vue      → /users
    ├── create.vue     → /users/create
    └── [id].vue       → /users/:id
```

---

# Components (`app/components`)

Components are organized using a **feature-based structure**. Instead of placing all components in a single directory, related components are grouped by domain.

Example:

```
components/
└── Users/
    ├── UserHeader.vue
    ├── UserTable.vue
    └── UserFilters.vue
```

This improves maintainability and makes it easier to locate related files.

### Benefits

- Better organization for large projects
- Easier navigation
- Logical grouping by feature
- Improved scalability

---

# Example Usage

A page can use the feature components like this:

```vue
<template>
  <div>
    <UserHeader />
    <UserTable />
  </div>
</template>
```

Since Nuxt automatically imports components from the `components/` directory, manual imports are usually not required.

---

# Summary

This project structure combines:

- **Nuxt file-based routing** for page navigation
- **Feature-based component organization** for maintainability

Recommended pattern:

```
app/
├── pages/
│   └── users/
│       └── index.vue
│
└── components/
    └── Users/
        ├── UserHeader.vue
        └── UserTable.vue
```

This pattern ensures the project remains clean and scalable as more features are added.
