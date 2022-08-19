<?php	
public function actionIndex()
    {
        require_once 'google-api-php-client-main/vendor/autoload.php';
        $client = new Google\Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes('https://www.googleapis.com/auth/gmail.labels https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify https://mail.google.com/');
        $client->setAuthConfig('client_secret_70068748978-2a4jlqngpfh50q19nai5ahnvogccjdhd.apps.googleusercontent.com.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
         $authUrl = $client->createAuthUrl();
        echo '<a href="'.$authUrl.'">Auth</a>';
        //$client->setAuthConfig('/home/ncbetavp/public_html/etl_new/client_secret_70068748978-2a4jlqngpfh50q19nai5ahnvogccjdhd.apps.googleusercontent.com.json');
        // renders the view file 'protected/views/site/index.php'
        // using the default layout 'protected/views/layouts/main.php'
       
    }


    public function actionGetToken()
    {
        require_once 'google-api-php-client-main/vendor/autoload.php';
        echo $authCode = $_GET['code'];
        //die();
        $client = new Google\Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
         $client->setScopes('https://www.googleapis.com/auth/gmail.labels https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify https://mail.google.com/');
        $client->setAuthConfig('client_secret_70068748978-2a4jlqngpfh50q19nai5ahnvogccjdhd.apps.googleusercontent.com.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $client->setAccessToken($accessToken);
        $tokenPath = 'token.json';
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
       
        
        //$client->setAuthConfig('/home/ncbetavp/public_html/etl_new/client_secret_70068748978-2a4jlqngpfh50q19nai5ahnvogccjdhd.apps.googleusercontent.com.json');
        // renders the view file 'protected/views/site/index.php'
        // using the default layout 'protected/views/layouts/main.php'
        //$this->render('index');
    }



public function email_extract(){
		require_once 'google-api-php-client-main/vendor/autoload.php';
	    $authCode = $_GET['code'];
	    $client = new Google\Client();
	    $client->setApplicationName('Gmail API PHP Quickstart');
         $client->setScopes('https://www.googleapis.com/auth/gmail.labels https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify https://mail.google.com/');
        $client->setAuthConfig('client_secret_70068748978-2a4jlqngpfh50q19nai5ahnvogccjdhd.apps.googleusercontent.com.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $tokenPath = 'token.json';
        
	    
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }
        
        if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            }
        }
        
        
        $service = new Google\Service\Gmail($client);
        try{
            
            $optParams = [];
            $optParams['maxResults'] = 10; // Return Only 5 Messages
            $optParams['labelIds'] = 'INBOX'; // Only show messages in Inbox
            $optParams['q'] = 'in:inbox -category:(promotions OR social)'; //is:unread
            
            $messages = $service->users_messages->listUsersMessages('me',$optParams);
            $emaillist = $messages->getMessages();
            
            foreach($emaillist as $list){
                $messageId = $list->getId();
                $message = $service->users_messages->get('me', $messageId);
                $headerArr = $this->getHeaderArr($message->getPayload()->getHeaders());
                
                //echo '<pre/>';print_r($headerArr);
                //echo '<pre/>';print_r($headerArr['Subject']);
                
                $authresult = $headerArr['Authentication-Results'];
                $emailFrom = explode('smtp.mailfrom=',$authresult);
                $email_from = $emailFrom[count($emailFrom)-1];
                //die();
                $Subject = $headerArr['Subject'];
                $Date = $headerArr['Date'];
              

                    $message_body = $message->getPayload()->getParts();
                    $attachId = isset($message_body[1]['body']['attachmentId'])?$message_body[1]['body']['attachmentId']:'';
                    if($attachId){
                        $filename = isset($message_body[1]['filename'])?$message_body[1]['filename']:'';
                        $tmpNametype = explode(".", $filename);
				                            //var_dump(end($tmpNametype));
				        if(strtolower($tmpNametype[count($tmpNametype)-1])=="xls" || strtolower($tmpNametype[count($tmpNametype)-1])=="xlsx"){
				            //echo 'hi';
				            $attachment = $service->users_messages_attachments->get('me', $messageId, $attachId);
                            
                            echo $hotelcode = explode("-", $Subject)[0];
                            echo '-';
				            echo $jobcode = explode("-", $Subject)[1];
				            $fileOfDate = explode("-", $Subject)[2];
				            $EmailReceiveDate = date("Y-m-d h:i:s",strtotime($Date));
				            $MailSubjectDate = date("Y-m-d",strtotime($fileOfDate));
				            $filelocation = "./attachments/new/";
				            $filefullpath = $filelocation . $messageId . "-" . $filename;
				            $fp = fopen($filefullpath, "w+");
				            $data = $attachment->getData();
				            $data = strtr($data, array('-' => '+', '_' => '/'));
				                fwrite($fp, base64_decode($data));
				                fclose($fp);
				                
				                $tmpFilename = explode(".", $filename);
				                $model = new Jobs;
								$model->SenderEmail = $email_from;
				                $model->EmailReceiveDate = $EmailReceiveDate;
				                $model->MailSubjectDate = $MailSubjectDate;
				                $model->FileName = $messageId . "-" . $filename;
				                $model->FileExt = end($tmpFilename);
				                $model->FileLocation = $filelocation;
				                $model->FileSize = filesize($filefullpath);
				                $model->JobCreate = date("Y-m-d h:i:s");
				                $model->JobStatus = 0;
				                $model->HotelId = Hotels::model()->findByAttributes(array('HotelsCode'=>$hotelcode, 'HotelsStatus'=>1))->HotelsId;
				                $model->JobTypeId = JobTypes::model()->findByAttributes(array('JobTypesCode'=>$jobcode, 'JobTypesStatus'=>1))->JobTypesId;
				                if($model->save()){
				                    $mods = new Google\Service\Gmail\ModifyMessageRequest();
                                    //$mods->setaddLabelIds(array("success"));
                                    $mods->setRemoveLabelIds(array("UNREAD","INBOX"));
                                    $mods->setaddLabelIds(array("Label_1"));
                                    // Move from inbox to anaother label where Labrl_1 is the label id
                                    
                                    $service->users_messages->modify('me', $messageId, $mods);
                                    
                                    
				                }
				                
				                
				        }else{
				            /* Wrong attachment*/
				            $mods = new Google\Service\Gmail\ModifyMessageRequest();
                            $mods->setRemoveLabelIds(array("UNREAD","INBOX"));
                            $mods->setaddLabelIds(array("Label_2"));// Move from inbox to anaother label where Labrl_2 is the label id
                            $service->users_messages->modify('me', $messageId, $mods);
                            
				        }
                        
                    }else{
                        /* No attachment*/
                        //echo 'No';
                        $mods = new Google\Service\Gmail\ModifyMessageRequest();
                            $mods->setRemoveLabelIds(array("UNREAD","INBOX"));
                            $mods->setaddLabelIds(array("Label_2")); // Move from inbox to anaother label where Labrl_2 is the label id
                            $service->users_messages->modify('me', $messageId, $mods);
                       
                    }
                }
            
                
            }
            
            //echo '<pre/>';print_r($list);
        }
        catch(Exception $e) {
            // TODO(developer) - handle error appropriately
            echo 'Message: ' .$e->getMessage();
        }

	}