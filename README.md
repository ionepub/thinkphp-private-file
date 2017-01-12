# ThinkPHP按权限访问文件

## ThinkPHP版本

测试版本：3.2.3，其他版本未测试

服务器为Apache，需配合 `.htaccess` 文件使用

## 功能

上传的图片、文档、文件可以按权限区分，如果有权限，则可以访问和查看，如果没有权限，则不能查看

如用户上传一张身份证照片，此照片只能被后台部分管理人员查看、审核，照片被保存到`/Upload/private/idcard/123.png`，此时非授权人员在访问`http://demo.com/Upload/private/idcard/123.png`将被拒绝。

此功能可用于下载站（要求会员登录后才能下载）或管理后台部分文件（仅管理员可见）等场景。

## 使用

这个repo中包含了主程序和配置、错误页示例等内容，可以按需添加到项目中。

### #1 复制主程序控制器

复制`/Application/Api/Controller/FileController.class.php`文件到项目相应位置，如果需要调整文件名、文件位置等，可以自行调整。

在控制器中，添加了`checkAuth()`方法判断权限，其中包含`checkLogin()`判断登录状态方法，登录默认使用 `session('userid')` 判断，需自行调整。

```
<?php
	/**
     * 检查用户权限
     * @return bool true表示有权限 false表示无权限
     */
    private function checkAuth(){
        if(!$this->checkLogin()){
            return false;
        }
        // todo: 检查用户是否有查看下载文件的权限
        return true;
    }

    /**
     * 检查用户登录状态
     * 使用session('userid')是否存在判断是否登录，请根据当前系统代码进行调整
     * @return bool true表示已登录
     */
    private function checkLogin(){
        return session('?userid') && session('userid') > 0 ? true : false;
    }
```

### #2 修改 `.htaccess` 文件

打开`.htaccess`文件，在`RewriteEngine On`下添加如下代码：

```
# 重定向private文件
RewriteCond %{REQUEST_URI} ^/Upload/private/.*$
RewriteRule ^(.*)$ /Api/File/check.html?file=$1 [L]
```

上面代码的含义为：

如果当前访问的Url地址为 `/Upload/private/`文件夹下的任意文件，都将这个请求重定向到`/Api/File/check.html`方法中，并将请求的文件Url地址以参数形式传递给check方法。

如果在上一步中对类名或模块有做更改，也要相应调整RewriteRule。

完整的`.htaccess`文件：

```
<IfModule mod_rewrite.c>
  Options +FollowSymlinks
  RewriteEngine On

  # 重定向private文件
  RewriteCond %{REQUEST_URI} ^/Upload/private/.*$
  RewriteRule ^(.*)$ /Api/File/check.html?file=$1 [L]

  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
</IfModule>
```

如果是Windows主机，可能会碰到不能正确rewrite的问题，修改rewrite规则，在index.php后加个?即可：

```
RewriteRule ^(.*)$ index.php?/$1 [QSA,PT,L]
```

### #3 修改配置文件（可选）

修改`/Application/Common/Conf/config.php`，添加下列项：

```
'TMPL_EXCEPTION_FILE'	=>	APP_PATH.'/Common/View/error.tpl', // 自定义的错误页面
```

`TMPL_EXCEPTION_FILE`项用于配置错误显示页面，在这个页面中，支持所有PHP原生语法，可以使用`$e['message']`获取错误信息。

```
<?=$e['message']?>
```

### #4 错误页模板（可选）

上面的配置项中自定义的错误页放在 `APP_PATH/Common/View/error.tpl` ，所以在相应位置，新增文件即可

## 须知

### 1、文件上传时就要分开，可公开的文件放在`/Upload/public/`文件夹下，需控制权限的文件放在`/Upload/private/`文件夹下

需控制权限的文件统一放在`/Upload/private/`文件夹下，这样就可以根据url地址，对private类型文件进行重定向，达到目的

### 2、图片文件统一转换成`png`格式

在上传图片类型的文件时，需将图片转换成`png`格式（否则将以普通文件形式，直接下载）。

如果需要调整成支持多种图片格式，只要修改输出头信息即可。如：

```
$fileExt = $this->getFileType($fileUrl);
if($fileExt == 'png'){
	// png图片
	@header("Content-type: image/png");
}elseif($fileExt == 'jpg' || $fileExt == 'jpeg'){
	// jpg图片
	@header("Content-type: image/jpeg");
}elseif($fileExt == 'gif'){
	// gif图片
	@header("Content-type: image/gif");
}elseif($fileExt == 'pdf'){
	// pdf文档
	@header('Content-type: application/pdf');
}elseif($fileExt == 'mp4'){
	// 视频
	@header('Content-type: video/mp4');
}else{
	// 其他格式文档文件 直接下载
	@header('Content-Type: application/octet-stream');
	@header('Content-Disposition: attachment;');
	@header('Content-Transfer-Encoding: binary');
}
```

### 3、视频格式仅支持mp4，如需要调整，参考上面图片部分

