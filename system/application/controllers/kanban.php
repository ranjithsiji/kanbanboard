<?php

class kanban extends Controller {

    function kanban()    {
        parent::Controller();
		$this->load->database();     
		$this->load->helper('xml');    
		$this->load->helper('url');
		$this->load->library('session');
		
	}
	
	function index() {
		$pagedata = array();

		$projects = array();

		$query = $this->db->query('SELECT id,name FROM kanban_project');
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$projects[$i]=$row;
			$i++;
		}
		$pagedata['projects'] = $projects;	
		$this->load->view('kanban/login', $pagedata);
	}
	
	function about($projectid,$sprintid=0) {
		$pagedata = array();
		$pagedata['projectid'] = $projectid;
		$pagedata['sprintid'] = $sprintid;	
		$projectname = "no-name";
		$projectstartdate = '2010-01-01';
		$query = $this->db->query('SELECT id,name,startdate FROM kanban_project WHERE id = '.$projectid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$projectname = $res[0]['name'];	
			$projectstartdate =  $res[0]['startdate'];	
		} 
		$pagedata['projectname'] = $projectname;	
		
		$query = $this->db->query('SELECT id, name, startdate, enddate FROM `kanban_sprint` where id = '.$sprintid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$sprintid = $res[0]['id'];
			$sprintname = $res[0]['name'];
			$startdate = $res[0]['startdate'];
			$enddate = $res[0]['enddate'];	
		} 
		$pagedata['sprintid'] = $sprintid;	
		$pagedata['sprintname'] = $sprintname;	
		$pagedata['startdate'] = $startdate;	
		$pagedata['enddate'] = $enddate;	
		$this->load->view('kanban/about', $pagedata);
	}
	
	
	function logout() {
		$this->session->sess_destroy();
        kanban::index();
	}

	function login($redirect_to_url='')
	{
		$pagedata['errormessage'] = '';
		$pagedata['redirect_to_url'] = $redirect_to_url;
		if( strlen( trim( $redirect_to_url ) ) <= 0 ) {
			$redirect_to_url = "/kanban/selectproject";
			$pagedata['redirect_to_url'] = $redirect_to_url;
		}
		if( strlen( trim( $this->input->post('redirect_to_url') ) ) > 0 ) {
			$pagedata['redirect_to_url'] = $this->input->post('redirect_to_url');
			$redirect_to_url = $this->input->post('redirect_to_url');
		}

		// check to see if we are loging in or if we are viewing the login page
		if( $this->input->post('login') == FALSE ) {
			$pagedata['errormessage'] = 'Sorry wrong login or password!';
			$this->load->view('kanban/login',$pagedata);
			return;
		}

		// verify username and password in the database
		$md5password = md5( $this->input->post('password') );
		$query = $this->db->query("SELECT id,login FROM kanban_user WHERE password = '".$md5password."' AND login = '".$this->input->post('login')."'");
		if ($query->num_rows() <= 0)
		{
			// Oops! no such user
			$pagedata['errormessage'] = 'Sorry wrong login or password!';
			$this->load->view('kanban/login',$pagedata);
			return;
		}
		$res = $query->result_array();
		$id = $res[0]['id'];	
		// Set session / login OK
		$this->session->set_userdata('login', $this->input->post('login') );
		$this->session->set_userdata('id', $id );
		$this->session->set_userdata('logged_in', 'TRUE');
		$this->session->set_userdata('sess_expiration', '0');

		header("Location: ".$redirect_to_url );
	}
	
	
	function register()
	{
		$pagedata['errormessage'] = '';
		$pagedata['login'] = 'loginname';
		$pagedata['password'] = 'passwd';
		$pagedata['password2'] = 'passwd2';
		
		// check to see if we are loging in or if we are viewing the login page
		if( $this->input->post('login') == FALSE ) {
			$pagedata['errormessage'] = '';
			$this->load->view('kanban/register',$pagedata);
			return;
		}
		
		$pagedata['login'] = $this->input->post('login');
		$pagedata['password'] = $this->input->post('password');
		$pagedata['password2'] = $this->input->post('password2');
		
		if( $this->input->post('password') != $this->input->post('password2')  ) {
			$pagedata['errormessage'] = 'Oops! the password was not equal ! Try again.';
			$this->load->view('kanban/register',$pagedata);
			return;
		}
		// verify if the login allready exists in the database
		$query = $this->db->query("SELECT id FROM kanban_user WHERE login = '".$this->input->post('login')."'");
		if ($query->num_rows() >= 1)
		{
			// Oops! there is allreadt a user with that login
			$pagedata['errormessage'] = 'Sorry login allready exists, please choose a different login-name!';
			$this->load->view('kanban/register',$pagedata);
			return;
		}
		// Store in DB
		$md5password = md5( $this->input->post('password') );
		$data = array(
					   'login' => $this->input->post('login'),
					   'password' => $md5password
					);			
		$this->db->insert('kanban_user', $data); 
		
		// Retrieve the ID from the db for the new user
		$query = $this->db->query("SELECT id FROM kanban_user WHERE password = '".$md5password."' AND login = '".$this->input->post('login')."'");
		if ($query->num_rows() <= 0)
		{
			// Oops! no such user
			$pagedata['errormessage'] = 'Oops! database problems, unable to find the NEW user...';
			$this->load->view('kanban/register',$pagedata);
			return;
		}
		$res = $query->result_array();
		$id = $res[0]['id'];	
		// Set session / login OK
		$this->session->set_userdata('login', $this->input->post('login') );
		$this->session->set_userdata('id', $id );
		$this->session->set_userdata('logged_in', 'TRUE');
		$this->session->set_userdata('sess_expiration', '0');
		$redirect_to_url = "/kanban/selectproject";
		header("Location: ".$redirect_to_url );
	}
	
	
	function selectproject() {
		if ($this->session->userdata('logged_in') != TRUE) {
			kanban::login( uri_string() );
			return;
	    }
		$id = $this->session->userdata('id');
		$pagedata = array();

		$projects = array();

		$query = $this->db->query('SELECT id,name FROM kanban_project WHERE user_id = "'.$id.'"');
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$projects[$i]=$row;
			$i++;
		}
		$pagedata['projects'] = $projects;	
		$this->load->view('kanban/selectproject', $pagedata);
	}
	
	
	function status($projectid,$sprintid=0) {
		if ($this->session->userdata('logged_in') != TRUE) {
			kanban::login( uri_string() );
			return;
	    }
	
		$pagedata = array();
		
		if( $sprintid <=0 ) {
			$query = $this->db->query('SELECT max(id) as id FROM `kanban_sprint` where project_id = '.$projectid);
			$sprintid=0;
			if ($query->num_rows() > 0)	{
				$res = $query->result_array();		
				$sprintid = $res[0]['id'];
			} 
		}
		
		$pagedata['projectid'] = $projectid;
		$projectname = "no-name";
		$projectstartdate = '2010-01-01';
		$query = $this->db->query('SELECT id,name,startdate FROM kanban_project WHERE id = '.$projectid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$projectname = $res[0]['name'];	
			$projectstartdate =  $res[0]['startdate'];	
		} 
		$pagedata['projectname'] = $projectname;	
		
		$query = $this->db->query('SELECT id, name, startdate, enddate FROM `kanban_sprint` where id = '.$sprintid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$sprintid = $res[0]['id'];
			$sprintname = $res[0]['name'];
			$startdate = $res[0]['startdate'];
			$enddate = $res[0]['enddate'];	
		} 
		$pagedata['sprintid'] = $sprintid;	
		$pagedata['sprintname'] = $sprintname;	
		$pagedata['startdate'] = $startdate;	
		$pagedata['enddate'] = $enddate;	
		
		$e = strtotime($enddate);
		$s = strtotime($startdate);
		$totaldays = floor( ($e - $s) / 86400 ); // strtotime($enddate) � strtotime($startdate);
		$pagedata['totaldays'] = $totaldays;
		
		$now = date( "Y-m-d" );
		$e = strtotime($now);
		$s = strtotime($enddate);
		$daysleft = floor( ($s - $e) / 86400 ); // strtotime($enddate) � strtotime($startdate);
		if( $daysleft < 0 ) {
			$daysleft = 0;
		}
		$pagedata['daysleft'] = $daysleft;
		
		$sql="SELECT sum(estimation) as total FROM `kanban_item` where sprint_id = ".$sprintid." and not enddate = '0000-00-00'";
//		$sql="SELECT sum(estimation) as total FROM `kanban_item` where sprint_id = ".$sprintid;
		$query = $this->db->query( $sql );
		$subtotal=0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			$subtotal = $res[0]['total'];	
		} 
		if( $daysleft <= 0 ) {
			$velocity = $subtotal / $totaldays;
		} else {
			if( ($totaldays-$daysleft) == 0 ) $velocity = 0;
			else $velocity = $subtotal / ($totaldays-$daysleft);
		} 
		
		$pagedata['velocity'] = $velocity;
		
		//
		// Burndown chart
		//		
		$sql="SELECT sum(estimation) as total FROM `kanban_item` where sprint_id = ".$sprintid;
		$query = $this->db->query( $sql );
		$totalestimation=0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			$totalestimation = $res[0]['total'];	
		} 
		$pagedata['totalestimation'] = $totalestimation;
		$e = strtotime($enddate);
		$s = strtotime($startdate);
		$totaldays = floor( ($e - $s) / 86400 ); // strtotime($enddate) � strtotime($startdate);
		$pagedata['totaldays'] = $totaldays;
		$pointsperday = $totalestimation / $totaldays;
		$diagrambaseline = array();
		$days = array();
		$baseline = $totalestimation;
		for( $i = 0; $i < $totaldays; $i++ ) {
			$diagrambaseline[ $i ] = floor( $baseline );
			$days[ $i ] = $i;
			$baseline = $baseline - $pointsperday;
		}
		$pagedata['diagrambaseline'] = $diagrambaseline;
		$pagedata['days'] = $days;
		
		$sql="SELECT enddate,sum(estimation) as total FROM `kanban_item` where sprint_id = ".$sprintid." and not enddate = '0000-00-00' and estimation > 0 group by enddate order by enddate";
		$diagramactual = array();
		$query = $this->db->query( $sql );
		$i=0;
		$points = $totalestimation;
		foreach ($query->result_array() as $row)
		{			
			$e = strtotime( $row['enddate'] );
			$s = strtotime($startdate);
			$day = floor( ($e - $s) / 86400 ) ; 
			if( $day > $i ) {
				$averageperday = floor( $row['total'] / ($day-$i) );
				while( $i <= $day ) {									
					$diagramactual[$i] =  $points;					
					$points = $points - $averageperday;
					$i++;
				}
			} else {
				$points = $points - $row['total'];
				$diagramactual[$i] = $points;
				$i++;
			}
		}
		$pagedata['diagramactual'] = $diagramactual;

		
		//
		// Inflow/Outflow for project as a whole
		//
				
		$e = strtotime($now);
		$s = strtotime($projectstartdate);
		$totaldays = floor( ($e - $s) / 86400 ); // strtotime($enddate) � strtotime($startdate);
		$pagedata['dayssofar'] = $totaldays;
				
		$diagraminflow = array();
		$sql = 'SELECT added,count(added) as total FROM kanban_item where project_id = '.$projectid.' and added > '.$projectstartdate.' and not added = "0000-00-00" group by added order by added';
		$query = $this->db->query( $sql );
		$i=0;
		foreach ($query->result_array() as $row)
		{			
			$e = strtotime( $row['added'] );
			$s = strtotime($projectstartdate);
			$day = floor( ($e - $s) / 86400 ) ; 
			if( $day > $i ) {
				while( $i < $day ) {				
					$diagraminflow[$i] = 0;
					$i++;
				}
			}
			$diagraminflow[$i] = $row['total'];
			$i++;			
		}
		while( $i <= $totaldays ) {
			$diagraminflow[$i] = 0;
			$i++;
		}
		
		$pagedata['diagraminflow'] = $diagraminflow;
		
		$diagramoutflow = array();
		$sql = 'SELECT enddate,count(enddate) as total FROM kanban_item where project_id = '.$projectid.' and enddate > '.$projectstartdate.' and not enddate = "0000-00-00" group by enddate order by enddate';
		$query = $this->db->query( $sql );
		$i=0;
		foreach ($query->result_array() as $row)
		{			
			$e = strtotime( $row['enddate'] );
			$s = strtotime($projectstartdate);
			$day = floor( ($e - $s) / 86400 ) ; 
			if( $day > $i ) {
				while( $i < $day ) {				
					$diagramoutflow[$i] = 0;
					$i++;
				}
			}
			$diagramoutflow[$i] = $row['total'];
			$i++;			
		}
		while( $i <= $totaldays ) {
			$diagramoutflow[$i] = 0;
			$i++;
		}
		$pagedata['diagramoutflow'] = $diagramoutflow;
		
		$diagramoutflowperweek = array();
		$sql = 'SELECT concat_ws("-",year(enddate),week(enddate)) as yearweek,count(enddate) as total FROM kanban_item where project_id = '.$projectid.' and enddate > "'.$projectstartdate.'" and not enddate = "0000-00-00" group by yearweek order by yearweek';
		$query = $this->db->query( $sql );
		$i=0;
		foreach ($query->result_array() as $row)
		{			
			$diagramoutflowperweek[ $i ] = $row;
			$i++;			
		}
		$pagedata['diagramoutflowperweek'] = $diagramoutflowperweek;
		
		
		$history=array();
		$sql="SELECT i.id,i.heading,s.name as sprintname,i.enddate,week(i.enddate) as weeknumber,estimation,priority FROM kanban_item i,kanban_sprint s  where i.project_id = ".$projectid." and not i.enddate = '0000-00-00' and sprint_id = s.id order by s.enddate,i.enddate";
		$query = $this->db->query( $sql );
		$i=0;
		foreach ($query->result_array() as $row)
		{			
			$history[$i] = $row;
			$i++;			
		}		
		$pagedata['history'] = $history;
		$this->load->view('kanban/status', $pagedata);
	}
	
	function settings($projectid,$sprintid=0) {
	
		if ($this->session->userdata('logged_in') != TRUE) {
			kanban::login( uri_string() );
			return;
	    }
	    
		$pagedata = array();
		
		if( $sprintid <=0 ) {
			$query = $this->db->query('SELECT max(id) as id FROM `kanban_sprint` where project_id = '.$projectid);
			$sprintid=0;
			if ($query->num_rows() > 0)	{
				$res = $query->result_array();		
				$sprintid = $res[0]['id'];
			} 
		}
		
		$pagedata['projectid'] = $projectid;
		$projectname = "no-name";
		$query = $this->db->query('SELECT id,name FROM kanban_project WHERE id = '.$projectid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$projectname = $res[0]['name'];	
		} 
		$pagedata['projectname'] = $projectname;	
		
		$query = $this->db->query('SELECT id, name, startdate, enddate FROM `kanban_sprint` where id = '.$sprintid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$sprintid = $res[0]['id'];
			$sprintname = $res[0]['name'];
			$startdate = $res[0]['startdate'];
			$enddate = $res[0]['enddate'];	
		} 
		$pagedata['sprintid'] = $sprintid;	
		$pagedata['sprintname'] = $sprintname;	
		$pagedata['startdate'] = $startdate;	
		$pagedata['enddate'] = $enddate;	

		$sprints = array();

		$sql='SELECT id, name, startdate, enddate FROM `kanban_sprint` where project_id = '.$projectid.' ORDER BY startdate';
		$query = $this->db->query($sql);
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$sprints[$i]=$row;			
			$i++;
		}
		$pagedata['sprints'] = $sprints;

		$groups = array();
		$sql='SELECT id,name FROM kanban_group WHERE project_id = '.$projectid.' ORDER BY displayorder';
		$query = $this->db->query($sql);
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$groups[$i]=$row;			
			$i++;
		}
		$pagedata['groups'] = $groups;

		$this->load->view('kanban/settings', $pagedata);
	}

	function project($projectid,$sprintid=0)    {
	
		if ($this->session->userdata('logged_in') != TRUE) {
			kanban::login( uri_string() );
			return;
	    }
	    
		if( $sprintid <=0 ) {
		
			$today = date( "Y-m-d" );
			$sql = 'SELECT id FROM `kanban_sprint` where project_id = '.$projectid.' AND startdate <= "'.$today.'" AND enddate >= "'.$today.'"';
			$query = $this->db->query( $sql );
			
			$sprintid=0;
			if ($query->num_rows() > 0)	{
				$res = $query->result_array();		
				$sprintid = $res[0]['id'];
			}  else {
				$query = $this->db->query('SELECT max(id) as id FROM `kanban_sprint` where project_id = '.$projectid);
				$sprintid=0;
				if ($query->num_rows() > 0)	{
					$res = $query->result_array();		
					$sprintid = $res[0]['id'];
				} 
			}
		}
		kanban::sprint($projectid,$sprintid);
	}

	function sprint($projectid,$sprintid)    {
	
		if ($this->session->userdata('logged_in') != TRUE) {
			kanban::login( uri_string() );
			return;
	    }
	    
		$pagedata = array();
		$pagedata['projectid'] = $projectid;
		$projectname = "no-name";
		$query = $this->db->query('SELECT id,name FROM kanban_project WHERE id = '.$projectid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$projectname = $res[0]['name'];	
		} 
		$pagedata['projectname'] = $projectname;	
		
		$query = $this->db->query('SELECT id, name, startdate, enddate FROM `kanban_sprint` where id = '.$sprintid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$sprintid = $res[0]['id'];
			$sprintname = $res[0]['name'];
			$startdate = $res[0]['startdate'];
			$enddate = $res[0]['enddate'];	
		} 
		$pagedata['sprintid'] = $sprintid;	
		$pagedata['sprintname'] = $sprintname;	
		$pagedata['startdate'] = $startdate;	
		$pagedata['enddate'] = $enddate;	

		$sprints = array();

		$sql='SELECT id, name, startdate, enddate FROM `kanban_sprint` where project_id = '.$projectid.' ORDER BY startdate';
		$query = $this->db->query($sql);
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$sprints[$i]=$row;			
			$i++;
		}
		$pagedata['sprints'] = $sprints;

	
	
		$groups = array();

		$sql='SELECT id,name FROM kanban_group WHERE project_id = '.$projectid.' ORDER BY displayorder';
		$query = $this->db->query($sql);
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$groups[$i]=$row;			
			$i++;
		}
		$pagedata['groups'] = $groups;

		$tasks = array();
		$query = $this->db->query('SELECT k.id as taskid,heading,description,group_id,name as groupname,priority,estimation,colortag FROM kanban_item k,kanban_group g WHERE group_id = g.id AND k.project_id = '.$projectid.' AND k.sprint_id = '.$sprintid.' ORDER BY displayorder,priority DESC');
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$tasks[$i]=$row;
			$i++;
		}
		$pagedata['tasks'] = $tasks;
		
		$tickers = array();

		$sql='SELECT id, enddate, message FROM `kanban_ticker` where project_id = '.$projectid.'  ORDER BY enddate';
		$query = $this->db->query($sql);
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$tickers[$i]=$row;			
			$i++;
		}
		$pagedata['tickers'] = $tickers;	
		
		$sql="SELECT sum(estimation) as total FROM `kanban_item` where sprint_id = ".$sprintid;
		$query = $this->db->query( $sql );
		$totalestimation=0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			$totalestimation = $res[0]['total'];	
		} 
		$pagedata['totalestimation'] = $totalestimation;
		$e = strtotime($enddate);
		$s = strtotime($startdate);
		$totaldays = floor( ($e - $s) / 86400 ); // strtotime($enddate) � strtotime($startdate);
		$pagedata['totaldays'] = $totaldays;
		$pointsperday = $totalestimation / $totaldays;
		$diagrambaseline = array();
		$days = array();
		$baseline = $totalestimation;
		for( $i = 0; $i < $totaldays; $i++ ) {
			$diagrambaseline[ $i ] = floor( $baseline );
			$days[ $i ] = $i;
			$baseline = $baseline - $pointsperday;
		}
		$pagedata['diagrambaseline'] = $diagrambaseline;
		$pagedata['days'] = $days;
		
		$sql="SELECT enddate,sum(estimation) as total FROM `kanban_item` where sprint_id = ".$sprintid." and not enddate = '0000-00-00' and estimation > 0 group by enddate order by enddate";
		$diagramactual = array();
		$query = $this->db->query( $sql );
		$i=0;
		$points = $totalestimation;
		foreach ($query->result_array() as $row)
		{			
			$e = strtotime( $row['enddate'] );
			$s = strtotime($startdate);
			$day = floor( ($e - $s) / 86400 ) ; 
			if( $day > $i ) {
				$averageperday = floor( $row['total'] / ($day-$i) );
				while( $i <= $day ) {				
					$diagramactual[$i] =  $points;					
					$points = $points - $averageperday;
					$i++;
				}
			} else {
				$points = $points - $row['total'];
				$diagramactual[$i] = $points;
				$i++;
			}
		}
		$pagedata['diagramactual'] = $diagramactual;

		$this->load->view('kanban/board', $pagedata);
	}

	function taskdetails($projectid,$taskid) {
//		$projectid = $this->input->post('projectid');
//		$taskid = $this->input->post('taskid');
		$sql = 'SELECT heading,priority,description,estimation,colortag,sprint_id FROM kanban_item WHERE project_id = '.$projectid.' AND id = '.$taskid;
		$query = $this->db->query( $sql );
		$jsonarray = array();
		if ($query->num_rows() > 0)	{
			$row = $query->row();	
			$jsonarray[ 'heading' ] = $row->heading;
			$jsonarray[ 'taskdescription' ] = $row->description;
			$jsonarray[ 'priority' ] = $row->priority;
			$jsonarray[ 'estimation' ] = $row->estimation;	
			$jsonarray[ 'projectid' ] = $projectid;		
			$jsonarray[ 'sprintid' ] = $row->sprint_id;	
			$jsonarray[ 'taskid' ] = $taskid;		
			$jsonarray[ 'colortag' ] = $row->colortag;			
		} 
		$jsondata = json_encode($jsonarray);
		echo $jsondata; 
	}

	function addtask() {
		$projectid = $this->input->post('projectid');
		$sprintid = $this->input->post('sprintid');
		$group = $this->input->post('group');
		$heading = $this->input->post('heading');
		$priority = $this->input->post('priority');
		$taskdescription = $this->input->post('taskdescription');
		$estimation = $this->input->post('estimation');
		$colortag = $this->input->post('colortag');
		$today = date( "Y-m-d" );
		echo "proj id=".$projectid."<br>";
		echo "group=".$group."<br>";
		echo "head=".$heading."<br>";
		echo "prio=".$priority."<br>";	
		echo "desc=".$taskdescription."<br>";
		echo "est=".$estimation."<br>";
		echo "tag=".$colortag."<br>";
		$data = array(
			'project_id' => $projectid,
			'group_id' => $group,
			'heading' => $heading,
			'priority' => $priority,
			'description' => $taskdescription,
			'estimation' => $estimation,
			'colortag' => $colortag,
			'added' => $today,
			'startdate' => 'null',
			'enddate' => 'null',
			'sprint_id' => $sprintid
			);
		$this->db->insert('kanban_item', $data);	
		$query = $this->db->query('SELECT max(id) as lastid FROM kanban_item');
		$taskid = 0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$taskid = $res[0]['lastid'];	
		} 
 		echo "task-id=".$taskid."<br>";	
		echo "inserted into db!";
	}


	function updatetask() {
		$taskid = $this->input->post('taskid');
		$heading = $this->input->post('heading');
		$priority = $this->input->post('priority');
		$taskdescription = $this->input->post('taskdescription');
		$estimation = $this->input->post('estimation');
		$colortag = $this->input->post('colortag');
		$newsprintid = $this->input->post('newsprintid');
		echo "head=".$heading."<br>";
		echo "prio=".$priority."<br>";	
		echo "desc=".$taskdescription."<br>";
		echo "est=".$estimation."<br>";
		echo "tag=".$colortag."<br>";
		$data = array(			
			'heading' => $heading,
			'priority' => $priority,
			'description' => $taskdescription,
			'estimation' => $estimation,
			'colortag' => $colortag,
			'sprint_id' => $newsprintid
			);
		$this->db->where('id', $taskid);
		$this->db->update('kanban_item', $data);	
		
		echo "db updated!";
	}

	function move() {
		$from = $this->input->post('from');
		$to = $this->input->post('to');
		$last = $this->input->post('last');
		$task = $this->input->post('task');
		$today = date( "Y-m-d" );
		echo "from=".$from."<br>";
		echo "to=".$to."<br>";
		echo "task=".$task."<br>";	
		echo "last=".$last."<br>";	
		if( $to == $last ) {
			$data = array(
				'group_id' => $to,
				'enddate' => $today				
				);		
		} else {
			$data = array(
				'group_id' => $to,
				'startdate' => $today,
				'enddate' => 'null'
				);		
		}
		$this->db->where('id', $task);
		$this->db->update('kanban_item', $data);
		echo "moved in db!";
	}

	function editgroup() {
		$name = $this->input->post('editgroup_name');
		$groupid = $this->input->post('editgroup_groupid');
		$projectid = $this->input->post('editgroup_projectid');
		$data = array(
			'name' => $name
		);
		$this->db->where('id', $groupid);
		$this->db->update('kanban_group', $data);		
	}
	
	function deletegroup() {
		$groupid = $this->input->post('deletegroup_groupid');
		$firstgroupid = $this->input->post('deletegroup_firstgroupid');
		$projectid = $this->input->post('deletegroup_projectid');
		//
		// First lets move the tasks in this group before we delete it
		// 
		$data = array(
			'group_id' => $firstgroupid
		);
		$this->db->where('group_id', $groupid);
		$this->db->update('kanban_item', $data);		
		
		//
		// Delete the group
		// 
		$this->db->where('id', $groupid);
		$this->db->delete('kanban_group'); 
	}
	
	
	function addgroup() {
		$name = $this->input->post('newgroup_name');
		echo "name=".$name."<br>";
		$projectid = $this->input->post('newgroup_projectid');
		echo "projectid=".$projectid."<br>";
		
		$query = $this->db->query('SELECT min(displayorder)+1 as secondposition FROM kanban_group WHERE project_id = '.$projectid);
		$displayorder = 10000;		
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$displayorder = $res[0]['secondposition'];	
		} 
		$data = array(
			'name' => $name,
			'project_id' => $projectid,
			'displayorder' => $displayorder
			);
		$this->db->insert('kanban_group', $data);	
		$query = $this->db->query('SELECT max(id) as lastid FROM kanban_group');
		$groupid = 0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$groupid = $res[0]['lastid'];	
		} 
 		echo "group-id=".$groupid."<br>";	
		echo "inserted into db!";
	}

	function changegrouporder() {
		$grouporder = rtrim( $this->input->post('grouporder'), ',');
		echo "grouporder=".$grouporder."<br>";
		$projectid = $this->input->post('projectid');
		echo "projectid=".$projectid."<br>";
		$listofgroups = explode( ",", $grouporder );
		$index = 1;
		foreach( $listofgroups as $groupid ) {
			$data = array(
				'displayorder' => $index
				);
			$this->db->where('id', $groupid);
			$this->db->update('kanban_group', $data);	
			$index++;
		}
 
	}

	function addsprint() {
		$name = $this->input->post('newsprint_name');
		$startdate = $this->input->post('newsprint_startdate');
		$enddate = $this->input->post('newsprint_enddate');
		$projectid = $this->input->post('newsprint_projectid');
		
		$data = array(
			'name' => $name,
			'project_id' => $projectid,
			'startdate' => $startdate,
			'enddate' => $enddate
			);
		$this->db->insert('kanban_sprint', $data);	
	}
	
	function editsprint() {
		$name = $this->input->post('editsprint_name');
		$sprintid = $this->input->post('editsprint_sprintid');
		$startdate = $this->input->post('editsprint_startdate');
		$enddate = $this->input->post('editsprint_enddate');
		$data = array(
			'name' => $name,
			'startdate' => $startdate,
			'enddate' => $enddate
		);
		$this->db->where('id', $sprintid);
		$this->db->update('kanban_sprint', $data);		
	}

	function deletesprint() {
		$sprintid = $this->input->post('deletesprint_sprintid');
		$projectid = $this->input->post('deletesprint_projectid');
		//
		// First lets delete all tasks that belongs to this sprint
		// 
		$this->db->where('sprint_id', $sprintid);
		$this->db->delete('kanban_item');		
		
		//
		// Delete the sprint
		// 
		$this->db->where('id', $sprintid);
		$this->db->delete('kanban_sprint'); 
	}
	
	function movetasksbetweensprints() {
		$from = $this->input->post('movetasksbetweensprints_from');
		$to = $this->input->post('movetasksbetweensprints_to');
		$projectid = $this->input->post('movetasksbetweensprints_projectid');

		// We do not create doubles
		if( $to == $from ) return;

		// Figure out which is the last group, i.e. containing the ones we should NOT move
		$sql = 'SELECT id,displayorder FROM kanban_group WHERE project_id = '.$projectid.' order by displayorder asc limit 1';
		$query = $this->db->query( $sql );
		$firstgroupid = 0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$firstgroupid = $res[0]['id'];	
		} 

		// Figure out which is the last group, i.e. containing the ones we should NOT move
		$sql = 'SELECT id,displayorder FROM kanban_group WHERE project_id = '.$projectid.' order by displayorder desc limit 1';
		$query = $this->db->query( $sql );
		$lastgroupid = 0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$lastgroupid = $res[0]['id'];	
		} 
		
		// SELECT k.id as taskid,heading,description,group_id,name as groupname,priority,estimation,colortag,k.startdate,k.enddate,k.added FROM kanban_item k,kanban_group g WHERE group_id = g.id AND k.project_id = 3 AND k.sprint_id = 1 AND not k.group_id = 5 ORDER BY displayorder,priority
		$sql = 'SELECT k.id as taskid,heading,description,group_id,name as groupname,priority,estimation,colortag,k.startdate,k.enddate,k.added FROM kanban_item k,kanban_group g WHERE group_id = g.id AND k.project_id = '.$projectid.' AND k.sprint_id = '.$from.' AND not k.group_id = '.$lastgroupid.' ORDER BY displayorder,priority';
		$query = $this->db->query($sql);
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$tasks[$i]=$row;
			$sprintid = $to;
			$group = $row['group_id']; 
			// we move to the first group, as that is where we always start a sprint
			// $group = $firstgroupid;
			$heading = $row['heading'];
			$priority = $row['priority'];
			$taskdescription = $row['description'];
			$estimation  = $row['estimation'];
			$colortag  = $row['colortag'];
			$startdate = $row['startdate'];
			$enddate= $row['enddate'];
			$added = $row['added'];
			kanban::addtaskwithdetails( $projectid,$sprintid,$group,$heading,$priority,$taskdescription,$estimation,$colortag,$startdate,$enddate,$added );
			$i++;
		}
		echo "Moved ".$i." tasks";
	}
	
	function addtaskwithdetails( $projectid,$sprintid,$group,$heading,$priority,$taskdescription,$estimation,$colortag,$startdate,$enddate,$added  ) 
	{
		$today = date( "Y-m-d" );
		echo "proj id=".$projectid."<br>";
		echo "group=".$group."<br>";
		echo "head=".$heading."<br>";
		echo "prio=".$priority."<br>";	
		echo "desc=".$taskdescription."<br>";
              		echo "est=".$estimation."<br>";
		echo "tag=".$colortag."<br>";
		$data = array(
			'project_id' => $projectid,
			'group_id' => $group,
			'heading' => $heading,
			'priority' => $priority,
			'description' => $taskdescription,
			'estimation' => $estimation,
			'colortag' => $colortag,
			'added' => $added,
			'startdate' => $startdate,
			'enddate' => 'null',
			'sprint_id' => $sprintid,
			
			);
		$this->db->insert('kanban_item', $data);	
		$query = $this->db->query('SELECT max(id) as lastid FROM kanban_item');
		$taskid = 0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$taskid = $res[0]['lastid'];	
		} 
 		echo "task-id=".$taskid."<br>";	
		echo "inserted into db!";
	}
	
	function sprintdetails($projectid,$sprintid) {

		$sql = 'SELECT name, startdate, enddate FROM kanban_sprint WHERE id = '.$sprintid;
		$query = $this->db->query( $sql );
		$jsonarray = array();
		if ($query->num_rows() > 0)	{
			$row = $query->row();	
			$jsonarray[ 'name' ] = $row->name;
			$jsonarray[ 'startdate' ] = $row->startdate;
			$jsonarray[ 'enddate' ] = $row->enddate;
		} 
		$jsondata = json_encode($jsonarray);
		echo $jsondata; 
	}

	function addproject() {
		$name = $this->input->post('name');
		// echo "name=".$name."<br>";
		$user_id = $this->session->userdata('id');
		//
		// Add project
		//
		$today = date( "Y-m-d" );
		$data = array(
			'name' => $name,
			'user_id' => $user_id,
			'startdate' => $today
			);
		$this->db->insert('kanban_project', $data);	
		$query = $this->db->query('SELECT max(id) as lastid FROM kanban_project');
		$projectid = 0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$projectid = $res[0]['lastid'];	
		} 
		
		//
		// Add a initial sprint, with dummy values
		// 
		$today = date( "Y-m-d" );
		$sprintname = "first sprint";
		$startdate = $today;
		$in4weeks = date( "Y-m-d" , (time()  + (4 * 7 * 24 * 60 * 60) ) );
		$enddate = $in4weeks;
		$data = array(
			'name' => $name,
			'project_id' => $projectid,
			'startdate' => $startdate,
			'enddate' => $enddate
			);
		$this->db->insert('kanban_sprint', $data);	
		$query = $this->db->query('SELECT max(id) as lastid FROM kanban_sprint');
		$sprintid = 0;
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			$sprintid = $res[0]['lastid'];	
		} 
		

		//
		// Add 'unassigned', 'Ongoing' and 'finished' groups
		//		
		$name = 'Unassigned';
		$displayorder = 1;
		$data = array(
			'name' => $name,
			'project_id' => $projectid,
			'displayorder' => $displayorder
			);
		$this->db->insert('kanban_group', $data);

		$name = 'Ongoing';
		$displayorder = 1;
		$data = array(
			'name' => $name,
			'project_id' => $projectid,
			'displayorder' => $displayorder
			);
		$this->db->insert('kanban_group', $data);

		$name = 'Finished';
		$displayorder = 1;
		$data = array(
			'name' => $name,
			'project_id' => $projectid,
			'displayorder' => $displayorder
			);
		$this->db->insert('kanban_group', $data);

		$redirect_to_url="/kanban/project/".$projectid;

		header("Location: ".$redirect_to_url );
	}


	function tickers($projectid,$sprintid)    {
		$pagedata = array();
		$pagedata['projectid'] = $projectid;
		$projectname = "no-name";
		$query = $this->db->query('SELECT id,name FROM kanban_project WHERE id = '.$projectid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$projectname = $res[0]['name'];	
		} 
		$pagedata['projectname'] = $projectname;	
		
		$query = $this->db->query('SELECT id, name, startdate, enddate FROM `kanban_sprint` where id = '.$sprintid);
		if ($query->num_rows() > 0)	{
			$res = $query->result_array();		
			// print_r( $release );		
			$sprintid = $res[0]['id'];
			$sprintname = $res[0]['name'];
			$startdate = $res[0]['startdate'];
			$enddate = $res[0]['enddate'];	
		} 
		$pagedata['sprintid'] = $sprintid;	
		$pagedata['sprintname'] = $sprintname;	
		$pagedata['startdate'] = $startdate;	
		$pagedata['enddate'] = $enddate;	

		$tickers = array();

		$sql='SELECT id, enddate, message FROM `kanban_ticker` where project_id = '.$projectid.' ORDER BY enddate';
		$query = $this->db->query($sql);
		$i=0;
		foreach ($query->result_array() as $row)
		{
			$tickers[$i]=$row;			
			$i++;
		}
		$pagedata['tickers'] = $tickers;		
		
		$this->load->view('kanban/tickers', $pagedata);
	}


	function deleteticker($tickerid) {		
		$this->db->where('id', $tickerid);
		$this->db->delete('kanban_ticker');						
	}
	
	
	function addticker() {
		$msg = $this->input->post('msg');
		$enddate = $this->input->post('enddate');
		$projectid = $this->input->post('projectid');
		$sprintid = $this->input->post('sprintid');
		
		$data = array(
			'message' => $msg,
			'project_id' => $projectid,
			'enddate' => $enddate
			);
		$this->db->insert('kanban_ticker', $data);	
		
		$redirect_to_url="/kanban/tickers/".$projectid."/".$sprintid;
		header("Location: ".$redirect_to_url );
	}
	
	function jsonStep1() {
		$res = array();
		$res["php_message"] = "I am PHP";
		echo json_encode($res);
	}

	function json() {
		$data = array();
		$this->load->view('kanban/json', $data);
	}


	function tabs() {
		$data = array();
		$this->load->view('kanban/tabs', $data);
	}

	function get() {
		//$res = json_decode($_REQUEST['data'], true);
		$res=array();
		$res["php_message"] = "I am PHP";
		echo json_encode($res);
	}
}

?>
