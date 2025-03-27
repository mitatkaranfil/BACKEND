<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MiningStats;
use App\Models\UserBoost;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MiningController extends Controller
{
    /**
     * Madencilik başlat
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function startMining(Request $request)
    {
        try {
            $user = $request->user();
            $miningStats = $user->miningStats;
            
            if (!$miningStats) {
                // Eğer kullanıcının madencilik istatistikleri yoksa oluştur
                $miningStats = MiningStats::create([
                    'user_id' => $user->id,
                    'total_mined' => 0,
                    'today_mined' => 0,
                    'mining_rate' => 1,
                    'active_days' => 0,
                    'last_active_at' => now()
                ]);
            }
            
            // Günlük madencilik kontrolü
            $this->checkDailyReset($miningStats);
            
            // Aktif boost kontrolü
            $this->checkActiveBoosts($user);
            
            // Madencilik hızını hesapla
            $miningRate = $this->calculateMiningRate($user);
            
            // Madencilik istatistiklerini güncelle
            $miningStats->update([
                'mining_rate' => $miningRate,
                'last_active_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'mining_stats' => $miningStats,
                'message' => 'Madencilik başlatıldı.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Madencilik başlatılırken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Madencilik başlatılırken bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Madencilik durdur
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stopMining(Request $request)
    {
        try {
            $user = $request->user();
            $miningStats = $user->miningStats;
            
            // Madencilik süresi ve kazanılan miktar
            $miningDuration = $request->input('duration', 0); // Saniye cinsinden
            $minedAmount = $miningStats->mining_rate * $miningDuration;
            
            // Madencilik istatistiklerini güncelle
            $miningStats->update([
                'total_mined' => $miningStats->total_mined + $minedAmount,
                'today_mined' => $miningStats->today_mined + $minedAmount,
                'last_active_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'mining_stats' => $miningStats->fresh(),
                'mined_amount' => $minedAmount,
                'message' => 'Madencilik durduruldu ve kazanılan miktar eklendi.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Madencilik durdurulurken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Madencilik durdurulurken bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Madencilik istatistiklerini al
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getMiningStats(Request $request)
    {
        try {
            $user = $request->user();
            $miningStats = $user->miningStats;
            
            if (!$miningStats) {
                return response()->json([
                    'success' => false,
                    'message' => 'Madencilik istatistikleri bulunamadı.'
                ], 404);
            }
            
            // Günlük madencilik kontrolü
            $this->checkDailyReset($miningStats);
            
            // Aktif boost'ları al
            $activeBoosts = $user->userBoosts()
                ->where('is_active', true)
                ->where('end_time', '>', now())
                ->with('boost')
                ->get();
            
            return response()->json([
                'success' => true,
                'mining_stats' => $miningStats->fresh(),
                'active_boosts' => $activeBoosts
            ]);
            
        } catch (\Exception $e) {
            Log::error('Madencilik istatistikleri alınırken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Madencilik istatistikleri alınırken bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Günlük madencilik sıfırlama kontrolü
     *
     * @param  \App\Models\MiningStats  $miningStats
     * @return void
     */
    private function checkDailyReset($miningStats)
    {
        $lastActiveDate = $miningStats->last_active_at ? Carbon::parse($miningStats->last_active_at)->startOfDay() : null;
        $today = Carbon::now()->startOfDay();
        
        if (!$lastActiveDate || $lastActiveDate->lt($today)) {
            // Yeni gün, today_mined'ı sıfırla ve active_days'i artır
            $miningStats->update([
                'today_mined' => 0,
                'active_days' => $miningStats->active_days + 1
            ]);
        }
    }
    
    /**
     * Aktif boost'ları kontrol et
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    private function checkActiveBoosts($user)
    {
        // Süresi dolan boost'ları deaktif et
        UserBoost::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('end_time', '<=', now())
            ->update(['is_active' => false]);
    }
    
    /**
     * Madencilik hızını hesapla
     *
     * @param  \App\Models\User  $user
     * @return float
     */
    private function calculateMiningRate($user)
    {
        $baseRate = 1.0; // Temel madencilik hızı
        
        // Kullanıcı seviyesine göre bonus
        $levelBonus = $user->level * 0.1;
        
        // Aktif boost'lardan gelen bonus
        $boostMultiplier = 1.0;
        $activeBoosts = $user->userBoosts()
            ->where('is_active', true)
            ->where('end_time', '>', now())
            ->with('boost')
            ->get();
        
        foreach ($activeBoosts as $userBoost) {
            $boostMultiplier *= $userBoost->boost->multiplier;
        }
        
        // Toplam madencilik hızı
        $totalRate = $baseRate * (1 + $levelBonus) * $boostMultiplier;
        
        return round($totalRate, 2);
    }
}
