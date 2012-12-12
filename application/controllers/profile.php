<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profile extends CI_Controller {

    public function __construct() {
        parent::__construct();
        if ($this->session->userdata('auth') != 'true') {
            header('HTTP/1.1 403 Forbidden');
            exit();
        }
		$this->load->model('datamod');//load the database model
		
		if (!$this->datamod->getPrivKey($this->session->userdata('id')))
			redirect('secretsanta/survey');
        
    }
	
	public function index()
	{
		$this->load->helper('form');
		$this->load->library('form_validation');
		render('profile');
	}
	
	public function groupcode() { //form helper for adding group by code
		$this->load->helper('form');
		$this->load->library('form_validation');
		
		$this->form_validation->set_error_delimiters('<div style="color:red;margin-top:10px;font-size:12px;text-indent:5px">', '</div>');
		$this->form_validation->set_rules('group', 'Group Code', 'trim|min_length[4]|max_length[4]|alpha_numeric|callback_checkGroup|callback_inGroup|callback_numGroups');
		
		if ($this->form_validation->run() == FALSE)
		{
			render('profile');
		}
		else
		{
			$vars['success_code'] = true;
			$this->datamod->addGroup($this->session->userdata('name'), set_value('group'));
			render('profile',$vars);
		}
	}
	public function addgroup() { //form helper for creating new group
		$this->load->helper('form');
		$this->load->library('form_validation');
		$this->form_validation->set_error_delimiters('<div style="color:red;margin-top:10px;font-size:12px;text-indent:5px">', '</div>');
		$this->form_validation->set_rules('group_name', 'Group Name', 'trim|min_length[4]|max_length[50]|callback_numGroups|callback_checkGroupName|xss_clean');
		
		if ($this->form_validation->run() == FALSE)
		{
			render('profile');
		}
		else
		{
			$vars['success_name'] = true;
			$this->datamod->genGroup($this->session->userdata('name'), set_value('group_name'));
			render('profile',$vars);
		}
	}
	public function rm($code) { //remove membership from group
		$groupname=$this->datamod->getGroupName($code);
		
		if ($this->datamod->removeFromGroup($this->session->userdata('name'),$this->uri->segment(3))) {
			$this->session->set_flashdata('result','<div style="color:green;margin:10px 0 10px 0;font-size:12px;text-indent:5px">Successfully left the group <b>'.$groupname.'</b>.</div>');
		}
		else $this->session->set_flashdata('result','<div style="color:red;margin:10px 0 10px 0;font-size:12px;text-indent:5px">Poopy. Something went wrong. :( </div>');
		redirect('profile');
	}
	
	public function resetPin() { //reset pin form
		$this->load->helper('form');
		$this->load->library('form_validation');
		
		$this->form_validation->set_error_delimiters('<div style="color:red;margin-top:10px;font-size:12px;text-indent:5px">', '</div>');
		
		$this->form_validation->set_rules('pin', 'Pin', 'trim|required|min_length[4]|max_length[4]|numeric');
		$this->form_validation->set_rules('pinconf', 'Pin Confirmation', 'trim|required|min_length[4]|max_length[4]|numeric|matches[pin]');
	
		if ($this->form_validation->run() == FALSE)
		{
			render('survey',array('reset'=>1));
		}
		else
		{
			$this->load->library('crypt');
			$keys = $this->crypt->create_key(md5($this->session->userdata('email').set_value('pin'))); //key array: [private, public]
			
			$this->datamod->storeKeyPair($this->session->userdata('id'), $keys);
			$this->session->set_flashdata('result','<div style="color:green;margin:10px 0 10px 0;font-size:12px;text-indent:5px">Pin reset to <b>'.set_value('pin').'</b>. Don\'t forget it again!</div>');
			redirect('profile');
		}
	}
		
	//
	//form validation callback functions
	//
	public function checkGroup($str) {
		if ($this->datamod->checkGroup($str) == true) {//if exists
			return true;
		}
		else {
			$this->form_validation->set_message('checkGroup', 'The %s you entered does not exist.');
			return false;
		}
	}
	
	public function checkGroupName($str) {
		if ($this->datamod->checkGroupName($str) == true) {//if exists
			$this->form_validation->set_message('checkGroupName', 'The %s you entered already exists. :(');
			return false;//unusable
		}
		else return true;
	}
	
	public function inGroup($str) {
		if ($this->datamod->inGroup($this->session->userdata('name'),$str) == true) {//if ingroup
			$this->form_validation->set_message('inGroup', 'You are already in the group <b>'.$this->datamod->getGroupName(set_value('group')).'</b>.');
			return false;
		}
		else return true;
	}
	
	public function numGroups() {
		$num = $this->datamod->countPersonGroups($this->session->userdata('name'));
		if ($num <5)
			return true;
		else {
			$this->form_validation->set_message('numGroups', 'You are already in <b>'.$num.'</b> groups.  Leave a group and try again.');
			return false;
		}
	}
}