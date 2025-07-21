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
 * @param id ID
 */
export const getReadApi = (id: number) => {
  return http.get<Admin.AdminOptions>(
    `/admin/read/${id}`,
    {},
    { loading: true }
  );
};

/**
 * 更新
 * @param id ID
 * @param params 更新数据
 */
export const putUpdateApi = (
  id: number,
  params: Admin.AdminOptions
) => {
  return http.put(`/admin/update/${id}`, params, { loading: true });
};

/**
 * 删除（支持单个和批量删除）
 * @param params 删除参数
 */


export const deleteDeleteApi = (params: { ids: number[] }) => {
  return http.delete("/admin/delete", {
    data: params,       // 请求体数据
  });
};
