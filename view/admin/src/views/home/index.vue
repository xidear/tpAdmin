<template>
  <div class="home-container">
         <!-- 欢迎区域 -->
     <div class="welcome-section">
       <div class="welcome-bg">
         <img 
           src="@/assets/images/welcome.png" 
           alt="welcome" 
           @error="handleImageError"
         />
       </div>
       <div class="welcome-text">
         <h2>欢迎使用管理系统</h2>
         <p>今天是 {{ currentDate }}</p>
       </div>
     </div>

    <!-- 统计卡片区域 -->
    <div class="stats-section">
      <el-row :gutter="20">
                 <!-- 任务统计卡片 -->
         <el-col :span="6" v-if="statsData.taskCount !== null">
          <div class="stat-card card primary">
            <div class="stat-icon">
              <el-icon :size="24">
                <Document />
              </el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ statsData.taskCount }}</div>
              <div class="stat-label">总任务数</div>
            </div>
          </div>
        </el-col>

                 <!-- 日志统计卡片 -->
         <el-col :span="6" v-if="statsData.logCount !== null">
          <div class="stat-card card success">
            <div class="stat-icon">
              <el-icon :size="24">
                <DataAnalysis />
              </el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ statsData.logCount }}</div>
              <div class="stat-label">系统日志</div>
            </div>
          </div>
        </el-col>

                 <!-- 部门统计卡片 -->
         <el-col :span="6" v-if="statsData.departmentCount !== null">
          <div class="stat-card card warning">
            <div class="stat-icon">
              <el-icon :size="24">
                <Setting />
              </el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ statsData.departmentCount }}</div>
              <div class="stat-label">部门数量</div>
            </div>
          </div>
        </el-col>

                 <!-- 角色统计卡片 -->
         <el-col :span="6" v-if="statsData.roleCount !== null">
          <div class="stat-card card danger">
            <div class="stat-icon">
              <el-icon :size="24">
                <User />
              </el-icon>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ statsData.roleCount }}</div>
              <div class="stat-label">角色数量</div>
            </div>
          </div>
        </el-col>

        <!-- 无权限提示 -->
        <el-col :span="24" v-if="!hasAnyPermission()">
          <div class="no-permission-card card">
            <el-empty description="暂无数据查看权限" />
          </div>
        </el-col>
      </el-row>
    </div>

    <!-- 图表区域 -->
    <div class="charts-section">
      <el-row :gutter="20">
                 <!-- 任务状态饼图 -->
         <el-col :span="12" v-if="statsData.taskCount !== null">
          <div class="chart-card card">
            <div class="chart-header">
              <h3>任务状态分布</h3>
              <el-button type="primary" size="small" @click="refreshTaskData">
                刷新数据
              </el-button>
            </div>
            <div ref="taskPieChart" class="chart-container"></div>
          </div>
        </el-col>

                 <!-- 系统日志趋势图 -->
         <el-col :span="12" v-if="statsData.logCount !== null">
          <div class="chart-card card">
            <div class="chart-header">
              <h3>系统日志趋势</h3>
              <el-button type="primary" size="small" @click="refreshLogData">
                刷新数据
              </el-button>
            </div>
            <div ref="logTrendChart" class="chart-container"></div>
          </div>
        </el-col>
      </el-row>

      <el-row :gutter="20" style="margin-top: 20px;">
                         <!-- 部门结构树图 -->
         <el-col :span="12" v-if="statsData.departmentCount !== null">
          <div class="chart-card card">
            <div class="chart-header">
              <h3>部门结构图</h3>
              <el-button type="primary" size="small" @click="refreshDepartmentData">
                刷新数据
              </el-button>
            </div>
            <div ref="departmentTreeChart" class="chart-container"></div>
          </div>
        </el-col>

                 <!-- 角色权限雷达图 -->
         <el-col :span="12" v-if="statsData.roleCount !== null">
          <div class="chart-card card">
            <div class="chart-header">
              <h3>角色权限分析</h3>
              <el-button type="primary" size="small" @click="refreshRoleData">
                刷新数据
              </el-button>
            </div>
            <div ref="roleRadarChart" class="chart-container"></div>
          </div>
        </el-col>
      </el-row>

      <el-row :gutter="20" style="margin-top: 20px;">
                 <!-- 用户活跃度网状图 -->
         <el-col :span="24" v-if="statsData.roleCount !== null">
          <div class="chart-card card">
            <div class="chart-header">
              <h3>用户活跃度关系图</h3>
              <el-button type="primary" size="small" @click="refreshUserData">
                刷新数据
              </el-button>
            </div>
            <div ref="userNetworkChart" class="chart-container" style="height: 400px;"></div>
          </div>
        </el-col>
      </el-row>
    </div>

         <!-- 数据表格区域 -->
     <div class="table-section" v-if="statsData.taskCount !== null">
      <div class="chart-card card">
        <div class="chart-header">
          <h3>最近任务列表</h3>
          <el-button type="primary" size="small" @click="refreshTaskTable">
            刷新数据
          </el-button>
        </div>
        <el-table :data="recentTasks" style="width: 100%" v-loading="tableLoading">
          <el-table-column prop="task_id" label="任务ID" width="80" />
          <el-table-column prop="name" label="任务标题" />
          <el-table-column prop="status" label="状态" width="100">
            <template #default="scope">
              <el-tag :type="getStatusType(scope.row.status)">
                {{ getStatusText(scope.row.status) }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="created_at" label="创建时间" width="180" />
          <el-table-column prop="updated_at" label="更新时间" width="180" />
        </el-table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts" name="home">
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '@/stores/modules/auth';
import * as echarts from 'echarts';
import { getHomeStatsApi } from '@/api/modules/home';
import { 
  User, 
  Document, 
  Setting, 
  DataAnalysis,
  Refresh
} from '@element-plus/icons-vue';

// 权限控制
const authStore = useAuthStore();

// 权限控制 - 基于数据驱动，后端控制权限
const hasPermission = (permission: string) => {
  // 简化权限检查，主要依赖后端返回的数据
  // 如果后端返回了数据，说明有权限；如果返回空或错误，说明无权限
  return true;
};

// 检查是否有任何权限 - 基于数据判断
const hasAnyPermission = (): boolean => {
  return statsData.value.taskCount !== null || 
         statsData.value.logCount !== null || 
         statsData.value.departmentCount !== null || 
         statsData.value.roleCount !== null;
};

// 响应式数据
const currentDate = ref('');
const statsData = ref({
  taskCount: 0 as number | null,
  logCount: 0 as number | null,
  departmentCount: 0 as number | null,
  roleCount: 0 as number | null
});

// 图表引用
const taskPieChart = ref<HTMLElement>();
const logTrendChart = ref<HTMLElement>();
const departmentTreeChart = ref<HTMLElement>();
const roleRadarChart = ref<HTMLElement>();
const userNetworkChart = ref<HTMLElement>();

// 表格数据
const recentTasks = ref([]);
const tableLoading = ref(false);

// 图表实例
let charts: echarts.ECharts[] = [];

// 计算当前日期
const updateCurrentDate = () => {
  const now = new Date();
  currentDate.value = now.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    weekday: 'long'
  });
  console.log('当前日期:', currentDate.value); // 添加调试日志
};

