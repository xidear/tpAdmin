<template>
  <el-select
    v-model="selectedValue"
    :placeholder="placeholder"
    filterable
    clearable
    :disabled="disabled"
    @visible-change="handleVisibleChange"
    @change="handleChange"
    style="width: 100%"
  >
    <el-scrollbar style="max-height: 250px; overflow-y: auto">
      <div @scroll.passive="onScroll" style="overflow-y: auto; height: 100%">
        <el-option
          v-for="item in filteredItems"
          :key="item[valueKey]"
          :label="getLabel(item)"
          :value="item[valueKey]"
          :disabled="isDisabled(item)"
        />

        <div v-if="loading" class="loading-text">加载中...</div>

        <div
          v-else-if="!loading && hasMore"
          class="loading-text clickable"
          @click="loadMore"
        >
          点击加载更多
        </div>
      </div>
    </el-scrollbar>
  </el-select>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { ElMessage } from "element-plus";

interface Props {
  // 已选值
  modelValue: any;
  // 占位符
  placeholder?: string;
  // 禁用状态
  disabled?: boolean;
  // 远程加载方法
  remoteMethod: (params: { page: number; pageSize: number; keyword?: string }) => Promise<any>;
  // 标识字段名
  valueKey?: string;
  // 显示字段名或自定义显示方法
  labelKey?: string | ((item: any) => string);
  // 过滤已选择项的方法
  filterSelected?: (item: any) => boolean;
  // 禁用项判断方法
  disabledMethod?: (item: any) => boolean;
  // 分页大小
  pageSize?: number;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: "请选择",
  disabled: false,
  valueKey: "id",
  labelKey: "name",
  pageSize: 10,
  filterSelected: () => false,
  disabledMethod: () => false
});

const emit = defineEmits<{
  (e: "update:modelValue", value: any): void;
  (e: "change", value: any, item: any): void;
}>();

// 内部选中值
const selectedValue = ref<any>(props.modelValue);

// 监听外部值变化
watch(
  () => props.modelValue,
  (val) => {
    selectedValue.value = val;
  }
);

// 所有选项
const allItems = ref<any[]>([]);
// 过滤后的选项
const filteredItems = ref<any[]>([]);
// 加载状态
const loading = ref(false);
// 是否有更多数据
const hasMore = ref(true);
// 当前页码
const currentPage = ref(1);
// 搜索关键词
const keyword = ref("");

// 处理下拉框显示/隐藏
const handleVisibleChange = async (visible: boolean) => {
  if (visible) {
    // 首次打开或重新打开时加载第一页
    if (allItems.value.length === 0) {
      await loadData(1, true);
    }
  }
};

// 加载数据
const loadData = async (page: number, reset = false) => {
  if (loading.value || !hasMore.value) return;

  loading.value = true;

  try {
    const res = await props.remoteMethod({
      page,
      pageSize: props.pageSize,
      keyword: keyword.value
    });

    const newItems = res.data?.list || [];

    if (reset) {
      allItems.value = newItems;
    } else {
      allItems.value = [...allItems.value, ...newItems];
    }

    // 更新过滤后的列表
    updateFilteredItems();

    // 判断是否还有更多数据
    hasMore.value = newItems.length >= props.pageSize;
    currentPage.value = page;
  } catch (error) {
    console.error("加载数据失败", error);
    ElMessage.error("加载数据失败");
  } finally {
    loading.value = false;
  }
};

// 更新过滤后的选项
const updateFilteredItems = () => {
  filteredItems.value = allItems.value.filter(item => {
    // 过滤已选择的项目
    if (props.filterSelected(item)) return false;

    // 关键词过滤
    if (!keyword.value) return true;

    const label = typeof props.labelKey === 'function'
      ? props.labelKey(item)
      : item[props.labelKey!] || '';

    return label.toLowerCase().includes(keyword.value.toLowerCase());
  });
};

// 处理滚动加载
const onScroll = (event: Event) => {
  const target = event.target as HTMLElement;
  if (target.scrollTop + target.clientHeight >= target.scrollHeight - 10) {
    loadMore();
  }
};

// 加载更多
const loadMore = () => {
  loadData(currentPage.value + 1);
};

// 处理选择变化
const handleChange = (value: any) => {
  emit("update:modelValue", value);
  // 找到对应的项并触发change事件
  const selectedItem = allItems.value.find(item => item[props.valueKey] === value);
  if (selectedItem) {
    emit("change", value, selectedItem);
  }
};

// 获取显示文本
const getLabel = (item: any) => {
  if (typeof props.labelKey === 'function') {
    return props.labelKey(item);
  }
  return item[props.labelKey!] || '';
};

// 判断是否禁用
const isDisabled = (item: any) => {
  return props.disabledMethod(item);
};

// 暴露方法
defineExpose({
  loadData
});
</script>


<style scoped lang="scss">
@import "./index.scss";
</style>
