<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserSetting;

class UserSettingController extends Controller
{
    /**
     * Store or update user settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'user_name' => 'required|string',
            'monthly_working_hours' => 'nullable|numeric',
            'exclude_keywords' => 'nullable|string',
        ]);

        $userSetting = UserSetting::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'user_name' => $request->user_name,
                'monthly_working_hours' => $request->monthly_working_hours,
                'exclude_keywords' => $request->exclude_keywords,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'ユーザー設定が保存されました',
            'data' => $userSetting
        ]);
    }

    /**
     * Get user settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSettings(Request $request)
    {
        $userId = $request->input('user_id');
        
        if (!$userId) {
            return response()->json([
                'error' => true,
                'message' => 'ユーザーIDが指定されていません。'
            ], 400);
        }
        
        $userSetting = UserSetting::where('user_id', $userId)->first();
        
        if (!$userSetting) {
            return response()->json([
                'error' => false,
                'message' => 'ユーザー設定が見つかりません',
                'data' => null
            ]);
        }
        
        return response()->json([
            'error' => false,
            'message' => 'ユーザー設定を取得しました',
            'data' => $userSetting
        ]);
    }
}
