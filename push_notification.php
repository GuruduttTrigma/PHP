<?php
	public function android_push_notification () 
	{
		$deviceToken 	= 'dYa_J9yMo9s:APA91bGeSnpbnL24pp2BNEOYAdKLeRoQ8QIKGjdzIBZI7abzdECXxOaag_wTq5PwT_F5lvmEWrJpRosfgsqDDbcT4iTCPhJkN0hzhb0x84mlsMkbX9Dlzc_F7kpCbDThzQBdaFy9awY2';
		$api_key 			= "AIzaSyDL_ynycfW9hL8-sERnCs2v0bc3TiXN0dE";
		$registrationIds = array($deviceToken);
		
		$msg 					= array (
			'message' 	=> "There's a new post in your area. Share now!.",
			'title'			=> 'This is a title. title',
			'subtitle'		=> 'This is a subtitle. subtitle',
			'tickerText'	=> 'Ticker text here...Ticker text here...Ticker text here',
			'vibrate'		=> 1,
			'sound'		=> 1,
			'largeIcon'	=> 'large_icon',
			'smallIcon'	=> 'small_icon'
		); 
		
		$fields = array (
			'registration_ids' 	=> $registrationIds,
			'data'			=> $msg
		);
		
		$headers = array (
			'Authorization: key=' .$api_key,
			'Content-Type: application/json'
		);
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send');
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );	
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($fields ));
		$result = curl_exec($ch);
		//echo $result;
		
		if ($result === FALSE) {
			die('Curl failed: ' . curl_error($ch));
		}	
		curl_close($ch);
		die;
	}
	
	public function iphone_push_notification () 
	{
		$path 					= WWW_ROOT.'Stackck.pem';		
		$deviceToken 	= '';
		$message			=	"There's Guru!.";
		$passphrase 	= '123456';
		$name				= 'Sir';
		
		// Create a Stream
		$ctx = stream_context_create();
		
		// Define the certificate to use 
		stream_context_set_option($ctx, 'ssl', 'local_cert',$path);	
		
		// Passphrase to the certificate
		stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);			
		
		// Open a connection to the APNS server
		$fp 				= stream_socket_client('ssl://gateway.push.apple.com:2195', $err,$errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT,$ctx);
		
		//echo $fp;die;
		// Check that we've connected
		if (!$fp) {
			exit("Failed to connect: $err $errstr" . PHP_EOL);
		} 
		
		//echo $fp;die;
		$body['aps'] 	= array ('alert' => $message,'type' =>  'text','sound' => 'default');
		$body['data'] 	= array ('id' => 123,'name' => $name,'status'=>1,'user_name'=>$name);

		$payload 		= json_encode($body);
		$msg 				= chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		
		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));
		//echo $result;die;
		if (!$result) {	
			//return 0; 
			$response 	= array('success'=>0,'msg'=>'Error in notification.');
			echo json_encode ($response);exit;	
			fclose ($fp);						
		}  else  {
			//return 1;			
			$response			= 	array('success'=>1,'message'=>'success....');
			echo json_encode ($response);exit;		
			fclose($fp);						
		}
	}
}
	
?>