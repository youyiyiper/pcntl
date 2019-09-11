### php 实现多进程 
```
php的多进程处理依赖于pcntl扩展，通过pcntl_fork创建子进程来进行并行处理。

我们通过pcntl_fork来创建子进程，使用pcntl_wait和pcntl_waitpid来回收子进程。
子进程退出后，父进程没有及时回收，就会产生僵尸进程

pcntl_wait等同于以pid为-1调用pcntl_waitpid函数。
pcntl_waitpid函数可以等待指定pid的进程。
```

### Posix常用函数
```
posix_kill

向指定pid进程发送信号。成功时返回 TRUE ， 或者在失败时返回 FALSE 。

bool posix_kill ( int $pid , int $sig )
$sig=0，可以检测进程是否存在，不会发送信号。

示例：

//向当前进程发送SIGUSR1信号
posix_kill ( posix_getpid (),  SIGUSR1 );
注：通过 kill -l 可以看到Linux下所有的信号常量。

posix_getpid 返回当前进程id。

posix_getppid 返回父进程id。

posix_setsid设置新会话组长，脱离终端。成功时返回session id，失败返回 -1。写守护进程（Daemon） 用到该函数。下面引用Workerman源代码里的一段示例：

function daemonize(){
    umask(0);
    $pid = pcntl_fork();
    if (-1 === $pid) {
        die('fork fail');
    } elseif ($pid > 0) {
        exit(0);
    }
    
    if (-1 === posix_setsid()) {
        die("setsid fail");
    }
    
    // Fork again avoid SVR4 system regain the control of terminal.
    $pid = pcntl_fork();
    if (-1 === $pid) {
        die("fork fail");
    } elseif (0 !== $pid) {
        exit(0);
    }
}
如果程序需要以守护进程的方式执行，在业务代码之前调用该函数即可。
```

### 进程池
```
什么是进程池? 其实是很简单的概念，就是预先创建一组子进程,当有新任务来时,系统通过调配该组进程中的某个子进程完成此任务。

前面几节的示例里我们都是使用这种方式，预先创建好进程，而不是动态创建。

引入《Linux高性能服务器编程》的一段话，描述动态创建进程的缺点：

动态创建进程（或线程）比较耗费时间，这将导致较慢的客户响应。
动态创建的子进程通常只用来为一个客户服务，这样导致了系统上产生大量的细微进程（或线程）。进程和线程间的切换将消耗大量CPU时间。
动态创建的子进程是当前进程的完整映像，当前进程必须谨慎的管理其分配的文件描述符和堆内存等系统资源，否则子进程可能复制这些资源，从而使系统的可用资源急剧下降，进而影响服务器的性能。
所以任何时候，建议预先创建好进程，也就是使用进程池的方式实现。
```