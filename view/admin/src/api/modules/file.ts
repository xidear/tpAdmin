import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// * 获取文件列表
export const getFileListApi = (params?: any) => {
  return http.get(PORT1 + `/file/index`, params, { loading: true });
};

// * 获取文件详情
export const getFileDetailApi = (fileId: string) => {
  return http.get(PORT1 + `/file/read/${fileId}`, {}, { loading: true });
};

// * 上传文件
export const postFileUploadApi = (formData: FormData, params?: any) => {
  return http.post(PORT1 + `/file/upload`, formData, { 
    params,
    headers: {
      'Content-Type': 'multipart/form-data'
    },
    loading: true
  });
};

// * 获取文件迁移预览
export const getMigrationPreviewApi = (params: {
  old_domain: string
  new_domain: string
  page?: number
  list_rows?: number
}) => {
  return http.get(PORT1 + `/file/get-migration-preview`, params, { loading: true });
};

// * 执行文件地址迁移
export const postMigrateUrlsApi = (params: {
  old_domain: string
  new_domain: string
  batch_size?: number
  max_batches?: number
}) => {
  return http.post(PORT1 + `/file/migrate-urls`, params, { loading: true });
};
