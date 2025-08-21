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

// * 获取文件分类列表
export const getFileCategoriesApi = () => {
  return http.get(PORT1 + `/file/categories`, {}, { loading: false });
};

// * 获取文件标签列表
export const getFileTagsApi = () => {
  return http.get(PORT1 + `/file/tags`, {}, { loading: false });
};

// * 创建文件分类 (需要权限)
export const postFileCategoryCreateApi = (data: any) => {
  return http.post(PORT1 + `/file/category/create`, data);
};

// * 更新文件分类 (需要权限)
export const putFileCategoryUpdateApi = (id: number, data: any) => {
  return http.put(PORT1 + `/file/category/update/${id}`, data);
};

// * 删除文件分类 (需要权限)
export const deleteFileCategoryApi = (id: number) => {
  return http.delete(PORT1 + `/file/category/delete/${id}`);
};

// * 创建文件标签 (需要权限)
export const postFileTagCreateApi = (data: any) => {
  return http.post(PORT1 + `/file/tag/create`, data);
};

// * 删除文件标签 (需要权限)
export const deleteFileTagApi = (id: number) => {
  return http.delete(PORT1 + `/file/tag/delete/${id}`);
};

// * 移动文件分类 (需要权限)
export const postFileMoveCategoryApi = (ids: string[], categoryId: number) => {
  return http.post(PORT1 + `/file/move-category`, {
    ids,
    category_id: categoryId
  });
};