<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Login_model extends CI_Model {

    public function validate($username, $password){
        $result = $this->db->query("SELECT * FROM useraccounts WHERE username='$username' AND password='$password'");
        if($result->num_rows() > 0){
            $row = $result->row_array();
            $query = $this->db->query("SELECT fname, lname, avatarpath from extendedinfo where uid = " . $row['srno']);
            $row_ = $query->row_array();
            $data['userid'] = $row['srno'];
            $data['fname'] = $row_['fname'];
            $data['lname'] = $row_['lname'];
            $data['avatarpath'] = $row_['avatarpath'];
            return $data;
        }
        return false;
    }
    public function validateSocialLogin($email){
        $result = $this->db->query("SELECT username, uid FROM useraccounts, extendedinfo WHERE email='$email' AND useraccounts.srno = extendedinfo.uid");
        if($result->num_rows() > 0){
            $row = $result->row_array();
            $query = $this->db->query("SELECT fname, lname, avatarpath from extendedinfo where uid = " . $row['uid']);
            $row_ = $query->row_array();
            $data['userid'] = $row['uid'];
            $data['username'] = $row['username'];
            $data['fname'] = $row_['fname'];
            $data['lname'] = $row_['lname'];
            $data['avatarpath'] = $row_['avatarpath'];
            return $data;
        }
        return false;
    }

    public function createNewSocialLoginUser($json) {
        $profile_picture_url = '';

        if($json->provider == 'facebook') {
            $profile_picture_url =  FB_AVATAR_URL . $json->id . "/picture?type=large";
        } else if($json->provider = 'google') {
            $profile_picture_url = explode("?", $json->picture_url)[0];
        } else if($json->provider == 'github') {
            $profile_picture_url = GITHUB_AVATAR_URL . $json->id;
        }
        $this->db->query("INSERT INTO useraccounts(username, password) values('$json->username','')");
        $result = $this->db->query("SELECT srno FROM useraccounts WHERE username='$json->username'");
        $row = $result->row_array();
        $srno = $row['srno'];

        mkdir(FCPATH . "./userdata/" . $srno, 0777, TRUE);
        $filedata = file_get_contents($profile_picture_url);
        $fileName = FCPATH . "userdata/" . $srno . '/social_avatar.jpg';
        $file = fopen($fileName, 'w+');
        fputs($file, $filedata);
        fclose($file);

        if(!file_exists($fileName)){
            if($json->gender == "male"){
                copy(FCPATH . 'assets/images/avatar_male.png' , FCPATH . 'userdata/' . $srno . '/avatar_male.png');
                $json->avatarpath = "avatar_male.png";
            }
            else if($json->gender == "female"){
                copy(FCPATH . 'assets/images/avatar_female.png' , FCPATH . 'userdata/' . $srno . '/avatar_female.png');
                $json->avatarpath = "avatar_female.png";
            }
        }
        else{
            $json->avatarpath = 'social_avatar.jpg';
        }

        if($json->provider == 'github') {
            $this->db->query("INSERT into extendedinfo(fname, lname, email, gender, about, avatarpath, uid) VALUES (". $this->db->escape($json->fname) . "," . $this->db->escape ($json->lname) . "," . $this->db->escape($json->email) . "," . $this->db->escape($json->gender) . "," . $this->db->escape($json->bio) . "," . $this->db->escape($avatarpath) . "," . (int)$srno . ")");
        } else {
            $this->db->query("INSERT into extendedinfo(fname, lname, email, gender, avatarpath, uid) VALUES (". $this->db->escape($json->fname) . "," . $this->db->escape ($json->lname) . "," . $this->db->escape($json->email) . "," . $this->db->escape($json->gender) . "," . $this->db->escape($avatarpath) . "," . (int)$srno . ")");
        }

        $get_categories = $this->db->query("SELECT * FROM category");
        $categories = $get_categories->row_array();
        foreach ($categories as $category){
            $query = $this->db->query("INSERT INTO category_user VALUES(" . (int)$category . "," . (int)$srno .")");
        }

        $_query= $this->db->query("SELECT useraccounts.*, extendedinfo.* FROM useraccounts, extendedinfo WHERE useraccounts.username='" . $json->username . "' AND useraccounts.srno=extendedinfo.uid");
        if($_query->num_rows()>0){
            $result_= $_query->row_array();
            return $result_;
        }
        return false;
    }

    public function signup($nusername, $npassword){
        $this->db->query("INSERT INTO useraccounts(username, password) values('$nusername','$npassword')");
        $result = $this->db->query("SELECT srno FROM useraccounts WHERE username='$nusername'");
        $row = $result->row_array();
        return $row['srno'];
    }

    public function reset_password($data){
        $query = $this->db->query("UPDATE useraccounts SET password='" . $data['npassword'] . "' WHERE username='" . $data['nusername'] ."'");
        $query_= $this->db->query("SELECT * FROM useraccounts WHERE username='" . $data['nusername'] . "' AND password='" . $data['npassword'] . "'");
        if($query_->num_rows()>0){
            return true;
        }
        return false;
    }

    public function is_user_from_fb($data){
        $query = $this->db->query("SELECT useraccounts.*, extendedinfo.* FROM extendedinfo, useraccounts WHERE email='" . $data . "' AND useraccounts.srno=extendedinfo.uid");
        if($query->num_rows()>0){
            $result = $query->row_array();
            return $result;
        }
        return false;
    }

    public function check_username($data){
        $query = $this->db->query("SELECT * FROM useraccounts WHERE username='" . $data ."'");
        if($query->num_rows()>0){
            return true;
        }
        return false;
    }

    public function new_fb_user($data){
        $this->db->query("INSERT INTO useraccounts(username, password) VALUES('" . strtolower($data['username']) . "', '" . $data['password'] . "')");
        $query = $this->db->query("SELECT srno FROM useraccounts WHERE username='" . $data['username'] . "'");
        if($query->num_rows()>0){
            $result = $query->row_array();
            mkdir("userdata/" . $result['srno'], 0700, true);
            chmod("userdata/" . $result['srno'], 0777);
            $url = $data['avatarpath'];
            $filedata = file_get_contents($url);
            $fileName = FCPATH . "userdata/" . $result['srno'] . '/fb_profilepic.jpg';
            $file = fopen($fileName, 'w+');
            fputs($file, $filedata);
            fclose($file);

            if(!file_exists($fileName)){
                if($data['gender']=="male"){
                    copy(FCPATH . 'assets/images/avatar_male.png' , FCPATH . 'userdata/' . $data['uid'] . '/avatar_male.png');
                    $data['imagepath'] = "avatar_male.png";
                }
                else if($data['gender']=="female"){
                    copy(FCPATH . 'assets/images/avatar_female.png' , FCPATH . 'userdata/' . $data['uid'] . '/avatar_female.png');
                    $data['imagepath'] = "avatar_female.png";
                }
            }
            else{
                $data['imagepath'] = 'fb_profilepic.jpg';
            }

            $this->db->query("INSERT INTO extendedinfo(fname, lname, email, avatarpath, uid) VALUES('" . $data['fname'] . "', '" . $data['lname'] . "', '" . $data['email'] . "', '" . $data['imagepath'] . "', " . (int)$result['srno'] . ")");
            $_query= $this->db->query("SELECT useraccounts.*, extendedinfo.* FROM useraccounts, extendedinfo WHERE useraccounts.username='" . $data['username'] . "' AND useraccounts.srno=extendedinfo.uid");
            if($_query->num_rows()>0){
                $result_= $_query->row_array();
                return $result_;
            }
            return false;
        }
        return false;
    }
}
