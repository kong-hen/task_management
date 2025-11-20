<template>
  <div class="login-wrapper">
    <header class="login-header">
      <full-logo-icon class="logo" />

      <div class="operations-container">
        <t-button theme="default" shape="square" variant="text" @click="navToGitHub">
          <t-icon name="logo-github" class="icon" />
        </t-button>
        <t-button theme="default" shape="square" variant="text" @click="navToHelper">
          <t-icon name="help-circle" class="icon" />
        </t-button>
      </div>
    </header>

    <div class="login-container">
      <div class="title-container">
        <h1 class="title margin-no">登录到</h1>
        <h1 class="title">空痕流量变现系统</h1>
        <div class="sub-title">
          <p class="tip">使用小程序进行流量变现</p>
        </div>
      </div>

      <!-- 登录表单 -->
      <t-form ref="form" class="item-container" :class="[`login-${type}`]" :data="formData" :rules="FORM_RULES"
        label-width="0" @submit="onSubmit">
        <template v-if="type === 'password'">
          <t-form-item name="username">
            <t-input v-model="formData.username" size="large" placeholder="请输入账号">
              <template #prefix-icon>
                <t-icon name="user" />
              </template>
            </t-input>
          </t-form-item>

          <t-form-item name="password">
            <t-input v-model="formData.password" size="large" :type="showPsw ? 'text' : 'password'" clearable
              placeholder="请输入密码">
              <template #prefix-icon>
                <t-icon name="lock-on" />
              </template>
              <template #suffix-icon>
                <t-icon :name="showPsw ? 'browse' : 'browse-off'" @click="showPsw = !showPsw" />
              </template>
            </t-input>
          </t-form-item>
        </template>

        <!-- 登录按钮 -->
        <t-form-item class="btn-container">
          <t-button block size="large" type="submit">登录</t-button>
        </t-form-item>

      </t-form>

    </div>
    <footer class="copyright">Copyright @ 2025 KongHen. All Rights Reserved</footer>
  </div>
</template>
<script
  setup
  lang="ts"
>
  import FullLogoIcon from '@/assets/assets-logo-full.svg'
  import type { FormInstanceFunctions, FormRule, SubmitContext } from 'tdesign-vue-next';
  import { MessagePlugin, type FormProps } from 'tdesign-vue-next';
  import { ref } from 'vue';
  import router from '@/router';
  import { useRoute } from 'vue-router';
  const route = useRoute();

  import { useUserStore } from '@/store';

  const userStore = useUserStore();

  const navToGitHub = () => {
    window.open('https://github.com/kong-hen/task_management');
  };

  const navToHelper = () => {
    window.open('https://www.khkj6.com/archives/task-m-s.html');
  };

  const initForm = {
    username: '',
    password: ''
  };

  const FORM_RULES: Record<string, FormRule[]> = {
    username: [{ required: true, message: '账号不能为空', type: 'error' }],
    password: [{ required: true, message: '密码不能为空', type: 'error' }],
  };

  const type = ref('password');

  const form = ref<FormInstanceFunctions>();
  const formData = ref({ ...initForm });
  const showPsw = ref(false);

  const onSubmit: FormProps['onSubmit'] = async ({ validateResult }) => {
    if (validateResult === true) {
      try {
        await userStore.login(formData.value);

        MessagePlugin.success('登录成功');
        const redirect = route.query.redirect as string;
        const redirectUrl = redirect ? decodeURIComponent(redirect) : '/';
        router.replace(redirectUrl);
      } catch (e) {
        console.log(e);
        MessagePlugin.error('登录失败');
      }
    }
  };
</script>

<style
  lang="less"
  scoped
>
  .login-wrapper {
    background-color: white;
    background-image: url('@/assets/assets-login-bg-white.png');
  }

  .login-wrapper {
    height: 100vh;
    display: flex;
    flex-direction: column;
    background-size: cover;
    background-position: 100%;
    position: relative;
  }

  .login-header {
    padding: 0 var(--td-comp-paddingLR-xl);
    display: flex;
    justify-content: space-between;
    align-items: center;
    backdrop-filter: blur(10px);
    color: var(--td-text-color-primary);
    height: var(--td-comp-size-xxxl);

    .logo {
      width: 178px;
      height: var(--td-comp-size-s);
    }

    .operations-container {
      display: flex;
      align-items: center;

      .t-button {
        margin-left: var(--td-comp-margin-l);
      }
    }
  }

  .login-container {
    position: absolute;
    top: 22%;
    left: 5%;
    min-height: 500px;
  }

  .title-container {
    .title {
      font: var(--td-font-headline-large);
      color: var(--td-text-color-primary);
      margin-top: var(--td-comp-margin-xs);

      &.margin-no {
        margin-top: 0;
      }
    }

    .sub-title {
      margin-top: var(--td-comp-margin-xxl);

      .tip {
        display: inline-block;
        margin-right: var(--td-comp-margin-s);
        font: var(--td-font-body-medium);

        &:first-child {
          color: var(--td-text-color-secondary);
        }

        &:last-child {
          color: var(--td-text-color-primary);
          cursor: pointer;
        }
      }
    }
  }

  .item-container {
    width: 400px;
    margin-top: var(--td-comp-margin-xxxxl);

    .check-container {
      display: flex;
      align-items: center;

      &.remember-pwd {
        margin-bottom: var(--td-comp-margin-l);
        justify-content: space-between;
      }

      span {
        color: var(--td-brand-color);

        &:hover {
          cursor: pointer;
        }
      }
    }

    .btn-container {
      margin-top: var(--td-comp-margin-xxxxl);
    }
  }

  .switch-container {
    margin-top: var(--td-comp-margin-xxl);

    .tip {
      font: var(--td-font-body-medium);
      color: var(--td-brand-color);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      margin-right: var(--td-comp-margin-l);

      &:last-child {
        &::after {
          display: none;
        }
      }

      &::after {
        content: '';
        display: block;
        width: 1px;
        height: 12px;
        background: var(--td-component-stroke);
        margin-left: var(--td-comp-margin-l);
      }
    }
  }

  .check-container {
    font: var(--td-font-body-medium);
    color: var(--td-text-color-secondary);

    .tip {
      float: right;
      font: var(--td-font-body-medium);
      color: var(--td-brand-color);
    }
  }

  .copyright {
    font: var(--td-font-body-medium);
    position: absolute;
    left: 5%;
    bottom: 64px;
    color: var(--td-text-color-secondary);
  }

  @media screen and (height <=700px) {
    .copyright {
      display: none;
    }
  }
</style>