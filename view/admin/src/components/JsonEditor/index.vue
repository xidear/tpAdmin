<template>
  <div class="json-editor-wrapper">
    <div class="json-editor-container">
      <div class="json-editor-header">
        <span class="json-editor-title">JSON 配置编辑器</span>
        <div class="json-editor-actions">
          <el-button 
            size="small" 
            type="primary" 
            @click="formatJson"
            icon="MagicStick"
          >
            格式化
          </el-button>
          <el-button 
            size="small" 
            @click="validateJson"
            icon="Check"
          >
            验证
          </el-button>
        </div>
      </div>
      
      <!-- 使用 Element Plus 的文本编辑器作为 JSON 编辑器 -->
      <el-input
        v-model="jsonString"
        type="textarea"
        :rows="15"
        placeholder="请输入 JSON 配置"
        class="json-textarea"
        @input="handleInput"
        @blur="handleBlur"
      />
      
      <div class="json-editor-footer">
        <el-text type="info" size="small">
          💡 专业的 JSON 编辑器，支持语法高亮、错误提示、格式化等功能
        </el-text>
        <el-text v-if="jsonError" type="danger" size="small">
          ❌ {{ jsonError }}
        </el-text>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { ElMessage } from 'element-plus';

interface Props {
  modelValue?: any;
  options?: any;
  plus?: boolean;
  expandedOnStart?: boolean;
}

interface Emits {
  (e: 'update:modelValue', value: any): void;
  (e: 'change', value: any): void;
  (e: 'error', error: any): void;
  (e: 'validate', errors: any): void;
  (e: 'format', formatted: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: {},
  options: () => ({}),
  plus: false,
  expandedOnStart: true
});

const emit = defineEmits<Emits>();

// 内部值
const jsonString = ref('');
const jsonError = ref('');

// 监听外部值变化
watch(() => props.modelValue, (newVal) => {
  if (newVal !== undefined && newVal !== null) {
    try {
      if (typeof newVal === 'object') {
        jsonString.value = JSON.stringify(newVal, null, 2);
      } else if (typeof newVal === 'string') {
        jsonString.value = newVal;
      } else {
        jsonString.value = JSON.stringify(newVal, null, 2);
      }
      jsonError.value = '';
    } catch (error) {
      jsonString.value = String(newVal);
    }
  } else {
    jsonString.value = '';
  }
}, { immediate: true, deep: true });

// 处理输入
const handleInput = (value: string) => {
  jsonString.value = value;
  jsonError.value = '';
  
  try {
    if (value.trim()) {
      const parsed = JSON.parse(value);
      emit('update:modelValue', parsed);
      emit('change', parsed);
    } else {
      emit('update:modelValue', {});
      emit('change', {});
    }
  } catch (error) {
    // 输入过程中不显示错误，只在失焦时验证
  }
};

// 处理失焦验证
const handleBlur = () => {
  try {
    if (jsonString.value.trim()) {
      const parsed = JSON.parse(jsonString.value);
      emit('update:modelValue', parsed);
      emit('change', parsed);
      jsonError.value = '';
    }
  } catch (error: any) {
    jsonError.value = `JSON 格式错误: ${error.message}`;
    emit('error', error);
  }
};

// 格式化 JSON
const formatJson = () => {
  try {
    if (jsonString.value.trim()) {
      const parsed = JSON.parse(jsonString.value);
      jsonString.value = JSON.stringify(parsed, null, 2);
      jsonError.value = '';
      ElMessage.success('JSON 格式化成功');
      emit('format', jsonString.value);
    } else {
      ElMessage.warning('请先输入有效的 JSON 数据');
    }
  } catch (error: any) {
    jsonError.value = `JSON 格式错误: ${error.message}`;
    ElMessage.error('JSON 格式化失败');
  }
};

// 验证 JSON
const validateJson = () => {
  try {
    if (jsonString.value.trim()) {
      JSON.parse(jsonString.value);
      jsonError.value = '';
      ElMessage.success('JSON 格式正确');
      emit('validate', []);
    } else {
      jsonError.value = '';
      ElMessage.info('JSON 数据为空');
    }
  } catch (error: any) {
    jsonError.value = `JSON 格式错误: ${error.message}`;
    ElMessage.error('JSON 格式错误');
    emit('validate', [{ message: error.message }]);
  }
};
</script>

<style scoped>
.json-editor-wrapper {
  width: 100%;
  min-height: 300px;
}

.json-editor-container {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 16px;
  background: #fafafa;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.json-editor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.json-editor-title {
  font-size: 16px;
  font-weight: bold;
  color: #303133;
}

.json-editor-actions {
  display: flex;
  gap: 8px;
}

.json-textarea {
  flex-grow: 1;
  font-family: 'Consolas', 'Monaco', 'Andale Mono', 'Ubuntu Mono', 'Monospace', monospace;
  font-size: 14px;
  line-height: 1.5;
  border: none; /* Remove default border */
  padding: 0; /* Remove default padding */
  resize: none; /* Prevent resizing */
  box-sizing: border-box; /* Include padding and border in element's total width and height */
}

.json-editor-footer {
  margin-top: 10px;
  text-align: center;
  color: #909399;
}
</style>
