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

        $url = "https://graph.facebook.com/me?fields=email&access_token=" . $accessToken;
        $content = file_get_contents($url);
        $json = json_decode($content);

        if ($json->email == $email) {
            $result = $this->Login_model->validateSocialLogin($email);
            if (!$result) {
                $data['error'] = 'invalid';
                $this->load->view('login_view', $data);
            }
            else {
                $newdata = array("userid"=>$result['userid'], "username"=>$result['username'], "fname"=>$result['fname'], "lname"=>$result['lname'], "avatarpath"=>$result['avatarpath']);
                $this->session->set_userdata($newdata);
                redirect(base_url(),'location');
            }
        }
    }
}