// 处理图片加载失败
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement;
  img.style.display = 'none';
  console.log('欢迎图片加载失败，隐藏图片元素');
};

// 获取统计数据 - 调用统一的统计接口
const fetchStatsData = async () => {
  try {
    const response = await getHomeStatsApi();
    if (response.data) {
      // 后端返回的数据结构：{ stats: {...}, details: {...} }
      const data = response.data as any;
      
      // 设置统计数据
      statsData.value.taskCount = data?.stats?.task !== null ? data.stats.task : null;
      statsData.value.logCount = data?.stats?.log !== null ? data.stats.log : null;
      statsData.value.departmentCount = data?.stats?.department !== null ? data.stats.department : null;
      statsData.value.roleCount = data?.stats?.role !== null ? data.stats.role : null;
      
      // 存储详细数据，供图表使用
      window.homeDetails = data?.details || {};
    }
  } catch (error) {
    console.error('获取统计数据失败:', error);
    // 错误时设置为null，不显示
    statsData.value.taskCount = null;
    statsData.value.logCount = null;
    statsData.value.departmentCount = null;
    statsData.value.roleCount = null;
  }
};

// 初始化任务状态饼图
const initTaskPieChart = async () => {
  if (!taskPieChart.value || statsData.value.taskCount === null) return;

  try {
    // 使用从getStats获取的数据
    const tasks = (window as any).homeDetails?.task || [];
    
    // 统计任务状态
    const statusCount = tasks.reduce((acc: any, task: any) => {
      const status = task.status || 0;
      acc[status] = (acc[status] || 0) + 1;
      return acc;
    }, {});

    const chart = echarts.init(taskPieChart.value);
    const option = {
      tooltip: {
        trigger: 'item',
        formatter: '{a} <br/>{b}: {c} ({d}%)'
      },
      legend: {
        orient: 'vertical',
        left: 'left'
      },
      series: [
        {
          name: '任务状态',
          type: 'pie',
          radius: '50%',
          data: [
            { value: statusCount[1] || 0, name: '进行中' },
            { value: statusCount[2] || 0, name: '已完成' },
            { value: statusCount[0] || 0, name: '待开始' },
            { value: statusCount[3] || 0, name: '已暂停' }
          ],
          emphasis: {
            itemStyle: {
              shadowBlur: 10,
              shadowOffsetX: 0,
              shadowColor: 'rgba(0, 0, 0, 0.5)'
            }
          }
        }
      ]
    };
    chart.setOption(option);
    charts.push(chart);
  } catch (error) {
    console.error('初始化任务饼图失败:', error);
    // 显示默认图表
    if (taskPieChart.value) {
      const chart = echarts.init(taskPieChart.value);
      const option = {
        tooltip: { trigger: 'item' },
        series: [{
          name: '任务状态',
          type: 'pie',
          radius: '50%',
          data: [
            { value: 0, name: '进行中' },
            { value: 0, name: '已完成' },
            { value: 0, name: '待开始' },
            { value: 0, name: '已暂停' }
          ]
        }]
      };
      chart.setOption(option);
      charts.push(chart);
    }
  }
};

