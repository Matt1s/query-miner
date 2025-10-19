<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    /**
     * Handle search request and call Google Custom Search API.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:512',
        ]);

        $q = $request->input('q');

        // Toggle this to true to use the local fixture instead of calling Google
        // Can be overridden by passing 'debug' parameter in the request
        $debug = $request->boolean('debug', false);

        // If debug mode is enabled, return example_result.json fixture instead of calling Google
        if ($debug) {
            $path = base_path('example_result.json');
            if (! file_exists($path)) {
                return response()->json(['error' => 'example_result.json fixture not found'], 500);
            }
            $body = json_decode(file_get_contents($path), true);
            $items = $body['results'] ?? [];
            $results = array_map(function ($it) {
                return [
                    'title' => $it['title'] ?? null,
                    'snippet' => $it['snippet'] ?? null,
                    'link' => $it['link'] ?? null,
                    'displayLink' => $it['displayLink'] ?? null,
                ];
            }, $items);

            return response()->json([
                'query' => $q,
                'totalResults' => $body['searchInformation']['totalResults'] ?? null,
                'results' => $results,
                'raw' => $body,
            ]);
        } else {

            $apiKey = env('GOOGLE_API_KEY');
            $cx = env('GOOGLE_CX');

            if (empty($apiKey) || empty($cx)) {
                return response()->json(['error' => 'Google API key or CX (search engine id) not configured. Set GOOGLE_API_KEY and GOOGLE_CX in .env.'], 500);
            }

            $endpoint = 'https://www.googleapis.com/customsearch/v1';

            try {
                $res = Http::get($endpoint, [
                    'key' => $apiKey,
                    'cx' => $cx,
                    'q' => $q,
                    'num' => 10,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => 'HTTP request failed: '.$e->getMessage()], 500);
            }

            if ($res->failed()) {
                return response()->json(['error' => 'Google API request failed', 'details' => $res->body()], 500);
            }

            $body = $res->json();

            // Google Custom Search returns 'items' for results. We'll map to a minimal structure.
            $items = $body['items'] ?? [];

            $results = array_map(function ($it) {
                return [
                    'title' => $it['title'] ?? null,
                    'snippet' => $it['snippet'] ?? null,
                    'link' => $it['link'] ?? null,
                    'displayLink' => $it['displayLink'] ?? null,
                ];
            }, $items);

            return response()->json([
                'query' => $q,
                'totalResults' => $body['searchInformation']['totalResults'] ?? null,
                'results' => $results,
                'raw' => $body,
            ]);
        }
    }

    /**
     * Export search results as JSON file.
     */
    public function exportJson(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:512',
        ]);

        // Perform the search
        $searchResponse = $this->search($request);
        $data = $searchResponse->getData(true);

        if (isset($data['error'])) {
            return response()->json($data, 500);
        }

        $filename = 'search-results-'.date('Y-m-d-His').'.json';

        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Export search results as CSV file with UTF-8 BOM for Excel compatibility.
     */
    public function exportCsv(Request $request)
    {
        $request->validate([
            'q' => 'required|string|max:512',
        ]);

        // Perform the search
        $searchResponse = $this->search($request);
        $data = $searchResponse->getData(true);

        if (isset($data['error'])) {
            return response()->json($data, 500);
        }

        $results = $data['results'] ?? [];

        // Generate CSV with UTF-8 BOM
        $csv = $this->generateCsv($results);

        $filename = 'search-results-'.date('Y-m-d-His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Generate CSV content from results array with UTF-8 BOM.
     */
    protected function generateCsv(array $results): string
    {
        // Start with UTF-8 BOM for Excel compatibility
        $csv = "\xEF\xBB\xBF";

        // Add header row
        $headers = ['title', 'snippet', 'link', 'displayLink'];
        $csv .= implode(',', array_map(fn ($h) => '"'.$h.'"', $headers))."\r\n";

        // Add data rows
        foreach ($results as $result) {
            $row = [];
            foreach ($headers as $header) {
                $value = $result[$header] ?? '';
                // Escape double quotes by doubling them
                $value = str_replace('"', '""', $value);
                $row[] = '"'.$value.'"';
            }
            $csv .= implode(',', $row)."\r\n";
        }

        return $csv;
    }
}
