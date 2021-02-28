## 前言

就在下午我发了个动态 "[尝试给一个PHP平台的RSS聚合器添加汉化包][1]"在晚上收到一些人的留言希望搞好能分享，我明天就要出发了估计没空了就想着反正汉化的差不多了（是大概能看懂了至少 哈哈）赶紧写文章发布了！！

>很多地方没汉化好，或者没汉化。一是因为谷歌翻译中文和我大概理解的意思,二是时间太赶了每天出去了还要帮人家装显卡。见谅啊！

![86799-m433h4f7a79.png](https://xiamuyourenzhang.cn/usr/uploads/2021/02/2508788597.png)

## 声明

原作者版本：https://github.com/timovn/C-LX-RSS
汉化版本：https://github.com/xiamuguizhi/php-rss-feed

## 下载

Github: https://github.com/xiamuguizhi/php-rss-feed

本站下载:[https://xiamuyourenzhang.cn/usr/uploads/2021/02/1082307393.zip][2]

## 界面

![19446-rml0ksla23.png](https://xiamuyourenzhang.cn/usr/uploads/2021/02/4046775019.png)

## 特征

C-LX RSS是另一个基于Web的单用户RSS阅读器。它带有OPML和CronJobs支持。

## 要求

### 必需组件

 * PHP> 5.5和具有PDO支持的SQLite（或具有PDO支持的MySQL）
 * 现代的网络浏览器（桌面或移动）
 * 最少1 Mo服务器端磁盘空间（更多用户数据=需要更多空间）
 * PHP GD模块（用于网站图标）；
 * PHP cURL模块
 * PHP LibXML模块

## 安装简介

 *解压缩下载的存档文件
 *将文件夹上传到您的站点（例如：到`http://example.com/feed-reader/`）
 *使用浏览器转到您的站点
 *遵循几个步骤

## 安装过程

建议安装在子目录哈，不占用新的空间和域名  哈哈

1. 选择中文

![1.png][3]

2. 设置账户密码

![2.png][4]

3. SQLite 不需要另外链接数据库 点击ok就行

![3.png][5]

3.2 mysql 不用我在讲了吧  没时间详细汉化

```
数据库账号
数据库密码
数据库名
数据库地址默认就行
```
![3.1.png][6]

4. 输入前面设置账号密码登录就行

![4.png][7]

就是这么简单！

## 添加订阅

1. 填入订阅地址

![53024-d29w3a7j4w.png](https://xiamuyourenzhang.cn/usr/uploads/2021/02/3470710496.png)

2.如果要归类 填写 `名字` 不归类留 `空`

![97456-komgvdz299.png](https://xiamuyourenzhang.cn/usr/uploads/2021/02/749282364.png)

3.添加后刷新一下获取订阅内容就能看到了

![88231-o8pqdrgciq.png](https://xiamuyourenzhang.cn/usr/uploads/2021/02/2223296624.png)

![59401-tqi5ln381d.png](https://xiamuyourenzhang.cn/usr/uploads/2021/02/1314449063.png)

## 最后

真的文章写比较赶，因为我想洗澡了！！啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊啊！！！

  [1]: https://xiamuyourenzhang.cn/show/36.html
  [2]: https://xiamuyourenzhang.cn/usr/uploads/2021/02/1082307393.zip
  [3]: https://xiamuyourenzhang.cn/usr/uploads/2021/02/3841541653.png
  [4]: https://xiamuyourenzhang.cn/usr/uploads/2021/02/3128334573.png
  [5]: https://xiamuyourenzhang.cn/usr/uploads/2021/02/4013481805.png
  [6]: https://xiamuyourenzhang.cn/usr/uploads/2021/02/2188348623.png
  [7]: https://xiamuyourenzhang.cn/usr/uploads/2021/02/3999503395.png
