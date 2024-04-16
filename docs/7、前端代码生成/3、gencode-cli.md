# 前端代码生成，兼容react+ts、uniapp+vue3+ts 项目

## npm install

```bash
 npm install nichozuo-gencode-cli
```

## 项目根目录下，新建：gencode.json
```json
{
  "url": "http://0.0.0.0:8000/api/docs/openapi",
  "module": "Admin",
  "outPath": "./src/gen/",
  "apis": {
    "firstLine": "import { MyResponseType } from '@/common';\nimport { request } from '@umijs/max';"
  }
}
```
- url：openapi文档地址
- module：生成的模块名称
- outPath：生成的代码路径
- apis：生成的api的代码的第一行

## 生成的文件片段

- Apis.ts
```ts
import { MyResponseType } from '@/common';
import { request } from '@umijs/max';

export const Apis = {
  Companies: {
    List(data?: ApiTypes.Companies.List): Promise<MyResponseType> {
      return request('Admin/Companies/list', { data });
    },
    Store(data: ApiTypes.Companies.Store): Promise<MyResponseType> {
      return request('Admin/Companies/store', { data });
    },
    Update(data: ApiTypes.Companies.Update): Promise<MyResponseType> {
      return request('Admin/Companies/update', { data });
    },
    Show(data: ApiTypes.Companies.Show): Promise<MyResponseType> {
      return request('Admin/Companies/show', { data });
    },
    Delete(data: ApiTypes.Companies.Delete): Promise<MyResponseType> {
      return request('Admin/Companies/delete', { data });
    },
  },
}
```

- ApiTypes.ts
```ts
declare namespace ApiTypes {
  namespace Companies {
    type List = {
      "name"?: string; // 模糊搜索：名称 
    };
    type Store = {
      "name": string; // 公司名称 
    };
    type Update = {
      "id": number; // id 
      "name": string; // 公司名称 
    };
    type Show = {
      "id": number; // id 
    };
    type Delete = {
      "id": number; // id 
    };
  }
}
```

- Enums.ts
```ts
// 状态
export const CompanyAdminsStatusEnum= {
  'Enabled': {"text":"已启用","color":"#52c41a","value":"Enabled"}, 
  'UnderReview': {"text":"审核中","color":"#faad14","value":"UnderReview"}, 
  'Disabled': {"text":"已禁用","color":"#f5222d","value":"Disabled"}, 
};

// 系统模块
export const SysModuleEnum= {
  'Admin': {"text":"管理员","color":"#cf1322","value":"Admin"}, 
  'Company': {"text":"机构","color":"#389e0d","value":"Company"}, 
  'Customer': {"text":"客户","color":"#d4b106","value":"Customer"}, 
};

// 权限类型
export const SysPermissionsTypeEnum= {
  'Directory': {"text":"目录","color":"#1c9eff","value":"Directory"}, 
  'Page': {"text":"页面","color":"#f5222d","value":"Page"}, 
  'Button': {"text":"按钮","color":"#faad14","value":"Button"}, 
};
```

# 使用API
```ts
Apis.SysRoles.Store(values)
  .then(() => {
    props.reload?.();
    message.success(props.title + '成功');
    return true;
  })
  .catch(() => false)
```

# 使用枚举
```ts
columns={[
    {
        title: '性别',
        dataIndex: 'sex',
        valueEnum: SexEnum,
    },
    MyFormItems.EnumRadio({
      key: 'sex',
      title: '性别',
      valueEnum: SexEnum,
    }),
]}