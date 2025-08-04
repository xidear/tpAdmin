<template>
  <div class="footer flx-center">
    <!-- 使用从 Store 获取的公司名称和网站地址 -->
    <a   :href="formatUrl(systemStore.site_url)"  target="_blank">{{ systemStore.company_name||" " }}</a>
  </div>
</template>

<script setup lang="ts">
// 导入系统信息 Store
import { useSystemStore } from "@/stores/modules/system";

// 初始化 Store 实例
const systemStore = useSystemStore();



/**
 * 格式化 URL，确保包含协议
 * @param url 原始 URL
 * @returns 补全协议后的 URL
 */
const formatUrl = (url?: string) => {
  if (!url) return "";
  // 检查是否已包含协议（http:// 或 https://）
  if (!/^https?:\/\//i.test(url)) {
    return `https://${url}`; // 补全 https 协议（或根据需求用 http）
  }
  return url;
};



</script>

<style scoped lang="scss">
@import "./index.scss";
</style>
