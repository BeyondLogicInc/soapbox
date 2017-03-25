<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Login extends CI_Controller {
    public function index(){
        if(is_logged_in()){
            redirect(base_url(),'location');
        }
        $this->load->view('login_view');
    }
    public function process() {
        // $data['login_url'] = $this->facebook->getLoginUrl(array('scope' => 'email'));
        $this->load->model('Login_model');
        $username = $this->security->xss_clean($this->input->post('uname'));
        $password = $this->security->xss_clean($this->input->post('pword'));
        $result = $this->Login_model->validate($username, md5($password));
        if(!$result) {
            $data['error'] = 'invalid';
            $this->load->view('login_view', $data);
        }
        else{
            $newdata = array("userid"=>$result['userid'], "username"=>$username, "fname"=>$result['fname'], "lname"=>$result['lname'], "avatarpath"=>$result['avatarpath']);
            $this->session->set_userdata($newdata);
            redirect(base_url(),'location');
        }
    }
    public function signup() {
        $nusername = $this->security->xss_clean($this->input->post('nusername'));
        $npassword = $this->security->xss_clean($this->input->post('npassword'));
        $cpassword = $this->security->xss_clean($this->input->post('cpassword'));
        if (!preg_match("/^[a-z][a-zA-Z0-9_.]{5,25}$/", $nusername)) {
            $data['error_signup'] = 'found';
        }
        if (!preg_match("/^[a-zA-Z0-9!@#$%^&*]{8,30}$/", $npassword)) {
            $data['error_signup'] = 'found';
        }
        if ($npassword != $cpassword) {
            $data['error_signup'] = 'found';
        }
        if (!isset($data['error_signup'])) {
            $npassword = md5($npassword);
            $this->load->model('Login_model');
            $srno = $this->Login_model->signup($nusername, $npassword);
            $newdata = array("userid"=>$srno,"username"=>$nusername);
            $this->session->set_userdata($newdata);
            mkdir(FCPATH . "userdata/" . $this->session->userdata('userid'), 0700, true);
            chmod(FCPATH . "userdata/" . $this->session->userdata('userid'), 0777);
            redirect('Signup', 'location');
        }
        else{
            $this->load->view('login_view', $data);
        }
    }
    public function reset_password(){
        $nusername = $this->security->xss_clean($this->input->post('unameconfirm'));
        $npassword = $this->security->xss_clean($this->input->post('new_password'));
        $cpassword = $this->security->xss_clean($this->input->post('con_password'));
        if (!preg_match("/^[a-z][a-zA-Z0-9_.]{5,25}$/", $nusername)) {
            $data['error_signup'] = 'found';
        }
        if (!preg_match("/^[a-zA-Z0-9!@#$%^&*]{8,30}$/", $npassword)) {
            $data['error_signup'] = 'found';
        }
        if ($npassword != $cpassword) {
            $data['error_signup'] = 'found';
        }
        if (!isset($data['error_signup'])) {
            $data['nusername'] = $nusername;
            $data['npassword'] = md5($npassword);
            $this->load->model('Login_model');
            $result = $this->Login_model->reset_password($data);
            redirect(base_url(), 'location');
        }
        else{
            $this->load->view('login_view', $data);
        }
    }
}