@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="text-2xl font-semibold">Query Miner</h1>
        <p class="text-sm text-slate-500 mt-1">Enter a keyword phrase and press Search. (Simplified view.)</p>

        <form id="searchForm" method="post" class="mt-4">
            @csrf
            <div class="relative">
                <input id="q" name="q" type="text" placeholder="e.g. how to bake a bundt cake" required
                    class="w-full border-2 border-slate-300 rounded-md px-4 py-3 focus:outline-none focus:ring-4 focus:ring-indigo-100" />
                <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 bg-gradient-to-r from-violet-600 to-indigo-600 text-white px-4 py-2 rounded-md">Search</button>
            </div>

            <div id="status" class="mt-3 text-sm text-slate-500"></div>
        </form>
    </div>

    @push('scripts')
    <script>
        const form = document.getElementById('searchForm');
        const input = document.getElementById('q');
        const status = document.getElementById('status');

        form.addEventListener('submit', function(e){
            e.preventDefault();
            const q = input.value.trim();
            if(!q) return;
            status.textContent = 'Query received: ' + q + ' — server endpoint not yet implemented.';
        });
    </script>
    @endpush
@endsection
