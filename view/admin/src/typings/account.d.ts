/* Account */
declare namespace Admin {
  interface AdminOptions {
    admin_id: number;
    username: string;
    password?: string; // 创建时需要，编辑时可选
    password_confirm?: string; // 创建/编辑时需要
    real_name?: string;
    nick_name?: string;
    openid?: string; // 可能的微信登录标识
    type?: string; // 管理员类型
    status: 1 | 2; // 1=启用，2=禁用
    avatar?: string;
    created_at: string;
    updated_at: string;
  }

  interface AdminListResponse {
    total: number;
    per_page: number;
    current_page: number;
    list: AdminOptions[];
  }
}
