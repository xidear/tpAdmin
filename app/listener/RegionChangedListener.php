<?php

namespace app\listener;

use app\event\RegionChanged;
use think\facade\Log;
use think\facade\Queue;

class RegionChangedListener
{
    public function handle(RegionChanged $event)
    {
        try {
            Log::info('地区变更事件触发，类型：' . $event->type . '，地区ID：' . $event->regionId);
            
            // 统一推送到队列异步刷新所有缓存，不区分事件类型
            $queue = Queue::push(\app\job\RefreshRegionCache::class, [], 'region_cache');
            
            if (!$queue) {
                Log::error('地区变更缓存刷新任务推送失败');
            } else {
                Log::info('地区变更缓存刷新任务已推送，队列ID：' . $queue);
            }
            
        } catch (\Exception $e) {
            Log::error('地区变更事件处理失败：' . $e->getMessage());
        }
    }
}