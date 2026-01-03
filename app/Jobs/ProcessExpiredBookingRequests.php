<?php

namespace App\Jobs;

use App\Models\BookingRequest;
use App\Services\VendorBookingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExpiredBookingRequests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $expiredRequests = BookingRequest::where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->get();

        Log::info('Processing expired booking requests', [
            'count' => $expiredRequests->count()
        ]);

        $service = app(VendorBookingService::class);

        foreach ($expiredRequests as $request) {
            $service->handleExpiredRequest($request->id);
        }
    }
}
