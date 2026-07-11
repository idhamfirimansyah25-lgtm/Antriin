@extends('layouts.app')

@section('content')
    <div class="flex-grow flex bg-panel border border-line shadow-sm overflow-hidden min-h-[calc(100vh-8rem)]">



        <div class="flex-1 flex flex-col justify-center items-center relative">
            <div
                class="absolute inset-0 bg-[linear-gradient(rgba(31,41,55,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(31,41,55,0.03)_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none">
            </div>

            <div class="z-10 text-center flex flex-col items-center">

                <div class="text-cyan font-mono text-sm tracking-widest uppercase mb-2">
                    <span
                        id="nama-layanan">{{ $antrianTerakhir ? $antrianTerakhir->layanan->nama_layanan : 'MENUNGGU ANTRIAN' }}</span>
                </div>

                <div class="text-cyan font-sans text-4xl lg:text-5xl font-bold tracking-wider uppercase mb-8" id="nama-loket">
                    {{ $antrianTerakhir ? $antrianTerakhir->loket->nama_loket : 'MENUNGGU KONEKSI' }}
                </div>

                <div id="nomor-antrian"
                    class="text-[10rem] md:text-[14rem] lg:text-[18rem] leading-none font-mono font-bold text-amber tracking-tighter">
                    {{ $antrianTerakhir ? $antrianTerakhir->nomor_antrian : '---' }}
                </div>

            </div>


        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Echo) {
                window.Echo.channel('display-antrian')
                    .listen('.nomor.dipanggil', (e) => {
                        const nomorEl = document.getElementById('nomor-antrian');
                        const loketEl = document.getElementById('nama-loket');
                        const layananEl = document.getElementById('nama-layanan');

                        nomorEl.innerText = e.nomor_antrian;
                        loketEl.innerText = e.loket;
                        layananEl.innerText = e.layanan;

                        nomorEl.classList.remove('animate-led-flicker');
                        loketEl.classList.remove('animate-led-flicker');

                        void nomorEl.offsetWidth;

                        nomorEl.classList.add('animate-led-flicker');
                        loketEl.classList.add('animate-led-flicker');
                    });
            } else {
                console.error("Echo instance not found.");
            }
        });
    </script>
@endpush
