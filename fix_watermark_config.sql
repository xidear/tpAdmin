-- 修复 watermark_config 字段类型
-- 将 upload_common_config 中的 watermark_config 字段类型从 KEY_VALUE(40) 改为 JSON(32)

-- 方法1：更新字段类型（如果 watermark_config 是独立的配置项）
UPDATE `system_config` 
SET `config_type` = 32 
WHERE `config_key` = 'watermark_config';

-- 方法2：如果 watermark_config 是 upload_common_config 的一部分，需要重新插入配置
-- 先删除旧的配置
DELETE FROM `system_config` WHERE `config_key` = 'upload_common_config';

-- 重新插入正确的配置
INSERT INTO `system_config` (
    `config_key`, 
    `config_value`, 
    `config_name`, 
    `config_type`, 
    `system_config_group_id`, 
    `options`, 
    `sort`, 
    `is_enabled`, 
    `remark`, 
    `created_by`, 
    `created_at`, 
    `updated_by`, 
    `updated_at`, 
    `is_system`
) VALUES (
    'upload_common_config',
    '{"allowed_extensions":"jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,zip,rar","max_file_size":"10485760","image_quality":"80","watermark_enabled":false,"watermark_config":{"text":"","image":"","position":"bottom-right"}}',
    '上传通用配置',
    32,  -- 改为 JSON 类型
    5,
    NULL,
    7,
    '1',
    '文件上传的通用配置，包括允许的文件类型、最大文件大小、图片质量、水印等',
    1,
    NOW(),
    1,
    NOW(),
    '2'
);
