-- 图片分类功能相关表结构
-- 按照tp_admin_old的命名规则：表名不带s，主键是表名_id

-- 图片分类表
CREATE TABLE `image_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(100) NOT NULL COMMENT '分类名称',
  `code` varchar(50) DEFAULT NULL COMMENT '分类编码',
  `parent_id` int(11) DEFAULT 0 COMMENT '父分类ID，0表示顶级分类',
  `level` int(11) DEFAULT 1 COMMENT '分类层级',
  `path` varchar(255) DEFAULT NULL COMMENT '分类路径，用逗号分隔的ID序列',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `description` text DEFAULT NULL COMMENT '分类描述',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '更新人ID',
  `created_type` varchar(20) DEFAULT 'admin' COMMENT '创建人类型',
  PRIMARY KEY (`category_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图片分类表';

-- 图片标签表
CREATE TABLE `image_tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `color` varchar(20) DEFAULT '#409eff' COMMENT '标签颜色',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '更新人ID',
  `created_type` varchar(20) DEFAULT 'admin' COMMENT '创建人类型',
  PRIMARY KEY (`tag_id`),
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图片标签表';

-- 图片标签关联表
CREATE TABLE `image_tag_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID',
  `image_id` varchar(32) NOT NULL COMMENT '图片ID',
  `tag_id` int(11) NOT NULL COMMENT '标签ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_image_tag` (`image_id`, `tag_id`),
  KEY `idx_image_id` (`image_id`),
  KEY `idx_tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='图片标签关联表';

-- 修改文件表，添加分类字段
ALTER TABLE `file` ADD COLUMN `category_id` int(11) DEFAULT NULL COMMENT '图片分类ID' AFTER `uploader_type`;
ALTER TABLE `file` ADD COLUMN `tags` varchar(500) DEFAULT NULL COMMENT '图片标签，逗号分隔' AFTER `category_id`;
ALTER TABLE `file` ADD KEY `idx_category_id` (`category_id`);

-- 插入一些示例分类数据
INSERT INTO `image_category` (`name`, `code`, `parent_id`, `level`, `path`, `sort`, `status`, `description`) VALUES
('系统图片', 'SYSTEM', 0, 1, '1', 1, 1, '系统默认图片'),
('产品图片', 'PRODUCT', 0, 1, '2', 2, 1, '产品相关图片'),
('营销图片', 'MARKETING', 0, 1, '3', 3, 1, '营销推广图片'),
('装饰图片', 'DECORATION', 0, 1, '4', 4, 1, '页面装饰图片'),
('用户上传', 'USER_UPLOAD', 0, 1, '5', 5, 1, '用户上传的图片'),
('商品主图', 'PRODUCT_MAIN', 2, 2, '2,6', 1, 1, '商品主要展示图片'),
('商品详情', 'PRODUCT_DETAIL', 2, 2, '2,7', 2, 1, '商品详情页图片'),
('Banner图', 'BANNER', 3, 2, '3,8', 1, 1, '轮播图、横幅图'),
('活动图片', 'ACTIVITY', 3, 2, '3,9', 2, 1, '活动宣传图片'),
('图标素材', 'ICON', 4, 2, '4,10', 1, 1, '各种图标素材'),
('背景图片', 'BACKGROUND', 4, 2, '4,11', 2, 1, '背景装饰图片');

-- 插入一些示例标签数据
INSERT INTO `image_tag` (`name`, `color`, `sort`, `status`) VALUES
('产品图', '#67c23a', 1, 1),
('banner', '#409eff', 2, 1),
('logo', '#e6a23c', 3, 1),
('图标', '#909399', 4, 1),
('背景图', '#f56c6c', 5, 1),
('装饰图', '#9c27b0', 6, 1),
('高清', '#00bcd4', 7, 1),
('矢量', '#ff9800', 8, 1);

-- 添加外键约束（可选）
-- ALTER TABLE `file` ADD CONSTRAINT `fk_file_category` FOREIGN KEY (`category_id`) REFERENCES `image_category` (`category_id`) ON DELETE SET NULL;
-- ALTER TABLE `image_tag_relation` ADD CONSTRAINT `fk_tag_rel_image` FOREIGN KEY (`image_id`) REFERENCES `file` (`file_id`) ON DELETE CASCADE;
-- ALTER TABLE `image_tag_relation` ADD CONSTRAINT `fk_tag_rel_tag` FOREIGN KEY (`tag_id`) REFERENCES `image_tag` (`tag_id`) ON DELETE CASCADE;
