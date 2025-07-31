import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";
import type { Config } from "@/typings/config";

/**
 * 获取配置项列表
 * @param params 查询参数
 * @returns 配置项列表数据
 */
export const getConfigListApi = (params?: any) => {
  return http.get<Config.ConfigListResponse>(
    PORT1 + `/config/index`,
    params,
    { loading: true }
  );
};


/**
 * 新增配置项
 * @param params 配置项数据
 * @returns 新增结果
 */
export const createConfigApi = (params: Config.ConfigFormData) => {
  return http.post(
    PORT1 + `/config/create`,
    params,
    { loading: true }
  );
};

/**
 * 获取配置项详情
 * @param system_config_id 配置项ID
 * @returns 配置项详情
 */
export const getConfigDetailApi = (system_config_id: number) => {
  return http.get<Config.ConfigOptions>(
    PORT1 + `/config/read/${system_config_id}`,
    {},
    { loading: true }
  );
};

/**
 * 更新配置项
 * @param system_config_id 配置项ID
 * @param params 更新数据
 * @returns 更新结果
 */
export const updateConfigApi = (
  system_config_id: number,
  params: Partial<Config.ConfigFormData>
) => {
  return http.put(
    PORT1 + `/config/update/${system_config_id}`,
    params,
    { loading: true }
  );
};

/**
 * 删除配置项
 * @param system_config_id 配置项ID
 * @returns 删除结果
 */
export const deleteConfigApi = (system_config_id: number) => {
  return http.delete(
    PORT1 + `/config/delete/${system_config_id}`,
    { loading: true }
  );
};
