<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Class Login
 */
class Login extends CI_Controller
{
    /**
     * controller index
     */
    public function index()
    {
        $this->load->model('datamod');
        $this->config->load('oauth');

        $provider = new League\OAuth2\Client\Provider\Google(array(
        'clientId'  =>  $this->config->item('google_client_id'),
        'clientSecret'  =>  $this->config->item('google_client_secret'),
        'redirectUri'   =>  $this->config->item('google_redirect_uri'),
        'scopes' => array('email'),
    ));

        if ( ! isset($_GET['code'])) {

            // If we don't have an authorization code then get one
            header('Location: '.$provider->getAuthorizationUrl());
            exit;
            //redirect('/');

        } else {

            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $_GET['code']
            ]);


            try {

                // We got an access token, let's now get the user's details
                $userDetails = $provider->getUserDetails($token);
                $fname = $userDetails->firstName;
                $lname = $userDetails->lastName;
                $email = $userDetails->email;
                $image = $userDetails->imageUrl;
                //var_dump($userDetails);

                //check that user is in domain restriction or whitelisted
                $domain_restriction = $this->datamod->getGlobalVar('domain_restriction');
                if ($domain_restriction == '' || (preg_match($domain_restriction, $email)) || $this->datamod->checkAllowedEmailException($email)) {

                    // Load user ID if it exists
                    $user_id = $this->datamod->getUserId($email);
                    if ($user_id == false) {
                        $this->datamod->addUser($fname . " " . $lname, $email);
                        $user_id = $this->datamod->getUserId($email);//@todo addUser should return id
                    }
                    //check for admin permissions
                    if (in_array($email,$this->datamod->getGlobalVar('admin_users'))) //check against admin users config
                        $admin = 'true';
                    else
                        $admin = 'false';

                    //set session info
                    $this->session->set_userdata(array('auth' => 'true', 'admin' => $admin, 'fname' => $fname, 'lname' => $lname,'email' => $email, 'id' => $user_id, 'image' =>$image));


                    redirect(base_url('/profile'));
                } else {
                    $this->login_failure('Please log in using an authorized email account or contact an administrator.');
                }


            } catch (Exception $e) {

                // Failed to get user details
                $this->login_failure('Authentication failed, try logging in again.');
            }

            // Use this to interact with an API on the users behalf
            //echo $token->accessToken;

            // Use this to get a new access token if the old one expires
           // echo $token->refreshToken;

            // Number of seconds until the access token will expire, and need refreshing
            //echo $token->expires;
        }
    }


    /**
     * page to render if login fails
     * @param string $message
     */
    private function login_failure($message = 'Login failure')
    {
        //echo $message;
        render("landing",array("icon"=>"&#xf071;","header"=>"Login failure","subheader"=>$message));
    }

    /**
     * login timeout
     */
    public function timeout(){
        render("landing",array("icon"=>"&#xf071;","header"=>"Oops! You don't have permission to view this page.","subheader"=>"Your session has expired, or you are not logged in. Please <a href='/login'>login</a> to continue."));
    }

    /**
     * logout
     */
    public function logout()
    {
        $this->session->sess_destroy();
        render("landing",array("icon"=>"&#xf058;","header"=>"Logout success!","subheader"=>"You have successfully been logged out of your account. Come back soon!"));
    }

}