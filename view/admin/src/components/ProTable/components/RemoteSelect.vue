<template>
  <el-select
    v-bind="$attrs"
    v-model="innerValue"
    :remote="true"
    :loading="loading"
    :filterable="true"
    :remote-method="handleRemote"
    :popper-class="'remote-select-dropdown'"
    @visible-change="onDropdownVisible"
    @change="emitChange"
  >
    <el-option
      v-for="item in options"
      :key="item[valueKey]"
      :label="item[labelKey]"
      :value="item[valueKey]"
    />
    <!-- 插入加载更多按钮 -->
    <div v-if="showLoadMore" class="load-more-wrapper">
      <el-button
        type="primary"
        size="small"
        @click.stop="loadMore"
        :loading="loadingMore"
      >
        {{ loadingMore ? '加载中...' : hasMore ? '加载更多' : '已加载全部' }}
      </el-button>
    </div>
  </el-select>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number],
  remoteMethod: Function,
  labelKey: {
    type: String,
    default: 'label'
  },
  valueKey: {
    type: String,
    default: 'value'
  },
  pageSize: {
    type: Number,
    default: 10
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const innerValue = ref(props.modelValue)
const options = ref([])
const page = ref(1)
const total = ref(0)
const loading = ref(false)
const loadingMore = ref(false)
const searchQuery = ref('')
const dropdownVisible = ref(false)

watch(() => props.modelValue, val => {
  innerValue.value = val
})

function emitChange(val) {
  emit('update:modelValue', val)
  emit('change', val)
}

function handleRemote(query) {
  searchQuery.value = query
  page.value = 1
  loadData({ reset: true })
}

function loadMore() {
  if (!hasMore.value || loadingMore.value) return
  page.value++
  loadData()
}

function onDropdownVisible(visible) {
  dropdownVisible.value = visible
  if (visible && options.value.length === 0) {
    handleRemote('')
  }
}

const hasMore = computed(() => options.value.length < total.value)
const showLoadMore = computed(() => dropdownVisible.value && options.value.length > 0)

async function loadData({ reset = false } = {}) {
  if (reset) {
    options.value = []
    total.value = 0
  }

  const params = {
    query: searchQuery.value,
    page: page.value,
    pageSize: props.pageSize
  }

  const isFirst = page.value === 1
  if (isFirst) loading.value = true
  else loadingMore.value = true

  try {
    const { list, total: totalCount } = await props.remoteMethod(params)
    if (reset) {
      options.value = list
    } else {
      options.value.push(...list)
    }
    total.value = totalCount
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}
</script>

<style scoped>
.load-more-wrapper {
  padding: 8px;
  text-align: center;
  border-top: 1px solid #eee;
}
.remote-select-dropdown .el-select-dropdown__wrap {
  max-height: 260px !important;
}
</style>
