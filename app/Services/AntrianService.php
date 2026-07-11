<?php

namespace App\Services;

use App\Models\Antrian;
use App\Models\Layanan;
use App\Models\Loket;
use App\Exceptions\AntrianKosongException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AntrianService
{
    /**
     * Mengambil nomor antrian baru berdasarkan layanan.
     */
    public function ambilNomor(int $layananId): Antrian
    {
        return DB::transaction(function () use ($layananId) {
            $layanan = Layanan::findOrFail($layananId);
            $tanggalSekarang = now()->toDateString();

            // Kunci baris antrian terakhir hari ini untuk mencegah race condition
            // jika ada 2 pelanggan yang klik tombol ambil di detik yang sama.
            $antrianTerakhir = Antrian::where('layanan_id', $layananId)
                ->where('tanggal', $tanggalSekarang)
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            // Tentukan nomor selanjutnya (Mulai dari 1 jika belum ada)
            if ($antrianTerakhir) {
                // Hapus prefix dari nomor sebelumnya (Misal: A005 -> 5)
                $angkaTerakhir = (int) substr($antrianTerakhir->nomor_antrian, strlen($layanan->kode_prefix));
                $nomorSelanjutnya = $angkaTerakhir + 1;
            } else {
                $nomorSelanjutnya = 1;
            }

            // Format nomor: Prefix + Angka dipadding 3 digit (Contoh: A001, B012)
            $formatNomor = $layanan->kode_prefix . str_pad((string) $nomorSelanjutnya, 3, '0', STR_PAD_LEFT);

            return Antrian::create([
                'layanan_id'  => $layanan->id,
                'nomor_antrian' => $formatNomor,
                'status'      => 'menunggu',
                'tanggal'     => $tanggalSekarang,
            ]);
        });
    }

    /**
     * Memanggil antrian berikutnya yang berstatus 'menunggu' di layanan terkait loket.
     */
    public function panggilBerikutnya(int $loketId): Antrian
    {
        return DB::transaction(function () use ($loketId) {
            $loket = Loket::findOrFail($loketId);
            $tanggalSekarang = now()->toDateString();

            // Kunci baris antrian tertua yang statusnya 'menunggu' untuk layanan ini.
            // lockForUpdate memastikan jika ada 2 petugas di layanan yang sama klik "Panggil",
            // mereka tidak akan mendapatkan nomor yang sama (race condition).
            $antrian = Antrian::where('layanan_id', $loket->layanan_id)
                ->where('status', 'menunggu')
                ->where('tanggal', $tanggalSekarang)
                ->lockForUpdate()
                ->orderBy('waktu_ambil', 'asc')
                ->first();

            if (!$antrian) {
                // Akan di-catch secara elegan oleh render() di custom exception kita (Fase 2)
                throw new AntrianKosongException('Tidak ada antrian yang menunggu saat ini untuk layanan ini.');
            }

            // Update status ke dipanggil.
            // Catatan: Ini akan otomatis memicu AntrianObserver untuk broadcast event 'NomorDipanggil'.
            $antrian->update([
                'status'          => 'dipanggil',
                'loket_id'        => $loket->id,
                'waktu_dipanggil' => now(),
            ]);

            return $antrian;
        });
    }

    /**
     * Menyelesaikan antrian yang sedang dipanggil.
     */
    public function selesaikanAntrian(int $antrianId, int $loketId): Antrian
    {
        return DB::transaction(function () use ($antrianId, $loketId) {
            $antrian = Antrian::where('id', $antrianId)
                ->where('loket_id', $loketId)
                ->where('status', 'dipanggil')
                ->lockForUpdate()
                ->first();

            if (!$antrian) {
                throw new InvalidArgumentException('Antrian tidak valid atau sudah tidak dalam status dipanggil.');
            }

            $antrian->update([
                'status'        => 'selesai',
                'waktu_selesai' => now(),
            ]);

            return $antrian;
        });
    }

    /**
     * Melewati antrian yang tidak merespon saat dipanggil.
     */
    public function lewatiAntrian(int $antrianId, int $loketId): Antrian
    {
        return DB::transaction(function () use ($antrianId, $loketId) {
            $antrian = Antrian::where('id', $antrianId)
                ->where('loket_id', $loketId)
                ->where('status', 'dipanggil')
                ->lockForUpdate()
                ->first();

            if (!$antrian) {
                throw new InvalidArgumentException('Antrian tidak valid atau sudah tidak dalam status dipanggil.');
            }

            $antrian->update([
                'status'        => 'dilewati',
                'waktu_selesai' => now(), // Dilewati juga mengakhiri waktu penanganan
            ]);

            return $antrian;
        });
    }
}
