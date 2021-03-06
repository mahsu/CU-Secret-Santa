<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Class Admin
 */
class Admin extends CI_Controller
{
    /**
     * Class Constructor
     */
    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('admin')) {
            redirect(base_url("login/timeout"));
        }
        $this->load->model('datamod'); //load the data model
        $this->load->model('adminmod'); //load the admin model
        $this->load->helper('message'); //load the bootstrap message helper
        $this->load->helper('render_admin');
    }

    /**
     * Index page for admin controller
     */
    public function index()
    {
        redirect(base_url("admin/groups"));
    }

    /**
     * Admin panel for group management
     * Includes: group pairing, template groups, email whitelist
     */
    public function groups() {
        $year = $this->datamod->getGlobalVar("first_year");//get the year of the group
        $groups = $this->datamod->listAllGroups();
        foreach ($groups as &$group) {//get list of groups for pairing
            $group->memberCount = $this->datamod->countMembers($group->code,$group->year);
            $group->paired = $this->datamod->paired($group->code,$group->year);
        }
        $data = array('groups' => $groups, 'templates'=>$this->adminmod->listTemplateGroups(), 'first_year'=>$year,'current_year'=>intval(date('Y')), 'allowed_emails' => $this->adminmod->getAllowedEmails());
        render_admin('admin/groups', $data);
    }

    /**
     * General settings
     * includes: event dates, max groups
     */
    public function general() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="alert alert-danger">', '</div>');
        $this->form_validation->set_rules('partner-date', 'edited Partner Date', 'trim|required|callback_validDate|exact_length[5]|xss_clean');
        $this->form_validation->set_rules('gift-date', 'edited Gift Date', 'trim|required|callback_validDate|exact_length[5]|xss_clean');
        $this->form_validation->set_rules('max-groups', 'edited Max Groups', 'trim|required|greater_than[0]|less_than[21]|xss_clean');


        $max_groups = $this->datamod->getGlobalVar('max_groups');

        if ($this->form_validation->run() == false) {
            render_admin('admin/general', array('max_groups' => $max_groups));
        }
        else {
            $partnerDate = $this->__parseDate(set_value('partner-date'));
            $giftDate = $this->__parseDate(set_value('gift-date'));

            $this->adminmod->setGlobalVar('evt_partner_date', $partnerDate);
            $this->adminmod->setGlobalVar('evt_gift_date', $giftDate);
            $this->adminmod->setGlobalVar('max_groups',set_value('max-groups'));

            $this->session->set_flashdata('admin', message('Success! Settings are updated.'));
            redirect(current_url());
        }
    }

    /**
     * Advanced settings
     * Includes: site name, domain restriction, admin emails
     */
    public function advanced() {
        $this->load->library('form_validation');
        $this->load->helper('email'); //email validation
        $this->form_validation->set_error_delimiters('<div class="alert alert-danger">', '</div>');
        $this->form_validation->set_rules('site-name', 'edited Site Name', 'trim|required|max_length[40]|xss_clean');
        $this->form_validation->set_rules('domain-restriction', 'edited Domain Restriction', 'trim|callback_validRegex|xss_clean');
        $this->form_validation->set_rules('admin-users', 'edited Admin Users', 'trim|required|callback_validEmails|callback_checkCurrentAdmin|xss_clean');


        $domain_restriction = $this->datamod->getGlobalVar('domain_restriction');
        $admin_users = $this->datamod->getGlobalVar('admin_users');

        if ($this->form_validation->run() == false) {
            render_admin('admin/advanced', array('domain_restriction' => $domain_restriction, 'admin_users' => $admin_users));
        }
        else {
            $this->adminmod->setGlobalVar('site_name', set_value('site-name'));
            $this->adminmod->setGlobalVar('domain_restriction', set_value('domain-restriction'));

            $admin_users = explode("\r\n", set_value('admin-users')); //\n\r
            $admin_users = array_unique($admin_users); //remove duplicates
            $admin_users = array_filter($admin_users,function($a){return $a!="";}); //remove blank elements
            $this->adminmod->setGlobalVar('admin_users', $admin_users);

            $this->session->set_flashdata('admin', message('Success! Settings are updated.'));
            redirect(current_url());
        }
    }

    /**
     * Form submit for email whitelisting
     */
    public function addAllowedEmail()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="alert alert-danger">', '</div>');
        $this->form_validation->set_rules('email', 'Email Address', 'trim|required|valid_email');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('admin', message('<strong>Error!</strong> You must enter a valid email address.'));
            redirect('admin/groups');
        }

        $email = $this->input->post('email');
        if ($this->adminmod->addAllowedEmail($email)) {
            $this->session->set_flashdata('admin', message("<strong>Success!</strong> Added $email to the list of allowed email addresses."));
        } else {
            $this->session->set_flashdata('admin', message("<strong>Error!</strong> Could not add $email."));
        }

        redirect('admin/groups');
    }

    /**
     * Form submit for group pairing
     */
    public function pairCustom()
    {
        if ($this->input->post('code') != '') {
            $result = $this->adminmod->pairCustom($this->input->post('code'));
            if ($result) {
                $this->session->set_flashdata('admin', message('<strong>Success!</strong> Successfully ran pairing on code ' . $this->input->post('code') . ' with ' . $result . ' members',1));
                redirect(base_url('admin/groups'));
            } else {
                $this->session->set_flashdata('admin', message('<strong>Error!</strong> Pairing failed. Invalid code, group does not meet requirements, or pairing was already run.',3));
                redirect(base_url('admin/groups'));
            }
        } else {
            $this->session->set_flashdata('admin', message('<strong>Error!</strong> No code specified',3));
            redirect(base_url('admin/groups'));
        }
    }

    /**
     * ajax post for adding a new template group to the groups_template table
     * Accepts post to the following variables:
     * n            group nane
     * c            group code
     * d            group description
     * p            group privacy
     */
    public function newTemplateGroup() {
        $name = $this->input->post("n");
        $code = $this->input->post("c");
        $description = $this->input->post("d");
        $privacy = $this->input->post("p");
        $group_code = $this->adminmod->newTemplateGroup($code,$name,$description,$privacy);//($user_id,$task_name,$date,$estimated)
        echo $group_code;
    }

    /**
     * ajax post for deleting a template group
     * c            group code
     */
    public function deleteTemplateGroup() {
        $code = $this->input->post('c');
        $this->adminmod->deleteTemplateGroup($code);
        echo true;
    }

    /**
     * ajax get for retrieving all template groups
     */
    public function loadAllTemplateGroups() {
        echo json_encode($this->adminmod->loadAllTemplateGroups());
    }

    /**
     * ajax post for editing a template group
     * c            group code
     * n            group name
     * d            group description
     * p            group privacy
     */
    public function editTemplateGroup() {
        $code = $this->input->post("c");//code of group
        $name = $this->input->post("n");//name of group
        $description = $this->input->post("d");//descrip of group
        $privacy = $this->input->post("p");//privacy
        echo $this->adminmod->editTemplateGroup($code,$name,$description,$privacy);
    }

    /**
     * ajax post for creating a group that people can join from the template group
     * c            group code
     */
    public function createTemplateGroup() {
        $code = $this->input->post("c");
        echo $this->adminmod->createTemplateGroup($code);
    }


    /**
     * sends an email to all members of a group
     * @param string $code
     * @param int $year
     */
    public function sendBulkMail($code = null, $year = null)
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="alert alert-danger">', '</div>');
        $this->form_validation->set_rules('subject', 'trim|required');
        $this->form_validation->set_rules('message', 'trim|required');

        $sendTo = $this->datamod->getMemberEmails($code, $year);

        if ($sendTo == false || count($sendTo) == 0) {
            $this->session->set_flashdata('admin', message('No recipient users found. Are you sure the group you specified is valid?'));
        }

        if ($this->form_validation->run() == false) {
            $data['varNames'] = array('name', 'email', 'groupCount');
            $data['code'] = $code;
            $data['year'] = ($year == null) ? $this->datamod->current_year : $year;
            $data['sendTo'] = $sendTo;
            render('admin/sendBulkMail', $data);
        } else {
            foreach ($sendTo as $email) {
                $userId = $this->datamod->getUserId($email);
                $vars['name'] = $this->datamod->getUserName($userId);
                $vars['email'] = $email;
                $vars['groupCount'] = $this->datamod->countPersonGroups($userId);
                $this->adminmod->sendMail($email, $this->input->post('subject'), $this->input->post('message'), $vars);
            }

            $this->session->set_flashdata('admin', message('Sent successfully to ' . count($sendTo) . ' users.'));
            redirect(current_url());
        }
    }

    //
    //form validation callback functions
    //

    /**
     * checks input is valid date in form MM/DD
     * @param $str
     * @return bool
     */
    public function validDate($str) {
        preg_match('/^([0-9]{2})\/([0-9]{2})$/',$str, $matches);
        $month = 0;
        $day = 0;
        if (!empty($matches)) {
            $month = intval($matches[1]);
            $day = intval($matches[2]);
        }
        if ($month >= 1 && $month <= 12 && $day >=1 && $day <=31)
            return true;
        else{
            $this->form_validation->set_message('validDate', 'Invalid date was inputted.');
            return false;
        }
    }

    /**
     * sloppily checks whether regex is valid
     * @param $str
     * @return bool
     */
    public function validRegex($str) {
        if (strlen($str) == 0) return true;
        $subject = 'test1@example.com';
        if (@preg_match($str, $subject) === false) { //suppress errors
            $this->form_validation->set_message('validRegex', 'Invalid regex was inputted');
            return false;
        }
        return true;
    }

    /**
     * Check whether every email in a '\n\r' delimited list is valid
     * @param $str
     * @return bool
     */
    public function validEmails($str) {
        $emails = explode("\r\n",$str);  //\n\r
        foreach( $emails as $email) {
            if (!valid_email($email) || $email == "") {
                $this->form_validation->set_message('validEmails', 'Invalid email address was inputted.');
                return false;
            }
        }

        return true;
    }

    /**
     * Checks the current admin is in a string
     * @param $str
     * @return bool
     */
    public function checkCurrentAdmin($str)
    {
        $admin = $this->session->userdata('email');

        if (strpos($str, $admin) === FALSE) {
            $this->form_validation->set_message("checkCurrentAdmin", 'Current user must be in admin emails.');
            return false;
        }
        return true;
    }

    /**
     * Return an array of [month,day]
     * Prec: valid date
     * @param string $date
     * @return array        [month,day]
     * @private
     */
    private function __parseDate($date) {
        preg_match('/^([0-9]{2})\/([0-9]{2})$/',$date, $matches);
        $date_array[] = intval($matches[1]);
        $date_array[] = intval($matches[2]);
        return $date_array;
    }
}