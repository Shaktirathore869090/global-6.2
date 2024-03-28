<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/* * *****************Web.php**********************************
 * @product name    : Global Multi School Management System Express
 * @type            : Class
 * @class name      : Web
 * @description     : Manage frontend website.  
 * @author          : Codetroopers Team 	
 * @url             : https://themeforest.net/user/codetroopers      
 * @support         : yousuf361@gmail.com	
 * @copyright       : Codetroopers Team	 	
 * ********************************************************** */

class Web extends CI_Controller {

    public $data = array();
    public $global_setting = array();
    public $schools = array();   
    
    function __construct() {
        parent::__construct();
        $this->load->model('Web_Model', 'web', true); 
        
        $global_setting = $this->db->get_where('global_setting',array('status'=>1))->row();
        if($global_setting){
            $this->global_setting = $global_setting;
            
            if(!$this->global_setting->enable_frontend){
                redirect('/', 'refresh');
            } 
        } 
   
         
        if($this->session->userdata('front_school_id')){ 
            $this->data['school'] = $this->web->get_single('schools', array('status' => 1, 'id'=>$this->session->userdata('front_school_id')));
            $this->data['footer_pages'] = $this->web->get_list('pages', array('status' => 1, 'page_location'=>'footer', 'school_id'=>$this->session->userdata('front_school_id')));
            $this->data['header_pages'] = $this->web->get_list('pages', array('status' => 1, 'page_location'=>'header',  'school_id'=>$this->session->userdata('front_school_id')));
            $this->data['opening_hour'] = $this->web->get_single('opening_hours', array('status' => 1, 'school_id'=>$this->session->userdata('front_school_id')));
        }


        if(!empty($global_setting) && !$this->session->userdata('front_school_id')){  
           
            if($global_setting->language){
                $this->lang->load($global_setting->language);
            }else{
                $this->lang->load('english');
            }
            
            define('SMS', $global_setting->brand_title);
             
        }else if(!empty($global_setting) && $this->session->userdata('front_school_id')){
            
            if($this->data['school']->language){
                $this->lang->load($this->data['school']->language);
            }else{
                $this->lang->load('english');
            }
            
            define('SMS', $this->data['school']->school_name);
        }
        
        $this->data['demo_school'] = $this->web->get_single('schools', array('status' => 1, 'is_demo'=>1));
    }
        
