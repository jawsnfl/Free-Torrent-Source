<?php
/**
 * Part of Free Torrent Source.
 * This script is open-source
 * You can modify the code bellow, but try to keep it as we made it if you
 * don't know PHP/MYSQL/HTML  
 * */  
    //delete old login attempts
    $secs = 1 * 86400 ; // Delete failed login attempts per one day.
    $dt = sqlesc( get_date_time(gmtime() - $secs) ) ; // calculate date.
    mysql_query( "DELETE FROM loginattempts WHERE banned='no' AND added < $dt" ) ; // do job.
    ?>