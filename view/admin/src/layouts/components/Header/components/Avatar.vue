<template>
  <el-dropdown trigger="click">
    <div class="avatar">
      <!-- 使用处理后的头像地址 -->
      <img
        :src="resolvedAvatar"
        :alt="alt || '用户头像'"
      />
    </div>
    <template #dropdown>
      <el-dropdown-menu>
        <el-dropdown-item @click="handleOpenDialog('infoRef')">
          <el-icon><User /></el-icon>{{ $t("header.personalData") }}
        </el-dropdown-item>
        <el-dropdown-item @click="handleOpenDialog('passwordRef')">
          <el-icon><Edit /></el-icon>{{ $t("header.changePassword") }}
        </el-dropdown-item>
        <el-dropdown-item divided @click="logout">
          <el-icon><SwitchButton /></el-icon>{{ $t("header.logout") }}
        </el-dropdown-item>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
  <!-- infoDialog -->
  <InfoDialog ref="infoRef"></InfoDialog>
  <!-- passwordDialog -->
  <PasswordDialog ref="passwordRef"></PasswordDialog>
</template>

<script setup lang="ts">
// 修复：导入computed
import { ref, defineProps, watch, computed } from "vue";
import { LOGIN_URL } from "@/config";
import { useRouter } from "vue-router";
import { logoutApi } from "@/api/modules/login";
import { useUserStore } from "@/stores/modules/user";
import { ElMessageBox, ElMessage } from "element-plus";
import InfoDialog from "./InfoDialog.vue";
import PasswordDialog from "./PasswordDialog.vue";
import { User, Edit, SwitchButton } from "@element-plus/icons-vue";

// 定义组件接受的属性
const props = defineProps({
  // 头像图片地址
  src: {
    type: String,
    default: ""
  },
  // 图片alt属性
  alt: {
    type: String,
    default: "用户头像"
  }
});

// 默认头像地址
const defaultAvatar = "@/assets/images/avatar.gif";

const router = useRouter();
const userStore = useUserStore();

// 处理可能的相对路径头像地址
const resolvedAvatar = computed(() => {
  if (!props.src) return defaultAvatar;
  
  // 检查是否是完整URL，如果不是则拼接基础URL
  if (props.src.startsWith('http://') || props.src.startsWith('https://')) {
    return props.src;
  }
  
  // 这里可以根据实际情况调整基础URL
  return props.src;
});

// 退出登录
const logout = () => {
  ElMessageBox.confirm("您是否确认退出登录?", "温馨提示", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning"
  }).then(async () => {
    try {
      // 1.执行退出登录接口
      await logoutApi();

      // 2.清除 Token
      userStore.setToken("");
      
      // 3.清除用户信息
      userStore.setUserInfo({});

      // 4.重定向到登陆页
      router.replace(LOGIN_URL);
      ElMessage.success("退出登录成功！");
    } catch (error) {
      ElMessage.error("退出登录失败，请重试");
      console.error("退出登录错误:", error);
    }
  }).catch(() => {
    // 取消退出登录
    ElMessage.info("已取消退出登录");
  });
};

// 打开修改密码和个人信息弹窗
const infoRef = ref<InstanceType<typeof InfoDialog> | null>(null);
const passwordRef = ref<InstanceType<typeof PasswordDialog> | null>(null);

// 修复：重命名方法避免可能的命名冲突
const handleOpenDialog = (refName: string) => {
  if (refName === "infoRef" && infoRef.value) {
    infoRef.value.openDialog();
  }
  if (refName === "passwordRef" && passwordRef.value) {
    passwordRef.value.openDialog();
  }
};
</script>

<style scoped lang="scss">
.avatar {
  width: 40px;
  height: 40px;
  overflow: hidden;
  cursor: pointer;
  border-radius: 50%;
  img {
    width: 100%;
    height: 100%;
    object-fit: cover; // 确保图片正确缩放
  }
}
</style>
