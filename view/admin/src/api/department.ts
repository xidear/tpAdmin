import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// 部门相关接口
export const departmentApi = {
  // 获取部门树形结构
  getTree: (params?: any) => http.get(PORT1 + `/department/index`, params),
  
  // 获取部门平铺列表
  getList: (params?: any) => http.get(PORT1 + `/department/list`, params),
  
  // 获取部门详情
  getDetail: (id: number) => http.get(PORT1 + `/department/read/${id}`),
  
  // 创建部门
  create: (data: any) => http.post(PORT1 + `/department/create`, data),
  
  // 更新部门
  update: (id: number, data: any) => http.put(PORT1 + `/department/update/${id}`, data),
  
  // 删除部门
  delete: (id: number) => http.delete(PORT1 + `/department/delete/${id}`),
  
  // 批量删除部门
  batchDelete: (ids: number[]) => http.batchDelete(PORT1 + `/department/batch-delete`, { data: { ids } }),
  
  // 更新部门状态
  updateStatus: (id: number, status: number) => http.put(PORT1 + `/department/update-status/${id}`, { status }),
  
  // 导出部门数据
  export: (params?: any) => http.download(PORT1 + `/department/export`, params),
  
  // 获取部门职位列表
  getPositions: (departmentId: number) => http.get(PORT1 + `/department/positions/${departmentId}`),
  
  // 创建职位
  createPosition: (data: any) => http.post(PORT1 + `/department/position/create`, data),
  
  // 更新职位
  updatePosition: (id: number, data: any) => http.put(PORT1 + `/department/position/update/${id}`, data),
  
  // 删除职位
  deletePosition: (id: number) => http.delete(PORT1 + `/department/position/delete/${id}`)
}

// 导出单个方法，方便使用
export const {
  getTree: getDepartmentTree,
  getList: getDepartmentList,
  getDetail: getDepartmentDetail,
  create: createDepartment,
  update: updateDepartment,
  delete: deleteDepartment,
  batchDelete: batchDeleteDepartment,
  updateStatus: updateDepartmentStatus,
  export: exportDepartment,
  getPositions: getDepartmentPositions,
  createPosition,
  updatePosition,
  deletePosition
} = departmentApi

// 类型定义
export interface Department {
  department_id: number
  name: string
  code?: string
  parent_id: number
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
  parent_id: number
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
