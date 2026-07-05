<x-apps.app-public-jobs>

    <section class="bg-gray-50 border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="mb-8">
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 tracking-tight">
                    Find your next <span class="text-[#a31b1b]">role</span>
                </h1>
                <p class="text-gray-500 mt-2 text-lg">Browse open positions from top companies — no account needed.</p>
            </div>

            <div class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-6 md:p-8">
                <form action="{{ route('jobseeker.jobs.search') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-grow relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-tag text-gray-400 text-lg"></i>
                        </div>
                        <input type="search" name="categorie" placeholder="Search by Category (e.g., Tech, Design)"
                               class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-[#a31b1b] focus:border-[#a31b1b] transition-colors font-medium">
                    </div>

                    <div class="flex-grow relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="bi bi-geo-alt text-gray-400 text-lg"></i>
                        </div>
                        <input type="search" name="location" placeholder="Search by Location"
                               class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 text-gray-900 rounded-xl focus:ring-[#a31b1b] focus:border-[#a31b1b] transition-colors font-medium">
                    </div>

                    <button type="submit" class="bg-[#a31b1b] hover:bg-[#8a1717] text-white px-8 py-3.5 rounded-xl font-bold transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2 md:w-auto w-full">
                        <i class="bi bi-search"></i>
                        Search Jobs
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @include('incs.alert')

            @if($jobs->isEmpty())
                <div class="bg-white border border-red-100 bg-red-50/30 rounded-2xl p-8 text-center">
                    <div class="text-red-500 mb-4">
                        <i class="bi bi-search text-5xl opacity-50"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">No jobs found</h3>
                    <p class="text-gray-500">Try adjusting your search filters to find more opportunities.</p>
                </div>
            @else
                <div class="flex justify-between items-center">
                    <p class="text-gray-500 font-medium">
                        Showing <span class="text-gray-900 font-bold">{{ $jobs->count() }}</span>
                        {{ $jobs->count() === 1 ? 'job' : 'jobs' }} on this page
                    </p>
                    @guest
                        <a href="{{ route('register') }}"
                           class="text-sm font-bold text-[#a31b1b] hover:text-[#8a1717] no-underline flex items-center gap-1">
                            <i class="bi bi-person-plus"></i> Create a free account to apply
                        </a>
                    @endguest
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($jobs as $job)
                        @include('Jobseeker.partials.job-card', ['job' => $job])
                    @endforeach
                </div>
            @endif

            <div class="mt-8">
                {{ $jobs->links() }}
            </div>

        </div>
    </section>
</x-apps.app-public-jobs>
