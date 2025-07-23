import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// 获取菜单列表
export const getTreeApi = (params?: any) => {
  return http.get<Menu.MenuOptions[]>(PORT1 + `/menu/tree`, params, { loading: true });
};

/**
 * 新增菜单
 * @param params 菜单数据
 */
export const postCreateApi = (params: Menu.MenuOptions) => {
  return http.post("/menu/create", params, { loading: true });
};

/**
 * 获取单个菜单详情
 * @param id 菜单ID
 */
export const getReadApi = (id: number) => {
  return http.get<Menu.MenuOptions>(`/menu/read/${id}`,{}, { loading: true });
};

/**
 * 更新菜单
 * @param id 菜单ID
 * @param params 更新数据
 */
export const putUpdateApi = (id: number, params: Menu.MenuOptions) => {
  return http.put(`/menu/update/${id}`, params, { loading: true });
};

/**
 * 删除菜单
 * @param id 菜单ID
 */
export const deleteDeleteApi = (id: number) => {
  return http.delete(`/menu/delete/${id}`, { loading: true });
};

