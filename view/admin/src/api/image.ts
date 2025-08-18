import request from '@/utils/request'

// 图片相关接口
export const imageApi = {
  // 获取图片列表
  getList: (params?: any) => request.get('/adminapi/image/index', { params }),
  
  // 获取图片详情
  getDetail: (id: string) => request.get(`/adminapi/image/read/${id}`),
  
  // 上传图片
  upload: (data: FormData, config?: any) => request.post('/adminapi/upload/image', data, config),
  
  // 删除图片
  delete: (id: string) => request.delete(`/adminapi/image/delete/${id}`),
  
  // 批量删除图片
  batchDelete: (ids: string[]) => request.delete('/adminapi/image/batch-delete', { data: { ids } }),
  
  // 更新图片信息
  update: (id: string, data: any) => request.put(`/adminapi/image/update/${id}`, data),
  
  // 移动图片到其他分类
  moveToCategory: (ids: string[], categoryId: number) => request.post('/adminapi/image/move-category', {
    ids,
    category_id: categoryId
  }),
  
  // 获取图片分类列表
  getCategories: () => request.get('/adminapi/image/categories'),
  
  // 创建图片分类
  createCategory: (data: any) => request.post('/adminapi/image/category/create', data),
  
  // 更新图片分类
  updateCategory: (id: number, data: any) => request.put(`/adminapi/image/category/update/${id}`, data),
  
  // 删除图片分类
  deleteCategory: (id: number) => request.delete(`/adminapi/image/category/delete/${id}`),
  
  // 获取图片标签列表
  getTags: () => request.get('/adminapi/image/tags'),
  
  // 创建图片标签
  createTag: (data: any) => request.post('/adminapi/image/tag/create', data),
  
  // 删除图片标签
  deleteTag: (id: number) => request.delete(`/adminapi/image/tag/delete/${id}`)
}

// 导出单个方法，方便使用
export const {
  getList: getImageList,
  getDetail: getImageDetail,
  upload: uploadImage,
  delete: deleteImage,
  batchDelete: batchDeleteImage,
  update: updateImage,
  moveToCategory: moveImageToCategory,
  getCategories: getImageCategories,
  createCategory: createImageCategory,
  updateCategory: updateImageCategory,
  deleteCategory: deleteImageCategory,
  getTags: getImageTags,
  createTag: createImageTag,
  deleteTag: deleteImageTag
} = imageApi

// 类型定义
export interface Image {
  file_id: string
  origin_name: string
  file_name: string
  size: number
  mime_type: string
  storage_type: string
  storage_path: string
  url: string
  access_domain: string
  storage_permission: string
  uploader_type: string
  uploader_id: number
  category_id?: number
  tags?: string[]
  created_at: string
  updated_at: string
  deleted_at?: string
  created_by?: number
  updated_by?: number
  created_type?: string
}

export interface ImageCategory {
  category_id: number
  name: string
  code?: string
  parent_id: number
  level: number
  path?: string
  sort: number
  status: number
  description?: string
  count: number
  created_at: string
  updated_at: string
  deleted_at?: string
  created_by?: number
  updated_by?: number
  created_type?: string
  children?: ImageCategory[]
}

export interface ImageTag {
  tag_id: number
  name: string
  color?: string
  count: number
  created_at: string
  updated_at: string
}

export interface CreateImageCategoryRequest {
  name: string
  code?: string
  parent_id: number
  sort?: number
  status: number
  description?: string
}

export interface UpdateImageCategoryRequest extends CreateImageCategoryRequest {}

export interface CreateImageTagRequest {
  name: string
  color?: string
}

export interface UpdateImageRequest {
  category_id?: number
  tags?: string[]
  storage_permission?: string
  description?: string
}

export interface ImageListParams {
  page?: number
  list_rows?: number
  keyword?: string
  category_id?: number
  tags?: string[]
  storage_type?: string
  uploader_type?: string
  date_range?: [string, string]
}

export interface ImageListResponse {
  list: Image[]
  total: number
  page: number
  list_rows: number
  has_more: boolean
}

// 文件存储类型常量
export const STORAGE_TYPES = {
  LOCAL: 'local',
  ALIYUN_OSS: 'aliyun_oss',
  QCLOUD_COS: 'qcloud_cos',
  AWS_S3: 'aws_s3'
} as const

// 文件权限常量
export const PERMISSION_TYPES = {
  PUBLIC: 'public',
  PRIVATE: 'private',
  ADMIN_ONLY: 'admin_only'
} as const

// 上传者类型常量
export const UPLOADER_TYPES = {
  ADMIN: 'admin',
  USER: 'user',
  SYSTEM: 'system'
} as const

// 图片格式常量
export const IMAGE_FORMATS = {
  JPEG: 'image/jpeg',
  PNG: 'image/png',
  GIF: 'image/gif',
  WEBP: 'image/webp',
  SVG: 'image/svg+xml'
} as const

// 图片尺寸常量
export const IMAGE_SIZES = {
  THUMBNAIL: '150x150',
  SMALL: '300x300',
  MEDIUM: '600x600',
  LARGE: '1200x1200',
  ORIGINAL: 'original'
} as const
