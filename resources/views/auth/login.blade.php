<x-guest-layout>
    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-stretch">
        <section class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-white/85 p-8 shadow-2xl shadow-black/5 backdrop-blur-md dark:border-gray-700/70 dark:bg-gray-800/85 sm:p-10 lg:p-12">
            <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-tosca via-tosca-light to-tosca-dark"></div>

            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-tosca text-lg font-bold text-white shadow-lg shadow-tosca/30">
                    AB
                </div>
                <div>
                    <p class="text-sm font-medium uppercase tracking-[0.24em] text-tosca-dark dark:text-tosca-light">Secure Access</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                        Sign in to {{ config('app.name') }}
                    </h1>
                </div>
            </div>

            <p class="mt-6 max-w-xl text-sm leading-7 text-gray-600 dark:text-gray-300 sm:text-base">
                Monitor AbworksLLM virtual key usage, compare spend against budget, and inspect key-level status from one operational dashboard.
            </p>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-tosca/10 bg-gradient-to-br from-tosca/10 to-white p-4 dark:from-tosca/15 dark:to-gray-800">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Refresh</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">11s</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Live dashboard cadence</p>
                </div>
                <div class="rounded-2xl border border-tosca/10 bg-gradient-to-br from-white to-tosca/10 p-4 dark:from-gray-800 dark:to-tosca/15">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Tracking</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Keys</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Spend, budget, expiry</p>
                </div>
                <div class="rounded-2xl border border-tosca/10 bg-gradient-to-br from-tosca/10 to-tosca-light/10 p-4 dark:from-tosca/15 dark:to-tosca-light/10">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">Theme</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Auto</p>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Dark or light persisted</p>
                </div>
            </div>

            <div class="mt-10 rounded-[1.5rem] border border-gray-200/70 bg-gray-50/80 p-5 dark:border-gray-700/80 dark:bg-gray-900/60">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Operator workflow</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Quick access to blocked keys, spend summaries, and detail inspection.</p>
                    </div>
                    <div class="hidden rounded-full bg-white px-3 py-1 text-xs font-semibold text-tosca shadow-sm dark:bg-gray-800 sm:block">
                        AbworksLLM
                    </div>
                </div>

                <ul class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full bg-tosca"></span>
                        <span>Inspect all virtual keys, including manager-mode keys, in one place.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full bg-tosca-light"></span>
                        <span>Compare total spend against total max budget with 7-day and 30-day trends.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-1 h-2.5 w-2.5 rounded-full bg-tosca-dark"></span>
                        <span>Open key details to review metadata, models, config, status, and expiry.</span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="rounded-[2rem] border border-white/70 bg-white/92 p-8 shadow-2xl shadow-black/10 backdrop-blur-md dark:border-gray-700/70 dark:bg-gray-800/92 sm:p-10" x-data="{ showPassword: false }">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-gray-500 dark:text-gray-400">Authentication</p>
                <h2 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">Welcome back</h2>
                <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">
                    Sign in with your Laravel account to access the monitoring panel.
                </p>
            </div>

            <x-auth-session-status class="mt-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900/60 dark:bg-green-950/40 dark:text-green-300" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('Email Address') }}
                    </label>
                    <x-text-input
                        id="email"
                        class="block w-full rounded-2xl border-gray-200 bg-white/80 px-4 py-3 text-gray-900 shadow-sm transition focus:border-tosca focus:ring-tosca dark:border-gray-700 dark:bg-gray-900/70 dark:text-white"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="name@company.com"
                    />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ __('Password') }}
                        </label>
                        @if (Route::has('password.request'))
                            <a class="text-sm font-medium text-tosca hover:text-tosca-dark dark:text-tosca-light" href="{{ route('password.request') }}">
                                {{ __('Forgot password?') }}
                            </a>
                        @endif
                    </div>

                    <div class="relative">
                        <x-text-input
                            id="password"
                            class="block w-full rounded-2xl border-gray-200 bg-white/80 px-4 py-3 pr-14 text-gray-900 shadow-sm transition focus:border-tosca focus:ring-tosca dark:border-gray-700 dark:bg-gray-900/70 dark:text-white"
                            x-bind:type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 inline-flex items-center px-4 text-sm font-medium text-gray-400 hover:text-tosca"
                        >
                            <span x-text="showPassword ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <label for="remember_me" class="flex items-center justify-between gap-4 rounded-2xl border border-gray-200/80 bg-gray-50/80 px-4 py-3 text-sm dark:border-gray-700/80 dark:bg-gray-900/50">
                    <div>
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ __('Remember me') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Keep this session active on this browser.</p>
                    </div>
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-tosca shadow-sm focus:ring-tosca" name="remember">
                </label>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-tosca to-tosca-dark px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-tosca/25 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-tosca/30 focus:outline-none focus:ring-2 focus:ring-tosca focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    {{ __('Log in') }}
                </button>
            </form>
        </section>
    </div>
</x-guest-layout>
