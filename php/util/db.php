<?php
function createMysqli() {
    $socket = getenv('MYSQL_SOCKET');
    if ($socket === false) {
        $socket = null;
    }

    return new mysqli(
        hostname: null,
        username: 'root',
        password: '',
        database: 'flightspocket',
        port: 0,
        socket: $socket
    );
}
