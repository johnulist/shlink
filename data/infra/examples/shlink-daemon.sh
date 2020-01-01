#!/bin/bash
### BEGIN INIT INFO
# Provides:          shlink_swoole
# Required-Start:    $local_fs $network $named $time $syslog
# Required-Stop:     $local_fs $network $named $time $syslog
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Description:       Shlink non-blocking server with swoole
### END INIT INFO

SCRIPT=/path/to/shlink/vendor/bin/mezzio-swoole\ start
RUNAS=root

PIDFILE=/var/run/shlink_swoole.pid
LOGDIR=/var/log/shlink
LOGFILE=${LOGDIR}/shlink_swoole.log

start() {
  if [[ -f "$PIDFILE" ]] && kill -0 $(cat "$PIDFILE"); then
    echo 'Shlink with swoole already running' >&2
    return 1
  fi
  echo 'Starting shlink with swoole' >&2
  mkdir -p "$LOGDIR"
  touch "$LOGFILE"
  local CMD="$SCRIPT &> \"$LOGFILE\" & echo \$!"
  su -c "$CMD" $RUNAS > "$PIDFILE"
  echo 'Shlink started' >&2
}

stop() {
  if [[ ! -f "$PIDFILE" ]] || ! kill -0 $(cat "$PIDFILE"); then
    echo 'Shlink with swoole not running' >&2
    return 1
  fi
  echo 'Stopping shlink with swoole' >&2
  kill -15 $(cat "$PIDFILE") && rm -f "$PIDFILE"
  echo 'Shlink stopped' >&2
}

case "$1" in
  start)
    start
    ;;
  stop)
    stop
    ;;
  restart)
    stop
    start
    ;;
  *)
    echo "Usage: $0 {start|stop|restart}"
esac
