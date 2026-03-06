# WayWatch – Crowdsourced Road Report Map

> **For AI agents:** See [PROJECT_STRUCTURE.md](./PROJECT_STRUCTURE.md) for project folder structure, page/component organization conventions, and routing patterns. Use it when adding new pages, components, or features.

WayWatch is a **mobile-first crowdsourced map reporting web application** where users can place temporary markers to report road conditions, hazards, and other real-world situations.

The platform allows the community to share **real-time local information** such as road repairs, accidents, floods, and checkpoints so other users can avoid affected areas.

Markers are **temporary and automatically expire** with a scheduled cleanup weekly, ensuring the map stays relevant while also allowing historical analysis.

---

# Project Goals

- Provide **community-driven real-time road information**
- Keep the system **simple and lightweight**
- Prioritize **mobile usability**
- Use **open and cost-efficient infrastructure**
- Build an **MVP that can run on a single VPS**
- Prepare for **future directions/route features**

---

# Core Features

## Map Display

- Interactive map using OpenStreetMap
- Show user's current location
- Display markers from backend
- Marker clustering when zoomed out
- Tap marker to view details
- Filter markers by category
- Filter markers by **date range**

---

## Marker System

Users can place markers representing real-world reports.

### Marker Categories

- Road Repair
- Accident
- Traffic
- Flood
- Hazard
- Checkpoint

### Marker Properties

Each marker includes:

- Latitude
- Longitude
- Category
- Description
- Images (max 6)
- Created timestamp
- Expiration timestamp
- Likes
- Dislikes

Markers will **remain visible until cleaned up weekly**. Users can filter markers by date to analyze historical data.

---

## Planned Features

- **Route/Directions**: The app will suggest a route and list markers along the route.
- Historical marker analysis via date filters.

---

## Image Uploads

Markers can include **up to 6 images**.

Requirements:

- Compress images before upload
- Store images in object storage
- Store only image URLs in database
- Enforce file size limits

---

## Voting System

Users can vote on markers to indicate credibility.

Vote types:

- Like
- Dislike

Rules:

- One vote per user per marker
- Votes help determine marker credibility
- Only **registered users** can vote

---

## Location Restrictions

Users can only create markers **within a limited radius of their current GPS location**.

Recommended restriction:

```
3km radius
```

Frontend must request browser geolocation.

Backend must validate the distance before accepting the marker.

---

## User System

- **Registration and login required** for adding markers, voting, or other interactions
- Guests can view the map and markers freely
- Main page is the **map view**
- User database prepared for future features

---

## Anti-Spam Protection

To prevent abuse:

- Maximum **5 markers per user per day**
- API rate limiting

---

# Tech Stack

## Frontend

- Nuxt 3
- Vue 3
- Nuxt UI (always use Nuxt UI components for UI elements)
- TailwindCSS
- Leaflet
- Pinia
- OpenStreetMap

---

## State Management

The frontend uses **Pinia** for global state. The `markers` store holds:

- Filter state (category, date)
- Map bounds parameters
- Markers list and loading state
- Debounced fetch logic for the markers API

This setup prepares for future features such as auth state, user preferences, and route planning.

---

## Backend

- Laravel
- REST API
- MySQL or PostgreSQL
- Laravel Queues
- Laravel Scheduler (weekly cleanup)
- Authentication and registration system

---

## Infrastructure

- VPS hosting
- Nginx reverse proxy
- Cloudflare CDN
- S3-compatible object storage

---

# Mobile First Design

The UI must prioritize **mobile devices**.

Design principles:

- Full-screen map
- Large touch-friendly buttons
- Floating "Add Marker" button
- Bottom sheet panels for marker details
- Minimal UI clutter

Desktop should adapt responsively.

---

# System Architecture

```
                Internet
                   │
              Cloudflare
                   │
                Nginx
                   │
        ┌──────────┴──────────┐
        │                     │
   Nuxt Frontend        Laravel API
     (Vue + Leaflet)      (REST)
        │                     │
        │                     │
        └──────────┬──────────┘
                   │
                Database
           (MySQL/PostgreSQL)
                   │
                   │
             Object Storage
              (Images)
```

---

# Application Components

## Frontend

Main components:

### MapView

- Full screen map
- Marker clustering
- User location
- Marker rendering
- Filter markers by category and date

---

### MapFilterBar

- Filter bar with category selector (USelect) and date picker
- Composes the main map page filter controls

---

### MapSection