// 初始化日志趋势图
const initLogTrendChart = async () => {
  if (!logTrendChart.value || statsData.value.logCount === null) return;

  try {
    // 使用从getStats获取的数据
    const logs = (window as any).homeDetails?.log || [];
    
    // 按日期分组统计
    const dateCount = logs.reduce((acc: any, log: any) => {
      const date = log.created_at?.split(' ')[0] || 'unknown';
      acc[date] = (acc[date] || 0) + 1;
      return acc;
    }, {});

    const dates = Object.keys(dateCount).sort().slice(-7);
    const counts = dates.map(date => dateCount[date]);

    const chart = echarts.init(logTrendChart.value);
    const option = {
      tooltip: {
        trigger: 'axis'
      },
      xAxis: {
        type: 'category',
        data: dates
      },
      yAxis: {
        type: 'value'
      },
      series: [
        {
          name: '日志数量',
          type: 'line',
          data: counts,
          smooth: true,
          areaStyle: {
            opacity: 0.3
          }
        }
      ]
    };
    chart.setOption(option);
    charts.push(chart);
  } catch (error) {
    console.error('初始化日志趋势图失败:', error);
    // 显示默认图表
    if (logTrendChart.value) {
      const chart = echarts.init(logTrendChart.value);
      const option = {
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', data: ['暂无数据'] },
        yAxis: { type: 'value' },
        series: [{
          name: '日志数量',
          type: 'line',
          data: [0]
        }]
      };
      chart.setOption(option);
      charts.push(chart);
    }
  }
};

// 初始化部门结构图
const initDepartmentTreeChart = async () => {
  if (!departmentTreeChart.value || statsData.value.departmentCount === null) return;

  try {
    // 使用从getStats获取的数据
    const departments = (window as any).homeDetails?.department || [];
    
    // 转换为树形结构
    const buildTreeData = (depts: any[]) => {
      return depts.map(dept => ({
        name: dept.name,
        children: dept.children ? buildTreeData(dept.children) : []
      }));
    };

    const treeData = buildTreeData(departments);

    const chart = echarts.init(departmentTreeChart.value);
    const option = {
      tooltip: {
        trigger: 'item'
      },
      series: [
        {
          type: 'tree',
          data: treeData,
          top: '5%',
          left: '7%',
          bottom: '2%',
          right: '20%',
          symbolSize: 7,
          orient: 'vertical',
          label: {
            position: 'left',
            verticalAlign: 'middle',
            align: 'right',
            fontSize: 12
          },
          leaves: {
            position: 'right',
            verticalAlign: 'middle',
            align: 'left'
          },
          emphasis: {
            focus: 'descendant'
          },
          expandAndCollapse: true,
          animationDuration: 550,
          animationDurationUpdate: 750
        }
      ]
    };
    chart.setOption(option);
    charts.push(chart);
  } catch (error) {
    console.error('初始化部门结构图失败:', error);
    // 显示默认图表
    if (departmentTreeChart.value) {
      const chart = echarts.init(departmentTreeChart.value);
      const option = {
        tooltip: { trigger: 'item' },
        series: [{
          type: 'tree',
          data: [{ name: '暂无数据', children: [] }]
        }]
      };
      chart.setOption(option);
      charts.push(chart);
    }
  }
};

