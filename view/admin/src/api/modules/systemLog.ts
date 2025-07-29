import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// 系统日志数据结构定义
export interface SystemLogItem {
  id: number;
  admin_id: number;
  username: string;
  module: string;
  controller: string;
  action: string;
  route_path: string;
  route_name: string;
  description: string;
  request_method: string;
  request_url: string;
  request_param: string;
  ip: string;
  user_agent: string;
  status: number;
  error_msg: string;
  execution_time: number;
  created_at: string;
  // 扩展字段 - 从后端append获取
  ua?: string;
}

// 系统日志列表响应
export interface SystemLogListResponse {
  list: SystemLogItem[];
  total: number;
  page: number;
  list_rows: number;
  total_page: number;
}

// 系统日志详情响应
export interface SystemLogDetailResponse {
  data: SystemLogItem;
  code: number;
  msg: string;
}

// 获取列表
export const getListApi = (params?: any) => {
  return http.get<SystemLogListResponse>(
    PORT1 + `/log/index`,
    params,
    { loading: true }
  );
};

/**
 * 获取详情
 * @param id 日志ID
 */
export const getReadApi = (id: number) => {
  return http.get<SystemLogDetailResponse>(
    PORT1 + `/log/read/${id}`,  // 补充完整PORT1路径
    {},
    { loading: true }
  );
};

/**
 * 批量删除
 * @param params 删除参数
 */
export const batchDeleteDeleteApi = (params: { ids: number[] }) => {
  return http.batchDelete(PORT1 + "/log/batch_delete", {  // 补充完整PORT1路径
    data: params,
  });
};

/**
 * 删除单个日志
 * @param id 日志ID
 */
export const deleteApi = (id: number) => {
  return http.delete(PORT1 + `/log/delete/${id}`, {
    loading: true
  });
};
