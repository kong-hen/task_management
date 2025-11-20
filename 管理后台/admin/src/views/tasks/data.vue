<template>
	<t-row :gutter="[16, 16]">
		<t-col :lg="6" :xs="12">
			<t-card title="基础信息" :bordered="false">
				<t-descriptions id="taskInfoContainer">
					<t-descriptions-item label="任务ID" :span="2">{{ taskId }}</t-descriptions-item>
					<t-descriptions-item label="任务名称" :span="1">{{ taskInfo.name }}</t-descriptions-item>
					<t-descriptions-item label="奖励类型" :span="1">{{ taskInfo.type == 1 ? '链接' : '文本' }}</t-descriptions-item>
					<t-descriptions-item label="任务域名" :span="2">{{ taskInfo.domain }}</t-descriptions-item>
					<t-descriptions-item label="奖励内容" :span="2">{{ taskInfo.award }}</t-descriptions-item>
					<t-descriptions-item label="任务状态" :span="1">
						<t-tag shape="round" :theme="taskInfo.status === 1 ? 'success' : 'danger'" variant="light-outline">
							{{ taskInfo.status === 1 ? '启用' : '禁用' }}
						</t-tag>
					</t-descriptions-item>
					<t-descriptions-item label="强制点击" :span="1">
						<t-tag shape="round" :theme="taskInfo.click === 1 ? 'success' : 'danger'" variant="light-outline">
							{{ taskInfo.click === 1 ? '开启' : '关闭' }}
						</t-tag>
					</t-descriptions-item>
					<t-descriptions-item label="创建时间" :span="1">{{ taskInfo.created_at }}</t-descriptions-item>
					<t-descriptions-item label="更新时间" :span="1">{{ taskInfo.updated_at }}</t-descriptions-item>
				</t-descriptions>
			</t-card>
		</t-col>
		<t-col :lg="6" :xs="12">
			<t-card :title="`${year}年第 ${week} 周数据`" :bordered="false">
				<div ref="weekDataContainer"></div>
				<template #actions>
					<t-space>
						<t-date-picker mode="week" clearable allow-input :first-day-of-week="1" :onChange="onChooseWeek" />
						<t-button :loading="isWeekLoading" @click="fetchWeekData">查询</t-button>
					</t-space>
				</template>
			</t-card>
		</t-col>
	</t-row>

	<t-card class="mt-l" title="浏览列表">
		<t-space direction="vertical" size="small" class="w-100">
			<t-table :data="viewList" :columns="viewColumns" row-key="id" :loading="isViewLoading" :hover="true" size="medium"
				tableLayout="fixed" style="width: 100%;">
				<template #status="{ row }">
					<t-tag shape="round" :theme="row.status === 2 ? 'success' : (row.status === 1 ? 'warning' : 'danger')"
						variant="light-outline">
						{{ row.status === 2 ? '已点击' : (row.status === 1 ? '已观看' : '未完成') }}
					</t-tag>
				</template>
				<template #award_type="{ row }">
					<t-tag shape="round"
						:theme="row.award_type === 1 ? 'primary' : (row.award_type === 2 ? 'success' : (row.award_type === 3 ? 'warning' : 'danger'))"
						variant="light-outline">
						{{ row.award_type === 1 ? '链接' : (row.award_type === 2 ? '文本' : (row.award_type === 3 ? '卡密' : '未知')) }}
					</t-tag>
				</template>
			</t-table>

			<t-pagination v-model:current="viewPage" v-model:pageSize="viewSize" :total="viewTotal"
				:page-size-options="[15, 30, 50]" :disabled="isViewLoading" @change="fetchViewList" />
		</t-space>
	</t-card>
</template>

<script
	setup
	lang="ts"
