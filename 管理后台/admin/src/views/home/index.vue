<template><t-row :gutter="[16, 16]">
    <t-col :xs="6" :xl="3">
      <t-card title="在用任务" :bordered="false" class="item" :loading="loading">
        <div class="item-top">
          <span style="font-size: 28px;">{{ usingTask }}</span>
        </div>
        <div class="item-left">
          <span style="margin-top: -24px;">
            <task-icon />
          </span>
        </div>
        <template #footer>
          <div class="item-bottom" @click="goTo('/tasks/list')">
            <div class="item-block">
              禁用任务
              <trend class="item-trend" type="up" :describe="disabledTask" />
            </div>
            <t-icon name="chevron-right" />
          </div>
        </template>
      </t-card>
    </t-col>
    <t-col :xs="6" :xl="3">
      <t-card title="在用小程序" :bordered="false" class="item" :loading="loading">
        <div class="item-top">
          <span style="font-size: 28px;">{{ usingXcx }}</span>
        </div>
        <div class="item-left">
          <span style="margin-top: -24px;">
            <task-checked-1-icon />
          </span>
        </div>
        <template #footer>
          <div class="item-bottom" @click="goTo('/xcx/list')">
            <div class="item-block">
              禁用小程序
              <trend class="item-trend" type="up" :describe="disabledXcx" />
            </div>
            <t-icon name="chevron-right" />
          </div>
        </template>
      </t-card>
    </t-col>
    <t-col :xs="6" :xl="3">
      <t-card title="在用域名" :bordered="false" class="item" :loading="loading">
        <div class="item-top">
          <span style="font-size: 28px;">{{ usingDomain }}</span>
        </div>
        <div class="item-left">
          <span style="margin-top: -24px;">
            <time-icon />
          </span>
        </div>
        <template #footer>
          <div class="item-bottom" @click="goTo('/domains/list')">
            <div class="item-block">
              禁用域名
              <trend class="item-trend" type="up" :describe="disabledDomain" />
            </div>
            <t-icon name="chevron-right" />
          </div>
        </template>
      </t-card>
    </t-col>
    <t-col :xs="6" :xl="3">
      <t-card title="观看广告" :bordered="false" class="item" :loading="loading">
        <div class="item-top">
          <span style="font-size: 28px;">{{ adViewTotal }}</span>
        </div>
        <div class="item-left">
          <span style="margin-top: -24px;">
            <browse-icon />
          </span>
        </div>
        <template #footer>
          <div class="item-bottom" @click="goTo('/tasks/view')">
            <div class="item-block">
              今日新增
              <trend class="item-trend" type="up" :describe="adViewToday" />
            </div>
            <t-icon name="chevron-right" />
          </div>
        </template>
      </t-card>
    </t-col>
  </t-row>

  <t-row class="mt-l" :gutter="[16, 16]">
    <t-col :lg="9" :xs="12">
      <t-card title="今日数据" :bordered="false">
        <div ref="todayDataContainer" style="height: 278px;"></div>
      </t-card>
    </t-col>
    <t-col :lg="3" :xs="12">
      <t-card title="任务数据" :bordered="false">
        <div ref="viewDataContainer" style="height: 278px;"></div>
      </t-card>
    </t-col>
  </t-row>

  <t-card title="近7日数据" :bordered="false" class="mt-l">
    <t-table :data="weekList" :columns="columns" row-key="id" :hover="true" size="medium" tableLayout="fixed"
      style="width: 100%;" :loading="loading">
    </t-table>
  </t-card>
</template>

