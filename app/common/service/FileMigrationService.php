<?php

namespace app\common\service;

use app\model\File as FileModel;
use think\facade\Log;

class FileMigrationService
{
    /**
     * 生成迁移预览
     * @param string $oldDomain
     * @param string $newDomain
     * @param int $page
     * @param int $listRows
     * @return array
     */
    public function generateMigrationPreview(string $oldDomain, string $newDomain, int $page = 1, int $listRows = 15): array
    {
        $preview = [
            'old_domain' => $oldDomain,
            'new_domain' => $newDomain,
            'affected_files' => [],
            'total_count' => 0,
            'local_count' => 0,
            'other_count' => 0,
            'page' => $page,
            'list_rows' => $listRows,
            'has_more' => false
        ];

        try {
            $fileModel = new FileModel();

            // 构建查询条件
            $conditions = function ($query) use ($oldDomain) {
                $query->where('url', 'like', "%{$oldDomain}%")
                    ->whereOr('storage_path', 'like', "%{$oldDomain}%");
            };

            // 使用TP内置的分页查询
            $result = $fileModel->fetchData($conditions, [
                'pageNum' => $page,
                'pageSize' => $listRows
            ]);

            // 处理分页数据
            if (isset($result['total'])) {
                $preview['total_count'] = $result['total'];
                $preview['has_more'] = ($page * $listRows) < $result['total'];

                if (isset($result['list']) && is_array($result['list'])) {
                    $this->processFileList($result['list'], $preview, $oldDomain, $newDomain);
                }
            } else {
                // 如果没有分页参数，返回所有数据（但限制数量）
                $files = $fileModel->where(function ($query) use ($oldDomain) {
                    $query->where('url', 'like', "%{$oldDomain}%")
                        ->whereOr('storage_path', 'like', "%{$oldDomain}%");
                })->limit($listRows)->select();

                $preview['total_count'] = $files->count();
                $preview['has_more'] = false;

                $this->processFileList($files->toArray(), $preview, $oldDomain, $newDomain);
            }

            // 如果总数超过限制，添加警告
            if ($preview['total_count'] > $listRows * 10) {
                $preview['warning'] = "数据量较大（{$preview['total_count']}条），建议分批迁移。当前显示第{$page}页，每页{$listRows}条。";
            }
        } catch (\Exception $e) {
            Log::error("生成迁移预览失败: " . $e->getMessage(), [
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'page' => $page,
                'list_rows' => $listRows
            ]);

            $preview['error'] = '生成预览失败：' . $e->getMessage();
        }

        return $preview;
    }

    /**
     * 执行URL迁移
     * @param string $oldDomain
     * @param string $newDomain
     * @param int $batchSize
     * @param int $maxBatches
     * @return array
     */
    public function performUrlMigration(string $oldDomain, string $newDomain, int $batchSize = 1000, int $maxBatches = 10): array
    {
        $stats = [
            'total_files' => 0,
            'migrated_files' => 0,
            'skipped_files' => 0,
            'errors' => [],
            'batches_processed' => 0,
            'current_batch' => 0
        ];

        try {
            $fileModel = new FileModel();

            Log::info("开始执行URL迁移", [
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'batch_size' => $batchSize,
                'max_batches' => $maxBatches
            ]);

            // 先统计总数
            $totalCount = $fileModel->where(function ($query) use ($oldDomain) {
                $query->where('url', 'like', "%{$oldDomain}%")
                    ->whereOr('storage_path', 'like', "%{$oldDomain}%");
            })->count();

            Log::info("查询到的文件总数", [
                'total_count' => $totalCount,
                'old_domain' => $oldDomain
            ]);

            $stats['total_files'] = $totalCount;

            if ($totalCount === 0) {
                return $stats;
            }

            // 分批处理
            $this->processMigrationBatches($fileModel, $oldDomain, $newDomain, $batchSize, $maxBatches, $stats);

            // 迁移其他表中的图片URL
            $this->migrateOtherTables($oldDomain, $newDomain, $stats);

            // 更新配置文件
        } catch (\Exception $e) {
            Log::error("执行URL迁移失败: " . $e->getMessage(), [
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'batch_size' => $batchSize,
                'max_batches' => $maxBatches
            ]);

            $stats['errors'][] = "迁移执行失败: " . $e->getMessage();
        }

        return $stats;
    }

    /**
     * 处理迁移批次
     * @param FileModel $fileModel
     * @param string $oldDomain
     * @param string $newDomain
     * @param int $batchSize
     * @param int $maxBatches
     * @param array &$stats
     */
    protected function processMigrationBatches(FileModel $fileModel, string $oldDomain, string $newDomain, int $batchSize, int $maxBatches, array &$stats): void
    {
        $batchCount = 0;
        $processedCount = 0;

        $fileModel->where(function ($query) use ($oldDomain) {
            $query->where('url', 'like', "%{$oldDomain}%")
                ->whereOr('storage_path', 'like', "%{$oldDomain}%");
        })->chunk($batchSize, function ($files) use (&$stats, &$batchCount, &$processedCount, $oldDomain, $newDomain, $maxBatches) {

            // 检查是否达到最大批次数限制
            if ($batchCount >= $maxBatches) {
                return false; // 停止chunk
            }

            $batchCount++;
            $stats['current_batch'] = $batchCount;

            // 处理当前批次
            foreach ($files as $file) {
                try {
                    $migrated = $this->migrateFileUrl($file, $oldDomain, $newDomain);

                    if ($migrated) {
                        $stats['migrated_files']++;
                    } else {
                        $stats['skipped_files']++;
                    }

                    $processedCount++;
                } catch (\Exception $e) {
                    $stats['errors'][] = "文件ID {$file->file_id}: " . $e->getMessage();
                }
            }

            $stats['batches_processed'] = $batchCount;

            // 如果还有更多数据但已达到最大批次数，添加提示
            if ($processedCount < $stats['total_files'] && $batchCount >= $maxBatches) {
                $stats['warning'] = "已达到最大批次数限制（{$maxBatches}批），还有 " . ($stats['total_files'] - $processedCount) . " 条数据未处理。请分批执行迁移。";
                return false; // 停止chunk
            }
            return true;
        });
    }

