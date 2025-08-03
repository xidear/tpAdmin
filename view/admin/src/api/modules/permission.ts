import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// 获取权限列表
export const getListApi = (params?: any) => {
  return http.get<Permission.PermissionListResponse>(
    PORT1 + `/permission/index`,
    params,
    { loading: true }
  );
};

/**
 * 新增权限
 * @param params 权限数据
 */
export const postCreateApi = (params: Permission.PermissionOptions) => {
  return http.post("/permission/create", params, { loading: true });
};

/**
 * 同步权限
 */
export const postSyncApi = () => {
  return http.post("/permission/sync", {}, { loading: true });
};
/**
 * 获取权限详情
 * @param permission_id 权限ID
 */
export const getReadApi = (permission_id: number) => {
  return http.get<Permission.PermissionOptions>(
    `/permission/read/${permission_id}`,
    {},
    { loading: true }
  );
};

/**
 * 更新权限
 * @param permission_id 权限ID
 * @param params 更新数据
 */
export const putUpdateApi = (
  permission_id: number,
  params: Permission.PermissionOptions
) => {
  return http.put(`/permission/update/${permission_id}`, params, { loading: true });
};


/**
 * 删除权限
 * @param permission_id
 */

export const deleteDeleteApi = (
  permission_id: number,
) => {
  return http.delete(`/permission/delete/${permission_id}`, { loading: true });
};





/**
 * 批量删除权限
 * @param params 删除参数
 */
export const batchDeleteDeleteApi = (params: { ids: number[] }) => {
  return http.batchDelete("/permission/batch_delete", {
    data: params,       // 请求体数据
  });
};
