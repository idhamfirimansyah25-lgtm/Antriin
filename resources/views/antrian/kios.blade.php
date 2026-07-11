@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto mt-12 px-4 sm:px-6">
    <div class="text-center mb-12">
        <h1 class="font-mono text-3xl md:text-4xl font-bold text-cyan tracking-widest uppercase mb-3">
            Pilih Layanan
        </h1>
        <p class="font-mono text-muted text-xs tracking-widest uppercase">
            Silakan sentuh panel untuk mencetak tiket antrian
        </p>
    </div>

    @if (session('success'))
        <div class="bg-mint/5 border border-mint/30 text-mint px-6 py-4 mb-10 font-mono text-sm text-center">
            Tiket tercetak — {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
        @forelse($layanans as $layanan)
            <form action="{{ route('kios.ambil') }}" method="POST" class="h-full">
                @csrf
                <input type="hidden" name="layanan_id" value="{{ $layanan->id }}">

                <button type="submit" class="w-full h-full bg-panel hover:bg-cyan/5 border border-line hover:border-cyan/40 aspect-[4/3] flex flex-col justify-center items-center group transition-all duration-300 relative overflow-hidden focus:outline-none focus:ring-2 focus:ring-cyan focus:ring-offset-2 focus:ring-offset-bg">

                    <div class="absolute top-0 left-0 w-full h-1 bg-line group-hover:bg-cyan transition-colors duration-300"></div>

                    <span class="font-mono text-[8rem] md:text-[10rem] leading-none font-bold text-ink/10 group-hover:text-cyan transition-colors duration-300 mb-2 pointer-events-none">
                        {{ $layanan->kode ?? substr($layanan->nama_layanan, 0, 1) }}
                    </span>

                    <span class="font-sans font-semibold text-2xl lg:text-3xl text-ink/80 group-hover:text-ink tracking-wide transition-colors pointer-events-none">
                        {{ $layanan->nama_layanan }}
                    </span>

                    <span class="absolute bottom-4 right-4 text-cyan/0 group-hover:text-cyan/60 font-mono text-xl transition-all duration-300 pointer-events-none">
                        +
                    </span>
                </button>
            </form>
        @empty
            <div class="col-span-full border border-red-200 bg-red-50 p-12 text-center">
                <span class="font-mono text-red-500 text-sm tracking-widest uppercase block mb-2">Peringatan Sistem</span>
                <span class="font-sans text-ink/60 text-lg">Tidak ada layanan yang aktif saat ini. Hubungi Administrator.</span>
            </div>
        @endforelse
    </div>
</div>
@endsection