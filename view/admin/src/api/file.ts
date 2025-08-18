import request from '@/utils/request'

// 文件相关接口
export const fileApi = {
  // 获取文件列表
  getList: (params?: any) => request.get('/adminapi/file/index', { params }),
  
  // 获取文件详情
  getDetail: (id: string) => request.get(`/adminapi/file/read/${id}`),
  
  // 上传文件
  upload: (data: FormData, config?: any) => request.post('/adminapi/upload/file', data, config),
  
  // 上传图片
  uploadImage: (data: FormData, config?: any) => request.post('/adminapi/upload/image', data, config),
  
  // 获取图片迁移预览
  getMigrationPreview: (params: any) => request.get('/adminapi/file/get-migration-preview', { params }),
  
  // 执行图片地址迁移
  migrateUrls: (data: any) => request.post('/adminapi/file/migrate-urls', data)
}

// 导出单个方法，方便使用
export const {
  getList: getFileList,
  getDetail: getFileDetail,
  upload: uploadFile,
  uploadImage: uploadImageFile,
  getMigrationPreview,
  migrateUrls
} = fileApi

// 类型定义
export interface File {
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
  created_at: string
  updated_at: string
  deleted_at?: string
  created_by?: number
  updated_by?: number
  created_type?: string
}

export interface UploadResponse {
  url: string
  message: string
}

export interface MigrationPreviewParams {
  old_domain: string
  new_domain: string
  page?: number
  list_rows?: number
}

export interface MigrationPreviewResponse {
  old_domain: string
  new_domain: string
  affected_files: Array<{
    table: string
    id: string | number
    old_url: string
    new_url: string
    file_name: string
    storage_type: string
    is_local: boolean
  }>
  total_count: number
  local_count: number
  other_count: number
  page: number
  list_rows: number
  has_more: boolean
  warning?: string
  error?: string
}

export interface MigrationParams {
  old_domain: string
  new_domain: string
  batch_size?: number
  max_batches?: number
}

export interface MigrationResponse {
  total_files: number
  migrated_files: number
  skipped_files: number
  errors: string[]
  batches_processed: number
  current_batch: number
  warning?: string
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
