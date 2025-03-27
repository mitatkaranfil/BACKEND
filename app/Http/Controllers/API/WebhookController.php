<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Telegram güncellemelerini işle
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleTelegramUpdate(Request $request)
    {
        try {
            // Telegram'dan gelen verileri al
            $data = $request->all();
            
            // Webhook verilerini logla (geliştirme aşamasında)
            Log::info('Telegram Update Webhook:', $data);
            
            // TODO: Telegram güncellemelerini işle
            // Gerçek uygulamada, Telegram Bot API'den gelen güncellemeleri
            // işlemek için gerekli kodlar buraya eklenecek.
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook başarıyla işlendi.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Telegram webhook işlenirken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook işlenirken bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Telegram ödeme bildirimlerini işle
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleTelegramPayment(Request $request)
    {
        try {
            // Telegram'dan gelen ödeme verilerini al
            $data = $request->all();
            
            // Webhook verilerini logla (geliştirme aşamasında)
            Log::info('Telegram Payment Webhook:', $data);
            
            // Gerekli alanların varlığını kontrol et
            if (!isset($data['telegram_payment_id']) || !isset($data['user_id']) || !isset($data['amount'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gerekli alanlar eksik.'
                ], 400);
            }
            
            // Kullanıcıyı bul
            $user = User::where('telegram_id', $data['user_id'])->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kullanıcı bulunamadı.'
                ], 404);
            }
            
            // İşlem kaydı oluştur veya güncelle
            $transaction = Transaction::updateOrCreate(
                ['telegram_payment_id' => $data['telegram_payment_id']],
                [
                    'user_id' => $user->id,
                    'amount' => $data['amount'],
                    'type' => $data['type'] ?? 'payment',
                    'status' => 'completed',
                    'metadata' => json_encode($data)
                ]
            );
            
            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'message' => 'Ödeme başarıyla işlendi.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Telegram ödeme webhook\'i işlenirken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Ödeme işlenirken bir hata oluştu.'
            ], 500);
        }
    }
}
