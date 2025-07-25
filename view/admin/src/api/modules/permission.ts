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
 * @param id 权限ID
 */
export const getReadApi = (id: number) => {
  return http.get<Permission.PermissionOptions>(
    `/permission/read/${id}`,
    {},
    { loading: true }
  );
};

/**
 * 更新权限
 * @param id 权限ID
 * @param params 更新数据
 */
export const putUpdateApi = (
  id: number,
  params: Permission.PermissionOptions
) => {
  return http.put(`/permission/update/${id}`, params, { loading: true });
};


/**
 * 删除权限
 * @param id
 */

export const deleteDeleteApi = (
  id: number,
) => {
  return http.delete(`/permission/delete/${id}`, { loading: true });
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
