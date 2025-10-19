# Query Miner

A lightweight web application for searching and exporting Google Custom Search results in structured formats.

## Functionality

Query Miner provides a simple interface for performing Google searches and exporting results in machine-readable formats:

- **Search Interface**: Clean, responsive UI for entering search queries
- **Google Custom Search Integration**: Retrieves organic search results via Google Custom Search JSON API
- **Export Formats**: 
  - JSON with UTF-8 encoding (unescaped Unicode, pretty-printed)
  - CSV with UTF-8 BOM for Excel compatibility (proper escaping, CRLF line endings)
- **Debug Mode**: Offline testing with fixture data from `example_result.json`
- **Server-Side Processing**: All export generation happens on the backend for consistency and testability

## Technologies

**Backend:**
- Laravel 11 (PHP 8.2)
- Pest PHP for testing
- Google Custom Search JSON API integration

**Frontend:**
- Blade templating engine
- Tailwind CSS for styling
- Vite for asset bundling
- Vanilla JavaScript (no framework dependencies)

**Infrastructure:**
- Docker & Docker Compose for containerization
- Nginx for web server
- PHP-FPM for PHP processing

## Testing

The application includes comprehensive feature tests covering all controller methods:

- **29 tests, 83 assertions**
- Search functionality with fixture and mocked API responses
- JSON export validation (UTF-8, character preservation)
- CSV export validation (BOM, encoding, quote escaping, CRLF)
- Helper method unit tests (CSV generation logic)

Run tests:
```bash
vendor/bin/pest
```

## Setup

### Local Development (Windows/Linux/Mac)

**Prerequisites:**
- PHP 8.2 or higher
- Composer
- Node.js & npm

**Installation:**

1. Clone the repository:
```bash
git clone https://github.com/Matt1s/query-miner.git
cd query-miner
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Add your Google API credentials to `.env`:
```env
GOOGLE_API_KEY=your_api_key_here
GOOGLE_CX=your_search_engine_id_here
```

5. Build frontend assets:
```bash
npm run build
```

6. Start development server:
```bash
php artisan serve
```

Access the application at `http://localhost:8000`

### Docker Setup

**Prerequisites:**
- Docker Desktop

**Installation:**

1. Clone the repository:
```bash
git clone https://github.com/Matt1s/query-miner.git
cd query-miner
```

2. Configure environment:
```bash
cp .env.docker .env
```

3. Add your Google API credentials to `.env`:
```env
GOOGLE_API_KEY=your_api_key_here
GOOGLE_CX=your_search_engine_id_here
```

4. Start containers:
```bash
docker-compose up -d
```

The application will be available at `http://localhost:8080`

The Docker setup automatically:
- Generates the Laravel application key
- Installs all dependencies
- Builds frontend assets
- Configures nginx and PHP-FPM

**Docker Commands:**

- View logs: `docker-compose logs -f`
- Stop containers: `docker-compose down`
- Rebuild: `docker-compose up -d --build`
- Run tests: `docker-compose exec app vendor/bin/pest`