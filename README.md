# Laravel Log Platform

A lightweight, **database-free** log browser for Laravel applications. Reads directly from `storage/logs`, presents your log files in a clean UI, and never touches a database connection.

## Features

- 📁 **File Browser** — lists all `.log` files in `storage/logs`, sorted by most-recent
- 🔍 **Log Viewer** — parsed, filterable log entries (level, keyword, date range) per file
- 📄 **Raw Contents** — paginated line-by-line view for any file
- 📡 **Live Tail** — real-time Server-Sent Events stream that follows a file as it grows
- ⬇️ **Download / 🗑️ Delete** — manage log files directly from the UI
- 🌗 **Dark / Light / Auto theme** — configurable via `LOG_PLATFORM_THEME`
- 🗂️ **Custom naming strategies** — daily, weekly, monthly, or custom patterns
- 🔌 **Zero DB migrations** — no tables, no queue jobs, no scheduler entries required

## Installation

```bash
composer require willypelz/laravel-log-platform
```

Run the install command to publish the config:

```bash
php artisan log:install
```

Then visit `/log-platform` in your browser.

## Configuration

Publish and edit `config/log-platform.php`:

```bash
php artisan vendor:publish --tag=log-platform-config
```

Key options:

```php
return [
    'default_strategy' => 'daily',        // daily | weekly | monthly | custom

    'ui' => [
        'theme'       => 'auto',           // light | dark | auto
        'logs_per_page' => 100,
    ],

    'files' => [
        'allow_download' => true,
        'allow_delete'   => true,
    ],

    'retention' => [
        'enabled' => false,                // enable log:clear command
        'days'    => 30,
    ],

    'additional_folders' => [],            // extra absolute paths to scan
];
```

## API Endpoints

All endpoints are prefixed with `/log-platform/api` and use the middleware defined in `config/log-platform.php`.

| Method   | Endpoint                    | Description                                      |
|----------|-----------------------------|--------------------------------------------------|
| `GET`    | `/files`                    | List all `.log` files                            |
| `GET`    | `/files/{filename}`         | Metadata for a single file                       |
| `POST`   | `/files/download`           | Download a file (`file` param)                   |
| `DELETE` | `/files/delete`             | Delete a file (`file` param)                     |
| `GET`    | `/logs`                     | Parsed + filtered log entries (`file`, `level`, `keyword`, `from`, `to`, `limit`) |
| `GET`    | `/contents`                 | Raw paginated lines (`file`, `page`, `per_page`) |
| `GET`    | `/stream`                   | SSE live tail (`file`, `lines`)                  |
| `GET`    | `/hosts`                    | Configured log paths                             |

## Artisan Commands

| Command       | Description                                         |
|---------------|-----------------------------------------------------|
| `log:install` | Publish config                                      |
| `log:clear`   | Delete physical log files older than N days (requires `retention.enabled = true`) |

## Custom Naming Strategy

```php
// config/logging.php
'channels' => [
    'custom' => [
        'driver'   => 'custom',
        'via'      => \Willypelz\LogPlatform\Handlers\StrategyRotatingFileHandler::class,
        'strategy' => 'weekly',   // or 'monthly', 'custom'
    ],
],
```

## Security

By default the API uses the `api` middleware and the UI uses `web`. Override in config:

```php
'security' => [
    'middleware'    => ['api', 'auth:sanctum'],
    'ui_middleware' => ['web', 'auth'],
],
```

## License

MIT
