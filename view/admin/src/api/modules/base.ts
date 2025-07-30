import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";



import { ResultData} from "@/api/interface";

// 修改密码接口的请求参数类型
export interface ChangePasswordParams {
  old_password:string;
  password: string;
  password_confirm: string;
}


// 管理员详情数据结构（完全匹配后端返回）
export interface BaseInfo {
  admin_id: number;
  username: string;
  created_at: string;
  updated_at: string;
  real_name: string;
  nick_name: string;
  status: number;
  avatar: string;
  deleted_at: string | null; // 可能为null
  is_super: number;
  role_name_list: string[]; // 角色列表数组
}


/**
 * 获取详情
 * @param id ID
 */
export const getBaseApi = (id: number) => {
  return http.get<ResultData<BaseInfo>>(
    `/base`,
    {},
    { loading: true }
  );
};
/**
 * 修改密码
 * @param data 密码修改参数
 */
export const changePassword = (data: ChangePasswordParams) => {
  return http.post<ResultData>(
    `/change_password`,
    data,
    { loading: true }
  );
};
