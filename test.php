<?php 

#demo1
	/*
		#root       6929  1.0  0.9 277416 17540 pts/0    S+   16:23   0:00 我是父进程,我的进程id是6929.
		#root       6931  0.0  0.3 277416  5904 pts/0    S+   16:23   0:00 我是6929的子进程,我的进程id是6931.

		//获取当前进程主id
		$ppid = posix_getpid();

		//pid来判断是主(父)进程还是子进程
		$pid = pcntl_fork();
		
		if ($pid == -1) {
		    throw new Exception('fork子进程失败!');
		} elseif ($pid > 0) {
		    cli_set_process_title("我是父进程,我的进程id是{$ppid}.");
		    sleep(30); // 保持30秒，确保能被ps查到
		} else {
		    $cpid = posix_getpid();
		    cli_set_process_title("我是{$ppid}的子进程,我的进程id是{$cpid}.");
		    sleep(30);// 保持30秒，确保能被ps查到
		}
	*/