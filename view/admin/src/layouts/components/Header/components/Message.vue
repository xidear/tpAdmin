<template>
  <div class="message">
    <el-popover placement="bottom" :width="310" trigger="click">
      <template #reference>
        <el-badge :value="unreadCount" class="item" :hidden="unreadCount === 0">
          <i class="iconfont icon-xiaoxi toolBar-icon"></i>
        </el-badge>
      </template>

      <el-tabs v-model="activeName">
        <!-- 通知 -->
        <el-tab-pane :label="`通知(${notifications.length})`" name="first">
          <div class="message-list">
            <div
              v-for="(msg, idx) in notifications"
              :key="idx"
              class="message-item"
            >
              <img :src="getIcon(msg.data.msgType)" class="message-icon" />
              <div class="message-content">
                <span class="message-title">{{ msg.data.title }}</span>
                <span class="message-date">{{ msg.data.date }}</span>
              </div>
            </div>
            <div v-if="notifications.length === 0" class="message-empty">
              <img src="@/assets/images/notData.png" alt="notData" />
              <div>暂无通知</div>
            </div>
          </div>
        </el-tab-pane>

        <!-- 消息 -->
        <el-tab-pane :label="`消息(${messages.length})`" name="second">
          <div class="message-list">
            <div
              v-for="(msg, idx) in messages"
              :key="idx"
              class="message-item"
            >
              <img :src="getIcon(msg.data.msgType)" class="message-icon" />
              <div class="message-content">
                <span class="message-title">{{ msg.data.title }}</span>
                <span class="message-date">{{ msg.data.date }}</span>
              </div>
            </div>
            <div v-if="messages.length === 0" class="message-empty">
              <img src="@/assets/images/notData.png" alt="notData" />
              <div>暂无消息</div>
            </div>
          </div>
        </el-tab-pane>

        <!-- 待办 -->
        <el-tab-pane :label="`待办(${todos.length})`" name="third">
          <div class="message-list">
            <div
              v-for="(msg, idx) in todos"
              :key="idx"
              class="message-item"
            >
              <img :src="getIcon(msg.data.msgType)" class="message-icon" />
              <div class="message-content">
                <span class="message-title">{{ msg.data.title }}</span>
                <span class="message-date">{{ msg.data.date }}</span>
              </div>
            </div>
            <div v-if="todos.length === 0" class="message-empty">
              <img src="@/assets/images/notData.png" alt="notData" />
              <div>暂无待办</div>
            </div>
          </div>
        </el-tab-pane>
      </el-tabs>
    </el-popover>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { wsService } from '@/utils/websocket'

import { useRoute } from 'vue-router'

const route = useRoute()

const isCustomerServicePage = () => {
  return route.path === '/customer-service' // 换成你实际的客服页面路径
}


const activeName = ref('first')
const notifications = ref<any[]>([])
const messages = ref<any[]>([])
const todos = ref<any[]>([])

const unreadCount = computed(
  () => notifications.value.length + messages.value.length + todos.value.length
)



const getIcon = (type: string) => {
  const map: Record<string, string> = {
    system: new URL('@/assets/images/msg01.png', import.meta.url).href,
    notice: new URL('@/assets/images/msg02.png', import.meta.url).href,
    task: new URL('@/assets/images/msg03.png', import.meta.url).href,
    customer: new URL('@/assets/images/msg04.png', import.meta.url).href,
    interactive: new URL('@/assets/images/msg05.png', import.meta.url).href,
  }
  return map[type] || map.default
}


const handleNewMessage = (data: any) => {

  // ✅ 客服页面忽略客户消息
  if ( isCustomerServicePage() &&
    data.type === 'chat' &&
    data.data?.msgType === 'customer'
  ) {
    return
  }



  switch (data.type) {
    case 'message':
      notifications.value.unshift(data)
      break
    case 'chat':
      messages.value.unshift(data)
      break
    case 'todo':
      todos.value.unshift(data)
      break
  }
}

onMounted(() => wsService.on('*', handleNewMessage))
onUnmounted(() => wsService.off('*', handleNewMessage))
</script>

<style scoped lang="scss">
.message-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 260px;
  line-height: 45px;
}
.message-list {
  display: flex;
  flex-direction: column;
  max-height: 400px;
  overflow-y: auto;
  .message-item {
    display: flex;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid var(--el-border-color-light);
    &:last-child {
      border: none;
    }
    .message-icon {
      width: 40px;
      height: 40px;
      margin: 0 20px 0 5px;
    }
    .message-content {
      display: flex;
      flex-direction: column;
      .message-title {
        margin-bottom: 5px;
      }
      .message-date {
        font-size: 12px;
        color: var(--el-text-color-secondary);
      }
    }
  }
}
</style>
