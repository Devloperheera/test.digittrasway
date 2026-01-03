<?php

namespace App\Services;

use App\Models\Vendor;
use App\Models\TruckBooking;
use App\Models\BookingRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VendorBookingService
{
    /**
     * Find nearby vendors sorted by distance
     */
    public function findNearbyVendors($pickupLat, $pickupLon, $vehicleModelId, $radius = 30)
    {
        try {
            $vendors = Vendor::where('is_available_for_booking', true)
                ->where('availability_status', 'available')
                ->where('vehicle_listed', true)
                ->where('vehicle_status', 'approved')
                ->where('vehicle_model_id', $vehicleModelId)
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude')
                ->get();

            $vendors = $vendors->filter(function($vendor) use($pickupLat, $pickupLon, $radius) {
                $distance = $this->calculateDistance(
                    $pickupLat,
                    $pickupLon,
                    $vendor->current_latitude,
                    $vendor->current_longitude
                );
                $vendor->distance_km = round($distance, 2);
                return $distance <= $radius;
            })
            ->sortBy('distance_km')
            ->values();

            Log::info('Nearby vendors found', [
                'count' => $vendors->count(),
                'vehicle_model_id' => $vehicleModelId,
                'radius_km' => $radius
            ]);

            return $vendors;
        } catch (\Exception $e) {
            Log::error('Find Nearby Vendors Error', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return collect([]);
        }
    }

    /**
     * ✅ Send booking request to vendor with complete booking details
     */
    public function sendBookingRequest($bookingId)
    {
        try {
            $booking = TruckBooking::findOrFail($bookingId);

            $existingRequests = BookingRequest::where('booking_id', $bookingId)->get();
            $attemptedVendorIds = $existingRequests->pluck('vendor_id')->toArray();
            $sequenceNumber = $existingRequests->count() + 1;

            $vendors = $this->findNearbyVendors(
                $booking->pickup_latitude,
                $booking->pickup_longitude,
                $booking->vehicle_model_id
            )->filter(function ($vendor) use ($attemptedVendorIds) {
                return !in_array($vendor->id, $attemptedVendorIds);
            });

            if ($vendors->isEmpty()) {
                Log::warning('No vendors available', [
                    'booking_id' => $booking->booking_id,
                    'attempted_vendors' => count($attemptedVendorIds)
                ]);

                $booking->update(['status' => 'no_vendor_available']);

                return [
                    'success' => false,
                    'message' => 'No vendors available in your area. Please try again later.'
                ];
            }

            $nextVendor = $vendors->first();

            // ✅ Create booking request WITH all booking details
            $request = BookingRequest::create([
                'booking_id' => $bookingId,
                'vendor_id' => $nextVendor->id,
                'status' => 'pending',
                'sent_at' => now(),
                'expires_at' => now()->addMinutes(10),
                'sequence_number' => $sequenceNumber,

                // ✅ Booking time and details - ab ye sab store honge!
                'pickup_datetime' => $booking->pickup_datetime,
                'pickup_address' => $booking->pickup_address,
                'drop_address' => $booking->drop_address,
                'distance_km' => $booking->distance_km,
                'final_amount' => $booking->final_amount,
            ]);

            // Block vendor temporarily
            $nextVendor->update([
                'availability_status' => 'requested',
                'is_available_for_booking' => false
            ]);

            Log::info('✅ Booking request sent with full details', [
                'booking_id' => $booking->booking_id,
                'vendor_id' => $nextVendor->id,
                'vendor_name' => $nextVendor->name ?? 'N/A',
                'distance_km' => $nextVendor->distance_km ?? 'N/A',
                'pickup_datetime' => $booking->pickup_datetime?->format('Y-m-d H:i:s'),
                'sequence' => $sequenceNumber,
                'expires_at' => $request->expires_at->format('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'vendor' => $nextVendor,
                'request' => $request,
                'sequence_number' => $sequenceNumber,
                'message' => 'Booking request sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Send Booking Request Error', [
                'booking_id' => $bookingId ?? 'N/A',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send booking request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle expired request
     */
    public function handleExpiredRequest($requestId)
    {
        try {
            $request = BookingRequest::findOrFail($requestId);

            if ($request->status !== 'pending') {
                return;
            }

            DB::beginTransaction();

            $request->update([
                'status' => 'expired',
                'responded_at' => now()
            ]);

            // Unlock vendor
            if ($request->vendor) {
                $request->vendor->update([
                    'availability_status' => 'available',
                    'is_available_for_booking' => true
                ]);
            }

            DB::commit();

            Log::info('Request expired, sending to next vendor', [
                'request_id' => $requestId,
                'booking_id' => $request->booking_id
            ]);

            // Send to next vendor
            $this->sendBookingRequest($request->booking_id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Handle Expired Request Error', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate distance (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get booking request stats
     */
    public function getBookingRequestStats($bookingId)
    {
        $requests = BookingRequest::where('booking_id', $bookingId)
            ->with('vendor:id,name,phone')
            ->get();

        return [
            'total_requests_sent' => $requests->count(),
            'pending' => $requests->where('status', 'pending')->count(),
            'accepted' => $requests->where('status', 'accepted')->count(),
            'rejected' => $requests->where('status', 'rejected')->count(),
            'expired' => $requests->where('status', 'expired')->count(),
            'vendors_attempted' => $requests->pluck('vendor_id')->unique()->count(),
            'requests' => $requests
        ];
    }

    /**
     * Send push notification (implement FCM here)
     */
    private function sendPushNotificationToVendor($vendorId, $booking)
    {
        // TODO: FCM implementation
        return false;
    }
}
