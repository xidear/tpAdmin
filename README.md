# TP Admin - 现代化后台管理系统

## 🌟 系统简介

TP Admin 是一个基于 ThinkPHP 8.x + Vue 3 + Element Plus 构建的现代化后台管理系统。系统采用前后端分离架构，提供完整的权限管理、用户管理、系统配置等功能，适用于企业级应用开发。

## 🚀 在线演示

- **演示地址**: [https://admin.test.binary.ha.cn/admin](https://admin.test.binary.ha.cn/admin)
- **默认账号**: `admin`
- **默认密码**: `admin@test1`

## ✨ 核心功能

### 🔐 权限管理
- **角色管理**: 支持多角色配置，灵活分配权限
- **菜单权限**: 动态菜单配置，支持多级菜单结构
- **按钮权限**: 细粒度权限控制，精确到按钮级别
- **数据权限**: 支持按部门、角色等维度控制数据访问

### 👥 用户管理
- **管理员管理**: 完整的用户CRUD操作
- **部门管理**: 支持多级部门结构，树形展示
- **职位管理**: 灵活的职位配置系统
- **个人信息**: 支持头像、用户名、昵称等个人信息编辑

### 📁 文件管理
- **文件上传**: 支持图片、视频、文档等多种文件类型
- **文件分类**: 灵活的文件分类管理
- **权限控制**: 支持私有、公共、共享等存储权限
- **文件选择器**: 可复用的文件选择组件

### ⚙️ 系统配置
- **配置管理**: 支持分组配置，便于系统参数管理
- **配置表单**: 动态表单生成，支持多种数据类型
- **缓存管理**: 智能缓存策略，提升系统性能

### 📊 数据管理
- **地区管理**: 完整的省市区三级联动
- **任务调度**: 支持定时任务和队列处理
- **日志记录**: 完整的操作日志和系统日志
- **数据导出**: 支持Excel等格式的数据导出

### 🔧 开发工具
- **代码生成**: 快速生成CRUD代码
- **API文档**: 自动生成API接口文档
- **调试工具**: 完善的调试和错误处理机制

## 🛠️ 技术架构

### 后端技术栈
- **框架**: ThinkPHP 8.x
- **数据库**: MySQL 8.0+
- **缓存**: Redis
- **队列**: ThinkPHP Queue
- **WebSocket**: Swoole（生产环境）/ Workerman（开发环境，仅Linux/WSL2）
- **PHP版本**: PHP 8.3+

### 前端技术栈
- **框架**: Vue 3 + TypeScript
- **UI组件**: Element Plus
- **状态管理**: Pinia
- **路由**: Vue Router 4
- **构建工具**: Vite
- **HTTP客户端**: Axios

### 核心特性
- **前后端分离**: 清晰的API接口设计
- **响应式设计**: 支持PC端和移动端
- **主题定制**: 支持明暗主题切换
- **国际化**: 支持多语言切换
- **权限验证**: 完整的权限验证体系

## 📦 安装部署

### 环境要求
- PHP >= 8.3
- MySQL >= 8.0
- Redis >= 6.0
- Node.js >= 16.0
- Composer >= 2.0

### 开发环境说明
- **生产环境**: 仅支持Linux，使用Swoole（已弃用Workerman）
- **开发环境**: 
  - 支持Windows（WSL2推荐）
  - 使用 `php think run` 启动Web服务
  - 使用 `php think queue:work` 启动队列处理
  - 如需WebSocket和定时任务调试，使用 `php admin_websocket.php start`

### 快速开始

#### 1. 克隆项目
```bash
git clone https://gitee.com/xidear/tp_admin.git
cd tp_admin
```

#### 2. 安装后端依赖
```bash
composer install
```

#### 3. 配置环境
```bash
# 复制环境配置文件
cp env.example .env

# 修改 .env 文件中的配置项：
# - 数据库密码 (DB_PASS)
# - Redis配置 (如果Redis有密码)
# - 文件上传路径和大小限制 (如果需要调整)

# 详细配置说明请参考项目根目录的 ENV_CONFIG_README.md 文件
```

#### 4. 导入数据库
```bash
# 使用项目根目录的 tp_admin.sql 文件导入数据库
# 方法1: 使用命令行
mysql -u root -p tp_admin < tp_admin.sql

# 方法2: 使用phpMyAdmin或其他数据库管理工具
# 创建数据库 tp_admin，然后导入 tp_admin.sql 文件
```

#### 5. 安装前端依赖
```bash
cd view/admin
npm install  # 推荐使用npm，项目未测试pnpm
```

#### 6. 构建前端
```bash
npm run build
```

#### 7. 启动服务

**开发环境**:
```bash
# 启动Web服务
php think run

# 启动队列处理（可选）
php think queue:work

# 启动WebSocket和定时任务（可选，仅Windows）
php admin_websocket.php start
```

**生产环境**:
```bash
# 启动Swoole服务（仅Linux）
php think swoole

```

## 🚀 部署说明

### Nginx配置
系统已提供完整的Nginx配置示例，支持：
- 静态文件缓存优化
- 上传文件直接访问
- 前端路由支持
- SSL证书配置

### 缓存配置
- **Redis缓存**: 用户信息、系统配置等
- **文件缓存**: 静态资源、上传文件等
- **智能清理**: 数据更新后自动清理相关缓存

### 安全配置
- **权限验证**: 完整的权限验证中间件
- **数据过滤**: 防止SQL注入和XSS攻击
- **文件上传**: 严格的文件类型和大小验证

## 📚 开发文档

### API接口
- 所有API接口遵循RESTful规范
- 支持JWT Token认证
- 完整的错误码和错误信息

### 前端组件
- 提供丰富的可复用组件
- 支持TypeScript类型定义
- 完整的组件文档和示例

### 数据库设计
- 清晰的数据库表结构
- 完整的字段说明
- 支持软删除和审计字段

## 🤝 贡献指南

欢迎提交Issue和Pull Request来帮助改进项目！

### 开发规范
- 遵循PSR-12编码规范
- 使用TypeScript进行前端开发
- 完善的代码注释和文档

### 提交规范
- 使用语义化的提交信息
- 提供完整的测试用例
- 更新相关文档

## 📄 开源协议

本项目采用 [MIT License](LICENSE) 开源协议。

## 📞 联系我们

- **项目地址**: [https://gitee.com/xidear/tp_admin](https://gitee.com/xidear/tp_admin)
- **问题反馈**: [Issues](https://gitee.com/xidear/tp_admin/issues)
- **邮箱**: xidear@126.com

## 🙏 致谢

感谢所有为这个项目做出贡献的开发者和用户！

---

**如果这个项目对你有帮助，请给个⭐️ Star 支持一下！**