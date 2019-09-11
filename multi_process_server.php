<?php 

class TcpServer{
    const MAX_PROCESS = 3;//最大进程数
    private $pids = []; //存储子进程pid
    private $socket;

    public function __construct(){
        $pid = pcntl_fork();
        if($pid <0){
            exit("fork fail\n");
        }elseif($pid > 0){
            exit;//父进程退出
        } else{
            //子进程操作
            
            // 从当前终端分离
            if (posix_setsid() == -1) {
                die("could not detach from terminal");
            }

            //将 PHP 的 umask 设定为 mask & 0777 并返回原来的 umask。
            //无参数调用 umask() 会返回当前的 umask，有参数则返回原来的 umask
            umask(0);

            //返回子进程的主进程id
            $id = getmypid();   
            echo time()." Master process, pid {$id}\n"; 

            //创建tcp server
            $this->socket = stream_socket_server("tcp://0.0.0.0:9201", $errno, $errstr);
            if(!$this->socket) exit("start server err: $errstr --- $errno");
        }
    }

    public function run(){
        for($i=0; $i<self::MAX_PROCESS;$i++){
            $this->start_worker_process();
        }

        echo "waiting client...\n";

        //Master进程等待子进程退出，必须是死循环
        while(1){
            foreach($this->pids as $k=>$pid){
                if($pid){
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if ( $res == -1 || $res > 0 ){
                        echo time()." Worker process $pid exit, will start new... \n";
                        $this->start_worker_process();
                        unset($this->pids[$k]);
                    }
                }
            }
            sleep(1);//让出1s时间给CPU
        }
    }

    /**
     * 创建worker进程，接受客户端连接
     */
    private function start_worker_process(){
        //创建worker进程
        $pid = pcntl_fork();
        if($pid <0){
            exit("fork fail\n");
        }elseif($pid > 0){
            //父进程操作
            $this->pids[] = $pid;
            // exit; //此处不可退出，否则Master进程就退出了
        }else{
            //子进程操作
            $this->acceptClient();
        }
    }

    private function acceptClient(){
        //子进程一直等待客户端连接，不能退出
        while(1){
            $conn = stream_socket_accept($this->socket, -1);
            if($this->onConnect) call_user_func($this->onConnect, $conn); //回调连接事件

            //开始循环读取消息
            $recv = ''; //实际收到消息
            $buffer = ''; //缓冲消息
            while(1){
                $buffer = fread($conn, 20);

                //没有收到正常消息
                if($buffer === false || $buffer === ''){
                    if($this->onClose) call_user_func($this->onClose, $conn); //回调断开连接事件
                    break;//结束读取消息，等待下一个客户端连接
                }

                $pos = strpos($buffer, "\n"); //消息结束符
                if($pos === false){
                    $recv .= $buffer;                            
                }else{
                    $recv .= trim(substr($buffer, 0, $pos+1));

                    if($this->onMessage) call_user_func($this->onMessage, $conn, $recv); //回调收到消息事件

                    //客户端强制关闭连接
                    if($recv == "quit"){
                        echo "client close conn\n";
                        fclose($conn);
                        break;
                    }

                    $recv = ''; //清空消息，准备下一次接收
                }
            }
        }
    }

    function __destruct() {
        @fclose($this->socket);
    }
}

$server =  new TcpServer();

$server->onConnect = function($conn){
    echo "onConnect -- accepted " . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"conn success\n");
};

$server->onMessage = function($conn,$msg){
    echo "onMessage --" . $msg . "\n";
    fwrite($conn,"received ".$msg."\n");
};

$server->onClose = function($conn){
    echo "onClose --" . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"onClose "."\n");
};

$server->run();

/*
    此时服务端已经变成守护进程了。新开终端，我们使用ps命令查看进程：
    $ ps -ef | grep php
    可以看到4个进程：1个主进程，3个子进程。使用kill命令结束子进程，主进程会重新拉起一个新的子进程。
 */