// 初始化角色权限雷达图
const initRoleRadarChart = async () => {
  if (!roleRadarChart.value || statsData.value.roleCount === null) return;

  try {
    // 使用从getStats获取的数据
    const roles = (window as any).homeDetails?.role || [];
    
    // 模拟角色权限数据
    const rolePermissions = roles.slice(0, 3).map((role: any, index: number) => ({
      name: role.name || `角色${index + 1}`,
      value: [
        Math.floor(Math.random() * 100), // 系统管理
        Math.floor(Math.random() * 100), // 用户管理
        Math.floor(Math.random() * 100), // 权限管理
        Math.floor(Math.random() * 100), // 日志查看
        Math.floor(Math.random() * 100)  // 数据导出
      ]
    }));

    const chart = echarts.init(roleRadarChart.value);
    const option = {
      tooltip: {
        trigger: 'item'
      },
      legend: {
        data: rolePermissions.map(r => r.name),
        bottom: 10
      },
      radar: {
        indicator: [
          { name: '系统管理', max: 100 },
          { name: '用户管理', max: 100 },
          { name: '权限管理', max: 100 },
          { name: '日志查看', max: 100 },
          { name: '数据导出', max: 100 }
        ],
        center: ['50%', '50%'],
        radius: '65%',
        name: {
          textStyle: {
            color: '#333',
            fontSize: 12,
            padding: [5, 0]
          }
        },
        splitArea: {
          show: true,
          areaStyle: {
            color: ['rgba(250,250,250,0.3)', 'rgba(200,200,200,0.3)']
          }
        },
        axisLine: {
          lineStyle: {
            color: 'rgba(0,0,0,.1)'
          }
        },
        splitLine: {
          lineStyle: {
            color: 'rgba(0,0,0,.1)'
          }
        }
      },
      series: [
        {
          type: 'radar',
          data: rolePermissions,
          areaStyle: {
            opacity: 0.3
          },
          lineStyle: {
            width: 2
          },
          symbol: 'circle',
          symbolSize: 6
        }
      ]
    };
    chart.setOption(option);
    charts.push(chart);
  } catch (error) {
    console.error('初始化角色雷达图失败:', error);
    // 显示默认图表
    if (roleRadarChart.value) {
      const chart = echarts.init(roleRadarChart.value);
      const option = {
        tooltip: { trigger: 'item' },
        radar: {
          indicator: [
            { name: '系统管理', max: 100 },
            { name: '用户管理', max: 100 },
            { name: '权限管理', max: 100 },
            { name: '日志查看', max: 100 },
            { name: '数据导出', max: 100 }
          ],
          center: ['50%', '50%'],
          radius: '65%',
          name: {
            textStyle: {
              color: '#333',
              fontSize: 12,
              padding: [5, 0]
            }
          }
        },
        series: [{
          type: 'radar',
          data: [{ name: '暂无数据', value: [0, 0, 0, 0, 0] }]
        }]
      };
      chart.setOption(option);
      charts.push(chart);
    }
  }
};

// 初始化用户活跃度网状图
const initUserNetworkChart = async () => {
  if (!userNetworkChart.value || statsData.value.roleCount === null) return;

  try {
    // 模拟用户关系数据
    const nodes = [
      { name: '管理员', value: 20, category: 0 },
      { name: '部门主管', value: 15, category: 1 },
      { name: '普通用户', value: 10, category: 2 },
      { name: '访客', value: 5, category: 3 }
    ];

    const links = [
      { source: '管理员', target: '部门主管', value: 1 },
      { source: '部门主管', target: '普通用户', value: 1 },
      { source: '普通用户', target: '访客', value: 1 },
      { source: '管理员', target: '普通用户', value: 1 }
    ];

    const chart = echarts.init(userNetworkChart.value);
    const option = {
      tooltip: {
        trigger: 'item'
      },
      legend: {
        data: ['管理员', '部门主管', '普通用户', '访客']
      },
      series: [
        {
          type: 'graph',
          layout: 'force',
          data: nodes,
          links: links,
          roam: true,
          label: {
            show: true,
            position: 'right'
          },
          force: {
            repulsion: 100
          }
        }
      ]
    };
    chart.setOption(option);
    charts.push(chart);
  } catch (error) {
    console.error('初始化用户关系图失败:', error);
    // 显示默认图表
    if (userNetworkChart.value) {
      const chart = echarts.init(userNetworkChart.value);
      const option = {
        tooltip: { trigger: 'item' },
        series: [{
          type: 'graph',
          layout: 'force',
          data: [{ name: '暂无数据' }],
          links: [],
          roam: true
        }]
      };
      chart.setOption(option);
      charts.push(chart);
    }
  }
};

