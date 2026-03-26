# File Format Converter

A REST API built with Symfony that converts uploaded files (CSV, JSON, XLSX, ODS) between supported formats. File conversion jobs are processed asynchronously via Symfony Messenger.

## Setup

### With Makefile (recommended)

```bash
make setup
```

This single command will:

1. Build and start all Docker containers (PHP, PostgreSQL, Nginx)
2. Install Composer dependencies
3. Run database migrations

Other useful commands — run `make help` for the full list:

```bash
make up              # Start services
make stop            # Stop services
make down            # Stop and remove containers
make restart         # Restart all services
make test            # Run all tests
make test-functional # Run functional tests only
make test-file FILE=tests/functional/Controller/FileConversionStatusTest.php
make shell-php       # Shell into the PHP container
```

### Without Makefile

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

## Async Processing with Symfony Messenger

File conversions are not processed inline. When a file is uploaded via `POST /api/file-conversions/upload`, a `ConvertFileMessage` is dispatched to the `async` transport for each requested target format. The message carries the `FileConversionJob` ID.

To actually process queued jobs you must run the Messenger worker:

```bash
docker compose exec php php bin/console messenger:consume async
```

The worker picks up each `ConvertFileMessage`, converts the source file to the target format, and updates the `FileConversionJob` status from `pending` → `processing` → `completed` (or `failed` on error).

Without a running worker, jobs will stay in `pending` status indefinitely.

### Retry strategy

Failed messages are automatically retried up to **3 times** with exponential back-off (1 s → 2 s → 4 s, max 30 s). After all retries are exhausted the message is moved to the `failed` transport.

To inspect and retry failed messages:

```bash
docker compose exec php php bin/console messenger:failed:show
docker compose exec php php bin/console messenger:failed:retry
```

## Project Structure

```
src/
├── Controller/              # API endpoints (upload, status, download)
├── Dto/
│   ├── Request/             # Input DTOs with validation constraints
│   └── Response/            # Output DTOs for JSON serialization
├── Entity/                  # Doctrine entities (File, FileConversionJob)
├── Enum/
│   └── FileConversionJob/   # Status and target format enums
├── Listener/                # Kernel event listeners (exception handling)
├── Manager/                 # Entity persistence logic (File, FileConversionJob)
├── Message/                 # Messenger message classes
├── MessageHandler/          # Messenger handlers (async job processing)
├── Repository/              # Doctrine repositories
└── Service/                 # Core business logic
    ├── ConversionDispatcherService.php   # Orchestrates upload + dispatch
    ├── ConversionProcessorService.php    # Runs a single conversion job
    ├── FileConversionService.php         # Performs the actual file conversion
    └── ValidatorService.php              # DTO validation helper


tests/
├── Factory/                 # Foundry factories (File, FileConversionJob)
├── functional/
│   └── Controller/          # HTTP-level tests for each endpoint
└── Unit/
    └── Service/             # Unit tests with mocked dependencies
```

## API Endpoints

Once the project is set up, the interactive OpenAPI documentation is available at [http://127.0.0.1/api/doc](http://127.0.0.1/api/doc).

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/file-conversions/upload` | Upload a file and request conversion to one or more target formats |
| `GET` | `/api/file-conversions/{id}/status` | Check the status of a conversion job |
| `GET` | `/api/file-conversions/{id}/download` | Download the converted file (once completed) |

## Running Tests

```bash
make test                    # All tests
make test TESTDOX=1          # With --testdox output
make test-unit               # Unit tests only
make test-functional         # Functional tests only
make test-file FILE=path     # Single test file
```
