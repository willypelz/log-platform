# Architecture Overview

## System Design

```
┌─────────────────────────────────────────────────────────────┐
│                        Write Path                            │
├─────────────────────────────────────────────────────────────┤
│  Logger → StrategyRotatingFileHandler → File System         │
│              ↓                                               │
│         Processors (Request ID, Fingerprint, Env)           │
│              ↓                                               │
│         Event (LogWritten) → SSE Stream                     │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                        Index Path                            │
├─────────────────────────────────────────────────────────────┤
│  Artisan Command / Scheduled Job                            │
│              ↓                                               │
│         LogIndexer (Chunked Reader)                         │
│              ↓                                               │
│         LogParser (Pattern Matching)                        │
│              ↓                                               │
│         DatabaseIndexerStore (Bulk Insert)                  │
│              ↓                                               │
│         IndexedLog Model                                    │
└─────────────────────────────────────────────────────────────┘
```

## Key Design Decisions

### 1. Chunked File Reading
- Never load entire log files into memory
- Read in 64KB chunks by default
- Handle partial lines across chunk boundaries
- Track file offset for incremental indexing

### 2. Background Indexing
- Use Laravel queues for async processing
- Checkpoint system prevents duplicate indexing
- Inode tracking detects file rotation
- Bulk database inserts for performance

### 3. Structured Query Language
- Simple syntax (level:error AND user_id:123)
- Tokenizer + AST approach
- Safely compiled to Eloquent/SQL

### 4. Request Correlation
- UUID generation per request
- Processor adds to all log entries
- Enables full request trace

### 5. Error Fingerprinting
- Normalize messages (replace numbers, UUIDs, paths)
- Hash normalized message for grouping
- Fast lookup of similar errors

## Performance Characteristics

### Scale Targets
- Files: 10+ GB
- Entries: 100M+ rows
- Query latency: <100ms p95
- Index throughput: 10K entries/sec

