import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// 获取列表
export const getListApi = (params?: any) => {
  return http.get<Admin.AdminListResponse>(
    PORT1 + `/admin/index`,
    params,
    { loading: true }
  );
};

/**
 * 新增
 * @param params 数据
 */
export const postCreateApi = (params: Admin.AdminOptions) => {
  return http.post("/admin/create", params, { loading: true });
};

/**
 * 获取详情
 * @param admin_id ID
 */
export const getReadApi = (admin_id: number) => {
  return http.get<Admin.AdminOptions>(
    `/admin/read/${admin_id}`,
    {},
    { loading: true }
  );
};

/**
 * 更新
 * @param admin_id admin_id
 * @param params 更新数据
 */
export const putUpdateApi = (
  admin_id: number,
  params: Admin.AdminOptions
) => {
  return http.put(`/admin/update/${admin_id}`, params, { loading: true });
};

/**
 * 删除
 * @param admin_id
 */


export const deleteDeleteApi = ( admin_id: number                                ) => {
  return http.delete(`/admin/delete/${admin_id}`, { loading: true });
};


export const batchDeleteApi = (params: { ids: number[] }) => {
  return http.batchDelete("/admin/batch_delete", {
    data: params,       // 请求体数据
  });
};

