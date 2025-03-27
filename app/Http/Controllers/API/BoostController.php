<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Boost;
use App\Models\UserBoost;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BoostController extends Controller
{
    /**
     * Mevcut boost'ları listele
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getBoosts(Request $request)
    {
        try {
            $boosts = Boost::where('is_active', true)->get();
            
            return response()->json([
                'success' => true,
                'boosts' => $boosts
            ]);
        } catch (\Exception $e) {
            Log::error('Boost\'lar listelenirken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Boost\'lar listelenirken bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Boost satın al
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function purchaseBoost(Request $request)
    {
        try {
            $user = $request->user();
            
            // Gerekli alanları doğrula
            $validatedData = $request->validate([
                'boost_id' => 'required|exists:boosts,id',
                'telegram_payment_id' => 'required|string'
            ]);
            
            $boost = Boost::findOrFail($validatedData['boost_id']);
            
            // Boost aktif mi kontrol et
            if (!$boost->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu boost artık aktif değil.'
                ], 400);
            }
            
            // TODO: Telegram ödeme doğrulaması
            // Gerçek uygulamada, Telegram ödeme API'si ile ödeme doğrulaması yapılmalıdır.
            // Şimdilik ödeme başarılı kabul ediyoruz.
            
            // İşlem kaydı oluştur
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $boost->price,
                'type' => 'boost_purchase',
                'status' => 'completed',
                'telegram_payment_id' => $validatedData['telegram_payment_id'],
                'metadata' => json_encode([
                    'boost_id' => $boost->id,
                    'boost_name' => $boost->name,
                    'boost_multiplier' => $boost->multiplier,
                    'boost_duration' => $boost->duration
                ])
            ]);
            
            // Kullanıcı boost'u oluştur (aktif değil)
            $userBoost = UserBoost::create([
                'user_id' => $user->id,
                'boost_id' => $boost->id,
                'is_active' => false
            ]);
            
            return response()->json([
                'success' => true,
                'user_boost' => $userBoost->load('boost'),
                'transaction' => $transaction,
                'message' => 'Boost başarıyla satın alındı. Aktifleştirmek için "Aktifleştir" butonuna tıklayın.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Boost satın alınırken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Boost satın alınırken bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Boost aktifleştir
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function activateBoost(Request $request)
    {
        try {
            $user = $request->user();
            
            // Gerekli alanları doğrula
            $validatedData = $request->validate([
                'user_boost_id' => 'required|exists:user_boosts,id'
            ]);
            
            $userBoost = UserBoost::where('id', $validatedData['user_boost_id'])
                ->where('user_id', $user->id)
                ->where('is_active', false)
                ->with('boost')
                ->first();
            
            if (!$userBoost) {
                return response()->json([
                    'success' => false,
                    'message' => 'Boost bulunamadı veya zaten aktif.'
                ], 404);
            }
            
            // Boost süresini hesapla
            $startTime = now();
            $endTime = $startTime->copy()->addSeconds($userBoost->boost->duration);
            
            // Boost'u aktifleştir
            $userBoost->update([
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_active' => true
            ]);
            
            return response()->json([
                'success' => true,
                'user_boost' => $userBoost->fresh()->load('boost'),
                'message' => 'Boost başarıyla aktifleştirildi.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Boost aktifleştirilirken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Boost aktifleştirilirken bir hata oluştu.'
            ], 500);
        }
    }
}
