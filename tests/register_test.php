<?php
$ch = curl_init('http://127.0.0.1:8000/api/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name'=>'autotest7','email'=>'autotest7@example.com','password'=>'secret123']));
$res = curl_exec($ch);
$info = curl_getinfo($ch);
echo $info['http_code']."\n";
echo $res;
curl_close($ch);
