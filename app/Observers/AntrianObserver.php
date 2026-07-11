<?php

namespace App\Observers;

use App\Models\Antrian;
use App\Events\NomorDipanggil;

class AntrianObserver
{
    /**
     * Handle the Antrian "updated" event.
     */
    public function updated(Antrian $antrian): void
    {
        // Hanya trigger broadcast jika field 'status' berubah
        if (!$antrian->isDirty('status')) {
            return;
        }

        // Dan hanya jika statusnya berubah menjadi 'dipanggil'
        if ($antrian->status === 'dipanggil') {
            // Broadcast ke orang lain (display board), 
            // menggunakan toOthers() tidak wajib jika dari controller api/backend terpisah, 
            // tapi praktik yang baik. Untuk public channel, broadcast() biasa juga cukup.
            broadcast(new NomorDipanggil($antrian));
        }
    }
}
