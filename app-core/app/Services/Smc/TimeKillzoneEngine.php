<?php

namespace App\Services\Smc;

use Carbon\Carbon;

class TimeKillzoneEngine
{
    /**
     * Cek apakah waktu tertentu berada dalam Killzone
     * Asumsi $time menggunakan zona waktu UTC
     */
    public function getActiveKillzone(Carbon $time): ?string
    {
        $hour = $time->hour;

        // Asian Killzone: 00:00 - 06:00 GMT (Bisa disesuaikan)
        if ($hour >= 0 && $hour < 6) return 'asia';

        // London Killzone: 07:00 - 10:00 GMT
        if ($hour >= 7 && $hour < 10) return 'london';

        // New York Killzone: 12:00 - 15:00 GMT
        if ($hour >= 12 && $hour < 15) return 'new_york';

        // London Close: 15:00 - 17:00 GMT
        if ($hour >= 15 && $hour < 17) return 'london_close';

        return null; // Dead zone
    }
}