<script setup lang="ts">
  import { onMounted, ref } from 'vue';
  import { TaskIcon, BrowseIcon, TaskChecked1Icon, TimeIcon } from 'tdesign-icons-vue-next';
  import Trend from '@/components/trend/index.vue';
  import { MessagePlugin } from 'tdesign-vue-next';
  import router from '@/router';
  import type { HomeResult, WeekDataItem } from '@/api/model/homeModel';
  import { getHomeData } from '@/api/home';

  import { BarChart, PieChart } from 'echarts/charts';
  import { GridComponent, LegendComponent, TitleComponent, TooltipComponent } from 'echarts/components';
  import * as echarts from 'echarts/core';
  import { CanvasRenderer } from 'echarts/renderers';

  echarts.use([
    TitleComponent,
    GridComponent,
    LegendComponent,
    BarChart,
    PieChart,
    CanvasRenderer,
    TooltipComponent,
  ]);

  const usingTask = ref<number>(0);
  const disabledTask = ref<number>(0);
  const usingDomain = ref<number>(0);
  const disabledDomain = ref<number>(0);
  const usingXcx = ref<number>(0);
  const disabledXcx = ref<number>(0);
  const adViewTotal = ref<number>(0);
  const adViewToday = ref<number>(0);

  // 页面跳转
  const goTo = (path: string) => {
    router.push(path);
  }

  // 添加ref来获取元素
  const todayDataContainer = ref<HTMLElement | null>(null);
  // 今日访问柱状图表
  let todayDataChart: echarts.ECharts;

  // 周图标折线图数据
  let todayChartData = {
    grid: {
      x: 40,
      y: 20,
      x2: 20,
      y2: 60
    },
    legend: {
      data: ["访问设备", "观看广告", "点击广告"],
      bottom: 0
    },
    xAxis: {
      name: '任务ID',
      type: 'category',
      data: ['98', '67', '89', '45', '25']
    },
    yAxis: {
      type: 'value'
    },
    tooltip: {
      show: true,
      trigger: 'item'
    },
    series: [
      {
        name: '访问设备',
        data: [120, 200, 150, 120, 200],
        type: 'bar',
        itemStyle: {
          color: '#5527f3'
        }
      },
      {
        name: '观看广告',
        data: [220, 160, 120, 220, 160],
        type: 'bar',
        itemStyle: {
          color: '#00a870'
        }
      },
      {
        name: '点击广告',
        data: [180, 100, 110, 180, 100],
        type: 'bar',
        itemStyle: {
          color: '#ed7b2f'
        }
      }
    ]
  }


  // 总任务数据圆环表
  const viewDataContainer = ref<HTMLElement | null>(null);
  let viewDataChart: echarts.ECharts;
  let viewChartData = {
    tooltip: {
      trigger: 'item'
    },
    legend: {
      data: ["访问量", "观看广告", "点击广告"],
      bottom: 0
    },
    series: [
      {
        name: '访问数据',
        type: 'pie',
        radius: ['40%', '70%'],
        center: ['50%', '50%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false
        },
        data: [
          {
            value: 1000,
            name: '访问量',
            itemStyle: {
              color: '#5527f3'
            }
          }
        ]
      },
      {
        name: '观看数据',
        type: 'pie',
        radius: ['30%', '60%'],
        center: ['50%', '50%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false
        },
        data: [
          {
            value: 800,
            name: '观看广告',
            itemStyle: {
              color: '#00a870'
            }
          },
          {
            value: 200,
            name: '未观看',
            itemStyle: {
              color: '#bcebdc',
            }
          }
        ]
      },
      {
        name: '点击数据',
        type: 'pie',
        radius: ['20%', '50%'],
        center: ['50%', '50%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false
        },
        data: [
          {
            value: 500,
            name: '点击广告',
            itemStyle: {
              color: "#ed7b2f"
            }
          },
          {
            value: 500,
            name: '未点击',
            itemStyle: {
              color: '#f9e0c7'
            }
          }
        ]
      }
    ]
  };

  const loading = ref<boolean>(true)
  const weekList = ref<WeekDataItem[]>([])

  // 近7日数据表
  const columns = [
    { title: '序号', colKey: 'index', width: 80 },
    { title: '任务访问量', colKey: 'did', minWidth: 120 },
    { title: '广告观看量', colKey: 'view', minWidth: 120 },
    { title: '广告点击量', colKey: 'click', minWidth: 120 },
    { title: '广告观看率', colKey: 'view_rate', minWidth: 120 },
    { title: '广告点击率', colKey: 'click_rate', minWidth: 120 },
    { title: '日期', colKey: 'date', minWidth: 200, fixed: 'right' },
  ];

  const fetchData = () => {
    getHomeData()
      .then((res: HomeResult) => {
        console.log("获取成功", res)
        // 基础数据
        usingTask.value = res.task[0] || 0
        disabledTask.value = res.task[1] || 0
        usingXcx.value = res.xcx[0] || 0
        disabledXcx.value = res.xcx[1] || 0
        usingDomain.value = res.domain[0] || 0
        disabledDomain.value = res.domain[1] || 0
        adViewTotal.value = res.ad_view[0] || 0
        adViewToday.value = res.ad_view[1] || 0

        // 今日观看数据
        let idList: string[] = []
        let didList: number[] = []
        let viewList: number[] = []
        let clickList: number[] = []
        res.today.map((item) => {
          idList.push(item.task_id.toString())
          didList.push(item.did)
          viewList.push(item.view)
          clickList.push(item.click)
        })
        if (idList.length > 0) {
          todayChartData.xAxis.data = idList
          todayChartData.series[0].data = didList
          todayChartData.series[1].data = viewList
          todayChartData.series[2].data = clickList
        } else {
          todayChartData.xAxis.data = ['0']
          todayChartData.series[0].data = [0]
          todayChartData.series[1].data = [0]
          todayChartData.series[2].data = [0]
        }
        todayDataChart = echarts.init(todayDataContainer.value);
        if (todayDataChart) {
          todayDataChart.setOption(todayChartData);
        }

        // 广告观看数据圆环图表
        const viewRes = res.all_view || [0, 0, 0]
        viewChartData.series[0].data[0].value = viewRes[0]
        viewChartData.series[1].data[0].value = viewRes[1]
        viewChartData.series[1].data[1].value = viewRes[0] - viewRes[1]
        viewChartData.series[2].data[0].value = viewRes[2]
        viewChartData.series[2].data[1].value = viewRes[0] - viewRes[2]
        viewDataChart = echarts.init(viewDataContainer.value);
        if (viewDataChart) {
          viewDataChart.setOption(viewChartData);
        }

        // 本周观看数据
        weekList.value = res.week || []
      })
      .catch(err => {
        console.log("获取失败", err)
        MessagePlugin.error('获取数据失败');
      })
      .finally(() => {
        loading.value = false
      })
  }

  onMounted(() => {
    fetchData();
  })