- Wrapper around MapView
- Forwards bounds-change and map-click events (add marker via map click)

---

### MarkerList

- Scrollable list of markers with loading and empty states
- Category-colored badges
- Click marker to focus on map

---

### AddMarkerModal

Allows users to submit a report.

Fields:

- Category selector
- Description input
- Image upload (max 6)
- Submit button
- Only **registered users** can submit

---

### MarkerDetails

Shows marker information.

Includes:

- Images
- Description
- Category
- Like / Dislike buttons (restricted to logged-in users)

---

### Authentication Pages

- User registration
- User login
- Password reset

---

### Mobile Navigation

Mobile layout includes:

- Map view (main page)
- Profile page
- Reports history

---

# Database Schema

## users

```
id
name
email
password
created_at
updated_at
```

---

## markers

```
id
user_id
latitude
longitude
category
description
likes
dislikes
expires_at
created_at
updated_at
```

---

## marker_images

```
id
marker_id
image_url
created_at
updated_at
```

---

## marker_votes

```
id
marker_id
user_id
vote_type
created_at
updated_at
```

---

# API Design

## Get Markers

```
GET /api/markers
```

Query parameters:

```
latitude
longitude
radius
start_date (optional)
end_date (optional)
```

Returns markers within the radius and optional date range.

---

## Create Marker

```
POST /api/markers
```

Payload:

```
latitude
longitude
category
description
images[]
```

Requires authentication.

---

## Vote Marker

```
POST /api/markers/{id}/vote
```

Payload:

```
vote_type (like | dislike)
```

Requires authentication.

---

# Marker Cleanup

Markers are cleaned **weekly** via Laravel Scheduler.

Tasks:

- Delete expired markers
- Delete associated images
- Clean up unused files

Example cron job:

```
* * * * * php artisan schedule:run
```

> Note: The above runs every minute (required by Laravel Scheduler). Laravel internally determines which scheduled tasks are due. The actual cleanup job is scheduled weekly inside `app/Console/Kernel.php`.

---

# Performance Considerations

To maintain map performance:

- Only fetch markers within a radius
- Use marker clustering
- Avoid loading full dataset
- Cache frequently accessed data if needed

---

# Security

Required protections:

- Validate GPS coordinates on backend
- Rate limit API requests
- Validate image uploads
- Restrict file size and formats
- Enforce location radius rules
- Authentication required for interactive actions

---

# Deployment

The application should be deployable on a **single VPS** or via **Docker Compose** (recommended).

Recommended stack:

```
Nginx
Node (Nuxt)
PHP (Laravel)
MySQL/PostgreSQL
Redis (optional)
```

Images should be stored in **external object storage**.

---

# Docker Setup

WayWatch is fully containerized using **Docker Compose**, making local development and VPS deployment consistent and reproducible.

---

## Recommended Services

```
waywatch/
├── docker/
│   ├── nginx/
│   │   └── default.conf        # Nginx reverse proxy config
│   ├── php/
│   │   └── Dockerfile          # Laravel PHP-FPM container
│   └── node/
│       └── Dockerfile          # Nuxt 3 SSR container
├── docker-compose.yml
└── docker-compose.prod.yml     # Production overrides
```

---

## docker-compose.yml (Development)

