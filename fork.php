<?php 

/* //阻塞模式
    $pid = pcntl_fork();

    if($pid == -1){
        //错误处理：创建子进程失败时返回-1.
        die( 'could not fork' );
    }elseif($pid > 0){
        //父进程会得到子进程号，所以这里是父进程执行的逻辑
        $id = getmypid();   
        $id2 = posix_getpid();   
        echo "parent_process_id {$id}--{$id2}, child_process_id {$pid}\n";
        sleep(10);
        //等待子进程中断，防止子进程成为僵尸进程。
        pcntl_wait($status);   
    }else{
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑
        $id = getmypid();  
        $id2 = posix_getpid();  
        echo "child_process_id {$id}--{$id2}\n";   
        sleep(10); 
    }

    #ps aux|grep 'fork.php'
    #parent_process_id 8304, child_process_id 8305
    #child_process_id 8305
    
    #root       8304  0.6  0.9 277416 17508 pts/0    S+   16:34   0:00 php fork.php
    #root       8305  0.0  0.3 277416  6132 pts/0    S+   16:34   0:00 php fork.php

    //pcntl_fork创建了子进程，父进程和子进程都继续向下执行，而不同是父进程会获取子进程的$pid也就是$pid不为零。而子进程会获取$pid为零。通过if else语句判断$pid我们就可以在指定位置写上不同的逻辑代码。
    //该例里父进程还没有来得及等子进程运行完毕就自动退出了，子进程由 init进程接管。通过 ps -ef | grep php 看到子进程还在运行
*/

/*  //阻塞模式
    define('FORK_NUMS', 5);
    $pids = array();
     
    //我们创建5个子进程
    for($i = 0; $i < FORK_NUMS; ++$i) {
        $pids[$i] = pcntl_fork();
        if($pids[$i] == -1) {
            die('fork error');
        } else if ($pids[$i]) {
            $id = posix_getpid();   
            echo "我是父进程,我的进程id是 {$id}\n";
            pcntl_wait($status);
        } else {
            echo "进程ID:".getmypid() , " {$i} \r\n";
            echo "父进程ID: ", posix_getppid(), " 进程ID : ", posix_getpid(), " {$i} \r\n\n";
            exit;
        }
    }
    //我们通过for循环fork出5个子进程，父进程会阻塞着等待子进程退出，然后创建下一个子进程。(*在子进程中，需通过exit来退出，不然会产生递归多进程，父进程中不需要exit，不然会中断多进程。)

    // 我是父进程,我的进程id是 9486
    // 进程ID:9487 0 
    // 父进程ID: 9486 进程ID : 9487 0 

    // 我是父进程,我的进程id是 9486
    // 进程ID:9488 1 
    // 父进程ID: 9486 进程ID : 9488 1 

    // 我是父进程,我的进程id是 9486
    // 进程ID:9489 2 
    // 父进程ID: 9486 进程ID : 9489 2 

    // 我是父进程,我的进程id是 9486
    // 进程ID:9490 3 
    // 父进程ID: 9486 进程ID : 9490 3 

    // 我是父进程,我的进程id是 9486
    // 进程ID:9491 4 
    // 父进程ID: 9486 进程ID : 9491 4
*/

/*
    //阻塞模式
    //紧接着上面的例子，如果想等子进程运行结束后父进程再退出，该怎么办？那就用到pcntl_wait了。
    //此时再次运行程序，父进程就会一直等待子进程运行结束然后退出。
   
    $pid = pcntl_fork();
    if($pid == -1){
        //错误处理：创建子进程失败时返回-1.
        die( 'could not fork' );
    }elseif($pid){
        //父进程会得到子进程号，所以这里是父进程执行的逻辑
        $id = getmypid();   
        echo "parent_process_id {$id}, child_process_id {$pid}\n";
        //会挂起当前进程，直到子进程退出，如果子进程在调用此函数之前就已退出，此函数会立刻返回。子进程使用的资源将被释放。
        pcntl_wait($status);   
        echo "parent_process_finish\n"; 
    }else{
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑
        $id = getmypid();   
        echo "child_process_id {$id}\n";  
        echo "10\n";  
        sleep(10); 
    }

    // root       9952  1.0  0.9 277416 17512 pts/0    S+   16:46   0:00 php fork.php
    // root       9954  0.0  0.3 277416  6132 pts/0    S+   16:46   0:00 php fork.php


    // parent_process_id 9952, child_process_id 9954
    // child_process_id 9954
    // 10
    // parent_process_finish   #等待子进程全部退出后 父进程才会结束退出
*/

