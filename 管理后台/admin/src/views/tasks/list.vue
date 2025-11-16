<template>
  <t-card bordered>
    <t-typography-title level="h4">任务列表</t-typography-title>
    <t-space direction="vertical" size="small" class="w-100">
      <t-space :size="12">
        <t-button theme="primary" @click="openCreate">新增</t-button>
        <t-button theme="danger" :disabled="selectedRowKeys.length === 0"
          @click="deleteConfirmDialog = true">删除</t-button>
      </t-space>
      <t-table :data="list" :columns="columns" :row-key="rowKey" :loading="loading" :hover="true" size="medium"
        :selected-row-keys="selectedRowKeys" @select-change="onSelectChange" tableLayout="fixed" style="width: 100%;">

        <template #type="{ row }">
          <t-tag shape="round" :theme="row.type === 1 ? 'primary' : 'warning'" variant="light-outline">
            {{ row.type === 1 ? '链接' : '文本' }}
          </t-tag>
        </template>

        <template #click="{ row }">
          <t-tag shape="round" :theme="row.click === 1 ? 'success' : 'danger'" variant="light-outline">
            {{ row.click === 1 ? '开启' : '关闭' }}
          </t-tag>
        </template>

        <template #status="{ row }">
          <t-switch size="small" :value="row.status === 1" @change="(val: boolean) => onToggleStatus(row, val)" />
        </template>

        <template #op="{ row }">
          <t-space size="small">
            <t-button size="small" theme="warning" variant="outline" @click="copyLink(row)">复制链接</t-button>
            <t-button size="small" theme="primary" variant="outline" @click="openEdit(row)">编辑任务</t-button>
            <t-button size="small" theme="danger" variant="outline" @click="openData(row)">查看数据</t-button>
          </t-space>
        </template>
      </t-table>

      <t-pagination v-model:current="page" v-model:pageSize="size" :total="total" :disabled="loading"
        :page-size-options="[10, 20, 50]" @change="fetchList" />
    </t-space>

  </t-card>

  <!-- 任务信息编辑弹窗 -->
  <t-dialog v-model:visible="dialogVisible" attach="body" :z-index="5000" :header="isEdit ? '修改任务' : '新增任务'"
    :confirm-btn="{ content: isSubmitting ? '提交中...' : '确定', theme: 'primary', loading: isSubmitting }"
    :cancel-btn="{ content: '取消' }" @confirm="onSubmit">
    <t-form ref="formRef" :data="form" :rules="isEdit ? rulesEdit : rulesCreate" label-align="left" :label-width="100"
      :status-icon="true">
      <t-form-item v-if="isEdit" label="ID" name="id">
        <t-input :value="form.id" disabled />
      </t-form-item>
      <t-form-item label="名称" name="name">
        <t-input v-model="form.name" placeholder="请输入任务名称" clearable />
      </t-form-item>
      <t-form-item label="域名" name="domain">
        <t-select v-model="form.domain" placeholder="请选择域名" clearable>
          <t-option v-for="item in domainList" :key="item.url" :label="item.url" :value="item.url" />
        </t-select>
      </t-form-item>
      <t-form-item label="奖励类型" name="type">
        <t-radio-group v-model="form.type">
          <t-radio :value="1">链接</t-radio>
          <t-radio :value="2">文本</t-radio>
          <t-radio :value="3">卡密</t-radio>
        </t-radio-group>
      </t-form-item>
      <t-form-item label="奖励内容" name="award">
        <t-textarea v-model="form.award" placeholder="请输入奖励内容。卡密一行一个" clearable />
      </t-form-item>
      <t-form-item label="强点广告" name="click">
        <t-radio-group v-model="form.click">
          <t-radio :value="1">启用</t-radio>
          <t-radio :value="0">关闭</t-radio>
        </t-radio-group>
      </t-form-item>
      <t-form-item v-if="isEdit" label="状态" name="status">
        <t-radio-group v-model="form.status">
          <t-radio :value="1">启用</t-radio>
          <t-radio :value="0">禁用</t-radio>
        </t-radio-group>
      </t-form-item>
    </t-form>
  </t-dialog>

  <!-- 批量删除任务二次确认弹窗 -->
  <t-dialog v-model:visible="deleteConfirmDialog" theme="danger" header="警告" :cancel-btn="null"
    :confirm-btn="{ content: '确认删除', theme: 'danger' }" @confirm="onBatchDelete">
    {{ `确定删除选中的 ${selectedRowKeys.length} 个任务吗？` }}
  </t-dialog>
  <!-- 批量删除任务二次确认弹窗结束 -->
</template>

<script
  setup
  lang="ts"