```yaml
version: '3.9'

services:
  # ─── Nginx Reverse Proxy ───────────────────────────────
  nginx:
    image: nginx:alpine
    ports:
      - '80:80'
    volumes:
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
      - ./backend:/var/www/backend
    extra_hosts:
      - 'host.docker.internal:host-gateway'
    depends_on:
      - backend
    networks:
      - waywatch

  # ─── Laravel Backend (PHP-FPM) ─────────────────────────
  backend:
    build:
      context: ./backend
      dockerfile: ../docker/php/Dockerfile
    volumes:
      - ./backend:/var/www/backend
    environment:
      APP_ENV: local
      APP_KEY: ${APP_KEY}
      DB_HOST: db
      DB_PORT: 5432
      DB_DATABASE: ${DB_DATABASE}
      DB_USERNAME: ${DB_USERNAME}
      DB_PASSWORD: ${DB_PASSWORD}
      REDIS_HOST: redis
      AWS_ENDPOINT: ${AWS_ENDPOINT} # S3-compatible object storage
      AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID}
      AWS_SECRET_ACCESS_KEY: ${AWS_SECRET_ACCESS_KEY}
      AWS_BUCKET: ${AWS_BUCKET}
    depends_on:
      - db
      - redis
    networks:
      - waywatch

  # ─── PostgreSQL Database ───────────────────────────────
  db:
    image: postgres:16-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - db_data:/var/lib/postgresql/data
    networks:
      - waywatch

  # ─── Redis (Queue & Cache) ─────────────────────────────
  redis:
    image: redis:7-alpine
    restart: unless-stopped
    volumes:
      - redis_data:/data
    networks:
      - waywatch

  # ─── Laravel Queue Worker ──────────────────────────────
  queue:
    build:
      context: ./backend
      dockerfile: ../docker/php/Dockerfile
    command: php artisan queue:work --sleep=3 --tries=3
    volumes:
      - ./backend:/var/www/backend
    environment:
      DB_HOST: db
      REDIS_HOST: redis
    depends_on:
      - db
      - redis
    networks:
      - waywatch

  # ─── Laravel Scheduler (Weekly Cleanup) ────────────────
  scheduler:
    build:
      context: ./backend
      dockerfile: ../docker/php/Dockerfile
    command: sh -c "while true; do php artisan schedule:run; sleep 60; done"
    volumes:
      - ./backend:/var/www/backend
    environment:
      DB_HOST: db
      REDIS_HOST: redis
    depends_on:
      - db
    networks:
      - waywatch

volumes:
  db_data:
  redis_data:

networks:
  waywatch:
    driver: bridge
```

---

## Service Summary

| Service     | Image / Build        | Purpose                                                                    |
| ----------- | -------------------- | -------------------------------------------------------------------------- |
| `nginx`     | `nginx:alpine`       | Reverse proxy, routes `/api` to backend, `/` to local frontend (port 3000) |
| `backend`   | Custom PHP-FPM       | Laravel REST API, authentication, marker logic                             |
| `db`        | `postgres:16-alpine` | Primary data store (markers, users, votes)                                 |
| `redis`     | `redis:7-alpine`     | Queue driver and optional response caching                                 |
| `queue`     | Same as backend      | Processes background jobs (e.g., image cleanup)                            |
| `scheduler` | Same as backend      | Runs Laravel Scheduler every 60s for weekly cleanup                        |

---

## Nginx Configuration (docker/nginx/default.conf)

```nginx
server {
    listen 80;

    # Forward API requests to Laravel
    location /api/ {
        proxy_pass http://backend:9000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }

    # Forward everything else to Nuxt (running locally on host)
    location / {
        proxy_pass http://host.docker.internal:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

> **Note:** Laravel runs PHP-FPM (port 9000), not a web server directly. Nginx handles HTTP and proxies `.php` requests through FastCGI if needed. Adjust to use `fastcgi_pass` instead of `proxy_pass` for a standard PHP-FPM setup.

---

## PHP Dockerfile (docker/php/Dockerfile)

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/backend
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN php artisan config:cache && php artisan route:cache

EXPOSE 9000
CMD ["php-fpm"]
```

---

## Environment Variables (.env)

```env
APP_KEY=base64:...
APP_ENV=local

DB_DATABASE=waywatch
DB_USERNAME=waywatch_user
DB_PASSWORD=secret

AWS_ENDPOINT=https://your-s3-compatible-endpoint
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_BUCKET=waywatch-images
```

---

## Phase 1 Quick Start

1. Copy `.env.example` to `.env` and set `APP_KEY` (run `cd backend && php artisan key:generate --show` to generate).
2. Install dependencies locally:
   ```bash
   cd backend && composer install && cd ..
   cd frontend && npm install && cd ..
   ```
3. Start Docker services (backend, nginx, db, redis): `docker compose up -d`
4. Run migrations: `docker compose exec backend php artisan migrate`
5. (Optional) Seed: `docker compose exec backend php artisan db:seed`
6. Run the frontend locally: `cd frontend && npm run dev`
7. API: `http://localhost/api` · Frontend: `http://localhost:3000` or `http://localhost` (via nginx proxy)

---

## Useful Docker Commands

```bash
# Start all services
docker compose up -d

# Run migrations
docker compose exec backend php artisan migrate

# Seed database
docker compose exec backend php artisan db:seed

# View logs
docker compose logs -f backend

# Run cleanup manually
docker compose exec backend php artisan schedule:run

# Stop everything
docker compose down
```

---

## Production Notes

For production (`docker-compose.prod.yml`):

