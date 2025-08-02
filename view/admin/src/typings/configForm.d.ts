import type { EnumItem } from "@/typings/enum";

/** 配置表单字段结构 */
export declare namespace ConfigForm {
  // 配置项字段详情
  interface ConfigField {
    key: string; // 字段标识
    label: string; // 字段名称
    type: number; // 字段类型（1:文本 3:数字 7:手机号 10:开关 20:文件上传）
    value: any[]; // 字段值（后端返回数组形式）
    options: EnumItem[]; // 下拉选项（如开关的开启/关闭）
    required: boolean; // 是否必填
    placeholder: string; // 占位提示
    rules: {
      label: string; // 验证类型（type/pattern）
      value: string; // 验证规则（如string/正则表达式）
      message: string; // 验证失败提示
    }[];
    accept?: string; // 文件上传接受类型
    multiple?: boolean; // 是否多文件上传
  }

  // 配置分组结构
  interface ConfigGroup {
    group_id: number; // 分组ID
    group_name: string; // 分组名称
    fields: ConfigField[]; // 分组下的字段列表
  }

  // 表单接口响应结构
  interface ConfigFormResponse {
    code: number;
    timestamp: number;
    msg: string;
    data: ConfigGroup[];
  }

  // 保存配置参数结构
  interface SaveConfigParams {
    group_id: number; // 分组ID
    fields: Record<string, any>; // 字段键值对（key: value）
  }
}

export default { ConfigForm };
