# KR Crew System

A Laravel 11 application for Kenya Railways crew management with a Filament admin panel.

## Overview

This project is a crew operations app built on Laravel 11 and PHP 8.5. It includes a Filament admin UI for managing depots, regions, crew members, reports, and operational dashboards.

## Features

- Filament admin dashboard and widgets for crew status, depot counts, and report activity
- Crew member and depot administration
- Region management with name-only UI support
- Report builder field support and dashboard analytics
- MySQL-backed data storage

## Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/Lukewilson-1/kr-crew-system.git
   cd kr-crew-system
   ```

2. Install Composer dependencies:
   ```bash
   composer install
   ```

3. Copy the environment file and configure it:
   ```bash
   copy .env.example .env
   ```

4. Generate an application key:
   ```bash
   php artisan key:generate
   ```

5. Run database migrations:
   ```bash
   php artisan migrate
   ```

6. Start the application server:
   ```bash
   php artisan serve
   ```

7. Open the app at `http://127.0.0.1:8000`.

## Admin Panel

- Filament admin pages are registered under `app/Providers/Filament/AdminPanelProvider.php`
- Widgets are stored in `app/Filament/Widgets`
- Resources are in `app/Filament/Resources`

## Key Files

- `app/Filament/Widgets/StatsOverview.php` - dashboard summary widget
- `app/Filament/Widgets/CrewDepotChartWidget.php` - depot distribution chart
- `app/Filament/Widgets/TimelineWidget.php` - recent crew activity timeline
- `app/Filament/Resources/RegionResource.php` - region CRUD UI
- `app/CrewMember.php` - crew member model and record id handling

## Notes

- The application no longer relies on legacy static HTML/JS entry points.
- Region management is configured to record only the region name in the Filament form.
- Dashboard widgets now use Filament-native components for stats and charts.

## Cleanup

- Removed test artifacts such as `.phpunit.result.cache`.
- No application test directory is present in this repository.

## License

Use and adapt this project as needed for Kenya Railways crew management.
