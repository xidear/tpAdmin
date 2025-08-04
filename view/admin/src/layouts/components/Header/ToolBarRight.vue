<template>
  <div class="tool-bar-ri">
    <div class="header-icon">
      <AssemblySize id="assemblySize" />
      <Language id="language" />
      <SearchMenu id="searchMenu" />
      <ThemeSetting id="themeSetting" />
      <Message id="message" />
      <Fullscreen id="fullscreen" />
    </div>
    <span class="username">{{ username }}</span>
    <Avatar :src="avatar" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useUserStore } from "@/stores/modules/user";
import {AdminInfo, getBaseApi} from "@/api/modules/base";
import AssemblySize from "./components/AssemblySize.vue";
import Language from "./components/Language.vue";
import SearchMenu from "./components/SearchMenu.vue";
import ThemeSetting from "./components/ThemeSetting.vue";
import Message from "./components/Message.vue";
import Fullscreen from "./components/Fullscreen.vue";
import Avatar from "./components/Avatar.vue";
import {UserState} from "@/stores/interface";
import {useSystemStore} from "@/stores/modules/system";

const userStore = useUserStore();

// 从store中获取用户信息，使用nick_name或username
const username = computed(() => {
  // 优先显示nick_name，如果没有则显示username
  return userStore.userInfo.nick_name || userStore.userInfo.username || '未知用户';
});
const avatar = computed(() => userStore.userInfo.avatar);
const systemStore = useSystemStore();
// 组件挂载时获取用户基本信息
onMounted(async () => {
  try {
    const response = await getBaseApi();
    if ( response.data) {
      userStore.setUserInfo(response.data.admin);

      systemStore.setSystemInfo(response.data.system);


    }
  } catch (error) {
    console.error("获取用户信息失败:", error);
  }
});
</script>

<style scoped lang="scss">
.tool-bar-ri {
  display: flex;
  align-items: center;
  justify-content: center;
  padding-right: 25px;
  .header-icon {
    display: flex;
    align-items: center;
    & > * {
      margin-left: 21px;
      color: var(--el-header-text-color);
    }
  }
  .username {
    margin: 0 20px;
    font-size: 15px;
    color: var(--el-header-text-color);
  }
}
</style>
