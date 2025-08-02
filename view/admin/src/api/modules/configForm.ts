import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";
import type { ConfigForm } from "@/typings/configForm";

/**
 * 获取配置表单数据（分组及字段）
 * @returns 配置表单结构
 */
export const getConfigFormApi = () => {
  return http.get<ConfigForm.ConfigFormResponse>(
    PORT1 + "/config_form/index",
    {},
    { loading: true }
  );
};

/**
 * 按分组批量保存配置
 * @param params 保存参数（分组ID + 字段键值对）
 * @returns 保存结果
 */
export const saveConfigFormApi = (params: ConfigForm.SaveConfigParams) => {
  console.log("发送的数据",params);
  return http.post(
    PORT1 + "/config_form/save",
    params,
    { loading: true }
  );
};

/**
 * 刷新配置缓存
 * @returns 刷新结果
 */
export const refreshConfigCacheApi = () => {
  return http.post(
    PORT1 + "/config_form/refresh_cache",
    {},
    { loading: true }
  );
};
