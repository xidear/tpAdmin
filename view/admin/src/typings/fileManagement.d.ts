export declare namespace FileManagement {
  // 文件信息
  interface FileInfo {
    file_id: string
    origin_name: string  // 原始文件名
    file_name: string    // 存储文件名
    mime_type: string
    url: string
    size: number
    storage_permission: string
    storage_type: string
    storage_path?: string
    created_at: string
    updated_at: string
    deleted_at?: string
    uploader_type?: string
    uploader_id?: number
  }

  // 文件列表响应
  interface FileListResponse {
    list: FileInfo[]
    total: number
    page: number
    pageSize: number
  }

  // 文件上传参数
  interface UploadParams {
    file_type?: string
    upload_dir?: string
    permission?: string
  }

  // 文件上传响应
  interface UploadResponse {
    file_id: string
    file_name: string
    url: string
    size: number
    mime_type: string
  }

  // 迁移预览参数
  interface MigrationPreviewParams {
    old_domain: string
    new_domain: string
    page?: number
    list_rows?: number
  }

  // 迁移预览响应
  interface MigrationPreviewResponse {
    old_domain: string
    new_domain: string
    affected_files: FileInfo[]
    total_count: number
    local_count: number
    other_count: number
    page: number
    list_rows: number
    has_more: boolean
    warning?: string
    error?: string
  }

  // 迁移执行参数
  interface MigrationParams {
    old_domain: string
    new_domain: string
    batch_size?: number
    max_batches?: number
  }

  // 迁移执行响应
  interface MigrationResponse {
    total_files: number
    migrated_files: number
    skipped_files: number
    errors: string[]
    success: boolean
    message: string
  }

  // 文件列表查询参数
  interface FileListParams {
    page?: number
    pageSize?: number
    keyword?: string
    mime_type?: string
    storage_type?: string
    storage_permission?: string
    date_range?: [string, string]
  }
}
