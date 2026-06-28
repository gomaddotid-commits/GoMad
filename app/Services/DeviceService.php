<?php
// File: app/Services/DeviceService.php
// Deskripsi: Service untuk manajemen device token dan push notification FCM

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeviceService
{
    public function registerDevice(User $user, string $token, string $platform): UserDevice
    {
        // Deactivate existing device with same token
        UserDevice::where('device_token', $token)->update(['is_active' => false]);

        // Deactivate other devices of same platform for this user
        UserDevice::where('user_id', $user->id)
            ->where('platform', $platform)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Register new device
        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_token' => $token,
            'platform' => $platform,
            'is_active' => true,
        ]);

        return $device;
    }

    public function unregisterDevice(string $token): void
    {
        UserDevice::where('device_token', $token)->update(['is_active' => false]);
    }

    public function unregisterAllUserDevices(User $user): void
    {
        UserDevice::where('user_id', $user->id)->update(['is_active' => false]);
    }

    public function getUserDevices(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
    }

    public function sendToDevice(string $token, string $title, string $body, array $data = []): void
    {
        $fcmServerKey = config('gomad.fcm.server_key');

        if (empty($fcmServerKey)) {
            Log::info('FCM Push Notification (simulated)', [
                'token' => $token,
                'title' => $title,
                'body' => $body,
            ]);
            return;
        }

        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => 1,
            ],
            'data' => $data,
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if (!$response->successful()) {
                Log::error('FCM Send Error', [
                    'token' => $token,
                    'response' => $response->body(),
                ]);

                // If token is invalid, deactivate it
                $responseData = $response->json();
                if (isset($responseData['results'][0]['error']) && 
                    in_array($responseData['results'][0]['error'], ['NotRegistered', 'InvalidRegistration'])) {
                    UserDevice::where('device_token', $token)->update(['is_active' => false]);
                }
            }
        } catch (\Exception $e) {
            Log::error('FCM Send Exception', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $devices = $this->getUserDevices($user);

        foreach ($devices as $device) {
            $deviceData = array_merge($data, [
                'notification_id' => $device->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]);
            
            $this->sendToDevice($device->device_token, $title, $body, $deviceData);
        }
    }

    public function sendToMultipleUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        $devices = UserDevice::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->get();

        foreach ($devices as $device) {
            $this->sendToDevice($device->device_token, $title, $body, $data);
        }
    }

    public function sendToAllAdmins(string $title, string $body, array $data = []): void
    {
        $adminUserIds = User::where('role', 'admin')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (!empty($adminUserIds)) {
            $this->sendToMultipleUsers($adminUserIds, $title, $body, $data);
        }
    }
}

// End of file