</script>

<style lang="less" scoped>
  .item {
    padding: var(--td-comp-paddingTB-xl) var(--td-comp-paddingLR-xxl);

    :deep(.t-card__header) {
      padding: 0;
    }

    :deep(.t-card__footer) {
      padding: 0;
    }

    :deep(.t-card__title) {
      font: var(--td-font-body-medium);
      color: var(--td-text-color-secondary);
    }

    :deep(.t-card__body) {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      flex: 1;
      position: relative;
      padding: 0;
      margin-top: var(--td-comp-margin-s);
      margin-bottom: var(--td-comp-margin-xxl);
    }

    &:hover {
      cursor: pointer;
    }

    &-top {
      display: flex;
      flex-direction: row;
      align-items: flex-start;

      >span {
        display: inline-block;
        color: var(--td-text-color-primary);
        font-size: var(--td-font-size-headline-medium);
        line-height: var(--td-line-height-headline-medium);
      }
    }

    &-bottom {
      display: flex;
      flex-direction: row;
      justify-content: space-between;
      align-items: center;

      >.t-icon {
        cursor: pointer;
        font-size: var(--td-comp-size-xxxs);
      }
    }

    &-block {
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--td-text-color-placeholder);
    }

    &-trend {
      margin-left: var(--td-comp-margin-s);
    }

    &-left {
      position: absolute;
      top: 0;
      right: 0;

      >span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--td-comp-size-xxxl);
        height: var(--td-comp-size-xxxl);
        background: var(--td-brand-color-light);
        border-radius: 50%;

        .t-icon {
          font-size: 24px;
          color: var(--td-brand-color);
        }
      }
    }

    /* 针对第一个卡片需要反色处理 */
    &--main-color {
      background: var(--td-brand-color);
      color: var(--td-text-color-primary);

      :deep(.t-card__title),
      .item-top span,
      .item-bottom {
        color: var(--td-text-color-anti);
      }

      .item-block {
        color: var(--td-text-color-anti);
        opacity: 0.6;
      }

      .item-bottom {
        color: var(--td-text-color-anti);
      }
    }
  }
</style>
