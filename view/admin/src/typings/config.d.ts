
import type {EnumItem} from "@/typings/enum";

export declare namespace Config {
  // 配置项基础信息
  interface ConfigOptions {
    system_config_id: number;
    config_key: string;
    config_value: string;
    config_name: string;
    config_type?: number|string; // 对应ConfigType枚举
    system_config_group_id: number;
    options?: EnumItem[];
    rules?: EnumItem[];
    sort: number;
    is_enabled: number; // 对应YesOrNo枚举
    remark?: string;
    created_by?: number;
    created_at?: string;
    updated_by?: number;
    updated_at?: string;
    is_system: string; // 由YesOrNo枚举控制，不硬编码限制

    // 关联数据
    config_group?: {
      system_config_group_id: number;
      group_name: string;
    };
  }

  // 配置项列表响应
  interface ConfigListResponse {
    total: number;
    per_page: number;
    current_page: number;
    list: ConfigOptions[];
  }

  // 配置项表单数据
  interface ConfigFormData {
    system_config_id?: number;
    config_key: string;
    config_value: string;
    config_name: string;
    config_type: number|string;
    system_config_group_id: number;
    options?: EnumItem[];
    rules?: EnumItem[];
    sort?: number;
    is_enabled: number|string; // 对应YesOrNo枚举
    is_system: number|string; // 对应YesOrNo枚举
    remark?: string;
  }
}

export default { Config };
