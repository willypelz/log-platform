# Changelog

All notable changes to Laravel Log Platform will be documented in this file.

## [2.0.0] - 2026-03-24

### Breaking Changes
- **Database layer completely removed.** No migrations, no Eloquent models, no queue jobs, no scheduled tasks.
- Removed commands: `log:index`, `log:stats`
- Removed controllers: `LogsController`, `AlertsController`, `MetricsController`
- Removed services: `DatabaseIndexerStore`, `LogIndexer`, `LogQueryService`, `StructuredQueryParser`
- Removed models: `IndexedLog`, `LogFileState`, `AlertRule`, `AlertEvent`, `MetricTimeseries`
- Removed jobs: `IndexLogChunkJob`, `AggregateMetricsJob`, `EvaluateAlertsJob`
- Removed contracts: `IndexerStoreInterface`, `QueryEngineInterface`
- Removed `composer.json` dependencies: `illuminate/database`, `illuminate/queue`
- Removed config keys: `indexing`, `alerts`, `metrics`, `streaming`, `shareable_links`

### Added
- `/log-platform/api/logs` — parsed, filterable log entries read directly from files
- `/log-platform/api/contents` — raw paginated line reader per file
- `/log-platform/api/stream` — filesystem-based SSE live tail (no DB polling)
- `LogClearCommand` (`log:clear`) rewritten to delete physical `.log` files only
- `LogInstallCommand` (`log:install`) simplified to publish config only

### Migration note for existing installs
If you previously ran migrations, the following tables can be safely dropped — they are no longer used:
```sql
DROP TABLE IF EXISTS log_platform_indexed_logs;
DROP TABLE IF EXISTS log_platform_file_states;
DROP TABLE IF EXISTS log_platform_alert_rules;
DROP TABLE IF EXISTS log_platform_alert_events;
DROP TABLE IF EXISTS log_platform_metric_timeseries;
```

---

## [1.0.0] - 2026-03-18

### Added
- Initial release with database indexing, alerting, metrics, and queue-based background processing.