>
  import { ref, reactive, onMounted } from 'vue';
  import router from '@/router';
  import { MessagePlugin } from 'tdesign-vue-next';
  import { getTaskList, createTask, updateTask, deleteTasks, updateTaskStatus, getDomainList } from '@/api/tasks';
  import type { TaskItem, TasksListResult, ListTasksParams, CreateTaskParams, UpdateTaskParams, UpdateTaskStatusParams, DeleteTasksParams, DomainItem, DomainResult } from '@/api/model/tasksModel';

  /* 表格配置 */
  const loading = ref(false);
  const list = ref<TaskItem[]>([]);
  const page = ref(1);
  const size = ref(10);
  const total = ref(0);

  const rowKey = 'id';
  const selectedRowKeys = ref<Array<string | number>>([]);

  const columns = [
    { colKey: 'row-select', type: 'multiple', width: 50 },
    { title: 'ID', colKey: 'id', width: 70 },
    { title: '名称', colKey: 'name', width: 140, ellipsis: true },
    { title: '域名', colKey: 'domain', minWidth: 160, ellipsis: true },
    { title: '奖励类型', colKey: 'type', width: 100 },
    { title: '奖励内容', colKey: 'award', minWidth: 260, ellipsis: true },
    { title: '创建时间', colKey: 'created_at', width: 200 },
    { title: '修改时间', colKey: 'updated_at', width: 200 },
    { title: '强点广告', colKey: 'click', width: 100 },
    { title: '状态', colKey: 'status', width: 80, fixed: 'right' },
    { title: '操作', colKey: 'op', width: 240, fixed: 'right' },
  ];

  /* 获取任务列表 */
  function fetchList() {
    loading.value = true;
    getTaskList({
      page: page.value,
      size: size.value
    } as ListTasksParams)
      .then((res: TasksListResult) => {
        list.value = res.list || [];
        total.value = res.total || 0;
        page.value = res.page || page.value;
        size.value = res.size || size.value;
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error('获取列表失败');
      })
      .finally(() => {
        loading.value = false;
      });
  }

  const domainList = ref<DomainItem[]>([])

  /* 获取域名列表 */
  function fetchDomainList() {
    getDomainList()
      .then((res: DomainResult) => {
        domainList.value = res.list;
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error('获取域名列表失败');
      });
  }

  /* 选择变化 */
  function onSelectChange(keys: Array<string | number>) {
    selectedRowKeys.value = keys;
  }

  onMounted(() => {
    fetchDomainList();
    fetchList();
  });

  /* 表单与弹窗 */
  const dialogVisible = ref(false);
  const isEdit = ref(false);
  const isSubmitting = ref(false);
  const formRef = ref();

  const initForm = () => ({
    id: 0,
    name: '',
    domain: '',
    type: 1 as 1 | 2,
    award: '',
    click: 1 as 0 | 1,
    status: 1 as 0 | 1,
  });

  const form = reactive<UpdateTaskParams>(initForm());

  /* 新增小程序表单验证规则 */
  const rulesCreate = {
    name: [{ required: true, message: '请输入任务名称', type: 'error', trigger: 'blur' }],
    domain: [{ required: true, message: '域名必选', type: 'error', trigger: 'blur' }, 
    { required: true, message: '域名必选', type: 'error', trigger: 'change' },],
    type: [{ required: true, message: '请选择奖励类型' }],
    award: [{ required: true, message: '请输入奖励内容', type: 'error', trigger: 'blur' }],
    click: [{ required: true, message: '是否开启强点广告' }],
  };

  /* 修改小程序表单验证规则 */
  const rulesEdit = {
    id: [{ required: true, message: 'ID异常', type: 'error', trigger: 'blur' }],
    ...rulesCreate,
    status: [{ required: true, message: '请选择状态' }],
  };

  /* 点击创建按钮 */
  function openCreate() {
    Object.assign(form, initForm());
    isEdit.value = false;
    dialogVisible.value = true;
  }

  /* 点击编辑按钮 */
  function openEdit(row: TaskItem) {
    Object.assign(form, {
      id: row.id,
      name: row.name,
      domain: row.domain,
      type: row.type as 1 | 2,
      award: row.award,
      click: row.click as 0 | 1,
      status: row.status as 0 | 1,
    });
    isEdit.value = true;
    dialogVisible.value = true;
  }

  /* 提交表单 */
  function onSubmit() {
    if (!formRef.value) return;
    isSubmitting.value = true;
    formRef.value
      .validate()
      .then(() => {
        const payload: any = {
          name: form.name,
          domain: form.domain,
          type: form.type,
          award: form.award,
          click: form.click,
        };
        const req = isEdit.value ? updateTask({ id: form.id, ...payload, status: form.status } as UpdateTaskParams) : createTask(payload as CreateTaskParams);
        return req
          .then(() => {
            MessagePlugin.success(isEdit.value ? '修改成功' : '新增成功');
            dialogVisible.value = false;
            fetchList();
          })
          .catch((err) => {
            console.error(err);
            MessagePlugin.error(isEdit.value ? '修改失败' : '新增失败');
          });
      })
      .catch((err: any) => {
        console.error(err);
        MessagePlugin.warning('请完善表单信息');
      })
      .finally(() => {
        isSubmitting.value = false;
      });
  }

  /* 复制链接 */
  function copyLink(row: TaskItem) {
    const link = row.domain +  '?id=' + row.id;
    navigator.clipboard.writeText(link)
      .then(() => {
        MessagePlugin.success('链接已复制');
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error('复制链接失败');
      });
  }

  /* 点击详情按钮 */
  function openData(row: TaskItem) {
    router.push({
      path: `/tasks/data/${row.id}`,
      query: {
        ...row
      }
    });
  }

  /* 批量删除任务二次确认弹窗 */
  const deleteConfirmDialog = ref(false);

  /* 批量删除任务 */
  function onBatchDelete() {
    if (selectedRowKeys.value.length === 0) return;
    deleteTasks({ ids: selectedRowKeys.value as number[] } as DeleteTasksParams)
      .then(() => {
        MessagePlugin.success('批量删除成功');
        selectedRowKeys.value = [];
        fetchList();
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error('批量删除失败');
      })
      .finally(() => {
        deleteConfirmDialog.value = false;
      });
  }

  /* 任务状态切换 */
  function onToggleStatus(row: TaskItem, checked: boolean) {
    const target = checked ? 1 : 0;
    updateTaskStatus({ id: row.id, status: target } as UpdateTaskStatusParams)
      .then(() => {
        row.status = target;
        MessagePlugin.success(target === 1 ? '已启用' : '已禁用');
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error('状态更新失败');
      });
  }
</script>

<style scoped></style>