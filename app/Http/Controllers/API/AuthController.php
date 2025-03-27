<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\MiningStats;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Telegram ile kimlik doğrulama
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticateWithTelegram(Request $request)
    {
        try {
            // Telegram'dan gelen verileri doğrula
            $telegramData = $this->validateTelegramData($request);
            
            if (!$telegramData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Telegram verisi doğrulanamadı.'
                ], 401);
            }
            
            // Kullanıcıyı bul veya oluştur
            $user = User::firstOrCreate(
                ['telegram_id' => $telegramData['id']],
                [
                    'username' => $telegramData['username'] ?? null,
                    'first_name' => $telegramData['first_name'] ?? null,
                    'last_name' => $telegramData['last_name'] ?? null,
                    'profile_photo' => $telegramData['photo_url'] ?? null,
                    'level' => 1
                ]
            );
            
            // Madencilik istatistiklerini oluştur (eğer yoksa)
            MiningStats::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'total_mined' => 0,
                    'today_mined' => 0,
                    'mining_rate' => 1,
                    'active_days' => 0,
                    'last_active_at' => now()
                ]
            );
            
            // Kullanıcı için token oluştur
            $token = $user->createToken('telegram-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'user' => $user->load('miningStats'),
                'token' => $token
            ]);
            
        } catch (\Exception $e) {
            Log::error('Telegram kimlik doğrulama hatası: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kimlik doğrulama sırasında bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Telegram'dan gelen verileri doğrula
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|bool
     */
    private function validateTelegramData(Request $request)
    {
        $data = $request->all();
        
        // Gerekli alanların varlığını kontrol et
        if (!isset($data['id']) || !isset($data['auth_date']) || !isset($data['hash'])) {
            return false;
        }
        
        // TODO: Telegram verisini doğrula (HMAC-SHA256)
        // Gerçek uygulamada, Telegram'dan gelen verileri doğrulamak için
        // HMAC-SHA256 kullanılmalıdır. Bu, botunuzun token'ı ile yapılır.
        // Şimdilik basit bir doğrulama yapıyoruz.
        
        return $data;
    }
}
