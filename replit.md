# CodeIgniter 4 Project

## Overview
A PHP web application built on the CodeIgniter 4 framework. The app includes a login page with Indonesian-language UI, suggesting it's a custom application built on top of CI4.

## Stack
- **Language:** PHP 8.2
- **Framework:** CodeIgniter 4
- **Dependencies:** Managed via Composer (`composer.json` / `composer.lock`)
- **Web root:** `public/` (contains `index.php` entry point)

## Running the App
The app is served using PHP's built-in development server:
```
php -S 0.0.0.0:5000 -t public
```
This is configured as the **"Start application"** workflow in Replit.

## Configuration
- Copy `env` → `.env` and edit values as needed
- Key `.env` settings:
  - `CI_ENVIRONMENT` — set to `development` for debug mode, `production` for live
  - `app.baseURL` — must be a full URL (e.g. `https://your-domain.replit.dev/`)
  - `database.*` — configure if the app uses a database

## Directory Structure
```
app/          # Application code (Controllers, Models, Views, Config, etc.)
public/       # Web root — point the server here
system/       # CodeIgniter 4 framework core
vendor/       # Composer dependencies
.env          # Local environment config (not committed)
env           # Template for .env
```

## Notes
- The debug toolbar makes XHR requests to `app.baseURL` — if you see CORS errors in the browser console, they come from the toolbar, not the app itself. Set `CI_ENVIRONMENT = production` in `.env` to disable it.
- The `utils/` directory referenced in `composer.json` post-install scripts is a dev tooling directory (code style / linting) that is not required to run the app.

## User Preferences
<!-- Record any user preferences here as you learn them -->
