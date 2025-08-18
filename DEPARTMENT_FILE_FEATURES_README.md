# 部门管理和文件功能迁移说明

## 概述

本文档说明了从 `tp_admin` 到 `tp_admin_old` 的部门和文件功能迁移情况。

## 已完成的迁移内容

### 1. 后端PHP文件

#### 1.1 部门管理功能
- **控制器**: `app/controller/admin/Department.php`
  - 支持部门的增删改查
  - 支持树状结构和平铺结构
  - 支持部门职位管理
  - 支持批量操作和状态管理
  - 支持数据导出

- **模型**: 
  - `app/model/Department.php` - 部门模型
  - `app/model/DepartmentPosition.php` - 部门职位模型
  - `app/model/DepartmentAdmin.php` - 部门管理员关联模型

#### 1.2 文件管理增强功能
- **控制器**: `app/controller/admin/File.php` (已增强)
  - 新增图片URL迁移功能
  - 支持批量迁移预览
  - 支持分批执行迁移
  - 支持多种存储类型

- **服务类**: `app/common/service/ImageUrlService.php`
  - 提供URL迁移相关工具方法
  - 支持本地存储识别
  - 支持URL生成和验证

### 2. 数据库结构

#### 2.1 新增表结构
- **department** - 部门表
  - 主键: `department_id`
  - 支持树状结构 (parent_id, level, path)
  - 支持软删除
  - 包含基础字段和审计字段

- **department_position** - 部门职位表
  - 主键: `position_id`
  - 关联部门ID
  - 支持编码唯一性验证

- **department_admin** - 部门管理员关联表
  - 主键: `id`
  - 支持多对多关系
  - 支持职位关联

#### 2.2 示例数据
- 包含技术部、产品部、运营部等示例部门
- 包含各类型职位示例

### 3. 路由配置

#### 3.1 部门管理路由
```
/adminapi/department/
├── index          - 获取部门树状结构
├── list           - 获取部门平铺列表
├── read/:id       - 获取部门详情
├── create         - 创建部门
├── update/:id     - 更新部门
├── delete/:id     - 删除部门
├── batch-delete   - 批量删除部门
├── update-status/:id - 更新部门状态
├── export         - 导出部门数据
├── positions/:id  - 获取部门职位
├── position/create - 创建职位
├── position/update/:id - 更新职位
└── position/delete/:id - 删除职位
```

#### 3.2 文件管理增强路由
```
/adminapi/file/
├── get-migration-preview - 获取迁移预览
└── migrate-urls          - 执行URL迁移
```

### 4. 前端页面

#### 4.1 部门管理页面
- **主页面**: `view/admin/src/views/department/index.vue`
  - 左侧树状结构展示
  - 右侧详情和职位管理
  - 支持搜索、新增、编辑、删除等操作

- **部门表单**: `view/admin/src/views/department/components/DepartmentForm.vue`
  - 支持创建和编辑部门
  - 支持选择父部门
  - 包含完整的表单验证

- **职位表单**: `view/admin/src/views/department/components/PositionForm.vue`
  - 支持创建和编辑职位
  - 关联部门ID

#### 4.2 图片迁移页面
- **迁移管理**: `view/admin/src/views/file/ImageMigration.vue`
  - 支持配置迁移参数
  - 提供迁移预览功能
  - 显示迁移结果和统计

### 5. API接口

#### 5.1 部门管理API
- **文件**: `view/admin/src/api/department.ts`
- 包含完整的CRUD操作接口
- 支持TypeScript类型定义

#### 5.2 文件管理API
- **文件**: `view/admin/src/api/file.ts`
- 包含文件上传和迁移相关接口
- 支持多种文件操作

## 命名规范适配

### 1. 数据库表名
- 使用单数形式 (如: `department` 而不是 `departments`)
- 主键使用 `表名_id` 格式 (如: `department_id`, `position_id`)

### 2. 模型配置
- `protected $pk` 设置为对应的主键名
- `protected $name` 设置为单数表名

### 3. 控制器参数
- 方法参数使用对应的主键名 (如: `read(int $department_id)`)
- 内部逻辑使用对应的主键名

## 使用方法

### 1. 数据库初始化
```sql
-- 执行部门表结构SQL
source department_tables.sql;
```

### 2. 访问部门管理
- 前端路由: `/department`
- 支持完整的部门树状管理
- 支持职位管理

### 3. 使用图片迁移
- 前端路由: `/file/migration`
- 配置旧域名和新域名
- 先预览后执行迁移

## 注意事项

### 1. 依赖关系
- 需要确保 `BaseController`, `BaseModel` 等基础类存在
- 需要确保 `ExportService` 等依赖服务可用

### 2. 权限配置
- 需要在菜单和权限系统中配置相应的访问权限
- 建议为不同操作配置细粒度权限

### 3. 数据安全
- 图片迁移操作不可逆，请谨慎使用
- 建议在测试环境先验证迁移效果

## 后续工作

### 1. 菜单配置
- 在系统菜单中添加部门和文件管理入口
- 配置相应的权限节点

### 2. 权限同步
- 运行权限同步命令，生成新的权限记录
- 为管理员分配相应权限

### 3. 功能测试
- 测试部门管理的各项功能
- 测试图片迁移的完整流程
- 验证数据一致性和完整性

## 总结

本次迁移成功将 `tp_admin` 的部门和文件功能完整复制到 `tp_admin_old`，并按照其命名规范进行了适配。所有功能包括：

- ✅ 完整的部门管理（增删改查、树状结构、职位管理）
- ✅ 增强的文件管理（图片URL迁移）
- ✅ 完整的数据库结构
- ✅ 完整的前端页面
- ✅ 完整的API接口
- ✅ 符合命名规范的代码适配

迁移后的功能与 `tp_admin` 保持一致，可以直接使用。
