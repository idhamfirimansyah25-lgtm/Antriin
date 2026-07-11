@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto mt-20 w-full">
    <div class="bg-panel p-8 border border-line shadow-sm relative">

        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-cyan to-transparent"></div>

        <div class="text-center mb-10">
            <h2 class="text-2xl font-mono font-bold text-cyan tracking-widest uppercase">Operator Login</h2>
            <p class="text-muted font-mono text-[11px] mt-2 uppercase tracking-widest">Autorisasi Sistem Diperlukan</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 mb-8 font-mono text-xs">
                <span class="block sm:inline">Error: {{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <div class="mb-6">
                <label class="block text-muted font-mono text-[11px] mb-2 uppercase tracking-wide" for="email">
                    Alamat Email
                </label>
                <input class="w-full bg-bg border border-line text-ink py-3 px-4 font-mono text-sm focus:outline-none focus:border-cyan focus:ring-1 focus:ring-cyan transition-colors" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="off">
            </div>

            <div class="mb-10">
                <label class="block text-muted font-mono text-[11px] mb-2 uppercase tracking-wide" for="password">
                    Kata Sandi
                </label>
                <input class="w-full bg-bg border border-line text-ink py-3 px-4 font-mono text-sm focus:outline-none focus:border-cyan focus:ring-1 focus:ring-cyan transition-colors" id="password" type="password" name="password" required>
            </div>

            <div class="flex items-center justify-between">
                <button class="w-full bg-cyan hover:bg-cyan/90 text-white font-sans font-bold tracking-widest py-3 px-4 transition-colors duration-200" type="submit">
                    Masuk
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-line text-center">
            <p class="font-mono text-[10px] text-muted mb-2 uppercase">Kredensial Uji Admin</p>
            <div class="inline-block bg-bg border border-line px-4 py-2 font-mono text-xs">
                <span class="text-amber">cs1@antrian.com</span>
                <span class="text-muted/40 mx-2">|</span>
                <span class="text-ink/70">password</span>
            </div>
        </div>
    </div>
</div>
@endsection