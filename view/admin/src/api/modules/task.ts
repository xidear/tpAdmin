import { PORT1 } from "@/api/config/servicePort";
import type{BaseResponse} from "@/typings/global";
import http from "@/api";
import type {
  TaskItem,
  TaskOptions,
  TaskDetailResponse,
  TaskLogItem,
  TaskLogListResponse,
  OptionItem
} from "@/typings/task";



/**
 * 获取任务列表
 * @param params 查询参数
 * @returns 任务列表响应
 */
export const getTaskListApi = (params?: any) => {
  return http.get<BaseResponse<{
    list: TaskItem[];
    total: number;
    page: number;
    limit: number;
    pages: number;
  }>>(
    PORT1 + `/task/index`,
    params,
    { loading: true }
  );
};

/**
 * 获取任务详情
 * @param task_id 任务ID
 * @param logParams 日志分页参数
 * @returns 任务详情响应
 */
export const getTaskReadApi = (task_id: number, logParams?: { log_page?: number; log_limit?: number }) => {
  return http.get<TaskDetailResponse>(
    PORT1 + `/task/read/${task_id}`,
    logParams || {},
    { loading: true }
  );
};

/**
 * 创建新任务
 * @param data 任务数据
 * @returns 响应结果
 */
export const postTaskCreateApi = (data: TaskOptions) => {
  return http.post<BaseResponse>(
    PORT1 + `/task/create`,
    data,
    { loading: true }
  );
};

/**
 * 更新任务
 * @param task_id 任务ID
 * @param data 任务数据
 * @returns 响应结果
 */
export const putTaskUpdateApi = (task_id: number, data: TaskOptions) => {
  return http.put<BaseResponse>(
    PORT1 + `/task/update/${task_id}`,
    data,
    { loading: true }
  );
};

/**
 * 删除单个任务
 * @param task_id 任务ID
 * @returns 响应结果
 */
export const deleteTaskApi = (task_id: number) => {
  return http.delete<BaseResponse>(
    PORT1 + `/task/delete/${task_id}`,
    { loading: true }
  );
};

/**
 * 批量删除任务
 * @param params 包含ID数组的参数
 * @returns 响应结果
 */
export const batchDeleteTaskApi = (params: { ids: number[] }) => {
  return http.batchDelete<BaseResponse>(
    PORT1 + "/task/batch_delete",
    { data: params }
  );
};

/**
 * 切换任务状态（启用/禁用）
 * @param task_id 任务ID
 * @returns 响应结果
 */
export const toggleTaskStatusApi = (task_id: number) => {
  return http.post<BaseResponse>(
    PORT1 + `/task/toggle_status/${task_id}`,
    {},
    { loading: true }
  );
};

/**
 * 立即执行任务
 * @param task_id 任务ID
 * @returns 响应结果
 */
export const executeTaskNowApi = (task_id: number) => {
  return http.post<BaseResponse>(
    PORT1 + `/task/execute_now/${task_id}`,
    {},
    { loading: true }
  );
};

/**
 * 获取任务类型选项
 * @returns 任务类型选项列表
 */
export const getTaskTypeOptionsApi = () => {
  return http.get<BaseResponse<OptionItem[]>>(
    PORT1 + `/task/get_type_options`,
    {},
    { loading: false }
  );
};

/**
 * 获取平台选项
 * @returns 平台选项列表
 */
export const getPlatformOptionsApi = () => {
  return http.get<BaseResponse<OptionItem[]>>(
    PORT1 + `/task/get_platform_options`,
    {},
    { loading: false }
  );
};

// 导出类型供外部使用
export type {
  TaskItem,
  TaskOptions,
  TaskDetailResponse,
  TaskLogItem,
  TaskLogListResponse,
  OptionItem
};
