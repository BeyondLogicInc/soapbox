<?php defined('BASEPATH') OR exit('No direct script access allowed');
class SocialLogin extends CI_Controller {
    public function index(){
        if(is_logged_in()){
            redirect(base_url(),'location');
        }
    }

    public function process() {
        $this->load->model('Login_model');
        $email = $this->security->xss_clean($this->input->get('email'));
        $id = $this->security->xss_clean($this->input->get('id'));
        $accessToken = $this->security->xss_clean($this->input->get('accessToken'));
        $provider = $this->security->xss_clean($this->input->get('provider'));
        $picture_url = $this->security->xss_clean($this->input->get('picture'));
        $url = '';
        $content = '';
        $json;

        switch($provider) {
            case 'facebook':    $url = FB_API_URL . $accessToken;
                                $content = file_get_contents($url);
                                $json = json_decode($content);
                                $json->username = explode(" ", $json->name)[0] . '_' . getToken(8);
                                $json->fname = explode(" ", $json->name)[0];
                                $json->lname = explode(" ", $json->name)[1];
                                $json->email = $json->email;
                                $json->gender = $json->gender;
                                break;
            case 'google':      $url = GOOGLE_API_URL . $accessToken;
                                $content = file_get_contents($url);
                                $json = json_decode($content);
                                $json->username = $json->name->givenName . '_' . getToken(8);
                                $json->fname = $json->name->givenName;
                                $json->lname = $json->name->familyName;
                                $json->email = $json->emails[0]->value;
                                $json->gender = $json->gender;
                                break;
            case 'github':      $url = GITHUB_API_URL . $accessToken;
                                $opts = [
                                    "http" => [
                                        "header" => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1521.3 Safari/537.36"
                                    ]
                                ];
                                $context = stream_context_create($opts);
                                $content = file_get_contents($url, false, $context);
                                $json = json_decode($content);
                                $json->username = explode(" ", $json->name)[0] . '_' . getToken(8);
                                $json->fname = explode(" ", $json->name)[0];
                                $json->lname = explode(" ", $json->name)[1];
                                $json->email = $json->email;
                                $json->gender = '';
                                $json->bio = $json->bio;
                                break;
        }

        $json->provider = $provider;
        $json->picture_url = $picture_url;
        
        if ($json->email == $email) {
            $result = $this->Login_model->validateSocialLogin($email);
            if (!$result) {
                $result_new = $this->Login_model->createNewSocialLoginUser($json);
                if($result_new) {
                    $newdata = array("userid"=>$result_new['srno'], "username"=>$result_new['username'], "fname"=>$result_new['fname'], "lname"=>$result_new['lname'], "avatarpath"=>$result_new['avatarpath']);
                    $this->session->set_userdata($newdata);
                    redirect(base_url(), 'location');
                } else {
                    $data['error'] = 'invalid';
                    $this->load->view('login_view', $data);
                }
            } else {
                $newdata = array("userid"=>$result['userid'], "username"=>$result['username'], "fname"=>$result['fname'], "lname"=>$result['lname'], "avatarpath"=>$result['avatarpath']);
                $this->session->set_userdata($newdata);
                redirect(base_url(),'location');
            }
        } else {
            $data['error'] = 'invalid';
            $this->load->view('login_view', $data);
        }
    }
}
