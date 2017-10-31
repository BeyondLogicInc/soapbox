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

        $url = "https://graph.facebook.com/me?fields=email,name,gender,id&access_token=" . $accessToken;
        $content = file_get_contents($url);
        $json = json_decode($content);
        $json->username = explode(" ", $json->name)[0] . '_' . getToken(8);
        $json->fname = explode(" ", $json->name)[0];
        $json->lname = explode(" ", $json->name)[1];

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
            }
            else {
                $newdata = array("userid"=>$result['userid'], "username"=>$result['username'], "fname"=>$result['fname'], "lname"=>$result['lname'], "avatarpath"=>$result['avatarpath']);
                $this->session->set_userdata($newdata);
                redirect(base_url(),'location');
            }
        }
    }
}