    public function school(){
         
        $school_url = $this->uri->segment(1);  
       
        if(!$school_url){  redirect(); }       
        
        $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url)); 
        
        if(empty($school)){
            $this->session->unset_userdata('front_school_id');
        }
        
        if(!empty($school)){          
            $this->session->set_userdata('front_school_id', $school->id);            
        }
        
        $school_id = $this->session->userdata('front_school_id');  
        if(!empty($school) && $school->id != $school_id){ 
            $this->session->unset_userdata('front_school_id');              
            $this->session->set_userdata('front_school_id', $school->id);            
            redirect($school->school_url);
        }
 
       
        if($this->session->userdata('front_school_id')){ 
                       
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
                   
            $this->data['sliders'] = $this->web->get_list('sliders', array('status' => 1, 'school_id'=>$school_id), '', '', '', 'id', 'ASC');
            $this->data['events'] = $this->web->get_event_list($school_id, 6);
            $this->data['news'] = $this->web->get_news_list($school_id, 6);
            
            $this->data['teacher'] = $this->web->get_total_teacher($school_id);
            $this->data['student'] = $this->web->get_total_student($school_id);
            $this->data['staff'] = $this->web->get_total_staff($school_id);
            $this->data['user'] = $this->web->get_total_user($school_id);            
            
            $this->data['feedbacks'] = $this->web->get_feedback_list($school_id, 20);
            
            // common data
            $this->data['school'] = $this->web->get_single('schools', array('status' => 1, 'id'=>$this->session->userdata('front_school_id')));
            $this->data['footer_pages'] = $this->web->get_list('pages', array('status' => 1, 'page_location'=>'footer', 'school_id'=>$this->session->userdata('front_school_id')));
            $this->data['header_pages'] = $this->web->get_list('pages', array('status' => 1, 'page_location'=>'header',  'school_id'=>$this->session->userdata('front_school_id')));
            $this->data['opening_hour'] = $this->web->get_single('opening_hours', array('status' => 1, 'school_id'=>$this->session->userdata('front_school_id')));
            
            
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('home') . ' | ' . SMS);
            $this->layout->view('index', $this->data);

        }else{   
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);               
                redirect($school->school_url);
            }            
            redirect();              
        }        
    }



    /*****************Function SaaS **********************************
    * @type            : Function
    * @function name   : index
    * @description     : Load "Frontend home page" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * *********************       SaaS           **************************** */
    public function index() {
        
        if($_POST){

            //$this->_prepare_plan_validation();
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('<div class="error-message" style="color: red;">', '</div>');
            $this->form_validation->set_rules('subscription_plan_id', $this->lang->line('subscription_plan'), 'trim|required');
            $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required');
            $this->form_validation->set_rules('email', $this->lang->line('email'), 'trim|required|valid_email');
            $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|min_length[6]|max_length[20]');
            $this->form_validation->set_rules('school_name', $this->lang->line('school_name'), 'trim|required');
            $this->form_validation->set_rules('address', $this->lang->line('address'), 'trim|required');
            
            if ($this->form_validation->run() === TRUE) {
                //$data = $this->_get_posted_plan_data(); 
                
                $data = array();               

                $data['subscription_plan_id'] = $this->input->post('subscription_plan_id');
                $data['name'] = $this->input->post('name');
                $data['email'] = $this->input->post('email');
                $data['phone'] = $this->input->post('phone');
                $data['school'] = $this->input->post('school_name');
                $data['address'] = $this->input->post('address');
                $data['modified_at'] = date('Y-m-d H:i:s');
                $data['modified_by'] = logged_in_user_id();
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['created_by'] = logged_in_user_id();
                $data['status'] = 1;
                $data['subscription_status'] = 'pending';
                
                $insert_id = $this->web->insert('saas_subscriptions', $data);                   
                if ($insert_id) {
                    
                    // Now Process for create a School as per Subscription..........
                    $data['subscription_id'] = $insert_id;
                    $this->_create_school();
                    $this->session->set_userdata('success', $this->lang->line('subscription_successful'));                        
                } else {
                    $this->session->set_userdata('error', $this->lang->line('subscription_failed'));
                }

                redirect(base_url().'#_subscription');
                
            } else { 
                $this->session->set_userdata('error', $this->lang->line('subscription_failed'));
                $this->data['post'] = $_POST;
            }                
        } 
        
        $this->data['sliders'] = $this->web->get_list('saas_sliders', array('status' => 1), '', '', '', 'id', 'ASC');
        $this->data['faqs'] = $this->web->get_list('saas_faqs', array('status' => 1), '', '', '', 'id', 'ASC');
        $this->data['plans'] = $this->web->get_list('saas_plans', array('status' => 1), '', '', '', 'id', 'ASC');
        $this->data['setting'] = $this->db->get_where('saas_settings',array('status'=>1))->row();
        
        $this->data['title_for_layout'] = $this->global_setting->brand_title ? $this->global_setting->brand_title : SMS;        
        $this->load->view('splash', $this->data);        
    }
    
    
    private function _create_school($data){
        
        $items = array();
        
        $items['subscription_id'] = $data['subscription_id'];        
        $items['school_url'] = get_slug($data['school']);
        $items['school_code'] = 'SCH123456';
        $items['school_name'] = $data['school'];
        $items['address'] = $data['address'];
        $items['phone'] = $data['phone'];
        $items['email'] = $data['email'];
        $items['currency'] = 'USD';
        $items['currency_symbol'] = '$';
        $items['school_fax'] = '1234567';     
        $items['zoom_api_key'] = ''; 
        $items['zoom_secret'] = ''; 
        $items['enable_frontend'] = 1;
        $items['final_result_type'] = 0;
        $items['registration_date'] = date('Y-m-d');
        $items['footer'] = 'Copyright © '.$data['school']. ' '. date('Y') ;
        $items['google_map'] = '';
        $items['theme_name'] = 'dodger-blue';
        $items['language'] = 'english';
        $items['enable_online_admission'] = 1;
        $items['enable_rtl'] = '0';
        $items['facebook_url'] = 'https://www.facebook.com/';
        $items['twitter_url'] = 'https://www.twitter.com/';
        $items['linkedin_url'] = 'https://www.linkedin.com/';
        $items['youtube_url'] = 'https://www.youtube.com/';
        $items['instagram_url'] = 'https://www.instagram.com/';
        $items['pinterest_url'] = 'https://www.pinterest.com/';       
       
        $items['status'] = 1;
        $items['created_at'] = date('Y-m-d H:i:s');
        $items['created_by'] = logged_in_user_id();
        $items['modified_at'] = date('Y-m-d H:i:s');
        $items['modified_by'] = logged_in_user_id();
        $items['about_text'] = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.Duis aute irure dolor in reprehenderit in voluptate velit esse cillum.';
        $items['logo'] = ''; 
        $items['frontend_logo'] = '';        
        $items['academic_year_id'] = '';        
        $items['academic_year'] = '';        
        $items['is_default'] = 0;        
        $school_id = $this->web->insert('schools', $items); 
        
        // Now we will create a Academic Year        
        $ay = array();
        $ay['school_id'] = $school_id;
        $ay['note'] = '';             
        $ay['start_year'] = date('Y');
        $ay['end_year']   = date('Y');
        $ay['session_year']   = 'January '.date('Y').' - December '.date('Y');
        
        $ay['is_running'] = 1;
        $ay['status'] = 1;
        $ay['created_at'] = date('Y-m-d H:i:s');
        $ay['created_by'] = logged_in_user_id();
        $ay['modified_at'] = date('Y-m-d H:i:s');
        $ay['modified_by'] = logged_in_user_id();
        $academic_year_id =$this->web->insert('academic_years', $ay);
        
        // Now we will update School AY 
        $academic_year = $ay['start_year'].' - '.$ay['end_year'];
        $this->web->update('schools', array('academic_year_id' => $academic_year_id, 'academic_year'=>$academic_year), array('id' => $school_id));       
        
    }




    /*****************Function news**********************************
    * @type            : Function
    * @function name   : news
    * @description     : Load "news listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function news() {
        
        $school_url = $this->uri->segment(1);   
        
        if($this->session->userdata('front_school_id')){            
          
            $school_id = $this->session->userdata('front_school_id');
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }           
            
            $this->data['news'] = $this->web->get_news_list($school_id, 100);
            $this->data['list'] = TRUE;
            
            $this->layout->title($this->lang->line('news') . ' | ' . SMS);
            $this->layout->view('news', $this->data);
        
        }else{    
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();           
        }
    }
    
    
    /*****************Function news**********************************
    * @type            : Function
    * @function name   : news
    * @description     : Load "news detail" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function news_detail() {
                
        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $id = $this->uri->segment(3);
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }    
            
            $this->data['news'] = $this->web->get_single_news($school_id, $id);  
            $this->data['latest_news'] = $this->web->get_news_list($school_id, 6);
            
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('news_detail') . ' | ' . SMS);
            $this->layout->view('news_detail', $this->data);
        
        }else{   
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();             
        }
    }
    
    
    
    /*****************Function notice**********************************
    * @type            : Function
    * @function name   : notice
    * @description     : Load "notice listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function notice() {
        
        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }   

            $this->data['notices'] = $this->web->get_notice_list($school_id, 100);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('notice') . ' | ' . SMS);
            $this->layout->view('notice', $this->data);
        
        }else{  
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();
        }
    }
    
    /*****************Function notice_detail**********************************
    * @type            : Function
    * @function name   : notice_detail
    * @description     : Load "notice_detail" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function notice_detail() {

        $school_url = $this->uri->segment(1);
        
        if($this->session->userdata('front_school_id')){           

            $id = $this->uri->segment(3);
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }    
            
            $this->data['notice'] = $this->web->get_single_notice($school_id, $id);
            $this->data['notices'] = $this->web->get_notice_list($school_id, 6);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('notice_detail') . ' | ' . SMS);
            $this->layout->view('notice_detail', $this->data);        
        
        }else{    
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    
    /*****************Function holiday**********************************
    * @type            : Function
    * @function name   : holiday
    * @description     : Load "holiday listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function holiday() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }   
            
            $this->data['holidays'] = $this->web->get_holiday_list($school_id, 6);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('holiday') . ' | ' . SMS);            
            $this->layout->view('holiday', $this->data);
            
        }else{  
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();             
        }
    }
    
    /*****************Function holiday_detail**********************************
    * @type            : Function
    * @function name   : holiday_detail
    * @description     : Load "holiday_detail" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function holiday_detail() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $id = $this->uri->segment(3);
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }   
            
            $this->data['holiday'] = $this->web->get_single_holiday($school_id,  $id);
            $this->data['holidays'] = $this->web->get_holiday_list($school_id, 6);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('holiday_detail') . ' | ' . SMS);
            $this->layout->view('holiday_detail', $this->data);
        
        }else{  
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    /*****************Function event**********************************
    * @type            : Function
    * @function name   : event
    * @description     : Load "event listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function events() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }   
            
            $this->data['events'] = $this->web->get_event_list($school_id, 6);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('event') . ' | ' . SMS);
            $this->layout->view('event', $this->data);
            
        }else{      
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();                
        }
    }
    
    /*****************Function event_detail**********************************
    * @type            : Function
    * @function name   : event_detail
    * @description     : Load "event_detail" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function event_detail(){

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $id = $this->uri->segment(3);
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }   
            
            $this->data['event'] = $this->web->get_single_event($school_id, $id);
            $this->data['events'] = $this->web->get_event_list($school_id, 6);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('event_detail') . ' | ' . SMS);
            $this->layout->view('event_detail', $this->data);
        
         }else{            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    
    
    /*****************Function gallery**********************************
    * @type            : Function
    * @function name   : gallery
    * @description     : Load "gallery listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function galleries() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }             

            $this->data['galleries'] = $this->web->get_list('galleries', array('status'=>1, 'school_id'=>$school_id, 'is_view_on_web'=>1), '', '', '', 'id', 'DESC');
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('media_gallery') . ' | ' . SMS);
            $this->layout->view('gallery', $this->data);
         
        }else{    
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();               
        }
    }

    /*****************Function teacher**********************************
    * @type            : Function
    * @function name   : teacher
    * @description     : Load "teacher listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function teachers() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }             

            $this->data['teachers'] = $this->web->get_teacher_list($school_id);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('teacher') . ' | ' . SMS);
            $this->layout->view('teacher', $this->data);        
          
        }else{   
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();                
        }
    }
    
    
    /*****************Function faq**********************************
    * @type            : Function
    * @function name   : faq
    * @description     : Load "faq listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function faq() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }                      

            $this->data['faqs'] = $this->web->get_faq_list($school_id);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('faq') . ' | ' . SMS);
            $this->layout->view('faq', $this->data);
            
        }else{     
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    
    /*****************Function staff**********************************
    * @type            : Function
    * @function name   : staff
    * @description     : Load "staff listing" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function staff() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }                         

            $this->data['employees'] = $this->web->get_employee_list($school_id);
            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('staff') . ' | ' . SMS);
            $this->layout->view('staff', $this->data);
        }else{     
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    
    /*****************Function Page**********************************
    * @type            : Function
    * @function name   : Page
    * @description     : Load "Dynamic Pages" user interface                 
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function page() { 
        
        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           
            
            $page_url = $this->uri->segment(3);
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }  
            
            $this->data['page'] = $this->web->get_single('pages', array('status' => 1, 'school_id'=>$school_id, 'page_slug'=>$page_url));
            
            if(empty($this->data['page'])){
                redirect(site_url(), 'refresh');
            }
            
            $this->layout->title($this->lang->line('page') .' | ' . SMS);
            $this->layout->view('page', $this->data);
            
         }else{   
           
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    
    /*****************Function About**********************************
    * @type            : Function
    * @function name   : About
    * @description     : Load "About Us" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function about() {
        
        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           
            
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }             

            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('about_school') .' | ' . SMS);
            $this->layout->view('about', $this->data);
            
        }else{       
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
        
    }
    
    /*****************Function admission**********************************
    * @type            : Function
    * @function name   : admission
    * @description     : Load "admission" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function admission_form() {
    
        $school_url = $this->uri->segment(1);
        
        if($this->session->userdata('front_school_id')){  
            
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }

             if(isset($this->data['school']->enable_online_admission) && $this->data['school']->enable_online_admission){               
           
                $school_id = $this->session->userdata('front_school_id');
                $this->data['list'] = TRUE;
                $this->layout->title($this->lang->line('admission_form') . ' | ' . SMS);
                $this->layout->view('admission-form', $this->data);
            
            }else{               
               redirect($school->school_url);
           }
            
        }else{    
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();               
        }
    }
    
    /*****************Function admission**********************************
    * @type            : Function
    * @function name   : admission
    * @description     : Load "online admission" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function admission_online() {
    
       $school_url = $this->uri->segment(1);
       
       if($this->session->userdata('front_school_id')){
           
            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
                        
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }
           
           if(isset($this->data['school']->enable_online_admission) && $this->data['school']->enable_online_admission){
              
            if($_POST){

                $this->_prepare_admission_validation();
                if ($this->form_validation->run() === TRUE) {
                    $data = $this->_get_posted_admission_data();                   
                    $insert_id = $this->web->insert('admissions', $data);                   
                       
                    if ($insert_id) {
                        $this->_send_admission_email();                        
                        $this->session->set_userdata('success', $this->lang->line('apply_successful'));                        
                    } else {
                        $this->session->set_userdata('error', $this->lang->line('apply_failed'));
                    }
                    
                    redirect($school->school_url.'/admission-online');
                } else { 
                    $this->session->set_userdata('error', $this->lang->line('apply_failed'));
                    $this->data['post'] = $_POST;
                }                
            } 

            $school_id = $this->session->userdata('front_school_id');            
            $this->data['classes'] = $this->web->get_list('classes', array('school_id'=>$school_id), '', '', '', 'id', 'ASC');
            $this->data['types'] = $this->web->get_list('student_types', array('school_id'=>$school_id), '', '', '', 'id', 'ASC');

            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('online_admission'). ' | ' . SMS);
            $this->layout->view('admission-online', $this->data);
            
           }else{               
               redirect($school->school_url);
           }
           
        }else{     
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    
    /*****************Function get_guardian_info**********************************
     * @type            : Function
     * @function name   : get_guardian_info
     * @description     : Get guardian information                 
     *                       
     * @param           : null
     * @return          : null 
     * ********************************************************** */
    public function get_guardian_info(){
        
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');
            header('Content-Type: application/json');
            $phone = $this->input->post('phone');        
            $guardian = $this->web->get_single('guardians', array('phone' => $phone, 'school_id'=>$school_id));
            echo json_encode($guardian);
            die();
        }
    }

        
    /*****************Function _prepare_admission_validation**********************************
     * @type            : Function
     * @function name   : _prepare_admission_validation
     * @description     : Process "admission" user input data validation                 
     *                       
     * @param           : null
     * @return          : null 
     * ********************************************************** */
    private function _prepare_admission_validation() {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<div class="error-message" style="color: red;">', '</div>');
        
        $this->form_validation->set_rules('name', $this->lang->line('name'), 'trim|required');   
        $this->form_validation->set_rules('dob', $this->lang->line('birth_date'), 'trim|required');   
        $this->form_validation->set_rules('gender', $this->lang->line('gender'), 'trim|required'); 
        $this->form_validation->set_rules('phone', $this->lang->line('phone'), 'trim|required|min_length[6]|max_length[20]'); 
        $this->form_validation->set_rules('class_id', $this->lang->line('class'), 'trim|required');   
        $this->form_validation->set_rules('type_id', $this->lang->line('student_type'), 'trim');   
        
        $this->form_validation->set_rules('gud_phone', $this->lang->line('guardian_phone'), 'trim|required'); 
        $this->form_validation->set_rules('gud_name', $this->lang->line('guardian_name'), 'trim|required'); 
        $this->form_validation->set_rules('gud_email', $this->lang->line('email'), 'trim'); 
        $this->form_validation->set_rules('photo', $this->lang->line('photo'), 'trim|callback_photo');
        
    }
    
    
    /*****************Function photo**********************************
    * @type            : Function
    * @function name   : photo
    * @description     : validate student profile photo                 
    *                       
    * @param           : null
    * @return          : boolean true/false 
    * ********************************************************** */
    public function photo() {
        if ($_FILES['photo']['name']) {
            $name = $_FILES['photo']['name'];
            $ext = pathinfo($name, PATHINFO_EXTENSION);            
            if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                return TRUE;
            } else {
                $this->form_validation->set_message('photo', $this->lang->line('select_valid_file_format'));
                return FALSE;
            }
        }
    }
    

        /*****************Function _get_posted_admission_data**********************************
     * @type            : Function
     * @function name   : _get_posted_admission_data
     * @description     : Prepare "admission" user input data to save into database                  
     *                       
     * @param           : null
     * @return          : $data array(); value 
     * ********************************************************** */
    private function _get_posted_admission_data() {

        $items = array();
        $items[] = 'name';
        $items[] = 'gender';
        $items[] = 'blood_group';
        $items[] = 'religion';
        $items[] = 'caste';
        $items[] = 'email';
        $items[] = 'phone';
        $items[] = 'national_id';
        $items[] = 'health_condition';
        $items[] = 'type_id';
        $items[] = 'class_id';
        $items[] = 'group';
        $items[] = 'second_language';
        $items[] = 'present_address';
        $items[] = 'permanent_address';
        
        $items[] = 'is_guardian';
        $items[] = 'guardian_id';
        $items[] = 'gud_phone';
        $items[] = 'gud_relation';
        $items[] = 'gud_name';
        $items[] = 'gud_email';
        $items[] = 'gud_national_id';
        $items[] = 'gud_profession';
        $items[] = 'gud_religion';
        $items[] = 'gud_other_info';
        $items[] = 'gud_present_address';
        $items[] = 'gud_permanent_address';
        
        $items[] = 'father_name';
        $items[] = 'father_phone';
        $items[] = 'father_education';
        $items[] = 'father_profession';
        $items[] = 'father_designation';
        $items[] = 'mother_name';
        $items[] = 'mother_phone';
        $items[] = 'mother_education';
        $items[] = 'mother_profession';
        $items[] = 'mother_designation';
        
        $items[] = 'previous_school';
        $items[] = 'previous_class';
        
        $data = elements($items, $_POST);        
        
        $data['school_id'] = $this->session->userdata('front_school_id');  
        $data['dob'] = date('Y-m-d', strtotime($this->input->post('dob')));  
        
        if ($this->input->post('id')) {
            $data['modified_at'] = date('Y-m-d H:i:s');
            $data['modified_by'] = logged_in_user_id();
        } else {
            $data['status'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = logged_in_user_id(); 
        }
            
        if ($_FILES['photo']['name']) {
            $data['photo'] = $this->_upload_photo();
        }
        
        return $data;
    }

    

               
    /*****************Function _upload_photo**********************************
    * @type            : Function
    * @function name   : _upload_photo
    * @description     : process to upload student profile photo in the server                  
    *                     and return photo file name  
    * @param           : null
    * @return          : $return_photo string value 
    * ********************************************************** */
    private function _upload_photo() {

        $photo = $_FILES['photo']['name'];
        $photo_type = $_FILES['photo']['type'];
        $return_photo = '';
        if ($photo != "") {
            if ($photo_type == 'image/jpeg' || $photo_type == 'image/pjpeg' ||
                    $photo_type == 'image/jpg' || $photo_type == 'image/png' ||
                    $photo_type == 'image/x-png' || $photo_type == 'image/gif') {

                $destination = 'assets/uploads/admission-photo/';

                $file_type = explode(".", $photo);
                $extension = strtolower($file_type[count($file_type) - 1]);
                $photo_path = 'photo-' . time() . '-sms.' . $extension;

                move_uploaded_file($_FILES['photo']['tmp_name'], $destination . $photo_path);

                $return_photo = $photo_path;
            }
        } 

        return $return_photo;
    }
    
    
    
    /* ***************Function _send_admission_email**********************************
     * @type            : Function
     * @function name   : _send_admission_email
     * @description     : this function used to send online admission email 
     * @param           : $data array(); 
     * @return          : null 
     * ********************************************************** */
    private function _send_admission_email() {
     
            $school_id     = $this->data['school']->id;  
            $email_setting = $this->web->get_single('email_settings', array('status' => 1, 'school_id'=>$school_id)); 
                
            if(!empty($email_setting) && $email_setting->mail_protocol == 'smtp'){
                $config['protocol']     = 'smtp';
                $config['smtp_host']    = $email_setting->smtp_host;
                $config['smtp_port']    = $email_setting->smtp_port;
                $config['smtp_timeout'] = $email_setting->smtp_timeout ? $email_setting->smtp_timeout  : 5;
                $config['smtp_user']    = $email_setting->smtp_user;
                $config['smtp_pass']    = $email_setting->smtp_pass;
                $config['smtp_crypto']  = $email_setting->smtp_crypto ? $email_setting->smtp_crypto  : 'tls';
                $config['mailtype'] = isset($email_setting) && $email_setting->mail_type ? $email_setting->mail_type  : 'html';
                $config['charset']  = isset($email_setting) && $email_setting->char_set ? $email_setting->char_set  : 'iso-8859-1';
                $config['priority']  = isset($email_setting) && $email_setting->priority ? $email_setting->priority  : '3';
                
            }elseif(!empty($email_setting) && $email_setting->mail_protocol != 'smtp'){
                $config['protocol'] = $email_setting->mail_protocol;
                $config['mailpath'] = '/usr/sbin/'.$email_setting->mail_protocol; 
                $config['mailtype'] = isset($email_setting) && $email_setting->mail_type ? $email_setting->mail_type  : 'html';
                $config['charset']  = isset($email_setting) && $email_setting->char_set ? $email_setting->char_set  : 'iso-8859-1';
                $config['priority']  = isset($email_setting) && $email_setting->priority ? $email_setting->priority  : '3';
                
            }else{// default    
                $config['protocol'] = 'sendmail';
                $config['mailpath'] = '/usr/sbin/sendmail'; 
            }                              
            
            $config['wordwrap'] = TRUE;            
            $config['newline']  = "\r\n";            
                        
            $this->load->library('email');
            $this->email->initialize($config);            
            
            $this->email->from($this->input->post('email'), $this->input->post('name'));
            $this->email->to($this->data['school']->email);
            $subject = $this->lang->line('contact_mail_from') . ' - '. $this->data['school']->school_name;
            $this->email->subject($subject);       

            $message = '<table border="0" style="border:1px solid lightgray;">';
            $message .= '<tbody>';
            
            $message .= '<tr>';
            $message .= '<td colspan="6"><strong>'. $this->lang->line('basic_information'). '</strong></td>';           
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td width="20%">'.$this->lang->line('name').'</td><td  width="2%">:</td>';
            $message .= '<td width="28%">'.$this->input->post('name').'</td>';            
            $message .= '<td width="20%">'.$this->lang->line('birth_date').'</td><td  width="2%">:</td>';
            $message .= '<td  width="28%">'.$this->input->post('dob').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('gender').'</td><td>:</td>';
            $message .= '<td>'.$this->lang->line($this->input->post('gender')).'</td>';           
            $message .= '<td>'.$this->lang->line('blood_group').'</td><td>:</td>';
            $message .= '<td>'.$this->lang->line($this->input->post('blood_group')).'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('religion').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('religion').'</td>';           
            $message .= '<td>'.$this->lang->line('caste').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('caste').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('email').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('email').'</td>';            
            $message .= '<td>'.$this->lang->line('phone').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('phone').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('national_id').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('national_id').'</td>';            
            $message .= '<td>'.$this->lang->line('health_condition').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('health_condition').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('present_address').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('present_address').'</td>';            
            $message .= '<td>'.$this->lang->line('permanent_address').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('permanent_address').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('previous_school').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('previous_school').'</td>';           
            $message .= '<td>'.$this->lang->line('previous_class').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('previous_class').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td colspan="6"><strong>'. $this->lang->line('academic_information'). '</strong></td>';           
            $message .= '</tr>';
            
            $type = $this->web->get_single('student_types', array('id' => $this->input->post('type_id'), 'school_id'=>$school_id)); 
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('student_type').'</td><td>:</td>';            
            if(isset($type) && !empty($type)){
                $message .= '<td>'.$type->type.'</td>';
            }else{
                $message .= '<td></td>';
            }                      
            
            $class = $this->web->get_single('classes', array('id' => $this->input->post('class_id'), 'school_id'=>$school_id));
            $message .= '<td>'.$this->lang->line('class').'</td><td>:</td>';
            $message .= '<td>'.$class->name.'</td>';
            $message .= '</tr>';
            
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('group').'</td><td>:</td>';
            $message .= '<td>'.$this->lang->line($this->input->post('group')).'</td>';           
            $message .= '<td>'.$this->lang->line('second_language').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('second_language').'</td>';
            $message .= '</tr>';
            
            
            $message .= '<tr>';
            $message .= '<td colspan="6"><strong>'. $this->lang->line('father_information'). '</strong></td>';           
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('father_name').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('father_name').'</td>';            
            $message .= '<td>'.$this->lang->line('father_phone').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('father_phone').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('father_education').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('father_education').'</td>';            
            $message .= '<td>'.$this->lang->line('father_education').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('father_education').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('father_profession').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('father_profession').'</td>';            
            $message .= '<td>'.$this->lang->line('father_designation').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('father_designation').'</td>';
            $message .= '</tr>';
                        
            
            $message .= '<tr>';
            $message .= '<td colspan="6"><strong>'. $this->lang->line('mother_information'). '</strong></td>';           
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('mother_name').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('mother_name').'</td>';            
            $message .= '<td>'.$this->lang->line('mother_phone').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('mother_phone').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('mother_education').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('mother_education').'</td>';            
            $message .= '<td>'.$this->lang->line('mother_education').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('mother_education').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('mother_profession').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('mother_profession').'</td>';            
            $message .= '<td>'.$this->lang->line('mother_designation').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('mother_designation').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td colspan="6"><strong>'. $this->lang->line('guardian_information'). '</strong></td>';           
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('guardian_name').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_name').'</td>';            
            $message .= '<td>'.$this->lang->line('guardian_phone').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_phone').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('relation_with_guardian').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_relation').'</td>';            
            $message .= '<td>'.$this->lang->line('email').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_email').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('national_id').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_national_id').'</td>';           
            $message .= '<td>'.$this->lang->line('profession').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_profession').'</td>';
            $message .= '</tr>';
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('religion').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_religion').'</td>';            
            $message .= '<td>'.$this->lang->line('other_info').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_other_info').'</td>';
            $message .= '</tr>';            
            
            $message .= '<tr>';
            $message .= '<td>'.$this->lang->line('present_address').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_present_address').'</td>';           
            $message .= '<td>'.$this->lang->line('permanent_address').'</td><td>:</td>';
            $message .= '<td>'.$this->input->post('gud_permanent_address').'</td>';
            $message .= '</tr>';            
            
            $message .= '</tbody>';
            $message .= '</table>';

            $this->email->message($message);
                      
           if(!empty($email_setting) && $email_setting->mail_protocol == 'smtp'){
                $this->email->send(); 
                return TRUE;
            }else if(!empty($email_setting) && $email_setting->mail_protocol != 'smtp'){
                $this->email->send();
                return TRUE;
            }else{
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
                $headers .= "From:  ".$this->input->post('name')." < ".$this->input->post('email')." >\r\n";
                $headers .= "Reply-To:  ".$this->input->post('name')." < ".$this->input->post('email')." >\r\n"; 
                mail($this->data['school']->email, $subject, $message, $headers);
                return TRUE;
            }
        
    }

    
    
    
    /*****************Function contact**********************************
    * @type            : Function
    * @function name   : contact
    * @description     : Load "contact" user interface                 
    *                    
    * @param           : null
    * @return          : null 
    * ********************************************************** */
    public function contact() {

        $school_url = $this->uri->segment(1);
        if($this->session->userdata('front_school_id')){           

            $school_id = $this->session->userdata('front_school_id');            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));
            
            // need to check school subscription status
            if(!check_saas_status($school->id, 'is_enable_frontend')){                        
               redirect();
            }
            
            if($school->id != $school_id){                
                $this->session->unset_userdata('front_school_id');              
                $this->session->set_userdata('front_school_id', $school->id);              
                redirect($school->school_url);
            }      
            
            
            if($_POST){               
                
                if($this->_send_email()){
                    $this->session->set_userdata('success', $this->lang->line('email_send_success'));
                }else{
                    $this->session->set_userdata('error', $this->lang->line('email_send_failed'));
                }               
                redirect(site_url($school->school_url.'/contact'));
            }

            $this->data['list'] = TRUE;
            $this->layout->title($this->lang->line('contact_us') . ' | ' . SMS);
            $this->layout->view('contact', $this->data);
        
        }else{   
            
            $school = $this->web->get_single('schools', array('status' => 1, 'school_url'=>$school_url));         
            if(!empty($school)){
                $this->session->set_userdata('front_school_id', $school->id);
                 redirect($school->school_url);
            }
            redirect();              
        }
    }
    
    
    /* ***************Function _send_email**********************************
     * @type            : Function
     * @function name   : _send_email
     * @description     : this function used to send contact email 
     * @param           : $data array(); 
     * @return          : null 
     * ********************************************************** */

    private function _send_email() {

        if($this->input->post('email')){
            
            $school_id     = $this->data['school']->id;  
            $email_setting = $this->web->get_single('email_settings', array('status' => 1, 'school_id'=>$school_id)); 
                
            if(!empty($email_setting) && $email_setting->mail_protocol == 'smtp'){
                $config['protocol']     = 'smtp';
                $config['smtp_host']    = $email_setting->smtp_host;
                $config['smtp_port']    = $email_setting->smtp_port;
                $config['smtp_timeout'] = $email_setting->smtp_timeout ? $email_setting->smtp_timeout  : 5;
                $config['smtp_user']    = $email_setting->smtp_user;
                $config['smtp_pass']    = $email_setting->smtp_pass;
                $config['smtp_crypto']  = $email_setting->smtp_crypto ? $email_setting->smtp_crypto  : 'tls';
                $config['mailtype'] = isset($email_setting) && $email_setting->mail_type ? $email_setting->mail_type  : 'html';
                $config['charset']  = isset($email_setting) && $email_setting->char_set ? $email_setting->char_set  : 'iso-8859-1';
                $config['priority']  = isset($email_setting) && $email_setting->priority ? $email_setting->priority  : '3';
                
            }elseif(!empty($email_setting) && $email_setting->mail_protocol != 'smtp'){
                $config['protocol'] = $email_setting->mail_protocol;
                $config['mailpath'] = '/usr/sbin/'.$email_setting->mail_protocol; 
                $config['mailtype'] = isset($email_setting) && $email_setting->mail_type ? $email_setting->mail_type  : 'html';
                $config['charset']  = isset($email_setting) && $email_setting->char_set ? $email_setting->char_set  : 'iso-8859-1';
                $config['priority']  = isset($email_setting) && $email_setting->priority ? $email_setting->priority  : '3';
                
            }else{// default    
                $config['protocol'] = 'sendmail';
                $config['mailpath'] = '/usr/sbin/sendmail'; 
            }                              
            
            $config['wordwrap'] = TRUE;            
            $config['newline']  = "\r\n";            
                        
            $this->load->library('email');
            $this->email->initialize($config);            
            
            $this->email->from($this->input->post('email'), $this->input->post('name'));
            $this->email->to($this->data['school']->email);
            $subject = $this->lang->line('contact_mail_from') . ' - '. $this->data['school']->school_name;
            $this->email->subject($subject);       

            $message = '<strong>'. $this->lang->line('contact_mail_from'). ' - ' . $this->data['school']->school_name . '.</strong><br/>';          
            $message .= '<br/><br/>';
            $message .= $this->lang->line('name'). ' : ' . $this->input->post('name');
            $message .= '<br/><br/>';      
            $message .= $this->lang->line('email'). ' : ' . $this->input->post('email');
            $message .= '<br/><br/>';
            $message .= $this->lang->line('phone'). ' : ' . $this->input->post('phone');
            $message .= '<br/><br/>';
            $message .= $this->lang->line('subject'). ' : ' . $this->input->post('subject');
            $message .= '<br/><br/>';
            $message .= $this->lang->line('message'). ' : ' . $this->input->post('message');           
            $message .= '<br/>';     

            $this->email->message($message);
                      
           if(!empty($email_setting) && $email_setting->mail_protocol == 'smtp'){
                $this->email->send(); 
                return TRUE;
            }else if(!empty($email_setting) && $email_setting->mail_protocol != 'smtp'){
                $this->email->send();
                return TRUE;
            }else{
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
                $headers .= "From:  ".$this->input->post('name')." < ".$this->input->post('email')." >\r\n";
                $headers .= "Reply-To:  ".$this->input->post('name')." < ".$this->input->post('email')." >\r\n"; 
                mail($this->data['school']->email, $subject, $message, $headers);
                return TRUE;
            }

        }else{
            return FALSE;
        }
    }

}