/*
    //阻塞模式
    define('FORK_NUMS', 3);
    $pids = array();

    for($i = 0; $i < FORK_NUMS; ++$i) {
        $pids[$i] = pcntl_fork();
        if($pids[$i] == -1) {
            die('fork error');
        } else if ($pids[$i]) {
            //这里是父进程空间，也就是主进程
            //我们的for循环第一次进入到这里时，pcntl_wait会挂起当前主进程，等待第一个子进程执行完毕退出
            //注意for循环的代码是在主进程的，挂起主进程，相当于当前的for循环也阻塞在这里了
            //第一个子进程退出后，然后再创建第二个子进程，到这里后又挂起，等待第二个子进程退出，继续创建第三个，等等。。

            #pcntl_wait($status);
            pcntl_waitpid($pids[$i], $status);
            echo "pernet \n";
        } else {
            sleep(3);
            echo "child id:" . getmypid() . " \n";
            exit;
        }
    }
    #我们创建3个子进程，父进程分别挂起等待子进程结束后，输出parent。(*在子进程中，需通过exit来退出，不然会产生递归多进程，父进程中不需要exit，不然会中断多进程。)
*/

/*  //阻塞模式
    define('FORK_NUMS', 3);
    $pids = array();
    for($i = 0; $i < FORK_NUMS; ++$i) {
        $pids[$i] = pcntl_fork();
        if($pids[$i] == -1) {
            die('fork error');
        } else if ($pids[$i]) {
     
        } else {
            sleep(3);
            echo "child id:" . getmypid() . " \n";
            exit;
        }
    }
    
    //我们把pcntl_waitpid放到for循环外面，那样在for循环里创建子进程就不会阻塞了
    //但是在这里仍会阻塞，主进程要等待3个子进程都退出后，才退出。 
    foreach($pids as $k => $v) {
        if($v) {
            //pcntl_wait的第二个参数可以用来设置主进程不等待子进程退出，继续执行后续代码。
            pcntl_waitpid($v, $status);
            echo "parent \n";
        }
    }

    //我们可以看到例5的pcntl_waitpid函数放在了foreach中，foreach代码是在主进程中，也就是父进程的代码中。当执行foreach时，可能子进程已经全部执行完毕并退出。pcntl_waitpid会立刻返回，连续输出三个parent。(*在子进程中，需通过exit来退出，不然会产生递归多进程，父进程中不需要exit，不然会中断多进程。)
*/

/*
    //进程独立
    define('FORK_NUMS', 3);
    $pids = array();
    $fp = fopen('./test.log', 'wb');
    $num = 1;
     
    for($i = 0; $i < FORK_NUMS; ++$i) {
        $pids[$i] = pcntl_fork();
        if($pids[$i] == -1) {
            die('fork error');
        } else if ($pids[$i]) {
     
        } else {
            for($i = 0; $i < 5; ++$i) {
                flock($fp, LOCK_EX);
                fwrite($fp, getmypid() . ' : ' . date('Y-m-d H:i:s') . " : {$num} \r\n");
     
                flock($fp, LOCK_UN);
                echo getmypid(), ": success \r\n";
                ++$num;
            }
            exit;
        }
    }
     
    foreach($pids as $k => $v) {
        if($v) {
            pcntl_waitpid($v, $status);
        }
    }
     
    fclose($fp);

    #我们创建三个子进程，来同时向test.log文件写入内容，test.log内容如下：
    #们可以看到三个子进程的pid，它们分别执行了5次，时间几乎是在同时。但是$num的值并没像我们期望的那样从1－15进行递增。子进程中的变量是各自独立的，互不影响。子进程会自动复制父进程空间里的变量。
*/

