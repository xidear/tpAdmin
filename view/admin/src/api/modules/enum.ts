import {PORT1} from "@/api/config/servicePort";
import http from "@/api";
import type { EnumDict, EnumItem} from "@/typings/enum";
import type{BaseResponse} from "@/typings/global";
// 全局枚举缓存（内存缓存，避免重复请求）
let enumCache: EnumDict = {};

/**
 * 获取所有可用的枚举名称列表（后端自动扫描的枚举类名）
 * @returns 枚举名称数组（如["ConfigType", "AdminStatus"]）
 */
export const getEnumNameListApi = async () => {
  const res = await http.get<BaseResponse<string[]>>(
    PORT1 + "/enum/index", // 对应路由enum/index
    {},
    { loading: false }
  );
  return res.data || [];
};

/**
 * 获取指定枚举的完整数据
 * @param enumName 枚举名称（如"ConfigType"）
 * @param forceRefresh 是否强制刷新缓存（默认false，优先读缓存）
 * @returns 枚举项数组（[{label, value}, ...]）
 */
export const getEnumDataApi = async (enumName: string, forceRefresh = false): Promise<EnumItem[]> => {
  // 优先从缓存获取
  if (!forceRefresh && enumCache[enumName]) {
    return [...enumCache[enumName]]; // 返回副本，避免缓存被意外修改
  }

  // 缓存不存在或强制刷新时，请求接口
  const res = await http.get<BaseResponse<EnumItem[]>>(
    PORT1 + `/enum/read/${enumName}`, // 对应路由enum/read/:enum_code
    {},
    { loading: false }
  );

  // 更新缓存并返回
  if (Array.isArray(res.data)&&res.data) {
    enumCache[enumName] = res.data;
    return res.data;
  }

  // 接口异常时返回空数组
  return [];
};

/**
 * 批量获取多个枚举数据
 * @param enumNames 枚举名称数组（如["ConfigType", "AdminStatus"]）
 * @returns 枚举字典（{枚举名称: 枚举项数组, ...}）
 */
export const getBatchEnumDataApi = async (enumNames: string[]): Promise<EnumDict> => {
  const result: EnumDict = {};
  const needFetch: string[] = [];

  // 先从缓存获取已有数据，收集需要请求的枚举
  enumNames.forEach(name => {
    if (enumCache[name]) {
      result[name] = enumCache[name];
    } else {
      needFetch.push(name);
    }
  });

  // 批量请求未缓存的枚举
  if (needFetch.length > 0) {
    for (const name of needFetch) {
      result[name] = await getEnumDataApi(name);
    }
  }

  return result;
};

/**
 * 清除指定枚举的缓存（可选）
 * @param enumName 枚举名称（不传则清除所有缓存）
 */
export const clearEnumCache = (enumName?: string) => {
  if (enumName) {
    delete enumCache[enumName];
  } else {
    enumCache = {};
  }
};

/**
 * 根据枚举值获取对应的显示文本（工具函数）
 * @param enumName 枚举名称
 * @param value 枚举值
 * @param defaultValue 默认文本（值不存在时使用）
 * @returns 显示文本
 */
export const getEnumLabelByValue = (
  enumName: string,
  value: number | string,
  defaultValue = "未知"
): string => {
  const enumItems = enumCache[enumName] || [];
  const item = enumItems.find(item => item.value === value);
  return item?.label || defaultValue;
};
