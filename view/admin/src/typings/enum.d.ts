
/**
 * 单个枚举项结构（前端通用的下拉/选择器选项格式）
 * label: 显示文本（对应后端的value）
 * value: 实际值（对应后端的key）
 */
export interface EnumItem {
  label: string;
  value: number | string; // 兼容数字/字符串类型的枚举值
}

/**
 * 枚举字典结构（键为枚举名称，值为枚举项数组）
 * 用于全局缓存枚举数据，例如：
 * {
 *   "ConfigType": [{label: '文本输入', value: 1}, ...],
 *   "AdminStatus": [{label: '正常', value: 1}, ...]
 * }
 */
export interface EnumDict {
  [enumName: string]: EnumItem[];
}