- Remove volume mounts for source code (use `COPY` in Dockerfile instead)
- Set `APP_ENV=production` and `APP_DEBUG=false`
- Use external managed database (e.g., managed PostgreSQL) instead of the `db` container
- Point `AWS_ENDPOINT` to your production S3-compatible bucket
- Place Cloudflare in front of Nginx for CDN and DDoS protection
- Use Docker secrets or a `.env` file excluded from version control for credentials

---

# Development Philosophy

This project prioritizes:

- Simplicity
- Performance
- Mobile usability
- Cost efficiency
- Scalability

Focus on delivering a **working MVP first**, with **weekly marker cleanup** and **date filters** for analysis.  
Future features like **route planning and directions** will build upon the existing database and map framework.

---

## Component Guidelines

**Always use Nuxt UI components** for buttons, inputs, modals, cards, and other UI elements. Prefer Nuxt UI primitives over custom HTML or other component libraries.

Every new UI element that can be reused should be extracted into its own component. Place reusable components in `frontend/app/components/` so they can be shared across pages and composed into larger views.

The main map page (`pages/index.vue`) is composed of `MapFilterBar`, `MapSection`, `MarkerList`, and `AddMarkerModal`.

---

## Page and Component Organization

When adding new pages (e.g. Customer, Profile, Settings):

1. **New pages convention:** Create a folder named after the feature with `index.vue` inside:
   - Example: `pages/customer/index.vue` for the Customer page
   - This keeps routes organized and allows nested routes later (e.g. `pages/customer/[id].vue`)

2. **Feature-specific components:** For each feature page, create a matching components folder in `components/`:
   - Example: `components/customer/` for Customer-related components
   - Dissect the page into smaller components and place them in this folder
   - Keeps page-specific components colocated with the feature; shared components stay in `components/` root

3. **Example structure:**

```
pages/
  customer/
    index.vue
    [id].vue
components/
  customer/
    CustomerList.vue
    CustomerForm.vue
    CustomerCard.vue
```

---

# Project Folder Structure & Step-by-Step Build Plan

## Recommended Folder Structure

```
waywatch/
├── backend/                  # Laravel API
│   ├── app/
│   │   ├── Models/
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   └── Console/Jobs/    # Scheduled cleanup jobs
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
│       └── api.php
├── frontend/                 # Nuxt 3 + Vue 3
│   ├── components/
│   │   ├── MapFilterBar.vue
│   │   ├── MapSection.vue
│   │   ├── MapView.vue
│   │   ├── MarkerList.vue
│   │   ├── AddMarkerModal.vue
│   │   ├── DatePicker.vue
│   │   ├── AppHeader.vue
│   │   ├── MarkerDetails.vue
│   │   └── Navigation.vue
│   ├── pages/
│   │   ├── index.vue         # Main map page
│   │   ├── login.vue
│   │   └── register.vue
│   └── plugins/
│       └── leaflet.js
├── docker/                   # Docker configuration
│   ├── nginx/
│   │   └── default.conf
│   ├── php/
│   │   └── Dockerfile
│   └── node/
│       └── Dockerfile
├── docker-compose.yml
├── docker-compose.prod.yml
├── .env.example
├── README.md
└── package.json / composer.json
```

---

## Step-by-Step Build Plan

**Phase 1: Setup Backend & Auth**

1. Initialize Laravel project
2. Setup `users` table and authentication (register, login)
3. Setup marker-related tables (`markers`, `marker_images`, `marker_votes`)
4. Implement API endpoints for markers and votes
5. Implement weekly cleanup job

**Phase 2: Setup Frontend & Map**

1. Initialize Nuxt 3 project
2. Configure Leaflet with OpenStreetMap tiles
3. Create main map page (`index.vue`)
4. Fetch and display markers from API
5. Add marker clustering

**Phase 3: Marker Actions**

1. Implement AddMarkerModal
2. Restrict marker creation to registered users
3. Implement image upload (S3 or object storage)
4. Add category selection and description
5. Implement location radius check

**Phase 4: Voting & Date Filters**

1. Add voting buttons in MarkerDetails
2. Restrict votes to authenticated users
3. Add filter for date ranges on markers
4. Test API for fetching filtered markers

**Phase 5: UI Polish & Future Features Prep**

1. Mobile-first styling with TailwindCSS
2. Bottom navigation for mobile
3. Prepare database and frontend hooks for future route/direction feature
4. Test weekly cleanup and ensure historical markers can be analyzed via date filter
5. Deploy using Docker Compose on VPS with Nginx, configure Cloudflare CDN, and object storage

---

**End of README.md**
