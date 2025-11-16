<template>
  <t-card bordered>
    <t-typography-title level="h4">小程序管理</t-typography-title>
    <t-space direction="vertical" size="small" class="w-100">
      <t-space :size="12">
        <t-button theme="primary" @click="openCreate">新增</t-button>
        <t-button theme="danger" :disabled="selectedRowKeys.length === 0"
          @click="deleteConfirmDialog = true">批量删除</t-button>
      </t-space>
      <t-table :data="list" :columns="columns" :row-key="rowKey" :loading="loading" :hover="true" size="medium"
        :selected-row-keys="selectedRowKeys" @select-change="onSelectChange" tableLayout="fixed" style="width: 100%;">

        <template #type="{ row }">
          <t-tag :theme="row.type === 1 ? 'primary' : 'success'">{{ row.type === 1 ? '非个体' : '个体' }}</t-tag>
        </template>

        <template #status="{ row }">
          <t-switch size="small" :value="row.status === 1" @change="(val: boolean) => onToggleStatus(row, val)" />
        </template>

        <template #op="{ row }">
          <t-space size="small">
            <t-button size="small" theme="primary" variant="outline" @click="openEdit(row)">编辑</t-button>
            <t-popconfirm content="确认删除该小程序吗？" @confirm="onDelete(row.id)">
              <t-button size="small" theme="danger" variant="outline">删除</t-button>
            </t-popconfirm>
          </t-space>
        </template>
      </t-table>

      <t-pagination v-model:current="page" v-model:pageSize="size" :total="total" :disabled="loading"
        :page-size-options="[10, 20, 50]" @change="fetchList" />
    </t-space>

  </t-card>

  <!-- 小程序信息编辑弹窗 -->
  <t-dialog v-model:visible="dialogVisible" attach="body" :z-index="5000" :header="isEdit ? '修改小程序' : '新增小程序'"
    :confirm-btn="{ content: isSubmitting ? '提交中...' : '确定', theme: 'primary', loading: isSubmitting }"
    :cancel-btn="{ content: '取消' }" @confirm="onSubmit">
    <t-form ref="formRef" :data="form" :rules="isEdit ? rulesEdit : rulesCreate" label-align="left" :label-width="100" :status-icon="true">
      <t-form-item v-if="isEdit" label="ID" name="id">
        <t-input :value="form.id" disabled />
      </t-form-item>
      <t-form-item label="类型" name="type">
        <t-radio-group v-model="form.type">
          <t-radio :value="1">非个体</t-radio>
          <t-radio :value="0">个体</t-radio>
        </t-radio-group>
      </t-form-item>
      <t-form-item label="名称" name="name">
        <t-input v-model="form.name" placeholder="请输入小程序名称" clearable />
      </t-form-item>
      <t-form-item label="AppID" name="appid">
        <t-input v-model="form.appid" placeholder="请输入AppID" clearable />
      </t-form-item>
      <t-form-item v-show="form.type === 0" label="小程序密钥" help="个体小程序密钥必填" name="secret">
        <t-input v-model="form.secret" placeholder="请输入小程序密钥" clearable />
      </t-form-item>
      <t-form-item label="广告路径" name="path">
        <t-input v-model="form.path" placeholder="请输入广告路径" clearable />
      </t-form-item>
      <t-form-item v-if="isEdit" label="状态" name="status">
        <t-radio-group v-model="form.status">
          <t-radio :value="1">启用</t-radio>
          <t-radio :value="0">禁用</t-radio>
        </t-radio-group>
      </t-form-item>
    </t-form>
  </t-dialog>
  <!-- 小程序信息编辑弹窗结束 -->

  <!-- 批量删除小程序二次确认弹窗 -->
  <t-dialog v-model:visible="deleteConfirmDialog" theme="danger" header="警告" :cancel-btn="null"
    :confirm-btn="{ content: '确认删除', theme: 'danger' }" @confirm="onBatchDelete">
    {{ `确定删除选中的 ${selectedRowKeys.length} 个小程序吗？` }}
  </t-dialog>
  <!-- 批量删除小程序二次确认弹窗结束 -->

</template>

