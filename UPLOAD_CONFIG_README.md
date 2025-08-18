# 文件上传配置使用说明

## 概述

本系统已经集成了完整的文件上传配置管理功能，支持多种存储方式：
- 本地存储
- 七牛云存储
- 阿里云OSS
- 腾讯云COS
- AWS S3

**重要说明**：所有配置都存储在现有的 `system_config` 表中，无需额外创建数据表。系统会自动读取配置，**前端组件无需修改**，继续使用现有的上传接口即可。

## 🎯 **架构设计**

### 1. **配置驱动**
- 存储方式通过系统配置 `upload_storage_type` 控制
- 前端上传时无需传递 `storage_type` 参数
- 系统自动根据配置选择存储方式

### 2. **保持兼容**
- 现有的上传接口 `/adminapi/upload/file` 和 `/adminapi/upload/image` 保持不变
- 前端组件 ImageUpload、Upload/Img、Upload/Imgs 等无需修改
- 所有上传都会自动使用系统配置的存储方式

## 配置结构

### 1. 配置分组
- **分组名称**: 文件上传
- **分组ID**: 自动生成

### 2. 配置项列表

#### 2.1 当前存储方式 (upload_storage_type)
- **类型**: 下拉选择器 (SELECT)
- **默认值**: local
- **选项**: 
  - local: 本地存储
  - qiniu: 七牛云
  - aliyun_oss: 阿里云OSS
  - qcloud_cos: 腾讯云COS
  - aws_s3: AWS S3

#### 2.2 本地存储配置 (upload_local_config)
- **类型**: 键值对配置 (KEY_VALUE)
- **默认值**: 
```json
{
    "storage_path": "public/storage",
    "url_prefix": "/storage",
    "max_size": "10485760"
}
```

#### 2.3 七牛云配置 (upload_qiniu_config)
- **类型**: 键值对配置 (KEY_VALUE)
- **默认值**: 
```json
{
    "access_key": "",
    "secret_key": "",
    "bucket": "",
    "domain": "",
    "url": "",
    "region": "",
    "is_enabled": false
}
```

#### 2.4 阿里云OSS配置 (upload_aliyun_oss_config)
- **类型**: 键值对配置 (KEY_VALUE)
- **默认值**: 
```json
{
    "access_key_id": "",
    "access_key_secret": "",
    "endpoint": "",
    "bucket": "",
    "url": "",
    "is_cname": false,
    "is_enabled": false
}
```

#### 2.5 腾讯云COS配置 (upload_qcloud_cos_config)
- **类型**: 键值对配置 (KEY_VALUE)
- **默认值**: 
```json
{
    "app_id": "",
    "secret_id": "",
    "secret_key": "",
    "region": "",
    "bucket": "",
    "url": "",
    "is_enabled": false
}
```

#### 2.6 AWS S3配置 (upload_aws_s3_config)
- **类型**: 键值对配置 (KEY_VALUE)
- **默认值**: 
```json
{
    "key": "",
    "secret": "",
    "region": "",
    "bucket": "",
    "url": "",
    "endpoint": "",
    "is_enabled": false
}
```

#### 2.7 上传通用配置 (upload_common_config)
- **类型**: 键值对配置 (KEY_VALUE)
- **默认值**: 
```json
{
    "allowed_extensions": "jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,mp4,zip,rar",
    "max_file_size": "10485760",
    "image_quality": "80",
    "watermark_enabled": false,
    "watermark_config": {
        "text": "",
        "image": "",
        "position": "bottom-right"
    }
}
```

## 安装步骤

### 1. 添加数据库字段（如果需要）
如果你的 `system_config` 表中没有 `vue_rules` 字段，请先执行：

```sql
-- 为 system_config 表添加 vue_rules 字段
ALTER TABLE `system_config` 
ADD COLUMN `vue_rules` JSON NULL COMMENT '前端验证规则' AFTER `rules`;
```

或者运行提供的脚本：
```bash
mysql -u username -p database_name < add_vue_rules_field.sql
```

### 2. 执行配置脚本
运行 `upload_config_setup.sql` 文件，该脚本会：
- 创建"文件上传"配置分组
- 添加所有必要的配置项
- 设置默认值和选项

### 3. 配置云存储服务
根据选择的云存储服务，填写相应的配置信息：

#### 七牛云配置示例
```json
{
    "access_key": "your_access_key",
    "secret_key": "your_secret_key",
    "bucket": "your_bucket_name",
    "domain": "your_domain.com",
    "url": "https://your_domain.com",
    "region": "cn-east-1",
    "is_enabled": true
}
```

#### 阿里云OSS配置示例
```json
{
    "access_key_id": "your_access_key_id",
    "access_key_secret": "your_access_key_secret",
    "endpoint": "oss-cn-hangzhou.aliyuncs.com",
    "bucket": "your_bucket_name",
    "url": "https://your_bucket_name.oss-cn-hangzhou.aliyuncs.com",
    "is_cname": false,
    "is_enabled": true
}
```

