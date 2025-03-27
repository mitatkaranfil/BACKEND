<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Kullanıcı bilgilerini al
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getUser(Request $request)
    {
        try {
            $user = $request->user()->load('miningStats');
            
            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Kullanıcı bilgileri alınırken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bilgileri alınırken bir hata oluştu.'
            ], 500);
        }
    }
    
    /**
     * Kullanıcı bilgilerini güncelle
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateUser(Request $request)
    {
        try {
            $user = $request->user();
            
            // Güncellenebilir alanlar
            $validatedData = $request->validate([
                'username' => 'sometimes|string|max:255',
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'profile_photo' => 'sometimes|string|max:255',
            ]);
            
            $user->update($validatedData);
            
            return response()->json([
                'success' => true,
                'user' => $user->fresh()->load('miningStats'),
                'message' => 'Kullanıcı bilgileri başarıyla güncellendi.'
            ]);
        } catch (\Exception $e) {
            Log::error('Kullanıcı bilgileri güncellenirken hata: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Kullanıcı bilgileri güncellenirken bir hata oluştu.'
            ], 500);
        }
    }
}
