import { defineStore } from "pinia";
import { UserState } from "@/stores/interface";
import piniaPersistConfig from "@/stores/helper/persist";
import {AdminInfo} from "@/api/modules/base";

export const useUserStore = defineStore({
  id: "tp_admin-admin",
  state: (): UserState => ({
    token: "",
    // 初始值结构与更新后的UserState保持一致，去掉name，补充必要字段的默认值
    userInfo: {
      admin_id: 0,
      username: "",
      created_at: "",
      updated_at: "",
      real_name: "",
      nick_name: "TpAdmin", // 用nick_name替代原来的name作为默认显示名
      status: 0,
      avatar: "",
      deleted_at: null,
      is_super: 0,
      role_name_list: []
    }
  }),
  getters: {},
  actions: {
    // Set Token
    setToken(token: string) {
      this.token = token;
    },
    setUserInfo(userInfo: AdminInfo) {
      this.userInfo = userInfo;
    },


  },
  persist: piniaPersistConfig("tp_admin_admin")
});
