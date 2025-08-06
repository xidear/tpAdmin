import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";
import { Region } from "@/typings/region";

// 获取地区树形结构
export const getTreeApi = (params?: any) => {
  return http.get<Region.RegionItem[]>(PORT1 + `/region/tree`, params, { loading: true });
};

// 获取单个地区详情
export const getReadApi = (region_id: number) => {
  return http.get<Region.RegionItem>(`/region/read/${region_id}`, {}, { loading: true });
};

// 新增地区
export const postCreateApi = (params: Region.RegionForm) => {
  return http.post("/region/create", params, { loading: true });
};

// 更新地区
export const putUpdateApi = (region_id: number, params: Region.RegionForm) => {
  return http.put(`/region/update/${region_id}`, params, { loading: true });
};

// 删除地区
export const deleteDeleteApi = (region_id: number) => {
  return http.delete(`/region/delete/${region_id}`, { loading: true });
};

// 恢复地区
export const postRestoreApi = (region_id: number) => {
  return http.post(`/region/restore/${region_id}`, {}, { loading: true });
};

// 合并地区
export const postMergeApi = (params: Region.MergeParams) => {
  return http.post("/region/merge", params, { loading: true });
};

// 拆分地区
export const postSplitApi = (params: Region.SplitParams) => {
  return http.post("/region/split", params, { loading: true });
};

// 获取子地区列表
export const getChildrenApi = (parent_id: number) => {
  return http.get<Region.RegionItem[]>(`/region/children/${parent_id}`, {}, { loading: true });
};
