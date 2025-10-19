@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-2xl font-semibold">Query Miner</h1>
        <p class="text-sm text-slate-500 mt-1">Enter a keyword phrase and press Search.</p>

        <form id="searchForm" method="post" class="mt-4">
            @csrf
            <div class="relative">
                <input id="q" name="q" type="text" placeholder="e.g. how to bake a bundt cake" required
                    class="w-full border-2 border-slate-300 rounded-md px-4 py-3 focus:outline-none focus:ring-4 focus:ring-indigo-100" />
                <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 bg-gradient-to-r from-violet-600 to-indigo-600 text-white px-4 py-2 rounded-md">Search</button>
            </div>

            <div id="status" class="mt-3 text-sm text-slate-500 break-all"></div>
        </form>

        <section id="resultsSection" class="mt-6 hidden">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">Results</h2>
                <div class="flex gap-2">
                    <button id="downloadJson" class="text-sm px-3 py-1 bg-slate-100 rounded">Download JSON</button>
                    <button id="downloadCsv" class="text-sm px-3 py-1 bg-slate-100 rounded">Download CSV</button>
                </div>
            </div>
            <div id="results" class="mt-3 space-y-3 text-slate-700"></div>
        </section>
    </div>

    @push('scripts')
    <script>
        const form = document.getElementById('searchForm');
        const input = document.getElementById('q');
        const status = document.getElementById('status');
        const resultsSection = document.getElementById('resultsSection');
        const resultsContainer = document.getElementById('results');
        const downloadJson = document.getElementById('downloadJson');
        const downloadCsv = document.getElementById('downloadCsv');

        function renderResults(data){
            resultsContainer.innerHTML = '';
            if(!data || !data.results || data.results.length === 0){
                resultsContainer.innerHTML = '<div class="text-sm text-slate-500">No results found.</div>';
                return;
            }

            data.results.forEach((r, i) => {
                const el = document.createElement('div');
                el.className = 'p-3 border rounded';
                el.innerHTML = `<a href="${r.link}" target="_blank" class="text-indigo-600 font-medium">${escapeHtml(r.title || r.link)}</a><div class="text-sm text-slate-600 mt-1">${escapeHtml(r.snippet || '')}</div><div class="text-xs text-slate-400 mt-1">${escapeHtml(r.displayLink || '')}</div>`;
                resultsContainer.appendChild(el);
            });
        }

        function escapeHtml(s){
            if(!s) return '';
            return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        form.addEventListener('submit', async function(e){
            e.preventDefault();
            const q = input.value.trim();
            if(!q) return;
            status.textContent = 'Searching...';
            resultsSection.classList.add('hidden');
            resultsContainer.innerHTML = '';

            try{
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch('/search/api', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ q })
                });

                const data = await res.json();
                if(res.ok){
                    status.textContent = 'Results for "' + q + '"';
                    renderResults(data);
                    resultsSection.classList.remove('hidden');
                    
                    // Store query for export endpoints
                    const currentQuery = q;
                    
                    // Update download handlers to call server-side export endpoints
                    downloadJson.onclick = () => downloadFromServer('/search/export/json', currentQuery);
                    downloadCsv.onclick = () => downloadFromServer('/search/export/csv', currentQuery);
                } else {
                    status.textContent = data.error || 'Search failed';
                }
            } catch(err){
                status.textContent = 'Request failed: ' + err.message;
            }
        });

        async function downloadFromServer(endpoint, query) {
            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({ q: query })
                });

                if (res.ok) {
                    // Get the filename from Content-Disposition header or generate one
                    const contentDisposition = res.headers.get('Content-Disposition');
                    let filename = 'download';
                    if (contentDisposition) {
                        const match = contentDisposition.match(/filename="(.+)"/);
                        if (match) filename = match[1];
                    }

                    // Download the file
                    const blob = await res.blob();
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                } else {
                    const error = await res.json();
                    alert('Export failed: ' + (error.error || 'Unknown error'));
                }
            } catch (err) {
                alert('Export failed: ' + err.message);
            }
        }
    </script>
    @endpush
@endsection
