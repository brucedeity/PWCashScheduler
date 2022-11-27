#!/bin/sh

nohup php scheduler.php start > /dev/null 2>&1 &
echo "PWCashScheduler Started!"