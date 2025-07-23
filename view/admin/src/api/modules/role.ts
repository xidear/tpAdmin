import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// 获取列表
export const getListApi = (params?: any) => {
  return http.get<Admin.AdminListResponse>(
    PORT1 + `/role/index`,
    params,
    { loading: true }
  );
};

/**
 * 新增
 * @param params 数据
 */
export const postCreateApi = (params: Admin.AdminOptions) => {
  return http.post("/role/create", params, { loading: true });
};

/**
 * 获取详情
 * @param id ID
 */
export const getReadApi = (id: number) => {
  return http.get<Admin.AdminOptions>(
    `/role/read/${id}`,
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
  return http.put(`/role/update/${id}`, params, { loading: true });
};




export const deleteDeleteApi = (
  id: number,
) => {
  return http.delete(`/role/delete/${id}`, { loading: true });
};





/**
 * 批量删除
 * @param params 删除参数
 */
export const batchDeleteDeleteApi = (params: { ids: number[] }) => {
  return http.batchDelete("/role/batch_delete", {
    data: params,       // 请求体数据
  });
};
