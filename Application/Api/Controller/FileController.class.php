<?php
namespace Api\Controller;
use Think\Controller;

class FileController extends Controller {
    /**
     * 用于文件访问鉴权
     * 适用所有/Upload/private/.*的文件
     * 由.htaccess重定向过来
     * @author lan
     */
    public function check(){
        $fileUrl = isset($_GET['file']) ? $_GET['file'] : "";
        // 非正常请求
        if(!$fileUrl || strpos($fileUrl, '/private/') <= 0){
            $this->showError(403, "[80001] Access denied");
        }

        // 检查文件是否存在
        if( file_exists('./' . $fileUrl)){
            // 文件存在
            // 鉴权
            if(!$this->checkAuth()){
                // 无权限
                $this->showError(403, "[80003] Access denied");
            }
            $fileExt = $this->getFileType($fileUrl);
            if($fileExt == 'png'){
                // 图片
                @header("Content-type: image/png");
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
            @readfile('./' . $fileUrl);
            exit(0);
        }else{
            // 文件不存在
            $this->showError(404, "[80002] 404 File not found");
        }

    }

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
    
    /**
     * 显示错误信息
     */
    private function showError($httpCode = 404, $errorMessage = '404 File not found'){
        $httpCode = intval($httpCode);
        if($httpCode == 404){
            // 文件不存在
            @header("HTTP/1.1 404 Not Found");
        }elseif ($httpCode == 403) {
            // 无权限或非法访问请求
            @header('HTTP/1.1 403 Forbidden');
        }else{
            // 暂以200输出
        }

        $e['message'] = $errorMessage;

        // 检查是否有自定义的错误页
        if(C('TMPL_EXCEPTION_FILE') && C('TMPL_EXCEPTION_FILE') != ""){
            // 使用错误模板输出信息
            @include_once C('TMPL_EXCEPTION_FILE');
        }else{
            // 直接输出错误信息
            echo $e['message'];
        }
        exit(0);
    }

    /**
     * 获取文件类型(后缀)
     * @param string $filename 文件名称
     * @return string 文件类型
     */
    private function getFileType($filename) {
       return strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

}