<template>
  <t-card bordered>
    <t-typography-title level="h4">域名管理</t-typography-title>
    <t-space direction="vertical" size="small" class="w-100">
      <t-space :size="12">
        <t-button theme="primary" @click="openCreate">新增</t-button>
        <t-button theme="danger" :disabled="selectedRowKeys.length === 0"
          @click="deleteConfirmDialog = true">批量删除</t-button>
      </t-space>

      <t-table :data="list" :columns="columns" :row-key="rowKey" :loading="loading" table-layout="fixed" hover
        size="medium" :selected-row-keys="selectedRowKeys" @select-change="onSelectChange">
        <template #url="{ row }">
          <span class="truncate max-w-[320px]">{{ row.url }}</span>
        </template>

        <template #status="{ row }">
          <t-switch size="small" :value="row.status === 1" @change="(val: boolean) => onToggleStatus(row, val)" />
        </template>

        <template #op="{ row }">
          <t-space size="small">
            <t-button size="small" theme="primary" variant="outline" @click="openEdit(row)">编辑</t-button>
            <t-popconfirm content="确认删除该域名吗？" @confirm="onDelete(row.id)">
              <t-button size="small" theme="danger" variant="outline">删除</t-button>
            </t-popconfirm>
          </t-space>
        </template>
      </t-table>

      <t-pagination v-model:current="page" v-model:pageSize="size" :total="total" :disabled="loading"
        :page-size-options="[10, 20, 50]" @change="fetchList" />
    </t-space>
  </t-card>

  <!-- 域名信息编辑弹窗 -->
  <t-dialog v-model:visible="dialogVisible" attach="body" :z-index="5000" :header="isEdit ? '修改域名' : '新增域名'"
    :confirm-btn="{ content: isSubmitting ? '提交中...' : '确定', theme: 'primary', loading: isSubmitting }"
    :cancel-btn="{ content: '取消' }" @confirm="formRef?.submit()">
    <t-form ref="formRef" :data="form" :rules="isEdit ? rulesEdit : rulesCreate" label-align="left" :label-width="100" :status-icon="true" @submit="onSubmit">
      <t-form-item v-if="isEdit" label="ID" name="id">
        <t-input :value="form.id" disabled />
      </t-form-item>
      <t-form-item label="域名名称" name="name">
        <t-input v-model="form.name" placeholder="请输入域名名称" clearable />
      </t-form-item>
      <t-form-item label="URL" name="url">
        <t-input v-model="form.url" placeholder="例如：https://example.com" clearable />
      </t-form-item>
      <t-form-item v-if="isEdit" label="状态" name="status">
        <t-radio-group v-model="form.status">
          <t-radio :value="1">启用</t-radio>
          <t-radio :value="0">禁用</t-radio>
        </t-radio-group>
      </t-form-item>
    </t-form>
  </t-dialog>
  <!-- 域名信息编辑弹窗结束 -->

  <!-- 批量删除域名二次确认 -->
  <t-dialog v-model:visible="deleteConfirmDialog" theme="danger" header="警告" :cancel-btn="null"
    :confirm-btn="{ content: '确认删除', theme: 'danger' }" @confirm="onBatchDelete">
    {{ `确定删除选中的 ${selectedRowKeys.length} 个域名吗？` }}
  </t-dialog>
  <!-- 批量删除域名二次确认结束 -->

</template>

<script
  setup
  lang="ts"
