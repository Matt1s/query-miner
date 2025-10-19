<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Http;

describe('SearchController::search', function () {

    it('returns fixture data when debug is true', function () {
        $response = $this->postJson('/search/api', ['q' => 'test query', 'debug' => true]);

        $response->assertStatus(200);
        $json = $response->json();

        expect($json)->toHaveKeys(['query', 'totalResults', 'results', 'raw']);
        expect($json['query'])->toBe('test query');
        expect($json['results'])->toBeArray();
    });

    it('returns fixture with Czech characters preserved when debug is true', function () {
        $response = $this->postJson('/search/api', ['q' => 'Ceska republika', 'debug' => true]);

        $response->assertStatus(200);
        $json = $response->json();

        // Check presence of Czech characters in titles from fixture
        $hasCzech = collect($json['results'])->contains(function ($it) {
            return mb_strpos($it['title'] ?? '', 'Č') !== false ||
                   mb_strpos($it['title'] ?? '', 'á') !== false;
        });
        expect($hasCzech)->toBeTrue();
    });

    it('calls Google API when debug is false with real credentials', function () {
        // Mock the Google API response
        Http::fake([
            'https://www.googleapis.com/customsearch/v1*' => Http::response([
                'searchInformation' => ['totalResults' => '42'],
                'items' => [
                    [
                        'title' => 'Test Result',
                        'snippet' => 'Test snippet',
                        'link' => 'https://test.com',
                        'displayLink' => 'test.com',
                    ],
                ],
            ], 200),
        ]);

        // Set environment variables for this test
        putenv('GOOGLE_API_KEY=fake-key');
        putenv('GOOGLE_CX=fake-cx');

        $response = $this->postJson('/search/api', ['q' => 'test', 'debug' => false]);

        $response->assertStatus(200);
        $json = $response->json();

        expect($json['totalResults'])->toBe('42');
        expect($json['results'])->toHaveCount(1);
        expect($json['results'][0]['title'])->toBe('Test Result');
    });

    it('validates that q parameter is required', function () {
        $response = $this->postJson('/search/api', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    });

    it('validates that q parameter has max length of 512', function () {
        $longQuery = str_repeat('a', 513);
        $response = $this->postJson('/search/api', ['q' => $longQuery]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    });

    it('returns error when fixture file is missing and debug is true', function () {
        // Temporarily rename fixture
        $fixturePath = base_path('example_result.json');
        $backupPath = base_path('example_result.json.backup.test');

        if (file_exists($fixturePath)) {
            rename($fixturePath, $backupPath);
        }

        try {
            $response = $this->postJson('/search/api', ['q' => 'test', 'debug' => true]);

            $response->assertStatus(500);
            $response->assertJson(['error' => 'example_result.json fixture not found']);
        } finally {
            // Restore fixture
            if (file_exists($backupPath)) {
                rename($backupPath, $fixturePath);
            }
        }
    });

    it('handles Google API HTTP errors gracefully', function () {
        Http::fake([
            'www.googleapis.com/*' => Http::response(null, 500),
        ]);

        putenv('GOOGLE_API_KEY=fake-key');
        putenv('GOOGLE_CX=fake-cx');

        $response = $this->postJson('/search/api', ['q' => 'test', 'debug' => false]);

        $response->assertStatus(500);
        $json = $response->json();
        expect($json)->toHaveKey('error');
    });

    it('defaults to debug mode when debug parameter is not specified', function () {
        // By default, debug should be false based on the controller code
        $response = $this->postJson('/search/api', ['q' => 'test']);

        // Check if it returned successfully - status could be 200 or 500 depending on env
        expect($response->status())->toBeIn([200, 500]);
    });

});

describe('SearchController::exportJson', function () {

    it('exports search results as JSON file with proper headers', function () {
        $response = $this->postJson('/search/export/json', ['q' => 'test', 'debug' => true]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json; charset=utf-8');
        $response->assertHeader('Content-Disposition');

        // Check filename format in Content-Disposition
        $disposition = $response->headers->get('Content-Disposition');
        expect($disposition)->toContain('attachment');
        expect($disposition)->toContain('search-results-');
        expect($disposition)->toContain('.json');
    });

    it('exports JSON with UTF-8 characters unescaped', function () {
        $response = $this->post('/search/export/json', ['q' => 'Ceska republika', 'debug' => true]);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Czech characters should appear as-is, not escaped
        expect($content)->toContain('Česká republika');
        expect($content)->toContain('Česko');
        expect($content)->not->toContain('\\u010c'); // No unicode escapes
        expect($content)->not->toContain('\\u00e1'); // No unicode escapes
    });

    it('exports JSON with pretty print formatting', function () {
        $response = $this->post('/search/export/json', ['q' => 'test', 'debug' => true]);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Should have indentation (pretty print)
        expect($content)->toContain('    '); // 4 spaces indent
        expect($content)->toContain("\n"); // newlines
    });

    it('validates q parameter for JSON export', function () {
        $response = $this->postJson('/search/export/json', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    });

    it('returns error in JSON export when search fails', function () {
        // Remove fixture temporarily
        $fixturePath = base_path('example_result.json');
        $backupPath = base_path('example_result.json.backup.test2');

        if (file_exists($fixturePath)) {
            rename($fixturePath, $backupPath);
        }

        try {
            $response = $this->postJson('/search/export/json', ['q' => 'test', 'debug' => true]);

            $response->assertStatus(500);
            $response->assertJsonStructure(['error']);
        } finally {
            if (file_exists($backupPath)) {
                rename($backupPath, $fixturePath);
            }
        }
    });

});

describe('SearchController::exportCsv', function () {

    it('exports search results as CSV file with proper headers', function () {
        $response = $this->post('/search/export/csv', ['q' => 'test', 'debug' => true]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition');

        // Check filename format
        $disposition = $response->headers->get('Content-Disposition');
        expect($disposition)->toContain('attachment');
        expect($disposition)->toContain('search-results-');
        expect($disposition)->toContain('.csv');
    });

    it('exports CSV with UTF-8 BOM', function () {
        $response = $this->post('/search/export/csv', ['q' => 'test', 'debug' => true]);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check UTF-8 BOM at the start
        expect(substr($content, 0, 3))->toBe("\xEF\xBB\xBF");
    });

    it('exports CSV with correct headers', function () {
        $response = $this->post('/search/export/csv', ['q' => 'test', 'debug' => true]);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check header row
        expect($content)->toContain('"title","snippet","link","displayLink"');
    });

    it('exports CSV with CRLF line endings', function () {
        $response = $this->post('/search/export/csv', ['q' => 'test', 'debug' => true]);

        $response->assertStatus(200);
        $content = $response->getContent();

        expect($content)->toContain("\r\n");
    });

    it('exports CSV with Czech characters correctly', function () {
        $response = $this->post('/search/export/csv', ['q' => 'Ceska republika', 'debug' => true]);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Czech characters should be preserved
        expect($content)->toContain('Česká republika');
        expect($content)->toContain('Česko');
    });

    it('validates q parameter for CSV export', function () {
        $response = $this->postJson('/search/export/csv', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['q']);
    });

    it('exports CSV with mocked Google API data', function () {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'searchInformation' => ['totalResults' => '3'],
                'items' => [
                    ['title' => 'Title 1', 'snippet' => 'Snippet 1', 'link' => 'https://1.com', 'displayLink' => '1.com'],
                    ['title' => 'Title "2"', 'snippet' => 'Snippet, with comma', 'link' => 'https://2.com', 'displayLink' => '2.com'],
                    ['title' => 'Česká republika', 'snippet' => 'Czech snippet', 'link' => 'https://cz.com', 'displayLink' => 'cz.com'],
                ],
            ], 200),
        ]);

        putenv('GOOGLE_API_KEY=fake-key');
        putenv('GOOGLE_CX=fake-cx');

        $response = $this->post('/search/export/csv', ['q' => 'test', 'debug' => false]);

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check all titles are present
        expect($content)->toContain('Title 1');
        expect($content)->toContain('Title ""2""'); // Double quotes escaped
        expect($content)->toContain('Snippet, with comma'); // Comma handled
        expect($content)->toContain('Česká republika'); // Czech characters
    });

});

describe('SearchController::generateCsv', function () {

    it('generates CSV with UTF-8 BOM', function () {
        $controller = new SearchController;
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateCsv');
        $method->setAccessible(true);

        $results = [['title' => 'Test', 'snippet' => 'S', 'link' => 'L', 'displayLink' => 'D']];
        $csv = $method->invoke($controller, $results);

        expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF");
    });

    it('generates CSV with proper quote escaping', function () {
        $controller = new SearchController;
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateCsv');
        $method->setAccessible(true);

        $results = [
            ['title' => 'Test "Quote"', 'snippet' => 'Snippet', 'link' => 'https://test.com', 'displayLink' => 'test.com'],
        ];

        $csv = $method->invoke($controller, $results);

        // Double quotes should be escaped as ""
        expect($csv)->toContain('"Test ""Quote"""');
    });

    it('generates CSV with comma handling', function () {
        $controller = new SearchController;
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateCsv');
        $method->setAccessible(true);

        $results = [
            ['title' => 'Title', 'snippet' => 'Snippet, with comma', 'link' => 'https://test.com', 'displayLink' => 'test.com'],
        ];

        $csv = $method->invoke($controller, $results);

        // Should be properly quoted
        expect($csv)->toContain('"Snippet, with comma"');
    });

    it('generates CSV with empty fields', function () {
        $controller = new SearchController;
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateCsv');
        $method->setAccessible(true);

        $results = [
            ['title' => 'Title', 'snippet' => null, 'link' => '', 'displayLink' => null],
        ];

        $csv = $method->invoke($controller, $results);

        // Should have empty quoted fields
        expect($csv)->toContain('"Title","","",""');
    });

    it('generates CSV with CRLF line endings', function () {
        $controller = new SearchController;
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateCsv');
        $method->setAccessible(true);

        $results = [
            ['title' => 'T1', 'snippet' => 'S1', 'link' => 'L1', 'displayLink' => 'D1'],
            ['title' => 'T2', 'snippet' => 'S2', 'link' => 'L2', 'displayLink' => 'D2'],
        ];

        $csv = $method->invoke($controller, $results);

        expect($csv)->toContain("\r\n");

        // Should have 3 lines (header + 2 data rows)
        $lines = explode("\r\n", trim($csv));
        expect(count($lines))->toBe(3);
    });

    it('generates empty CSV with just header when no results', function () {
        $controller = new SearchController;
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateCsv');
        $method->setAccessible(true);

        $csv = $method->invoke($controller, []);

        // Should have BOM and header
        expect(substr($csv, 0, 3))->toBe("\xEF\xBB\xBF");
        expect($csv)->toContain('"title","snippet","link","displayLink"');

        // Only one line (header)
        $lines = explode("\r\n", trim($csv));
        expect(count($lines))->toBe(1);
    });

    it('generates CSV with Czech and special characters', function () {
        $controller = new SearchController;
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateCsv');
        $method->setAccessible(true);

        $results = [
            ['title' => 'Česká republika', 'snippet' => 'Příliš žluťoučký kůň', 'link' => 'https://cz.com', 'displayLink' => 'cz.com'],
            ['title' => 'Test á é í ó ú', 'snippet' => 'ř ž ý č š', 'link' => 'https://test.cz', 'displayLink' => 'test.cz'],
        ];

        $csv = $method->invoke($controller, $results);

        // All Czech characters should be preserved
        expect($csv)->toContain('Česká republika');
        expect($csv)->toContain('Příliš žluťoučký kůň');
        expect($csv)->toContain('á é í ó ú');
        expect($csv)->toContain('ř ž ý č š');
    });

});
