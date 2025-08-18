-- 部门功能相关表结构
-- 按照tp_admin_old的命名规则：表名不带s，主键是表名_id

-- 部门表
CREATE TABLE `department` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '部门ID',
  `name` varchar(100) NOT NULL COMMENT '部门名称',
  `code` varchar(50) DEFAULT NULL COMMENT '部门编码',
  `parent_id` int(11) DEFAULT 0 COMMENT '父部门ID，0表示顶级部门',
  `level` int(11) DEFAULT 1 COMMENT '部门层级',
  `path` varchar(255) DEFAULT NULL COMMENT '部门路径，用逗号分隔的ID序列',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `description` text DEFAULT NULL COMMENT '部门描述',
  `leader_id` int(11) DEFAULT NULL COMMENT '部门主管ID',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '更新人ID',
  `created_type` varchar(20) DEFAULT 'admin' COMMENT '创建人类型',
  PRIMARY KEY (`department_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部门表';

-- 部门职位表
CREATE TABLE `department_position` (
  `position_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '职位ID',
  `department_id` int(11) NOT NULL COMMENT '部门ID',
  `name` varchar(100) NOT NULL COMMENT '职位名称',
  `code` varchar(50) DEFAULT NULL COMMENT '职位编码',
  `description` text DEFAULT NULL COMMENT '职位描述',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL COMMENT '删除时间',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '更新人ID',
  `created_type` varchar(20) DEFAULT 'admin' COMMENT '创建人类型',
  PRIMARY KEY (`position_id`),
  KEY `idx_department_id` (`department_id`),
  KEY `idx_code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  UNIQUE KEY `uk_dept_pos_code` (`department_id`, `code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部门职位表';

-- 部门管理员关联表
CREATE TABLE `department_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联ID',
  `department_id` int(11) NOT NULL COMMENT '部门ID',
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `position_id` int(11) DEFAULT NULL COMMENT '职位ID',
  `is_leader` tinyint(1) DEFAULT 0 COMMENT '是否部门主管：0-否，1-是',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：0-禁用，1-启用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `created_by` int(11) DEFAULT NULL COMMENT '创建人ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '更新人ID',
  `created_type` varchar(20) DEFAULT 'admin' COMMENT '创建人类型',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dept_admin` (`department_id`, `admin_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_position_id` (`position_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='部门管理员关联表';

-- 插入一些示例数据
INSERT INTO `department` (`name`, `code`, `parent_id`, `level`, `path`, `sort`, `status`, `description`) VALUES
('技术部', 'TECH', 0, 1, '1', 1, 1, '负责技术研发工作'),
('产品部', 'PROD', 0, 1, '2', 2, 1, '负责产品设计工作'),
('运营部', 'OPER', 0, 1, '3', 3, 1, '负责运营推广工作'),
('前端组', 'TECH_FE', 1, 2, '1,4', 1, 1, '负责前端开发'),
('后端组', 'TECH_BE', 1, 2, '1,5', 2, 1, '负责后端开发'),
('测试组', 'TECH_QA', 1, 2, '1,6', 3, 1, '负责测试工作');

INSERT INTO `department_position` (`department_id`, `name`, `code`, `description`, `sort`, `status`) VALUES
(1, '技术总监', 'TECH_DIRECTOR', '技术部门负责人', 1, 1),
(1, '高级工程师', 'TECH_SENIOR', '高级技术岗位', 2, 1),
(1, '工程师', 'TECH_ENGINEER', '技术岗位', 3, 1),
(4, '前端工程师', 'FE_ENGINEER', '前端开发岗位', 1, 1),
(5, '后端工程师', 'BE_ENGINEER', '后端开发岗位', 1, 1),
(6, '测试工程师', 'QA_ENGINEER', '测试岗位', 1, 1);

-- 添加外键约束（可选）
-- ALTER TABLE `department_position` ADD CONSTRAINT `fk_dept_pos_dept` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE CASCADE;
-- ALTER TABLE `department_admin` ADD CONSTRAINT `fk_dept_admin_dept` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE CASCADE;
-- ALTER TABLE `department_admin` ADD CONSTRAINT `fk_dept_admin_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE CASCADE;
-- ALTER TABLE `department_admin` ADD CONSTRAINT `fk_dept_admin_pos` FOREIGN KEY (`position_id`) REFERENCES `department_position` (`position_id`) ON DELETE SET NULL;
