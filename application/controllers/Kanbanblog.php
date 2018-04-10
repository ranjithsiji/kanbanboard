<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kanbanblog extends CI_Controller {

    public function __construct() {
		parent::__construct();
	}
	
	function index()
	{
		$pagedata = array();
		$pagedata['errormessage'] = '';
		$this->load->view('kanban/bloghome',$pagedata);
	}
	
	function tutorials()
	{
		$pagedata = array();
		$pagedata['errormessage'] = '';
		$this->load->view('kanban/blogtutorial',$pagedata);
	}
	
	function about()
	{
		$pagedata = array();
		$pagedata['errormessage'] = '';
		$this->load->view('kanban/blogabout',$pagedata);
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */
?>
