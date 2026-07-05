<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} | Find Jobs</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/btn.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --jb-red: #a31b1b;
            --jb-red-dark: #8b1616;
        }
        body {
            font-family: 'Figtree', sans-serif;
        }
        .jb-nav-link {
            font-weight: 600;
            font-size: 1.05rem;
            color: #4a5568;
            text-decoration: none !important;
            transition: color 0.2s ease;
        }
        .jb-nav-link:hover,
        .jb-nav-link.active {
            color: var(--jb-red);
        }
        .jb-btn-primary {
            background-color: var(--jb-red);
            border: 1px solid var(--jb-red);
            color: #fff;
            font-weight: 700;
            padding: 0.55rem 1.25rem;
            border-radius: 12px;
            text-decoration: none !important;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .jb-btn-primary:hover {
            background-color: var(--jb-red-dark);
            border-color: var(--jb-red-dark);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 8px 15px -3px rgba(163, 27, 27, 0.3);
        }
    </style>
</head>
<body class="bg-white text-gray-900 antialiased">

    <nav class="sticky top-0 z-40 bg-white/85 backdrop-blur-lg border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 no-underline">
                    <img src="{{ asset('imgs/empj.png') }}" alt="JobBoard Logo" class="h-10 w-auto" />
                    <span class="text-2xl font-extrabold tracking-tighter text-gray-900 hidden sm:block">Job<span class="text-[#a31b1b]">Board</span></span>
                </a>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('jobseeker.jobs.index') }}"
                       class="jb-nav-link {{ request()->routeIs('jobseeker.jobs.index') || request()->routeIs('jobseeker.jobs.search') ? 'active' : '' }}">
                        Find Jobs
                    </a>
                    <a href="{{ route('register') }}" class="jb-nav-link">For Employers</a>
                </div>

                <div class="flex items-center space-x-4">
                    @auth
                        @if (auth()->user()->role === 'Job Seeker')
                            <a href="{{ route('jobseeker.dashboard') }}" class="jb-btn-primary">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        @elseif (auth()->user()->role === 'employer')
                            <a href="{{ route('employer.dashboard') }}" class="jb-btn-primary">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        @elseif (auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="jb-btn-primary">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="text-lg font-bold text-gray-700 hover:text-[#a31b1b] transition-colors no-underline">Log In</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="jb-btn-primary text-lg">Sign Up</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    <footer class="bg-gray-50 py-14 border-t border-gray-100 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('imgs/empj.png') }}" alt="JobBoard Logo" class="h-9 w-auto" />
                    <span class="text-xl font-extrabold tracking-tighter text-gray-900">Job<span class="text-[#a31b1b]">Board</span></span>
                </div>
                <div class="text-sm text-gray-400 font-medium">
                    &copy; {{ date('Y') }} JobBoard Platform. All rights reserved.
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
