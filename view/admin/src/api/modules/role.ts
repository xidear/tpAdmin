import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

export namespace Role {
  // 角色信息结构体（对应列表、表单中的单条角色数据）
  export interface RoleOptions {
    role_id?: number; // 角色ID（新增时可选，编辑/查看时必选）
    name?: string; // 角色名称
    description?: string; // 角色描述
    created_at?: string; // 创建时间
    updated_at?: string; // 更新时间
  }

  // 角色列表接口返回的分页数据结构体
  export interface RolePageResponse {
    total: number; // 总条数
    list: RoleOptions[]; // 当前页角色列表
    current_page: number; // 当前页码
    per_page: number; // 每页条数
  }

  // 角色列表接口整体响应结构体（对应后端返回的完整数据）
  export interface RoleListResponse {
    code: number;
    timestamp: number;
    msg: string;
    data: RolePageResponse[]; // 后端返回的分页数据数组（实际是单元素数组）
  }
}


// 获取列表
export const getListApi = (params?: any) => {
  return http.get<Role.RoleListResponse>(
    PORT1 + `/role/index`,
    params,
    { loading: true }
  );
};

/**
 * 新增
 * @param params 数据
 */
export const postCreateApi = (params: Role.RoleOptions) => {
  return http.post("/role/create", params, { loading: true });
};

/**
 * 获取详情
 * @param id ID
 */
export const getReadApi = (id: number) => {
  return http.get<Role.RoleOptions>(
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
  params: Role.RoleOptions
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
