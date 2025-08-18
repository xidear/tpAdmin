# 图片选择器组件使用说明

## 概述

图片选择器（ImageSelector）是一个可复用的Vue组件，允许用户从系统中选择现有图片或上传新图片。该组件支持图片分类管理、标签系统、搜索过滤等功能，类似于crmeb的附件管理设计。

## 功能特性

- **图片选择**：从系统中选择现有图片
- **图片上传**：上传新图片到指定分类
- **分类管理**：支持图片分类的创建、编辑、删除
- **标签系统**：为图片添加标签，支持多标签关联
- **搜索过滤**：按名称搜索图片
- **分页显示**：支持大量图片的分页展示
- **多选模式**：支持单张或多张图片选择
- **自定义触发**：支持自定义触发按钮样式

## 组件结构

```
ImageSelector/
├── index.vue              # 主组件
├── ImageUpload.vue        # 图片上传组件
└── CategoryManager.vue    # 分类管理组件
```

## 使用方法

### 基础用法

```vue
<template>
  <div>
    <ImageSelector v-model="selectedImages" />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ImageSelector from '@/components/ImageSelector/index.vue'

const selectedImages = ref([])
</script>
```

### 高级用法

```vue
<template>
  <div>
    <!-- 单张图片选择 -->
    <ImageSelector 
      v-model="singleImage" 
      :multiple="false"
      @change="handleImageChange"
    />
    
    <!-- 多张图片选择 -->
    <ImageSelector 
      v-model="multipleImages" 
      :multiple="true"
      :max-count="5"
      @change="handleImagesChange"
    />
    
    <!-- 自定义触发按钮 -->
    <ImageSelector v-model="selectedImages">
      <template #trigger>
        <el-button type="success" icon="Picture">
          选择图片
        </el-button>
      </template>
    </ImageSelector>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ImageSelector from '@/components/ImageSelector/index.vue'

const singleImage = ref(null)
const multipleImages = ref([])
const selectedImages = ref([])

const handleImageChange = (image) => {
  console.log('选择的图片:', image)
}

const handleImagesChange = (images) => {
  console.log('选择的图片列表:', images)
}
</script>
```

## 组件属性

| 属性名 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| `v-model` | Array/Object | `[]` | 选中的图片，支持v-model双向绑定 |
| `multiple` | Boolean | `true` | 是否支持多选 |
| `maxCount` | Number | `-` | 最大选择数量，仅在multiple为true时有效 |
| `visible` | Boolean | `false` | 控制对话框显示状态 |

## 组件事件

| 事件名 | 参数 | 说明 |
|--------|------|------|
| `change` | selectedImages | 选择变化时触发 |
| `confirm` | selectedImages | 确认选择时触发 |
| `cancel` | - | 取消选择时触发 |

## 插槽

| 插槽名 | 说明 |
|--------|------|
| `trigger` | 自定义触发按钮，默认显示"选择图片"按钮 |

## 数据库表结构

### 新增表

#### image_category（图片分类表）
- `category_id`: 分类ID（主键）
- `name`: 分类名称
- `code`: 分类编码
- `parent_id`: 父分类ID
- `level`: 分类层级
- `path`: 分类路径
- `sort`: 排序
- `status`: 状态
- `description`: 分类描述

#### image_tag（图片标签表）
- `tag_id`: 标签ID（主键）
- `name`: 标签名称
- `color`: 标签颜色
- `sort`: 排序
- `status`: 状态

#### image_tag_relation（图片标签关联表）
- `id`: 关联ID（主键）
- `file_id`: 文件ID
- `tag_id`: 标签ID

### 修改表

#### file（文件表）
- 新增 `category_id`: 图片分类ID
- 新增 `tags`: 图片标签（逗号分隔）

## API接口

### 图片管理接口

```typescript
// 获取图片列表
imageApi.getList(params)

// 上传图片
imageApi.upload(formData, config)

// 删除图片
imageApi.delete(id)

// 更新图片信息
imageApi.update(id, data)

// 移动图片到其他分类
imageApi.moveToCategory(ids, categoryId)
```

### 分类管理接口

```typescript
// 获取分类列表
imageApi.getCategories()

// 创建分类
imageApi.createCategory(data)

// 更新分类
imageApi.updateCategory(id, data)

// 删除分类
imageApi.deleteCategory(id)
```

### 标签管理接口

```typescript
// 获取标签列表
imageApi.getTags()

// 创建标签
imageApi.createTag(data)

// 删除标签
imageApi.deleteTag(id)
```

## 使用场景

### 1. 商品图片选择
```vue
<ImageSelector 
  v-model="productImages" 
  :multiple="true"
  :max-count="6"
/>
```

### 2. 头像选择
```vue
<ImageSelector 
  v-model="avatar" 
  :multiple="false"
/>
```

### 3. 文章配图选择
```vue
<ImageSelector 
  v-model="articleImages" 
  :multiple="true"
  :max-count="10"
/>
```

### 4. 轮播图选择
```vue
<ImageSelector 
  v-model="bannerImages" 
  :multiple="true"
  :max-count="5"
/>
```

## 注意事项

1. **权限控制**：确保用户有相应的图片查看和上传权限
2. **文件大小限制**：上传图片时注意服务器配置的文件大小限制
3. **存储类型**：支持本地存储、阿里云OSS、腾讯云COS、AWS S3等
4. **图片格式**：建议使用JPG、PNG、WebP等常见格式
5. **性能优化**：大量图片时建议启用分页和懒加载

## 扩展功能

### 自定义图片处理
```vue
<ImageSelector 
  v-model="selectedImages"
  @before-upload="handleBeforeUpload"
  @after-upload="handleAfterUpload"
/>
```

### 图片预览增强
```vue
<ImageSelector 
  v-model="selectedImages"
  :show-preview="true"
  :preview-size="['100px', '100px']"
/>
```

### 分类权限控制
```vue
<ImageSelector 
  v-model="selectedImages"
  :allowed-categories="[1, 2, 3]"
  :show-category-manager="false"
/>
```

## 常见问题

### Q: 如何限制上传图片的尺寸？
A: 在ImageUpload组件中添加尺寸验证逻辑。

### Q: 如何自定义图片分类的显示样式？
A: 修改CategoryManager组件的模板和样式。

### Q: 如何添加图片水印功能？
A: 在图片上传成功后调用水印处理接口。

### Q: 如何实现图片压缩？
A: 在客户端使用canvas进行压缩，或在上传后由服务端处理。

## 更新日志

- v1.0.0: 初始版本，支持基础图片选择和上传功能
- v1.1.0: 新增分类管理和标签系统
- v1.2.0: 优化搜索和分页功能
- v1.3.0: 新增图片迁移功能
- v1.4.0: 增强组件复用性和自定义能力