>
  import { ref, reactive, onMounted } from 'vue';
  import { MessagePlugin, type FormProps } from 'tdesign-vue-next';
  import { getDomainList, createDomain, deleteDomainsByIds, updateDomainStatus, updateDomain } from '@/api/domains';
  import type { DomainItem, DomainListResult, ListDomainParams, CreateDomainParams, UpdateDomainParams, UpdateDomainStatusParams, DeleteDomainsParams } from '@/api/model/domainsModel';

  /* 表格配置 */
  const loading = ref(false);
  const list = ref<DomainItem[]>([]);
  const page = ref(1);
  const size = ref(10);
  const total = ref(0);

  const rowKey = 'id';
  const selectedRowKeys = ref<Array<string | number>>([]);

  const columns = [
    { colKey: 'row-select', type: 'multiple', width: 50, fixed: 'left' },
    { title: 'ID', colKey: 'id', width: 70, fixed: 'left' },
    { title: '名称', colKey: 'name', width: 140, fixed: 'left', ellipsis: true },
    { title: 'URL', colKey: 'url', minWidth: 320, ellipsis: true },
    { title: '创建时间', colKey: 'created_at', width: 200 },
    { title: '修改时间', colKey: 'updated_at', width: 200 },
    { title: '状态', colKey: 'status', width: 80, fixed: 'right' },
    { title: '操作', colKey: 'op', width: 140, fixed: 'right' },
  ];

  /* 获取域名列表 */
  const fetchList = () => {
    loading.value = true;
    getDomainList({ page: page.value, size: size.value } as ListDomainParams)
      .then((res: DomainListResult) => {
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

  /* 监听选中行变化 */
  const onSelectChange = (keys: Array<string | number>) => {
    selectedRowKeys.value = keys;
  }

  onMounted(() => {
    fetchList();
  });

  /* 新增弹窗 */
  const dialogVisible = ref(false);
  const isSubmitting = ref(false);
  const formRef = ref();
  const isEdit = ref<boolean>(false);

  const initForm = () => ({
    id: 0,
    name: '',
    url: '',
    status: 1 as 0 | 1,
  });

  const form = reactive<UpdateDomainParams>(initForm());

  /* 新增域名表单验证规则 */
  const rulesCreate: FormProps['rules'] = {
    name: [{ required: true, message: '请输入域名名称', type: 'error', trigger: 'blur' }],
    url: [
      { required: true, message: '请输入URL', type: 'error' },
      {
        url: {
          protocols: ['http', 'https'],
          require_protocol: true,
        },
        message: 'URL格式不正确，需以 http/https 开头',
      },
    ],
  };

  /* 修改域名表单验证规则 */
  const rulesEdit: FormProps['rules'] = {
    id: [{ required: true, message: 'ID异常', type: 'error', trigger: 'blur' }],
    ...rulesCreate,
    status: [{ required: true, message: '请选择状态' }],
  };

  /* 点击创建按钮 */
  const openCreate = () => {
    isEdit.value = false
    Object.assign(form, initForm());
    dialogVisible.value = true;
  }

  /* 点击编辑按钮 */
  const openEdit = (row: DomainItem) => {
    isEdit.value = true
    Object.assign(form, {
      id: row.id,
      name: row.name,
      url: row.url,
      status: row.status === 1 ? 1 : 0,
    });
    dialogVisible.value = true;
  }

  /* 提交表单 */
  const onSubmit: FormProps['onSubmit'] = ({ validateResult, firstError }) => {
    if (!formRef.value) return;
    if (validateResult !== true) {
      console.error(firstError);
      MessagePlugin.warning(firstError || '请完善表单信息');
      return;
    }
    isSubmitting.value = true;
    const payload: CreateDomainParams = {
      name: form.name,
      url: form.url,
    };
    const req = isEdit.value ? updateDomain({ id: form.id, ...payload, status: form.status } as UpdateDomainParams) : createDomain(payload);
    return req
      .then(() => {
        MessagePlugin.success(isEdit.value ? '修改成功' : '新增成功');
        dialogVisible.value = false;
        fetchList();
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error(isEdit.value ? '修改失败' : '新增失败');
      })
      .finally(() => {
        isSubmitting.value = false;
      });
  }

  /* 删除单个域名 */
  const onDelete = (id: number) => {
    deleteDomainsByIds({ ids: [id] } as DeleteDomainsParams)
      .then(() => {
        MessagePlugin.success('删除成功');
        fetchList();
      })
      .catch((err) => {
        console.error(err);
        MessagePlugin.error('删除失败');
      });
  }

  /* 批量删除域名二次确认弹窗 */
  const deleteConfirmDialog = ref(false);

  /* 批量删除 */
  const onBatchDelete = () => {
    if (selectedRowKeys.value.length === 0) return;
    deleteDomainsByIds({ ids: selectedRowKeys.value as number[] } as DeleteDomainsParams)
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

  /* 域名状态切换 */
  const onToggleStatus = (row: DomainItem, checked: boolean) => {
    const target = checked ? 1 : 0;
    updateDomainStatus({ id: row.id, status: target } as UpdateDomainStatusParams)
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