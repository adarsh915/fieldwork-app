<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - AMPL Fieldwork</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
    <main class="grid min-h-screen grid-cols-1 lg:grid-cols-2">
        <section class="hidden bg-slate-950 px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-md bg-white text-sm font-bold text-slate-950">AF</span>
                    <div>
                        <div class="text-lg font-bold">AMPL Fieldwork</div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Admin Console</div>
                    </div>
                </div>
            </div>

            <div class="max-w-xl">
                <h1 class="text-4xl font-bold leading-tight">Manage field teams, customers, and finance activity from one place.</h1>
                <p class="mt-5 text-base leading-7 text-slate-300">
                    Review users, assign access, monitor customer records, and keep transaction workflows visible for every admin role.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-3 text-sm">
                <div class="rounded-md border border-white/10 bg-white/5 p-4">
                    <div class="font-bold">Users</div>
                    <div class="mt-1 text-slate-400">Access control</div>
                </div>
                <div class="rounded-md border border-white/10 bg-white/5 p-4">
                    <div class="font-bold">Customers</div>
                    <div class="mt-1 text-slate-400">Master data</div>
                </div>
                <div class="rounded-md border border-white/10 bg-white/5 p-4">
                    <div class="font-bold">Finance</div>
                    <div class="mt-1 text-slate-400">Transactions</div>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-center px-5 py-10">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-950 text-sm font-bold text-white">AF</span>
                        <div>
                            <div class="text-lg font-bold">AMPL Fieldwork</div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin Console</div>
                        </div>
                    </div>
                </div>

                <div class="panel p-6">
                    <div>
                        <h1 class="text-2xl font-bold">Sign in</h1>
                        <p class="mt-2 text-sm text-slate-500">Use your administrator account to continue.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Email</label>
                            <input
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                class="form-input mt-1"
                                required
                                autofocus
                            >
                            @error('email')
                                <div class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">Password</label>
                            <input
                                name="password"
                                type="password"
                                class="form-input mt-1"
                                required
                            >
                            @error('password')
                                <div class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm font-medium text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-slate-950 focus:ring-slate-300">
                            Remember me
                        </label>

                        <button class="btn-primary w-full">Sign in</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
