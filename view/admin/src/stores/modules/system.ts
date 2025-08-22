import { defineStore } from 'pinia';
// 导入默认Logo，让构建工具提前解析@路径
import defaultLogo from '@/assets/images/logo.svg';

// 与后端字段完全对齐：使用下划线命名
export interface SystemState {
  site_name: string;
  admin_logo: string |null;
  phone: string;
  company_name: string
  company_url?: string;
  icp: string;
}

// 初始化状态（字段名与接口一致）
const defaultState: SystemState = {
  site_name: '',
  admin_logo: null,
  phone: '',
  company_name: '',
  company_url: '',
  icp: ''
};

export const useSystemStore = defineStore('system', {
  state: (): SystemState => ({ ...defaultState }),

  getters: {
    // 获取网站名称（用下划线字段）
    getSiteName: (state) => state.site_name || 'TpAdmin',
    // 获取管理员Logo，处理默认值
    // 这里使用导入的defaultLogo（已解析@路径），而非包含@的字符串
    getAdminLogo: (state) => state.admin_logo || defaultLogo
  },

  actions: {
    // 直接接收后端的system对象（下划线字段）
    setSystemInfo(info: Partial<SystemState>) {
      this.$patch(info);
    },
    resetSystemInfo() {
      this.$reset();
    }
  }
});
