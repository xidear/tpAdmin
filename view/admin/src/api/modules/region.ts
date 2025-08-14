import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";

// * 获取地区树（支持层级参数）
export const getTreeApi = (params?: { level?: number; force_refresh?: boolean }) => {
  return http.get(PORT1 + `/region/tree`, params);
};



// * 刷新缓存
export const refreshCacheApi = () => {
  return http.get(PORT1 + `/region/refresh_cache`);
};

// * 获取地区子列表
export const getChildrenApi = (parentId: number) => {
  return http.get(PORT1 + `/region/children/${parentId}`);
};

// * 获取地区详情
export const getRegionDetailApi = (regionId: number) => {
  return http.get(PORT1 + `/region/read/${regionId}`);
};

// * 新增地区
export const addRegionApi = (params: any) => {
  return http.post(PORT1 + `/region/create`, params);
};

// * 编辑地区
export const editRegionApi = (params: any) => {
  return http.put(PORT1 + `/region/update/${params.region_id}`, params);
};

// * 删除地区
export const deleteRegionApi = (regionId: number) => {
  return http.delete(PORT1 + `/region/delete/${regionId}`);
};

// * 恢复地区
export const restoreRegionApi = (regionId: number) => {
  return http.post(PORT1 + `/region/restore/${regionId}`);
};


