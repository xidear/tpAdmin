import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";
import type {
  TaskItem,
  TaskOptions,
  TaskDetailResponse,
  TaskLogItem,
  TaskLogListResponse,
  OptionItem,
  BaseResponse
} from "@/typings/task";

// 任务类型枚举
export enum TaskType {
  COMMAND = 1,      // 命令行任务
  URL = 2,          // URL请求任务
  PHP_METHOD = 3    // PHP方法调用任务
}

// 运行平台枚举
export enum TaskPlatform {
  ALL = 0,          // 所有平台
  LINUX = 1,        // Linux平台
  WINDOWS = 2       // Windows平台
}

// 任务状态枚举
export enum TaskStatus {
  DISABLED = 0,     // 禁用
  ENABLED = 1       // 启用
}

// 日志状态枚举
export enum LogStatus {
  SUCCESS = 1,      // 成功
  FAILED = 2,       // 失败
  TIMEOUT = 3,      // 超时
  CANCELED = 4      // 取消
}

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
 * @param id 任务ID
 * @param logParams 日志分页参数
 * @returns 任务详情响应
 */
export const getTaskReadApi = (id: number, logParams?: { log_page?: number; log_limit?: number }) => {
  return http.get<TaskDetailResponse>(
    PORT1 + `/task/read/${id}`,
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
 * @param id 任务ID
 * @param data 任务数据
 * @returns 响应结果
 */
export const putTaskUpdateApi = (id: number, data: TaskOptions) => {
  return http.put<BaseResponse>(
    PORT1 + `/task/update/${id}`,
    data,
    { loading: true }
  );
};

/**
 * 删除单个任务
 * @param id 任务ID
 * @returns 响应结果
 */
export const deleteTaskApi = (id: number) => {
  return http.delete<BaseResponse>(
    PORT1 + `/task/delete/${id}`,
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
 * @param id 任务ID
 * @returns 响应结果
 */
export const toggleTaskStatusApi = (id: number) => {
  return http.post<BaseResponse>(
    PORT1 + `/task/toggle_status/${id}`,
    {},
    { loading: true }
  );
};

/**
 * 立即执行任务
 * @param id 任务ID
 * @returns 响应结果
 */
export const executeTaskNowApi = (id: number) => {
  return http.post<BaseResponse>(
    PORT1 + `/task/execute_now/${id}`,
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
