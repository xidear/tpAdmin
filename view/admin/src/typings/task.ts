/**
 * 基础响应结构
 */
export interface BaseResponse<T = any> {
  code: number;
  msg: string;
  data: T;
}

/**
 * 选项项结构
 */
export interface OptionItem {
  label: string;
  value: number;
}

/**
 * 任务项结构
 */
export interface TaskItem {
  id: number;
  name: string;
  description: string | null;
  type: number;
  content: string;
  schedule: string;
  status: number;
  platform: number;
  exec_user: string | null;
  timeout: number;
  retry: number;
  interval: number;
  last_exec_time: string | null;
  next_exec_time: string | null;
  sort: number;
  created_at: string;
  updated_at: string;
}

/**
 * 任务表单选项结构
 */
export interface TaskOptions {
  name: string;
  description: string;
  type: number;
  content: string;
  schedule: string;
  status: number;
  platform: number;
  exec_user: string;
  timeout: number;
  retry: number;
  interval: number;
  sort: number;
}

/**
 * 任务日志项结构
 */
export interface TaskLogItem {
  id: number;
  task_id: number;
  task_name: string;
  start_time: string;
  end_time: string | null;
  duration: number;
  status: number;
  output: string | null;
  error: string | null;
  pid: number | null;
  server_ip: string | null;
  created_at: string;
}

/**
 * 任务日志列表响应结构
 */
export interface TaskLogListResponse {
  list: TaskLogItem[];
  total: number;
  page: number;
  limit: number;
  pages: number;
}

/**
 * 任务详情响应结构
 */
export interface TaskDetailResponse extends BaseResponse {
  data: {
    task: TaskItem;
    logs: TaskLogListResponse;
  };
}
