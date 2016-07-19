<?php


function mail_attachment($files, $mailto, $from_mail, $from_name, $replyto, $subject, $mailMessage) {
    $uid = md5(uniqid(time()));
    $eol = PHP_EOL;

    $header = "From: ".$from_name." <".$from_mail.">".PHP_EOL;
    $header .= "Reply-To: ".$replyto.PHP_EOL;
    $header .= "MIME-Version: 1.0".PHP_EOL;

    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"".PHP_EOL;
    
    $message = "--".$uid.PHP_EOL;
    $message .= "Content-type:text/html; charset=iso-8859-1".PHP_EOL;
    $message .= "Content-Transfer-Encoding: 7bit".PHP_EOL.PHP_EOL;  
    $message .= $mailMessage.PHP_EOL.PHP_EOL;


        foreach ($files as $file) { 
            $name = basename($file);

            $blub = explode("/", $name);
            $filename = end($blub);         
            
            $file_size = filesize($file);
            $handle = fopen($file, "r");
            $content = fread($handle, $file_sizes);
            fclose($handle);
            $content = chunk_split(base64_encode($content));

            $message .= "--".$uid.PHP_EOL;
            $message .= "Content-Type: text/html; name=\"".$filename."\"".PHP_EOL; // use different content types here
            $message .= "Content-Transfer-Encoding: base64".PHP_EOL;
            $message .= "Content-Disposition: attachment; filename=\"".$filename."\"".PHP_EOL.PHP_EOL;
            $message .= $content.PHP_EOL;

            unlink($file);
        }

    $message .= "--".$uid."--";

    return mail($mailto, $subject, $message, $header);
}


function makeCurl($site, $key, $maxTries = 3) {
    $url = $site[0];
    $text = $site[1];

    $success     = false;
    $tries       = 0;
    global $errors;

    while(!$success && $tries <= $maxTries) {
        if($tries > 0) sleep(3);

        $handle = curl_init();

        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_MAXREDIRS      => 10,
        );

        curl_setopt_array($handle, $options);

        /* Get the HTML or whatever is linked in $url. */
        $response   = curl_exec($handle);
        
        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if($httpCode != 200) {
            /* Handle errors here. */
            $errors[$key] = array('url' => $url, 'fehlercode' => $httpCode);
        } else {
            $success = true;
        }
        
        if(empty($response) && empty($errors[$key])) {
            $errors[$key] = array('url' => $url, 'fehlercode' => 'NO CONTENT!'); 
            $success = false;   
        }

        if(!empty($response) && !empty($text)) {
            if (strpos($response, $text) === false) {
                $errors[$key] = array('url' => $url, 'fehlercode' => 'Did not find content: ' . $text); 
                $success = false;   

                if($tries === $maxTries) {
                    $filename = $url . ".html";
                    $offset = strpos($filename, "//") + 2;
                    $filename ="/volume1/@tmp/" . date("yymd_his") . "_" .  rtrim(substr($filename, $offset), "/");
                    
                    $fileWritten = writeFile($filename, $response);
                    if($fileWritten) {
                        echo "Created file: $filename \n";
                        $errors['attachements'][] = $filename;
                    } else {
                        echo "File could not be created: $filename \n";
                    }
                }
            }
        }
        
        curl_close($handle);
        $tries++;
    }  
}

function writeFile($filename, $data) {
    if (file_exists($filename)) {
        unlink($filename);
    }
    $file = fopen($filename, "w");
    
    $return = fwrite($file, $data);
    fclose($file);

    return $return;
}

?>