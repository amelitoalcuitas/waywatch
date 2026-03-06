# WayWatch – Crowdsourced Road Report Map

WayWatch is a **mobile-first crowdsourced map reporting web application** where users can place temporary markers to report road conditions, hazards, and other real-world situations.

The platform allows the community to share **real-time local information** such as road repairs, accidents, floods, and checkpoints so other users can avoid affected areas.

Markers are **temporary and automatically expire after 24 hours**, ensuring the map stays fresh and relevant.

---

# Project Goals

- Provide **community-driven real-time road information**
- Keep the system **simple and lightweight**
- Prioritize **mobile usability**
- Use **open and cost-efficient infrastructure**
- Build an **MVP that can run on a single VPS**

---

# Core Features

## Map Display

- Interactive map using OpenStreetMap
- Show user's current location
- Display markers from backend
- Marker clustering when zoomed out
- Tap marker to view details
- Filter markers by category

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

Markers automatically **expire after 24 hours**.

Expired markers should not appear on the map.

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

## Anti-Spam Protection

To prevent abuse:

- Maximum **5 markers per user per day**
- Require user authentication
- API rate limiting

---

# Tech Stack

## Frontend

- Nuxt 3
- Vue 3
- TailwindCSS
- Leaflet
- OpenStreetMap

---

## Backend

- Laravel
- REST API
- MySQL or PostgreSQL
- Laravel Queues
- Laravel Scheduler

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

---

### AddMarkerModal

Allows users to submit a report.

Fields:

- Category selector
- Description input
- Image upload (max 6)
- Submit button

---

### MarkerDetails

Shows marker information.

Includes:

- Images
- Description
- Category
- Like / Dislike buttons

---

### Mobile Navigation

Mobile layout includes:

- Map view
- Profile page
- Reports history

---

# Database Schema

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
```

---

## marker_images

```
id
marker_id
image_url
created_at
```

---

## marker_votes

```
id
marker_id
user_id
vote_type
created_at
```

---

## users

```
id
name
email
created_at
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
```

Returns markers within the specified radius.

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

---

## Vote Marker

```
POST /api/markers/{id}/vote
```

Payload:

```
vote_type (like | dislike)
```

---

# Marker Expiration

Markers expire **24 hours after creation**.

Implementation:

- `expires_at` column in markers table
- Expired markers excluded from queries
- Scheduled cleanup job deletes expired markers

---

# Background Jobs

Laravel Scheduler runs periodic jobs.

Tasks:

- Delete expired markers
- Delete associated images
- Clean up unused files

Example cron job:

```
* * * * * php artisan schedule:run
```

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

---

# Future Features (Optional)

Possible improvements after MVP:

- Push notifications for nearby reports
- Heatmap visualization
- Marker credibility score
- "Confirm marker still exists" voting
- Admin moderation tools

---

# Deployment

The application should be deployable on a **single VPS**.

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

# Development Philosophy

This project prioritizes:

- Simplicity
- Performance
- Mobile usability
- Cost efficiency
- Scalability

Avoid unnecessary complexity.

Focus on delivering a **working MVP first**.
