import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";
import {Menu} from "@/typings/global";

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
 * @param menu_id 菜单ID
 */
export const getReadApi = (menu_id: number) => {
  return http.get<Menu.MenuOptions>(`/menu/read/${menu_id}`,{}, { loading: true });
};

/**
 * 更新菜单
 * @param menu_id 菜单ID
 * @param params 更新数据
 */
export const putUpdateApi = (menu_id: number, params: Menu.MenuOptions) => {
  return http.put(`/menu/update/${menu_id}`, params, { loading: true });
};

/**
 * 删除菜单
 * @param menu_id 菜单ID
 */
export const deleteDeleteApi = (menu_id: number) => {
  return http.delete(`/menu/delete/${menu_id}`, { loading: true });
};

