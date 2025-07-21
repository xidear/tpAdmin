/* Permission */
declare namespace Permission {
  interface PermissionOptions {
    permission_id: number;
    node: string;
    name: string;
    description: string;
    method: 'get' | 'post' | 'put' | 'delete';
    is_public: 1 | 2;
    created_at: string;
    updated_at: string;
  }

  interface PermissionListResponse {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    data: PermissionOptions[];
  }
}
