import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";
import type { ConfigGroup } from "@/typings/configGroup";

// 获取配置分组列表
export const getListApi = (params?: any) => {
  return http.get<ConfigGroup.ConfigGroupListResponse>(
    PORT1 + `/config_group/index`,
    params,
    { loading: true }
  );
};

/**
 * 新增配置分组
 * @param params 配置分组数据
 */
export const postCreateApi = (params: Partial<ConfigGroup.ConfigGroupOptions>) => {
  return http.post(PORT1 + `/config_group/create`, params, { loading: true });
};

/**
 * 获取配置分组详情
 * @param group_id 配置分组ID
 */
export const getReadApi = (group_id: number) => {
  return http.get<ConfigGroup.ConfigGroupOptions>(
    PORT1 + `/config_group/read/${group_id}`,
    {},
    { loading: true }
  );
};

/**
 * 更新配置分组
 * @param group_id 配置分组ID
 * @param params 更新数据
 */
export const putUpdateApi = (
  group_id: number,
  params: Partial<ConfigGroup.ConfigGroupOptions>
) => {
  return http.put(PORT1 + `/config_group/update/${group_id}`, params, { loading: true });
};


/**
 * 删除配置分组
 * @param group_id 配置分组ID
 */
export const deleteDeleteApi = (group_id: number) => {
  return http.delete(PORT1 + `/config_group/delete/${group_id}`, { loading: true });
};

