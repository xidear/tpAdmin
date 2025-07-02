import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// 获取菜单列表
export const getAuthMenuListApi = (params?: any) => {
  return http.get<Menu.MenuOptions[]>(PORT1 + `/get_menu`, params, { loading: true });
};


/**
 * 新增菜单
 * @param params 菜单数据
 */
export const addMenuApi = (params: Menu.MenuOptions) => {
  return http.post("/menu", params);
};

/**
 * 获取单个菜单详情
 * @param id 菜单ID
 */
export const getMenuDetailApi = (id: number) => {
  return http.get<Menu.MenuOptions>(`/menu/${id}`);
};

/**
 * 更新菜单
 * @param id 菜单ID
 * @param params 更新数据
 */
export const updateMenuApi = (id: number, params: Menu.MenuOptions) => {
  return http.put(`/menu/${id}`, params);
};

/**
 * 删除菜单
 * @param id 菜单ID
 */
export const deleteMenuApi = (id: number) => {
  return http.delete(`/menu/${id}`);
};

