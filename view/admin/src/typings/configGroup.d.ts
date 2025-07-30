export declare namespace ConfigGroup {
  interface ConfigGroupOptions {
    system_config_group_id: number;
    group_name: string;
    created_by?: number;
    created_at?: string;
    updated_by?: number;
    updated_at?: string;
    sort: number;
  }

  interface ConfigGroupListResponse {
    total: number;
    per_page: number;
    current_page: number;
    list: ConfigGroupOptions[];
  }
}

export default {ConfigGroup,ConfigGroupListResponse,ConfigGroupOptions};