#### 腾讯云COS配置示例
```json
{
    "app_id": "your_app_id",
    "secret_id": "your_secret_id",
    "secret_key": "your_secret_key",
    "region": "ap-beijing",
    "bucket": "your_bucket_name",
    "url": "https://your_bucket_name.cos.ap-beijing.myqcloud.com",
    "is_enabled": true
}
```

### 3. 选择存储方式
在"当前存储方式"配置项中选择要使用的存储服务。

## 🚀 **使用方法**

### 1. **前端无需修改**
现有的前端组件和API调用保持不变：

```typescript
// 图片上传 - 无需修改
const response = await uploadImage(formData)

// 文件上传 - 无需修改  
const response = await uploadFile(formData)
```

### 2. **系统自动处理**
- 系统自动读取 `upload_storage_type` 配置
- 根据配置选择对应的存储方式
- 前端无需关心存储细节

### 3. **动态切换存储**
- 管理员在系统配置中修改 `upload_storage_type`
- 所有新的上传自动使用新的存储方式
- 无需重启服务或修改代码

## 🔧 **技术实现**

### 1. **配置读取**
```php
// File控制器自动读取配置
private function getCurrentStorageType(): string
{
    $storageType = \app\model\SystemConfig::getCacheValue('upload_storage_type', 'local');
    return $storageType;
}
```

### 2. **存储选择**
```php
// 根据配置自动选择存储方式
$storageType = $this->getCurrentStorageType();
$disk = $storageType === FileModel::STORAGE_LOCAL ? 'public' : $storageType;
```

### 3. **保持兼容**
- 现有的验证逻辑保持不变
- 现有的文件处理逻辑保持不变
- 现有的数据库存储逻辑保持不变

## 📱 **前端集成**

### 1. **配置管理页面**
在系统配置管理中找到"文件上传"分组，管理员可以通过Web界面管理这些配置。

### 2. **配置表单**
前端可以根据配置类型动态生成表单：
- 单选类型：显示下拉选择器
- 键值对类型：显示动态键值对编辑器

### 3. **实时生效**
配置修改后立即生效，新的上传自动使用新配置。

## ⚠️ **注意事项**

### 1. **云存储SDK**
要使用云存储功能，需要安装相应的SDK：
```bash
composer require qiniu/php-sdk
composer require aliyuncs/oss-sdk-php
composer require tencentcloud/cos-sdk-v5
```

### 2. **配置验证**
系统会自动验证配置的完整性，确保必要的字段都已填写。

### 3. **权限控制**
所有配置项都标记为系统配置，只有管理员可以修改。

### 4. **缓存机制**
配置修改后会自动刷新缓存，确保配置立即生效。

## 🔄 **扩展功能**

### 1. **添加新的存储服务**
1. 在 `File` 控制器中添加新的存储方法
2. 在配置中添加相应的配置项
3. 更新存储类型列表

### 2. **自定义验证规则**
可以在 `ConfigType` 枚举中添加新的配置类型，支持更复杂的配置结构。

### 3. **文件处理**
可以在现有的文件处理逻辑中添加更多功能，如：
- 图片压缩
- 视频转码
- 文件加密
- 水印处理

## 🐛 **故障排除**

### 1. **配置不生效**
- 检查配置缓存是否已刷新
- 确认配置项是否启用
- 验证配置值格式是否正确

### 2. **上传失败**
- 检查存储服务配置是否完整
- 确认存储服务是否可用
- 查看错误日志获取详细信息

### 3. **权限问题**
- 确认云存储的访问密钥是否正确
- 检查存储桶的访问权限设置
- 验证域名绑定是否正确

## 💡 **最佳实践**

### 1. **配置管理**
- 使用系统配置管理界面管理存储配置
- 定期备份重要的配置信息
- 测试环境使用本地存储，生产环境使用云存储

### 2. **监控和日志**
- 监控文件上传成功率
- 记录存储方式切换日志
- 定期检查云存储服务状态

### 3. **安全考虑**
- 云存储密钥定期轮换
- 限制上传文件类型和大小
- 配置适当的访问权限

## 🆘 **技术支持**

如有问题，请检查：
1. 系统日志文件
2. 配置项的值格式
3. 云存储服务的状态
4. 网络连接情况
5. 是否已执行配置脚本

## 📋 **总结**

这个解决方案的优势：
- ✅ **无需修改前端代码**：现有组件和API保持不变
- ✅ **配置驱动**：通过系统配置动态切换存储方式
- ✅ **保持兼容**：不影响现有的上传功能
- ✅ **易于维护**：所有配置集中管理
- ✅ **实时生效**：配置修改后立即生效
