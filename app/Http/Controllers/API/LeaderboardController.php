<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MiningStats;
use Illuminate\Support\Facades\Log;

class LeaderboardController extends Controller
{
    /**
     * Liderlik tablosunu al
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getLeaderboard(Request $request)
    {
        try {
            // Filtreleme parametreleri
            $type = $request->input('type', 'total'); // total, today, rate
            $limit = $request->input('limit', 10);
            
            // Liderlik tablosu sorgusu
            $query = MiningStats::with(['user' => function($query) {
                $query->select('id', 'telegram_id', 'username', 'first_name', 'last_name', 'profile_photo', 'level');
            }]);
            
            // Sıralama türüne göre sırala
            switch ($type) {
                case 'today':
                    $query->orderBy('today_mined', 'desc');
                    break;
                case 'rate':
                    $query->orderBy('mining_rate', 'desc');
                    break;
                case 'total':
                default:
                    $query->orderBy('total_mined', 'desc');
                    break;
            }
            
            // Sonuçları al
            $leaderboard = $query->limit($limit)->get();
            
            // Kullanıcının sıralamasını bul (eğer kimlik doğrulaması yapılmışsa)
            $userRank = null;
            if ($request->user()) {
                $userId = $request->user()->id;
                
                // Sıralama türüne göre kullanıcının sıralamasını bul
                switch ($type) {
                    case 'today':
                        $userRank = MiningStats::where('today_mined', '>', function($query) use ($userId) {
                            $query->select('today_mined')->from('mining_stats')->where('user_id', $userId);
                        })->count() + 1;
                        break;
                    case 'rate':
                        $userRank = MiningStats::where('mining_rate', '>', function($query) use ($userId) {
                            $query->select('mining_rate')->from('mining_stats')->where('user_id', $userId);
                        })->count() + 1;
                        break;
                    case 'total':
                    default:
                        $userRank = MiningStats::where('total_mined', '>', function($query) use ($userId) {
                            $query->select('total_mined')->from('mining_stats')->where('user_id', $userId);
                        })->count() + 1;
                        break;
                }
            }
            
            return response()->json([
                'success' => true,
                'leaderboard' => $leaderboard,
                'user_rank' => $userRank,
                'type' => $type
            ]);
            
        } catch (\Exception $e) {
            Log::error('Liderlik tablosu alınırken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Liderlik tablosu alınırken bir hata oluştu.'
            ], 500);
        }
    }
}