>
	import { onMounted, ref, nextTick } from 'vue'
	import { useRoute } from 'vue-router'
	import { MessagePlugin } from 'tdesign-vue-next'
	import type { TaskItem, ViewItem, WeekParams, WeekResult, ViewParams, ViewResult } from '@/api/model/tasksModel'
	import { getWeekData, getViewList } from '@/api/tasks'

	import { LineChart } from 'echarts/charts';
	import { GridComponent, LegendComponent, TitleComponent, TooltipComponent } from 'echarts/components';
	import * as echarts from 'echarts/core';
	import { CanvasRenderer } from 'echarts/renderers';

	echarts.use([
		TitleComponent,
		GridComponent,
		LegendComponent,
		LineChart,
		CanvasRenderer,
		TooltipComponent,
	]);

	// 初始化数据
	const route = useRoute()
	const taskId: number = Number(route.params.id)
	const taskInfo = route.query as unknown as TaskItem

	const year = ref<number>(0)
	const week = ref<number>(0)
	const isWeekLoading = ref<boolean>(false)

	// 监听周选择
	const onChooseWeek = (e: string) => {
		console.log("选择了：", e);
		const match = e.match(/^(\d{4})-(\d{1,2})周$/);
		year.value = parseInt(match![1], 10);
		week.value = parseInt(match![2], 10);
	}

	// 添加ref来获取元素
	const weekDataContainer = ref<HTMLElement | null>(null);
	// 初始化图表
	let weekDataChart: echarts.ECharts;

	// 周图标折线图数据
	let weekChartData = {
		grid: {
			x: 40,
			y: 20,
			x2: 20,
			y2: 60
		},
		legend: {
			data: ["访问IP", "观看广告", "点击广告"],
			bottom: 0
		},
		xAxis: {
			type: 'category',
			data: ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
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
				name: '访问IP',
				data: [120, 200, 150, 120, 200, 150, 120],
				type: 'line'
			},
			{
				name: '观看广告',
				data: [220, 160, 120, 220, 160, 120, 220],
				type: 'line'
			},
			{
				name: '点击广告',
				data: [180, 100, 110, 180, 100, 110, 180],
				type: 'line'
			}
		]
	}

	// 使用接口获取周数据
	const fetchWeekData = () => {
		isWeekLoading.value = true;
		getWeekData({
			id: taskId,
			year: year.value,
			week: week.value
		} as WeekParams)
			.then((res: WeekResult) => {
				console.log('周数据:', res);
				year.value = res.year;
				week.value = res.week;
				weekChartData.xAxis.data = res.day_list;
				weekChartData.series[0].data = res.did_list;
				weekChartData.series[1].data = res.view_list;
				weekChartData.series[2].data = res.click_list;
				// 确保图表已初始化
				if (weekDataChart) {
					weekDataChart.setOption(weekChartData);
				}
			})
			.catch(error => {
				console.error('获取周数据失败:', error);
				MessagePlugin.error('获取周数据失败');
			})
			.finally(() => {
				isWeekLoading.value = false;
			});
	}

	// 获取TaskInfo容器宽高
	const getTaskInfoContainerDimensions = (): { width: number; height: number } | null => {
		let taskInfoContainerElement = document.getElementById("taskInfoContainer");
		if (taskInfoContainerElement) {
			const rect = taskInfoContainerElement.getBoundingClientRect();
			return {
				width: rect.width,
				height: rect.height
			};
		}
		return null;
	};

	// 将taskInfoContainer的宽高应用到weekDataContainer
	const applyDimensionsToWeekDataContainer = (): void => {
		const dimensions = getTaskInfoContainerDimensions();
		console.log('taskInfoContainer 宽高:', dimensions);
		if (dimensions && weekDataContainer.value) {
			weekDataContainer.value.style.width = `${dimensions.width}px`;
			// 为了去除卡片组件操作插槽中日期选择组件高度超出8px的问题，需要减去8px
			weekDataContainer.value.style.height = `${dimensions.height - 8}px`;
			console.log('初始化周浏览数据折线图容器完成');
		}
	};


	// 初始化浏览历史数据
	const viewPage = ref<number>(1);
	const viewSize = ref<number>(15);
	const viewTotal = ref<number>(0);
	const viewList = ref<ViewItem[]>([]);
	const isViewLoading = ref<boolean>(false);

	const viewColumns = [
		{ title: 'ID', colKey: 'id', width: 70, fixed: 'left' },
		{ title: '任务ID', colKey: 'task_id', width: 80, fixed: 'left', ellipsis: true },
		{ title: '访问IP', colKey: 'ip', width: 140, ellipsis: true },
		{ title: '设备识别码', colKey: 'did', width: 180, ellipsis: true },
		{ title: '奖励类型', colKey: 'award_type', width: 100 },
		{ title: '奖励内容', colKey: 'award', width: 200, ellipsis: true },
		{ title: '状态', colKey: 'status', width: 100 },
		{ title: '创建时间', colKey: 'created_at', width: 200 },
		{ title: '完成时间', colKey: 'completed_at', width: 200 },
	];

	const fetchViewList = () => {
		isViewLoading.value = true;
		getViewList({
			id: taskId,
			page: viewPage.value,
			size: viewSize.value
		} as ViewParams)
			.then((res: ViewResult) => {
				console.log('浏览历史数据:', res);
				viewList.value = res.list || [];
				viewTotal.value = res.total || 0;
			})
			.catch(error => {
				console.error('获取浏览历史数据失败:', error);
			})
			.finally(() => {
				isViewLoading.value = false;
			});
	}

	onMounted(() => {
		// 等待DOM更新完成
		nextTick(() => {
			// 获取taskInfoContainer的宽高并应用到weekDataContainer
			applyDimensionsToWeekDataContainer();
			// 初始化图表
			if (weekDataContainer.value) {
				weekDataChart = echarts.init(weekDataContainer.value);
				fetchWeekData();
				fetchViewList();
			}
		});
	});
</script>
