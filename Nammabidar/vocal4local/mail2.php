<?php 

if($_POST && isset($_FILES['attachment']))
{

    
$from_email = $_POST['email']; //sender email
$recipient_email = 'sachin.chidre@gmail.com'; //recipient email
$subject = 'job application form'; //subject of email

$message.='First name :'.$_POST['firstname']. "\r\n";
$message.='Last name :'.$_POST['lastname']. "\r\n";
$message.='Birthday :'.$_POST['birthday']. "\r\n";
$message.='Gender :'.$_POST['gender']. "\r\n";
$message.='Email Address :'.$_POST['email']. "\r\n";
$message.='Phone Number :'.$_POST['number']. "\r\n";
$message.='Address1 :'.$_POST['address1']. "\r\n";
$message.='Address2 :'.$_POST['address2']. "\r\n";
$message.='State :'.$_POST['state']. "\r\n";
$message.='City :'.$_POST['city']. "\r\n";
$message.='Pincode :'.$_POST['pincode']. "\r\n";
$message.='Nation :'.$_POST['nation']. "\r\n";
$message.='Education :'.$_POST['education']. "\r\n";
$message.='Work Experience :'.$_POST['work']. "\r\n";
$message.='Skills :'.$_POST['skil']. "\r\n";
$message.='Applied for Position :'.$_POST['position']. "\r\n";
$message.='Resume :'.$_POST['attachment']. "\r\n";


 //get file details we need
    $file_tmp_name    = $_FILES['attachment']['tmp_name'];
    $file_name        = $_FILES['attachment']['name'];
    $file_size        = $_FILES['attachment']['size'];
    $file_type        = $_FILES['attachment']['type'];
    $file_error       = $_FILES['attachment']['error'];
    
    $user_email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

    if($file_error>0)
    {
        die('upload error');
    }
    //read from the uploaded file & base64_encode content for the mail
    $handle = fopen($file_tmp_name, "r");
    $content = fread($handle, $file_size);
    fclose($handle);
    $encoded_content = chunk_split(base64_encode($content));


         $boundary = md5("e-p-c-l"); 
        //header
        $headers = "MIME-Version: 1.0\r\n"; 
        $headers .= "From:".$from_email."\r\n"; 
        $headers .= "Reply-To: ".$user_email."" . "\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary = $boundary\r\n\r\n"; 
        
        //plain text 
         $body = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n"; 
        $body .= chunk_split(base64_encode($message)); 
        
        //attachment
          $body .= "--$boundary\r\n";
        $body .="Content-Type: $file_type; name=\"$file_name\"\r\n";
        $body .="Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        $body .="Content-Transfer-Encoding: base64\r\n";
        $body .="X-Attachment-Id: ".rand(1000,99999)."\r\n\r\n"; 
        $body .= $encoded_content; 
    
    $sentMail = @mail($recipient_email, $subject, $body, $headers);
    if($sentMail) //output success or failure messages
    {       
        header("location:thankyou.html");
    }else{
        die('Please contact by phone or email');  
    }
}


$to = "sachin.chidre@gmail.com";
	$subject = "Job Application Form";
	$headers = "From: " . strip_tags($_POST['email']) . "\r\n";
	$headers .= "Reply-To: ". strip_tags($_POST['email']) . "\r\n";
	$headers .= "CC:sachin.chidre@gmail.com\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	
$message = '<html><body>';
$message .= '<table rules="all" style="border-color: black;border:1px;background-color:lightblue;width:50%;" cellpadding="10">';
$message .= "<caption><h2>Applicant Details</h2></caption>";
$message .= "<tr><td><strong>First name</strong> </td><td>" . strip_tags($_POST['firstname']) . "</td></tr>";
$message .= "<tr><td><strong> Last name</strong></td><td>" . strip_tags($_POST['lastname']) . "</td></tr>";
$message .= "<tr><td><strong>Birthday</strong> </td><td>" . strip_tags($_POST['birthday']) . "</td></tr>";
$message .= "<tr><td><strong>Gender</strong> </td><td>" . strip_tags($_POST['gender']) . "</td></tr>";
$message .= "<tr><td><strong>Email Address</strong> </td><td>" . strip_tags($_POST['email']) . "</td></tr>";
$message .= "<tr><td><strong>Phone Number</strong> </td><td>" . strip_tags($_POST['number']) . "</td></tr>";
$message .= "<tr><td><strong>Address1 </strong> </td><td>" . strip_tags($_POST['address1']) . "</td></tr>";
$message .= "<tr><td><strong>Address2 </strong> </td><td>" . strip_tags($_POST['address2']) . "</td></tr>";
$message .= "<tr><td><strong>State</strong> </td><td>" . strip_tags($_POST['state']) . "</td></tr>";
$message .= "<tr><td><strong>City</strong> </td><td>" . strip_tags($_POST['city']) . "</td></tr>";
$message .= "<tr><td><strong>Pincode</strong> </td><td>" . strip_tags($_POST['pincode']) . "</td></tr>";
$message .= "<tr><td><strong>Nation</strong> </td><td>" . strip_tags($_POST['nation']) . "</td></tr>";
$message .= "<tr><td><strong>Education</strong> </td><td>" . strip_tags($_POST['education']) . "</td></tr>";
$message .= "<tr><td><strong>Work Experience</strong> </td><td>" . strip_tags($_POST['work']) . "</td></tr>";
$message .= "<tr><td><strong>Skills</strong> </td><td>" . strip_tags($_POST['skil']) . "</td></tr>";
$message .= "<tr><td><strong>Applied for Position</strong> </td><td>" . strip_tags($_POST['position']) . "</td></tr>";
$message .= "<tr><td><strong>Resume</strong> </td><td>" . strip_tags($_POST['attachment']) . "</td></tr>";
	$message .= "</table>";
	$message .= "</body></html>";
	
    		if(mail($to, $subject, $message, $headers)) {
		 header( 'location:thankyou.html' ) ;
	} else {
		echo "Failed to send";
	}

?>

