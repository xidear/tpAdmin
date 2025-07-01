<?php

namespace app\listener;

use app\model\Admin;
use think\facade\Cache;

class AdminInfoUpdated
{
    /**
     * 处理管理员信息更新事件
     *
     * @param  int  $adminId 管理员 ID
     * @return void
     */
    public function handle($adminId)
    {
        $cacheKey = 'admin_info_' . $adminId;
        // 从数据库获取最新的管理员信息
        $admin = (new Admin)->findOrEmpty($adminId);
        if (!$admin->isEmpty()) {
            // 更新缓存
            Cache::set($cacheKey, $admin, 86400);
        } else {
            // 若管理员信息不存在，删除缓存
            Cache::delete($cacheKey);
        }
    }
}