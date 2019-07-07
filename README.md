## 后台分类嵌套模型插件说明

### 安装
```
app/console plugin:install CategoryTest
```
### 初始化
```
app/console category_test:init
```
### 创建临时数据
```
app/console category_test:create_test_data  {分类前缀} {编码前缀} {数据数量} {normal: 普通数据创建,test: 嵌套数据创建}
``` 

### 简单自测10000条数据
1. 插入时间：   
+ 普通分类：131秒
+ 嵌套分类：409秒

2. 后台页面打开展示时间（前端页面可看到首批数据的时间）
+ 普通分类： 58923ms
+ 嵌套分类： 4460ms

### 代码不完全改造，和原模型的比较
getCategoryStructureTree:
+ 普通分类：
    + getCategoryTree方法  
        + 1次全表查询
        + 1次foreach所有数据
    + 调用makeCategoryTree方法: 递归处理数据，至少一次所有数据循环
    + 调用TreeToolkit::makeTree：递归+多个循环

+ 嵌套分类：
    + 1次全表查询
    + 1次内嵌的foreach：n * n

        