// 获取最近任务列表
const fetchRecentTasks = async () => {
  if (statsData.value.taskCount === null) return;
  
  try {
    tableLoading.value = true;
    // 使用从getStats获取的数据
    recentTasks.value = (window as any).homeDetails?.task || [];
  } catch (error) {
    console.error('获取任务列表失败:', error);
  } finally {
    tableLoading.value = false;
  }
};

// 刷新数据方法
const refreshTaskData = () => {
  initTaskPieChart();
};

const refreshLogData = () => {
  initLogTrendChart();
};

const refreshDepartmentData = () => {
  initDepartmentTreeChart();
};

const refreshRoleData = () => {
  initRoleRadarChart();
};

const refreshUserData = () => {
  initUserNetworkChart();
};

const refreshTaskTable = () => {
  fetchRecentTasks();
};

// 状态相关方法
const getStatusType = (status: number) => {
  const statusMap: { [key: number]: string } = {
    0: 'info',
    1: 'warning',
    2: 'success',
    3: 'danger'
  };
  return statusMap[status] || 'info';
};

const getStatusText = (status: number) => {
  const statusMap: { [key: number]: string } = {
    0: '待开始',
    1: '进行中',
    2: '已完成',
    3: '已暂停'
  };
  return statusMap[status] || '未知';
};

// 生命周期
onMounted(async () => {
  updateCurrentDate();
  await fetchStatsData();
  
  // 初始化所有图表
  setTimeout(() => {
    initTaskPieChart();
    initLogTrendChart();
    initDepartmentTreeChart();
    initRoleRadarChart();
    initUserNetworkChart();
    fetchRecentTasks();
  }, 100);

  // 定时更新日期
  setInterval(updateCurrentDate, 60000);

  // 监听窗口大小变化，重新调整图表大小
  window.addEventListener('resize', () => {
    charts.forEach(chart => {
      if (chart && !chart.isDisposed()) {
        chart.resize();
      }
    });
  });
});

// 组件卸载时销毁图表
import { onUnmounted } from 'vue';
onUnmounted(() => {
  charts.forEach(chart => chart.dispose());
});
</script>

<style scoped lang="scss">
.home-container {
  padding: 20px;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.welcome-section {
  position: relative;
  margin-bottom: 20px;
  height: 200px; /* 固定高度，适合16xx * 9xx的图片 */
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* 备用背景 */
  
  .welcome-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      filter: brightness(0.7); /* 稍微调暗图片，让文字更清晰 */
    }
  }
  
  .welcome-text {
    position: relative;
    z-index: 2;
    padding: 40px;
    color: white;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    
    h2 {
      margin: 0 0 15px 0;
      font-size: 32px;
      font-weight: 700;
      color: white;
    }
    
    p {
      margin: 0;
      font-size: 18px;
      opacity: 0.9;
      color: white;
    }
  }
}

.stats-section {
  margin-bottom: 20px;
  
  .stat-card {
    padding: 20px;
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
    
    &:hover {
      transform: translateY(-5px);
    }
    
    &.primary { border-left: 4px solid #409eff; }
    &.success { border-left: 4px solid #67c23a; }
    &.warning { border-left: 4px solid #e6a23c; }
    &.danger { border-left: 4px solid #f56c6c; }
    
    .stat-icon {
      margin-right: 15px;
      padding: 12px;
      border-radius: 8px;
      background-color: rgba(64, 158, 255, 0.1);
      color: #409eff;
    }
    
    .stat-content {
      .stat-number {
        font-size: 24px;
        font-weight: 600;
        color: #303133;
        margin-bottom: 5px;
      }
      
      .stat-label {
        font-size: 14px;
        color: #909399;
      }
    }
  }
}

.charts-section {
  margin-bottom: 20px;
  
  .chart-card {
    margin-bottom: 20px;
    
    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      border-bottom: 1px solid #ebeef5;
      
      h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #303133;
      }
    }
    
    .chart-container {
      height: 300px;
      padding: 20px;
    }
  }
}

.table-section {
  .chart-card {
    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 20px;
      border-bottom: 1px solid #ebeef5;
      
      h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #303133;
      }
    }
  }
}

.no-permission-card {
  padding: 40px;
  text-align: center;
  
  .el-empty {
    .el-empty__description {
      color: #909399;
      font-size: 14px;
    }
  }
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

// 响应式设计
@media (max-width: 768px) {
  .home-container {
    padding: 10px;
  }
  
  .stats-section .el-col {
    margin-bottom: 10px;
  }
  
  .charts-section .el-col {
    margin-bottom: 10px;
  }
}
</style>
