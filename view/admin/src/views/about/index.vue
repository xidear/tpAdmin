<template>
  <div>
    <div class="card mb10">
      <h4 class="title">简介</h4>
      <span class="text">
        <el-link type="primary" href="https://www.binnarui.com" target="_blank">tpAdmin</el-link>
        一款基于ThinkPhp8.1、Vue3.4、TypeScript、Vite5、Pinia、Element-Plus 开源的后台管理框架，使用目前最新技术栈开发。
      </span>
    </div>
    <div class="card mb10">
      <h4 class="title">项目信息</h4>
      <el-descriptions :column="2" border>
        <el-descriptions-item label="版本号" label-align="left">
          <el-tag>{{ version }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="发布时间" label-align="left">
          <el-tag>{{ lastBuildTime }}</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Gitee" label-align="left">
          <el-link type="primary" href="https://gitee.com/xidear/tp_admin" target="_blank"> Gitee </el-link>
        </el-descriptions-item>
        <el-descriptions-item label="Github" label-align="left">
          <el-link type="primary" href="https://github.com/xidear/tpAdmin" target="_blank"> Github </el-link>
        </el-descriptions-item>
      </el-descriptions>
    </div>
    <div class="card mb10">
      <h4 class="title">后端环境依赖</h4>
      <el-descriptions :column="2" border>
        <el-descriptions-item label="PHP版本" label-align="left">
          <el-tag type="success">≥ 8.3</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="MySQL版本" label-align="left">
          <el-tag type="success">≥ 8.0</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Web服务器" label-align="left">
          <el-tag type="info">Nginx / Apache</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="PHP扩展" label-align="left">
          <el-tag type="info">PDO、OpenSSL、Mbstring</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="URL重写" label-align="left">
          <el-tag type="warning">开启pathinfo</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Composer" label-align="left">
          <el-tag type="info">≥ 2.0</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Redis" label-align="left">
          <el-tag type="info">≥ 3.0</el-tag>
        </el-descriptions-item>
      </el-descriptions>
    </div>
    <div class="card mb10">
      <h4 class="title">前端生产环境依赖</h4>
      <el-descriptions :column="3" border>
        <el-descriptions-item v-for="(value, key) in dependencies" :key="key" width="400px" :label="key">
          <el-tag type="info">
            {{ value }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>
    </div>
    <div class="card">
      <h4 class="title">前端开发环境依赖</h4>
      <el-descriptions :column="3" border>
        <el-descriptions-item v-for="(value, key) in devDependencies" :key="key" width="400px" :label="key">
          <el-tag type="info">
            {{ value }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>
    </div>
  </div>
</template>

<script setup lang="ts" name="about">
const { pkg, lastBuildTime } = __APP_INFO__;
const { dependencies, devDependencies, version } = pkg;
</script>

<style lang="scss" scoped>
.card {
  .title {
    margin: 0 0 15px;
    font-size: 17px;
    font-weight: bold;
    color: var(--el-text-color-primary);
  }
  .text {
    font-size: 15px;
    line-height: 25px;
    color: var(--el-text-color-regular);
    .el-link {
      font-size: 15px;
    }
  }
}
</style>
