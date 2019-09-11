<?php
/*
	php多进程 防止出现僵尸进程

	对于用PHP进行多进程并发编程，不可避免要遇到僵尸进程的问题。

	僵尸进程是指的父进程已经退出，而该进程dead之后没有进程接受，就成为僵尸进程(zombie)进程。任何进程在退出前(使用exit退出) 都会变成僵尸进程(用于保存进程的状态等信息)，然后由init进程接管。如果不及时回收僵尸进程，那么它在系统中就会占用一个进程表项，如果这种僵尸进程过多，最后系统就没有可以用的进程表项，于是也无法再运行其它的程序。
*/

/*
	方法一：
	父进程通过pcntl_wait和pcntl_waitpid等函数等待子进程结束
	<?php
		$pid = pcntl_fork();
		
		if($pid == -1) {
		    die('fork error');
		} else if ($pid) {
		    //父进程阻塞着等待子进程的退出
		    // pcntl_wait($status);
		    // pcntl_waitpid($pid, $status);
		     
		    //非阻塞方式
		    pcntl_wait($status, WNOHANG);
		    pcntl_waitpid($pid, $status, WNOHANG);
		} else {
		    sleep(3);
		    echo "child \r\n";
		    exit;
		}
		var_dump($pid); 
*/
/*
	方法二：
	可以用signal函数为SIGCHLD安装handler，因为子进程结束后，父进程会收到该信号，可以在handler中调用pcntl_wait或pcntl_waitpid来回收。
	<?php
		declare(ticks = 1);
		 
		//信号处理函数
		function sig_func() {
		    echo "SIGCHLD \r\n";
		    //pcntl_wait($status);
		    //pcntl_waitpid(-1, $status);
		 
		    //非阻塞
		    pcntl_wait($status, WNOHANG);
		    //pcntl_waitpid(-1, $status, WNOHANG);
		}
		 
		pcntl_signal(SIGCHLD, 'sig_func');
		 
		$pid = pcntl_fork();
		 
		if($pid == -1) {
		    die('fork error');
		} else if ($pid) {
		    sleep(10);
		} else {
		    sleep(3);
		    echo "child \r\n";
		    exit;
		}
*/

/*
	如果子进程还没有结束时，父进程就结束了，那么init进程会自动接手这个子进程，进行回收。
	如果父进程是循环，又没有安装SIGCHLD信号处理函数调用wait或waitpid()等待子进程结束。那么子进程结束后，没有回收，就产生僵尸进程了。
	<?php*/
		$pid = pcntl_fork();
		 
		if($pid == -1) {
		    die('fork error');
		} else if ($pid) {
		    for(;;) {
		        sleep(3);
		    }
		} else {
		    echo "child \r\n";
		    exit;
		}
/*	父进程是个死循环，也没有安装SIGCHLD信号处理函数，子进程结束后。我们通过如下命令查看
	> ps -A -o stat,ppid,pid,cmd | grep -e '^[Zz]'
	会发现一个僵尸进程。


	代码改进：
	<?php
		declare(ticks = 1);
		 
		//信号处理函数
		function sig_func() {
		    echo "SIGCHLD \r\n";
		 
		    pcntl_waitpid(-1, $status, WNOHANG);
		}
		 
		pcntl_signal(SIGCHLD, 'sig_func');
		 
		$pid = pcntl_fork();
		 
		if($pid == -1) {
		    die('fork error');
		} else if ($pid) {
		    for(;;) {
		        sleep(3);
		    }
		} else {
		    echo "child \r\n";
		    exit;
		}
	当子进程结束后，再通过命令查看时，我们发现这时就没有僵尸进程了，这说明父进程对它进行了回收。
*/

/**
	方法三：
	如果父进程不关心子进程什么时候结束，那么可以用pcntl_signal(SIGCHLD, SIG_IGN)通知内核，自己对子进程的结束不感兴趣，那么子进程结束后，内核会回收，并不再给父进程发送信号。
	
	<?php
		declare(ticks = 1);
		 
		pcntl_signal(SIGCHLD, SIG_IGN);
		 
		$pid = pcntl_fork();
		 
		if($pid == -1) {
		    die('fork error');
		} else if ($pid) {
		    for(;;) {
		        sleep(3);
		    }
		} else {
		    echo "child \r\n";
		    exit;
		}
	当子进程结束后，SIGCHLD信号并不会发送给父进程，而是通知内核对子进程进行了回收。
 */

/**
 	方法四：
 	通过pcntl_fork两次，也就是父进程fork出子进程，然后子进程中再fork出孙进程，这时子进程退出。那么init进程会接管孙进程，孙进程退出后，init会回收。不过子进程还是需要父进程进行回收。我们把业务逻辑放到孙进程中执行，父进程就不需要pcntl_wait或pcntl_waitpid来等待孙进程(即业务进程)。
	
	<?php
		$pid = pcntl_fork();
		 
		if($pid == -1) {
		    die('fork error');
		} else if ($pid) {
		    //父进程等待子进程退出
		    pcntl_wait($status);
		    echo "parent \r\n";
		} else {
		    //子进程再fork一次，产生孙进程
		    $cpid = pcntl_fork();   
		    if($cpid == -1) {
		        die('fork error');
		    } else if ($cpid) {
		        //这里是子进程，直接退出
		        echo "child \r\n";
		        exit;
		    } else {
		        //这里是孙进程，处理业务逻辑
		        for($i = 0; $i < 10; ++$i) {
		            echo "work... \r\n";
		            sleep(3);
		        }
		    }
		}
	子进程退出后，父进程回收子进程，孙进程继续业务逻辑的处理。当孙进程也执行完毕退出后，init回收孙进程。
 */