<script setup lang="ts">
  import { ref, reactive, onMounted } from 'vue';
  import { MessagePlugin } from 'tdesign-vue-next';
  import { getXcxList, createXcx, updateXcx, deleteXcx, updateXcxStatus } from '@/api/xcx';
  import type { XcxItem, XcxListResult, ListXcxParams, CreateXcxParams, UpdateXcxParams, UpdateXcxStatusParams, DeleteXcxParams } from '@/api/model/xcxModel';

  /* 表格配置 */
  const loading = ref(false);
  const list = ref<XcxItem[]>([]);
  const page = ref(1);
  const size = ref(10);
  const total = ref(0);

  const rowKey = 'id';
  const selectedRowKeys = ref<Array<string | number>>([]);

  const columns = [
    { colKey: 'row-select', type: 'multiple', width: 50, fixed: 'left' },
    { title: 'ID', colKey: 'id', width: 70, fixed: 'left' },
    { title: '类型', colKey: 'type', width: 100 },
    { title: '名称', colKey: 'name', width: 140, fixed: 'left', ellipsis: true },
    { title: 'AppID', colKey: 'appid', width: 200 },
    { title: '小程序密钥', colKey: 'secret', width: 200, ellipsis: true },
    { title: '广告路径', colKey: 'path', minWidth: 260, ellipsis: true },
    { title: '创建时间', colKey: 'created_at', width: 200 },
    { title: '修改时间', colKey: 'updated_at', width: 200 },
    { title: '状态', colKey: 'status', width: 80, fixed: 'right' },
    { title: '操作', colKey: 'op', width: 140, fixed: 'right' },
  ];

  /* 获取小程序列表 */
  function fetchList() {
    loading.value = true;
    getXcxList({ page: page.value, size: size.value } as ListXcxParams)
      .then((res: XcxListResult) => {
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

  /* 选择变化 */
  function onSelectChange(keys: Array<string | number>) {
    selectedRowKeys.value = keys;
  }

  onMounted(() => {
    fetchList();
  });

  /* 表单与弹窗 */
  const dialogVisible = ref(false);
  const isEdit = ref(false);
  const isSubmitting = ref(false);
  const formRef = ref();

  const initForm = () => ({
    id: 0,
    type: 1 as 0 | 1,
    name: '',
    appid: '',
    secret: '',
    path: 'pages/user/view',
    status: 1 as 0 | 1,
  });

  const form = reactive<UpdateXcxParams>(initForm());

  /* 新增小程序表单验证规则 */
  const rulesCreate = {
    type: [{ required: true, message: '请选择类型', type: 'error', trigger: 'change' }],
    name: [{ required: true, message: '请输入小程序名称', type: 'error', trigger: 'blur' }],
    appid: [{ required: true, message: '请输入AppID', type: 'error', trigger: 'blur' }],
    secret: [{ required: true, message: '个体小程序密钥必填', type: 'error', trigger: 'blur' },
    { validator: (val: string) => form.type===0 && val.length > 0, message: '个体小程序密钥必填' , type: 'error', trigger: 'blur' }, ],
    path: [{ required: true, message: '请输入广告路径', type: 'error', trigger: 'blur' }],
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
  function openEdit(row: XcxItem) {
    Object.assign(form, {
      id: row.id,
      type: row.type as 0 | 1,
      name: row.name,
      appid: row.appid,
      secret: row.secret,
      path: row.path,
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
          type: form.type,
          name: form.name,
          appid: form.appid,
          secret: form.secret,
          path: form.path,
        };
        const req = isEdit.value ? updateXcx({ id: form.id, ...payload, status: form.status } as UpdateXcxParams) : createXcx(payload as CreateXcxParams);
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

  /* 删除单个小程序 */
  function onDelete(id: number) {
    deleteXcx({ ids: [id] } as DeleteXcxParams)
      .then(() => {
        MessagePlugin.success('删除成功');
        fetchList();
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error('删除失败');
      });
  }

  /* 批量删除小程序二次确认弹窗 */
  const deleteConfirmDialog = ref(false);

  /* 批量删除小程序 */
  function onBatchDelete() {
    if (selectedRowKeys.value.length === 0) return;
    deleteXcx({ ids: selectedRowKeys.value as number[] } as DeleteXcxParams)
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

  /* 小程序状态切换 */
  function onToggleStatus(row: XcxItem, checked: boolean) {
    const target = checked ? 1 : 0;
    updateXcxStatus({ id: row.id, status: target } as UpdateXcxStatusParams)
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