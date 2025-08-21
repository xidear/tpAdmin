-- 文件分类表
CREATE TABLE IF NOT EXISTS `file_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `parent_id` int(11) DEFAULT NULL COMMENT '父分类ID，NULL表示顶级分类',
  `sort` int(11) DEFAULT 0 COMMENT '排序，数字越小越靠前',
  `description` text COMMENT '分类描述',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用，2禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`category_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件分类表';

-- 文件标签表
CREATE TABLE IF NOT EXISTS `file_tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(100) NOT NULL COMMENT '标签名称',
  `sort` int(11) DEFAULT 0 COMMENT '排序，数字越小越靠前',
  `description` text COMMENT '标签描述',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用，2禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件标签表';

-- 文件标签关联表（多对多关系）
CREATE TABLE IF NOT EXISTS `file_tag_relation` (
  `file_id` int(36) unsigned NOT NULL COMMENT '文件ID',
  `tag_id` int(11) NOT NULL COMMENT '标签ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`file_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `fk_file_tag_relation_file` FOREIGN KEY (`file_id`) REFERENCES `file` (`file_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_tag_relation_tag` FOREIGN KEY (`tag_id`) REFERENCES `file_tag` (`tag_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文件标签关联表';

-- 修改现有的file表，扩展storage_permission字段支持shared选项
-- 注意：MySQL不支持直接修改enum的选项，需要先删除再添加
ALTER TABLE `file` 
MODIFY COLUMN `storage_permission` enum('private','public','shared') NOT NULL DEFAULT 'private' COMMENT '存储权限：private私有，public公共，shared共享';

-- 插入一些默认的分类数据
INSERT IGNORE INTO `file_category` (`name`, `parent_id`, `sort`, `description`, `status`) VALUES
('图片', NULL, 1, '图片文件分类', 1),
('视频', NULL, 2, '视频文件分类', 1),
('文档', NULL, 3, '文档文件分类', 1),
('其他', NULL, 4, '其他类型文件', 1),
('头像', 1, 1, '用户头像图片', 1),
('产品图', 1, 2, '产品相关图片', 1),
('背景图', 1, 3, '背景装饰图片', 1),
('教学视频', 2, 1, '教学培训视频', 1),
('宣传视频', 2, 2, '宣传推广视频', 1),
('合同文档', 3, 1, '合同协议文档', 1),
('技术文档', 3, 2, '技术说明文档', 1);

-- 插入一些默认的标签数据
INSERT IGNORE INTO `file_tag` (`name`, `sort`, `description`, `status`) VALUES
('重要', 1, '重要文件标签', 1),
('临时', 2, '临时文件标签', 1),
('归档', 3, '已归档文件标签', 1),
('审核中', 4, '审核中文件标签', 1),
('已通过', 5, '审核通过文件标签', 1),
('已拒绝', 6, '审核拒绝文件标签', 1);

-- 删除旧的图片相关表（如果存在）
DROP TABLE IF EXISTS `image_category`;
DROP TABLE IF EXISTS `image_tag`;
DROP TABLE IF EXISTS `image_tag_relation`;

-- 将现有的tags字段数据迁移到新的标签关联表（可选）
-- 注意：这个操作需要根据实际数据情况来决定是否执行
-- INSERT INTO file_tag_relation (file_id, tag_id)
-- SELECT f.file_id, t.tag_id 
-- FROM file f 
-- JOIN file_tag t ON FIND_IN_SET(t.name, f.tags)
-- WHERE f.tags IS NOT NULL AND f.tags != '';

-- 最后可以删除旧的tags字段（可选，建议先备份数据）
-- ALTER TABLE `file` DROP COLUMN `tags`;
