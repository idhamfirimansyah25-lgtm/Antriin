<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AntrianService;
use App\Models\Layanan;
use App\Models\Antrian;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Illuminate\View\View;

class AntrianController extends Controller
{
    public function __construct(
        private AntrianService $antrianService
    ) {}

    /**
     * Menampilkan UI Kios untuk mengambil nomor antrian (Fase 4).
     */
    public function kios(): View
    {
        $layanans = Layanan::where('status', true)->get();
        return view('antrian.kios', compact('layanans'));
    }

    /**
     * Menampilkan UI Layar Display Board Publik (Fase 4).
     */
    public function displayBoard(): View
    {
        // Mengambil antrian terakhir yang dipanggil hari ini untuk init board
        $antrianTerakhir = Antrian::with(['loket', 'layanan'])
            ->where('tanggal', now()->toDateString())
            ->where('status', 'dipanggil')
            ->orderBy('waktu_dipanggil', 'desc')
            ->first();

        return view('antrian.display', compact('antrianTerakhir'));
    }

    /**
     * Menampilkan UI Panel Petugas Loket (Fase 4).
     */
    public function panelPetugas(Request $request): View
    {
        $user = $request->user();

        // Ambil data antrian yang sedang dipanggil oleh petugas ini (jika ada yang menggantung)
        $antrianAktif = Antrian::where('loket_id', $user->loket_id)
            ->where('tanggal', now()->toDateString())
            ->where('status', 'dipanggil')
            ->first();

        // Ambil sisa antrian menunggu untuk informatif di dashboard loket
        $sisaAntrian = Antrian::where('layanan_id', $user->loket->layanan_id ?? 0)
            ->where('tanggal', now()->toDateString())
            ->where('status', 'menunggu')
            ->count();

        return view('antrian.panel', compact('user', 'antrianAktif', 'sisaAntrian'));
    }

    /**
     * Aksi: Publik mengambil nomor.
     */
    public function ambil(Request $request): RedirectResponse
    {
        $request->validate([
            'layanan_id' => 'required|exists:layanans,id'
        ]);

        $antrian = $this->antrianService->ambilNomor((int) $request->layanan_id);

        return back()->with('success', 'Nomor antrian Anda: ' . $antrian->nomor_antrian);
    }

    /**
     * Aksi: Petugas memanggil nomor antrian berikutnya.
     */
    public function panggilBerikutnya(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->loket_id) {
            return back()->with('error', 'Akun Anda belum dipasangkan dengan loket manapun.');
        }

        $antrian = $this->antrianService->panggilBerikutnya((int) $user->loket_id);

        return back()->with('success', 'Berhasil memanggil nomor: ' . $antrian->nomor_antrian);
    }

    /**
     * Aksi: Petugas menyelesaikan antrian aktif.
     */
    public function selesai(Request $request, int $id): RedirectResponse
    {
        try {
            $user = $request->user();
            $this->antrianService->selesaikanAntrian($id, (int) $user->loket_id);
            return back()->with('success', 'Antrian selesai.');
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Aksi: Petugas melewati antrian aktif.
     */
    public function lewati(Request $request, int $id): RedirectResponse
    {
        try {
            $user = $request->user();
            $this->antrianService->lewatiAntrian($id, (int) $user->loket_id);
            return back()->with('success', 'Antrian dilewati.');
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
