<?php

$redis = new Redis();

$redis->connect("tls://sharing-hawk-8606.upstash.io", 6379);
$redis->auth("ASGeAAImcDI0ODI5NTUxNDE3N2I0YmFjODdmODk1NTAyZGY5YzY4ZHAyODYwNg");
