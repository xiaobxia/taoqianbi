<?php
$SERVERS_CONFIG = array();
$SERVERS_CONFIG['demo'] = array(
    'mid' => 'E0000000000000000009',
    'pem' => 'MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMyxNwqvSr8MPt1TRjJUSl8NPL/y2cbWcuWvpUoRNoPMOiqV4Pq36ZASLmvjY9OCW3CkvINED/rWPP2ppZ+KYd5hxM9DZJE4wmou54KTCY/9z0XwpWE5Kat8bg9cKsZGS43Irf9U7Uk85aDRCA5bL55Y8QHVi6WOSG3woISUCeP3AgMBAAECgYB+inPPjCx2TRiz9J4p0QglGObcd0KAdOSU7/AMCPYdpmyzFPL/rCCc61B9bBazlBv5WC4eLD6AwF72JiF4rrDvEWDpp6d/4u7TO01wRzpKkbbbhiUUONYWkbGd6hqY33GIaKXxgh+wSRPbyw93zCrdKQNJpGN/wTEzG0GlKFZcQQJBAO2jm+hoBB8o/XyCYAgd9pwvF4zEWTVucIMMY+0ZSoVZ6yVkUVYpQ4Ocb5fI398z8axybWShwPRUgc+dLOz/ExcCQQDcge681gxZN5+f1TyYt3V9zECU3rkBufUvodthq2ZFIJ8ntjhsdmbJNtzZ6myUeFIFXQeuvz2/Lr2jyQzdd8IhAkA6ovM2bmwN8ERT86uUdShDs48BCfXlLEIQ4/7II0RzERPnnxA+zWG+WNxkPImY/q00WuvJN+xvnWaGfwb1156zAkEAx7DLWSum5yzeW8qqI8sQlanhWnAQryWOi2JS4DJuXW/bcgUtN9xJ3TLX8mi/h/0mmkDTckcyTe6wQqESC4YmwQJBALpmEvN42xTd9BapxqAQscrL51HOM1LzyleMu9qA5O+YDH66wQh3ICIPqwrtDKLVuUqkTaWQcpzRLAtUtwluykQ=',
    'host' => 'https://www.ssqsign.com',
);

if (file_exists(__DIR__ . '/config.local.php'))
{
    require(__DIR__ . '/config.local.php');  
}