import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";
import { ResultData } from "@/api/interface";

// 修改密码接口的请求参数类型
export interface ChangePasswordParams {
  old_password: string;
  password: string;
  password_confirm: string;
}

// 拆分管理员信息为独立接口（便于维护和类型匹配）


export interface AdminInfo {
  admin_id: number
  username: string
  created_at: string
  updated_at: string
  real_name: string
  nick_name: string
  status: number
  avatar: string
  deleted_at: null | string; // 精确匹配返回的null类型
  is_super: number;
  role_name_list: []; // 明确是空数组而非any[]
}




// 系统信息独立接口
export interface SystemInfo {
  site_log: string;
  site_name: string;
  admin_logo: string;
  phone: string;
  company_name: string;
  site_url: string;
  icp: string;
}

// 修正BaseInfo结构（使用对象字面量而非嵌套接口）
export interface BaseInfo {
  admin: AdminInfo; // 明确admin字段类型为AdminInfo
  system: SystemInfo; // 明确system字段类型为SystemInfo
}

/**
 * 获取详情
 */
export const getBaseApi = () => {
  return http.get<ResultData<BaseInfo>>(
    `/base`,
    {},
    { loading: true }
  );
};

/**
 * 修改密码
 */
export const changePassword = (data: ChangePasswordParams) => {
  return http.post<ResultData>(
    `/change_password`,
    data,
    { loading: true }
  );
};
