<?php
namespace app\job;

use think\queue\Job;
use app\common\service\RegionCacheService;
use think\facade\Log;

class RefreshRegionCache
{
    /**
     * 执行队列任务
     * @param Job $job
     * @param array $data
     */
    public function fire(Job $job, $data)
    {
        try {
            Log::info('开始执行地区缓存刷新队列任务');
            
            // 实例化缓存服务
            $cacheService = new RegionCacheService();
            
            // 统一执行全量缓存刷新，不区分类型
            $result = $cacheService->executeCacheRefresh();
            
            Log::info('地区缓存刷新队列任务执行完成', [
                'success' => $result
            ]);
            
            // 任务执行成功，删除队列任务
            $job->delete();
            
        } catch (\Exception $e) {
            Log::error('地区缓存刷新队列任务执行失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 重试次数检查
            if ($job->attempts() >= 3) {
                Log::error('地区缓存刷新队列任务重试次数已达上限，放弃执行');
                $job->delete();
            } else {
                // 延迟重试
                $job->release(60); // 60秒后重试
            }
        }
    }
    
    /**
     * 任务失败处理
     * @param $job
     * @param $data
     */
    public function failed($data)
    {
        Log::error('地区缓存刷新队列任务最终失败', $data);
    }
}