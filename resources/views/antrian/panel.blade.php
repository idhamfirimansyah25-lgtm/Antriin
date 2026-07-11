@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-12 w-full">
    
    <div class="bg-board-panel border border-white/5 shadow-[0_15px_40px_rgba(0,0,0,0.4)] p-8">
        
        <div class="flex justify-between items-end border-b border-white/10 pb-5 mb-8">
            <div>
                <h1 class="font-mono text-led-cyan text-sm tracking-widest uppercase mb-1">
                    OPERATOR : {{ $user->name }}
                </h1>
                <p class="font-sans text-led-white/50 text-xs font-semibold uppercase tracking-wide">
                    {{ $user->loket->nama_loket ?? 'STANDBY' }} <span class="text-white/20 mx-1">//</span> {{ $user->loket->layanan->nama_layanan ?? 'N/A' }}
                </p>
            </div>
            <div class="text-right">
                <p class="font-mono text-[10px] text-led-white/40 uppercase tracking-widest mb-1">Antrian</p>
                <p class="font-mono text-2xl text-led-white leading-none">{{ $sisaAntrian }}</p>
            </div>
        </div>

        @if (session('error'))
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 mb-6 font-mono text-xs uppercase tracking-wider">
                > SYSTEM_ERROR: {{ session('error') }}
            </div>
        @endif
        
        @if (session('success'))
            <div class="bg-led-mint/10 border border-led-mint/30 text-led-mint px-4 py-3 mb-6 font-mono text-xs uppercase tracking-wider">
                > SYSTEM_SUCCESS: {{ session('success') }}
            </div>
        @endif

        <div class="mt-4">
            
            @if (!$antrianAktif)
                <div class="text-center mb-6">
                    <p class="font-mono text-led-white/30 text-[10px] uppercase tracking-widest">SYSTEM READY FOR NEXT ASSIGNMENT</p>
                </div>
                <form action="{{ route('panel.panggil') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full bg-led-cyan hover:bg-led-cyan/80 text-board-bg font-sans font-bold text-2xl py-12 transition-colors duration-200 uppercase tracking-widest focus:outline-none focus:ring-4 focus:ring-led-cyan/30">
                        PANGGIL BERIKUTNYA
                    </button>
                </form>
            @else
                <div class="bg-board-bg border border-white/5 p-8 mb-6 text-center relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-led-amber shadow-[0_0_10px_#FFB347]"></div>
                    
                    <p class="font-mono text-led-amber/70 text-[11px] tracking-widest uppercase mb-4">
                        Nomor Antrian Saat Ini
                    </p>
                    
                    <div class="text-7xl md:text-8xl font-mono font-bold text-led-amber text-glow-amber tracking-tighter leading-none mb-2">
                        {{ $antrianAktif->nomor_antrian }}
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <form action="{{ route('panel.selesai', $antrianAktif->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full border border-led-mint/40 text-led-mint hover:bg-led-mint/10 font-sans font-bold py-4 text-sm tracking-widest uppercase transition-colors focus:outline-none">
                            SELESAI
                        </button>
                    </form>
                    
                    <form action="{{ route('panel.lewati', $antrianAktif->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full border border-white/10 text-led-white/40 hover:text-white/80 hover:bg-white/5 font-sans font-bold py-4 text-sm tracking-widest uppercase transition-colors focus:outline-none">
                            LEWATI
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection