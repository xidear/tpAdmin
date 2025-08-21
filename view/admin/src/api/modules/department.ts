import { PORT1 } from "@/api/config/servicePort"
import http from "@/api"

// 部门相关接口
export const getDepartmentTreeApi = (params?: any) => {
  return http.get(PORT1 + `/department/index`, params)
}

export const getDepartmentListApi = () => {
  return http.get(PORT1 + `/department/index`)
}

export const postDepartmentCreateApi = (params: any) => {
  return http.post(PORT1 + `/department/create`, params)
}

export const putDepartmentUpdateApi = (id: number, params: any) => {
  return http.put(PORT1 + `/department/update/${id}`, params)
}

export const deleteDepartmentApi = (id: number) => {
  return http.delete(PORT1 + `/department/delete/${id}`)
}

export const postDepartmentBatchDeleteApi = (ids: number[]) => {
  return http.post(PORT1 + `/department/batch-delete`, { ids })
}

export const putDepartmentUpdateStatusApi = (id: number, status: number) => {
  return http.put(PORT1 + `/department/update-status/${id}`, { status })
}

export const getDepartmentExportApi = (params?: any) => {
  return http.get(PORT1 + `/department/export`, params)
}

export const getDepartmentPositionsApi = (departmentId: number) => {
  return http.get(PORT1 + `/department/positions/${departmentId}`)
}

export const postPositionCreateApi = (params: any) => {
  return http.post(PORT1 + `/department/position/create`, params)
}

export const putPositionUpdateApi = (id: number, params: any) => {
  return http.put(PORT1 + `/department/position/update/${id}`, params)
}

export const deletePositionApi = (id: number) => {
  return http.delete(PORT1 + `/department/position/delete/${id}`)
}

// 类型定义
export interface Department {
  department_id: number
  name: string
  parent_id: number | null
  sort: number
  status: number
  admin_id: number | null
  created_at: string
  updated_at: string
  children?: Department[]
}

export interface DepartmentPosition {
  position_id: number
  department_id: number
  name: string
  sort: number
  status: number
  created_at: string
  updated_at: string
}