/*
    //进程中共享数据
    define('FORK_NUMS', 3);
    $pids = array();
    $fp = fopen('./test.log', 'wb');
    $num = 1;
    //共享内存段的key
    $shmKey = 123;
    //创建共享内存段
    $shmId = shmop_open($shmKey, 'c', 0777, 64);
    //写入数据到共享内存段
    shmop_write($shmId, $num, 0);
     
    for($i = 0; $i < FORK_NUMS; ++$i) {
        $pids[$i] = pcntl_fork();
        if($pids[$i] == -1) {
            die('fork error');
        } else if ($pids[$i]) {
            //阻塞，等待子进程退出
            //注意这里，如果是非阻塞的话，$num的计数会出现问题。
            pcntl_waitpid($pids[$i], $status);
        } else {
            //读取共享内存段中的数据
            $num = shmop_read($shmId, 0, 64);
            for($i = 0; $i < 5; ++$i) {
                fwrite($fp, getmypid() . ' : ' . date('Y-m-d H:i:s') . " : {$num} \r\n");
                echo getmypid(), ": success \r\n";
                //递增$num
                $num = intval($num) + 1;
            }
     
            //写入到共享内存段中
            shmop_write($shmId, $num, 0);
            exit;
        }
    }
     
    //shmop_delete不会实际删除该内存段，它将该内存段标记为删除。
    shmop_delete($shmId);
    shmop_close($shmId);
    fclose($fp);
*/
    
/*
    //非阻塞模式
    //紧接着上面的例子，如果想等子进程运行结束后父进程再退出，该怎么办？那就用到pcntl_wait了。
    //此时再次运行程序，父进程就会一直等待子进程运行结束然后退出。
    //pcntl_waitpid()和pcntl_wait()功能相同。前者第一个参数支持指定pid参数，当指定-1作为pid的值等同于后者。
    //我们可以通过设置pcntl_wait的第二个参数为WNOHANG来控制进程是否阻塞。
    //该函数可以在没有子进程退出的情况下立刻跳出执行后续代码。

    $pid = pcntl_fork();
    if($pid == -1){
        //错误处理：创建子进程失败时返回-1.
        die( 'could not fork' );
    }elseif($pid){
        //父进程会得到子进程号，所以这里是父进程执行的逻辑
        $id = getmypid();   
        echo "parent_process_id {$id}, child_process_id {$pid}\n";
        
        while(1){
            //这里是父进程空间，也就是主进程
            //这里与阻塞模式代码只有一点不同，就是加了第二个参数WNOHANG
            //执行到这里时，就不会挂起主进程，而是继续执行后续代码

            $res = pcntl_wait($status, WNOHANG);
            //$res = pcntl_waitpid($pid, $status, WNOHANG);
            if ($res == -1 || $res > 0){
                sleep(10);//此处为了方便看效果，实际不需要
                break;
            }
        }  
    }else{
        //这里是子进程空间
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑
        $id = getmypid();   
        echo "child_process_id {$id}\n";   
        //我们让子进程等待10秒，再退出
        sleep(10); 
    }
*/

/**/
    //非阻塞模式
    $child_pids = [];
    for($i=0;$i<3; $i++){
        $pid = pcntl_fork();
        if($pid == -1){
            exit("fork fail");
        }elseif($pid){
            $child_pids[] = $pid;

            $id = getmypid();   
            echo time()." Parent process,pid {$id}, child pid {$pid}\n";   
        }else{
            $id = getmypid(); 
            $rand =  rand(1,3);
            echo time()." Child process,pid {$id},sleep $rand\n";   
            sleep($rand); //#1 故意设置时间不一样
            exit();//#2 子进程需要exit,防止子进程也进入for循环
        }
    }

    while(count($child_pids)){
        foreach ($child_pids as $key => $pid) {
            // $res = pcntl_wait($status, WNOHANG);
            #$res = pcntl_waitpid($pid, $status);//#3
            $res = pcntl_waitpid($pid, $status, WNOHANG);//#3
            if ($res == -1 || $res > 0){
                echo time()." Child process exit,pid {$pid}\n";   
                unset($child_pids[$key]);
            }else{
                // echo time()." Wait End,pid {$pid}\n";   //#4
            }
        }
    } 

