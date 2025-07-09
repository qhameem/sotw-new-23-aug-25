<div x-data="gaStats()" x-init="fetchStats()" class="bg-white  rounded-lg p-6 border border-gray-200 ">
    <h3 class="text-lg font-semibold text-gray-900  mb-4">Traffic Statistics (Last 7 Days)</h3>
    <div x-show="loading" class="text-center text-gray-500  py-4">
        Loading stats...
    </div>
    <div x-show="error" class="text-center text-red-500  py-4" x-cloak>
        <p>Could not load traffic statistics.</p>
        <p x-text="errorMessage" class="text-sm"></p>
    </div>
    <div x-show="!loading && !error && stats" class="space-y-3" x-cloak>
        <div class="flex justify-between items-center">
            <span class="text-gray-600 ">Active Users:</span>
            <span class="font-bold text-gray-800 " x-text="stats.activeUsers !== undefined ? stats.activeUsers : 'N/A'"></span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-600 ">Page Views:</span>
            <span class="font-bold text-gray-800 " x-text="stats.screenPageViews !== undefined ? stats.screenPageViews : 'N/A'"></span>
        </div>
        <p class="text-xs text-gray-400  pt-2">
            Last updated: <span x-text="stats.last_updated ? new Date(stats.last_updated).toLocaleString() : 'N/A'"></span>
        </p>
    </div>
</div>

<script>
    function gaStats() {
        return {
            loading: true,
            error: false,
            errorMessage: '',
            stats: null,
            fetchStats() {
                fetch('/api/ga-stats')
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => { throw err; });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            this.error = true;
                            this.errorMessage = data.message || 'An unknown error occurred.';
                            console.error('Error fetching GA stats:', data);
                        } else {
                            this.stats = data;
                        }
                    })
                    .catch(error => {
                        this.error = true;
                        this.errorMessage = error.message || 'Failed to connect to the server.';
                        console.error('Failed to fetch GA stats:', error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        }
    }
</script>