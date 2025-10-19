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
            return response()->json(['error' => 'HTTP request failed: ' . $e->getMessage()], 500);
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