    /**
     * 处理文件列表
     * @param array $files
     * @param array &$preview
     * @param string $oldDomain
     * @param string $newDomain
     */
    protected function processFileList($files, array &$preview, string $oldDomain, string $newDomain): void
    {
        $localCount = 0;
        $otherCount = 0;

        foreach ($files as $file) {
            $isLocal = ImageUrlService::isLocalStorage($file['url'] ?? $file->url);
            $newUrl = ImageUrlService::generateNewUrl($file['url'] ?? $file->url, $oldDomain, $newDomain);

            $preview['affected_files'][] = [
                'table' => 'file',
                'id' => $file['file_id'] ?? $file->file_id,
                'old_url' => $file['url'] ?? $file->url,
                'new_url' => $newUrl,
                'file_name' => $file['origin_name'] ?? $file->origin_name,
                'storage_type' => $file['storage_type'] ?? $file->storage_type,
                'is_local' => $isLocal
            ];

            if ($isLocal) {
                $localCount++;
            } else {
                $otherCount++;
            }
        }

        $preview['local_count'] = $localCount;
        $preview['other_count'] = $otherCount;
        $preview['current_page_count'] = count($preview['affected_files']);
    }

    /**
     * 迁移单个文件的URL
     * @param \think\Model $file
     * @param string $oldDomain
     * @param string $newDomain
     * @return bool 是否成功迁移
     */
    protected function migrateFileUrl($file, string $oldDomain, string $newDomain): bool
    {
        $migrated = false;

        Log::info("检查文件是否需要迁移", [
            'id' => $file->file_id,
            'file_url' => $file->url,
            'storage_path' => $file->storage_path,
            'old_domain' => $oldDomain,
            'new_domain' => $newDomain
        ]);

        // 迁移URL字段
        if (!empty($file->url) && str_contains($file->url, $oldDomain)) {
            $file->url = ImageUrlService::generateNewUrl($file->url, $oldDomain, $newDomain);
            $migrated = true;
            Log::info("迁移URL字段", [
                'id' => $file->file_id,
                'old_url' => $file->url,
                'new_url' => $file->url
            ]);
        }

        // 迁移storage_path字段（如果是完整URL）
        if (!empty($file->storage_path) && filter_var($file->storage_path, FILTER_VALIDATE_URL)) {
            if (str_contains($file->storage_path, $oldDomain)) {
                $file->storage_path = ImageUrlService::generateNewUrl($file->storage_path, $oldDomain, $newDomain);
                $migrated = true;
                Log::info("迁移storage_path字段", [
                    'id' => $file->file_id,
                    'old_storage_path' => $file->storage_path,
                    'new_storage_path' => $file->storage_path
                ]);
            }
        }

        if ($migrated) {
            $file->save();
        }

        return $migrated;
    }

    /**
     * 迁移其他表中的图片URL
     * @param string $oldDomain
     * @param string $newDomain
     * @param array &$stats
     */
    protected function migrateOtherTables(string $oldDomain, string $newDomain, array &$stats): void
    {
        $imageFields = [
            'admin' => ['avatar', 'logo'],
            'system_config' => ['config_value'],
            'menu' => ['icon'],
        ];

        foreach ($imageFields as $table => $fields) {
            try {
                $this->migrateTableUrls($table, $fields, $oldDomain, $newDomain, $stats);
            } catch (\Exception $e) {
                $stats['errors'][] = "表 {$table}: " . $e->getMessage();

                Log::error("迁移表 {$table} 失败: " . $e->getMessage(), [
                    'table' => $table,
                    'old_domain' => $oldDomain,
                    'new_domain' => $newDomain
                ]);
            }
        }
    }

    /**
     * 迁移指定表的URL
     * @param string $table
     * @param array $fields
     * @param string $oldDomain
     * @param string $newDomain
     * @param array &$stats
     */
    protected function migrateTableUrls(string $table, array $fields, string $oldDomain, string $newDomain, array &$stats): void
    {
        $modelClass = "\\app\\model\\" . ucfirst($table);
        if (!class_exists($modelClass)) {
            return;
        }

        $model = new $modelClass();

        // 使用chunk方法分批处理，避免内存溢出
        $model->chunk(1000, function ($records) use ($table, $fields, $oldDomain, $newDomain, &$stats) {
            foreach ($records as $record) {
                $updated = false;

                foreach ($fields as $field) {
                    if (isset($record->$field) && !empty($record->$field)) {
                        $value = $record->$field;

                        // 检查是否为URL且需要迁移
                        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                            if (ImageUrlService::needsMigration($value, $oldDomain)) {
                                $record->$field = ImageUrlService::generateNewUrl($value, $oldDomain, $newDomain);
                                $updated = true;
                            }
                        }
                    }
                }

                if ($updated) {
                    try {
                        $record->save();
                        $stats['migrated_files']++;
                    } catch (\Exception $e) {
                        $stats['errors'][] = "表 {$table} 记录ID {$record->getKey()}: " . $e->getMessage();
                    }
                }
            }
        });
    }
}
