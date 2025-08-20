import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// * 获取部门树形结构
export const getDepartmentTreeApi = (params?: any) => {
  return http.get(PORT1 + `/department/index`, params);
};

// * 获取部门平铺列表
export const getDepartmentListApi = (params?: any) => {
  return http.get(PORT1 + `/department/list`, params);
};

// * 获取部门详情
export const getDepartmentDetailApi = (id: number) => {
  return http.get(PORT1 + `/department/read/${id}`);
};

// * 创建部门
export const postDepartmentCreateApi = (data: any) => {
  return http.post(PORT1 + `/department/create`, data);
};

// * 更新部门
export const putDepartmentUpdateApi = (id: number, data: any) => {
  return http.put(PORT1 + `/department/update/${id}`, data);
};

// * 删除部门
export const deleteDepartmentApi = (id: number) => {
  return http.delete(PORT1 + `/department/delete/${id}`);
};

// * 批量删除部门
export const postDepartmentBatchDeleteApi = (ids: number[]) => {
  return http.batchDelete(PORT1 + `/department/batch-delete`, { data: { ids } });
};

// * 更新部门状态
export const putDepartmentUpdateStatusApi = (id: number, status: number) => {
  return http.put(PORT1 + `/department/update-status/${id}`, { status });
};

// * 导出部门数据
export const getDepartmentExportApi = (params?: any) => {
  return http.download(PORT1 + `/department/export`, params);
};

// * 获取部门职位列表
export const getDepartmentPositionsApi = (departmentId: number) => {
  return http.get(PORT1 + `/department/positions/${departmentId}`);
};

// * 创建职位
export const postPositionCreateApi = (data: any) => {
  return http.post(PORT1 + `/department/position/create`, data);
};

// * 更新职位
export const putPositionUpdateApi = (id: number, data: any) => {
  return http.put(PORT1 + `/department/position/update/${id}`, data);
};

// * 删除职位
export const deletePositionApi = (id: number) => {
  return http.delete(PORT1 + `/department/position/delete/${id}`);
};

// 类型定义
export interface Department {
  department_id: number
  name: string
  code?: string
  parent_id: number | null
  level: number
  path?: string
  sort: number
  status: number
  description?: string
  leader_id?: number
  created_at: string
  updated_at: string
  deleted_at?: string
  created_by?: number
  updated_by?: number
  created_type?: string
  children?: Department[]
  parent?: Department
  leader?: any
}

export interface DepartmentPosition {
  position_id: number
  department_id: number
  name: string
  code?: string
  description?: string
  sort: number
  status: number
  created_at: string
  updated_at: string
  deleted_at?: string
  created_by?: number
  updated_by?: number
  created_type?: string
}

export interface CreateDepartmentRequest {
  name: string
  code?: string
  parent_id: number | null
  sort?: number
  status: number
  description?: string
}

export interface UpdateDepartmentRequest extends CreateDepartmentRequest {}

export interface CreatePositionRequest {
  department_id: number
  name: string
  code?: string
  sort?: number
  status: number
  description?: string
}

export interface UpdatePositionRequest extends CreatePositionRequest {}
