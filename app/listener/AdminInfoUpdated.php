<?php

namespace app\listener;

use app\model\Admin;
use think\facade\Log;

class AdminInfoUpdated
{
    /**
     * 处理管理员信息更新事件
     * @param array $data
     */
    public function handle(array $data): void
    {
        try {
            $adminId = $data['admin_id'] ?? 0;
            
            if ($adminId > 0) {
                // 清除管理员信息缓存
                Admin::clearCache($adminId);
                Log::info("管理员缓存已清除", ['admin_id' => $adminId]);
            }
        } catch (\Exception $e) {
            Log::error("清除管理员缓存失败", ['admin_id' => $data['admin_id'] ?? 0, 'error' => $e->getMessage()]);
        }
    }
}