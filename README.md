

# TP Admin

基于 ThinkPHP 8 的后台管理系统框架，提供完整的权限管理、菜单管理、角色管理、管理员管理等功能。系统采用现代化架构设计，整合了 Websocket 消息推送、JWT 认证、文件存储等实用功能，适用于快速搭建企业级后台系统。

## 特性

- **权限管理**：支持基于菜单和接口的权限配置，实现细粒度权限控制。
- **角色管理**：支持角色创建、编辑、菜单与权限绑定，便于权限体系搭建。
- **菜单管理**：支持菜单树结构管理，权限依赖配置，实现菜单与权限的联动控制。
- **管理员管理**：支持管理员的增删改查，密码加密修改，角色分配。
- **文件上传**：支持本地、阿里云 OSS、腾讯云 COS、AWS S3 �:// storage types.
- **消息推送**：通过 Websocket 与 Redis 订阅实现后台消息实时推送。
- **JWT 认证**：支持 Token 生成、验证、刷新、登出等认证相关操作。
- **数据导出**：支持 Excel 数据导出，整合了 Laravel Collection 的树形结构处理。
- **多环境配置**：支持开发、测试、生产等多环境配置文件管理。
- **前端组件**：基于 Vue 3 + Vite 构建，整合了 ECharts、WangEditor、ProTable、Upload、SearchForm、SvgIcon、ThemeSetting �:// UI 组件。
- **国际化支持**：系统支持多语言切换，集成 i18n 多语言体系。
- **主题定制**：支持深色模式、动态主题配置，提升用户体验。
- **动态路由**：基于 Vue Router 实现动态菜单路由加载。

## 安装

1. 克隆项目：
   ```bash
   git clone https://gitee.com/xidear/tp_admin
   ```

2. 安装依赖：
   ```bash
   cd tp_admin
   composer install
   cd view/admin
   pnpm install
   ```

3. 配置环境：
   - 修改 `config/database.php` 配置数据库连接。
   - 修改 `config/jwt.php` 配置 JWT 认证信息。
   - 修改 `config/filesystem.php` 配置文件存储。
   - 修改 `.env` 文件，配置前端相关参数。

4. 导入数据库：
   - 使用 `tp_admin.sql` 导入数据库结构和基础数据。

5. 启动前端：
   ```bash
   cd view/admin
   pnpm run dev
   ```

6. 启动后端服务：
   ```bash
   cd ../..
   php think run
   ```

7. 启动 Websocket 消息服务（如使用消息推送）：
   - 修改 `admin_websocket.php` 中 Redis 配置。
   - 运行：
     ```bash
     php think worker
     ```

## 使用说明

- **管理员**：通过 `/admin/admin` 路由管理后台用户。
- **角色**：通过 `/admin/role` 进行角色创建与权限配置。
- **权限**：通过 `/admin/permission` �ilogin` 进行接口权限管理。
- **菜单**：通过 `/admin/menu` 实现菜单树的管理与权限依赖配置。
- **文件**：通过 `/admin/file` 实现文件上传、下载、管理。
- **消息推送**：系统通过 Redis 订阅和 Websocket 实现实时消息推送，相关类 `app/controller/admin/websocket/Message.php`。
- **JWT 认证**：系统使用 `JwtService` 实现 Token 的生成、验证、刷新、登出等功能，位于 `app/service/JwtService.php`。
- **数据导出**：通过 `app/service/export/ExportService.php` 实现 Excel 数据导出功能。
- **前端构建**：使用 Vite 构建，支持自动代码压缩、PWA、环境变量注入、代理设置等高级功能。
- **主题与国际化**：前端支持深色模式、动态主题、多语言切换（zh/en）。
- **自定义指令**：如权限控制 `v-auth`、复制 `v-copy`、防抖 `v-debounce`、水印 `v-waterMarker`、拖动 `v-draggable` 等。

## 命名规范

- 控制器使用 `PascalCase`，如 `Index.php`。
- 模型文件采用 `PascalCase`，如 `Admin.php`。
- 请求验证类采用 `PascalCase`，如 `Create.php`、`Edit.php`。
- 前端组件命名采用 `kebab-case`，如 `search-form`。
- 前端路由模块使用 `PascalCase`，如 `staticRouter.ts`、`dynamicRouter.ts`。
- 枚举命名采用 `PascalCase`，如 `AdminStatus.php`、`Code.php`。

## 版权信息

本项目使用 MIT 开源协议，详情请参考 `LICENSE.txt`。

## 参与开发

欢迎提交 PR 或 Issue，帮助完善系统功能。前端基于 Vue 3 + Vite 构建，后端基于 ThinkPHP 8 + Composer 管理，数据库使用 MySQL。

## 贡献者

- [xidear](https://gitee.com/xidear)
- [HalseySpicy](https://github.com/HalseySpicy)

## 赞助

如果你觉得这个项目对你有帮助，欢迎赞助以支持项目持续发展。你可以通过 Gitee 或 GitHub 联系作者进行捐赠。

## 文档

详细文档请参考 `README.md` 和前端 `CHANGELOG.md` 文件，其中包含前端功能更新日志与后端接口文档。

## 目录结构说明

```
app/                     # ThinkPHP 后端代码
├── controller/           # 控制器
├── model/                # 数据模型
├── request/              # 请求验证类
├── middleware/           # 请求中间件
├── service/              # 业务逻辑服务类
├── common/               # 公共类、Trait、枚举、基础模型等
├── enum/                 # 枚举类
├── exception/            # 异常处理类
├── trait/                # Trait 扩展
├── common.php            # 公共函数文件
view/admin/              # 前端 Vue 项目
├── src/                  # Vue 源码
├── components/           # 自定义组件
├── layouts/              # 页面布局组件
├── routers/              # 路由配置
├── stores/               # Pinia 状态管理
├── utils/                # 工具类
├── assets/               # 静态资源文件
├── styles/               # 全局样式
├── typings/              # 类型定义
├── .env.*                # 多环境配置文件
├── pnpm-lock.yaml        # pnpm 包管理锁定
```

## 许可证

MIT License

## 联系

如有问题或合作意向，欢迎通过 Gitee 联系作者。