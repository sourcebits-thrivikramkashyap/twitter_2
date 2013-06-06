<?php
include($_SERVER['DOCUMENT_ROOT']."/application/libraries/EpiTwitter.php");

/*
 * @class : Twitter
 * @desc  : class for twitter connectivity and twitter apis.
 */
class Twitter_model extends CI_Model {
	
	var $twitter;
	var $twitter_url;
	
	protected $consumer_key = 'NMStUVVKyL8kvnJ4SNCEIg';
	protected $consumer_secret = 'sIcOGLQ2xVA3YAAVXS637km6ESYJfBjg7Scfhl8s';
	protected $token = '';
	protected $token_secret = '';
	
	public function __construct()
	{
		 // Call the Model constructor
		 parent::__construct();
		 
		 $this->load->library('session');
		 $this->token = $this->session->userdata('token');
		 $this->token_secret = $this->session->userdata('token_secret');	 
	}
	
	/*
	 * function : connect
	 * desc		: redirects to twitter to approve the use of the user's twitter credentials
	 * 			  and returns back to the specified callback url, gets and sets access tokens.
	 * param	: callback_url  	  			  
	 */
	public function connect($callback_url)
	{		
		//$twitter = new EpiTwitter('NMStUVVKyL8kvnJ4SNCEIg', 'sIcOGLQ2xVA3YAAVXS637km6ESYJfBjg7Scfhl8s');
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);
		$twitter_url = $twitter->getAuthenticateUrl(NULL, array('oauth_callback' => $callback_url));
		
		if(empty($_GET['oauth_token']))
		{
			header('Location:'.$twitter_url);
			exit;
		}
		else
		{
			$twitter->setToken($_GET['oauth_token']);
			$token = $twitter->getAccessToken();
			$twitter->setToken($token->oauth_token, $token->oauth_token_secret);
			//$twuser = $twitter->get_accountVerify_Credentials();die("here");
			
			// set session variables
			$this->load->library('session');
			$this->session->set_userdata('username', 'testtwlogin');
			$this->session->set_userdata('token', $token->oauth_token);
			$this->session->set_userdata('token_secret', $token->oauth_token_secret);			
			
//			$_SESSION['oauth_tokens']['token'] = $token->oauth_token;echo "token:".$_SESSION['oauth_tokens']['token'];
//			$_SESSION['oauth_tokens']['token_secret'] = $token->oauth_token_secret;
			$this->token = $token->oauth_token;
			$this->token_secret = $token->oauth_token_secret;
			// oauth_token and oauth_token_secret can be stored in db.
			$this->insert_into_db($this->token, $this->token_secret);
			
		//	$this->retrieve_tokens_from_db(1212);
		//	$status = $twitter->post('/statuses/update.json', array('status' => date('l jS \of F Y h:i:s A')));
		}
	}
	
	public function insert_into_db($token, $token_secret)
	{
//		$db = Database::instance();
//		echo $token.'--'.$token_secret;
//		$sql = "insert into twitter_users(token, token_secret) values('".$token."', '".$token_secret."')";
//		$result	=	$db->query(Database::INSERT, $sql, true);
//	//	$result	=	DB::query(Database::INSERT, $sql);
//		//$result->execute();
//		var_dump($result);die;	

//		$this->load->library('database');
		
		$sql = "INSERT into twitter_users(token, token_secret)
				Values(".$this->db->escape($token).",".$this->db->escape($token_secret).")";
		$this->db->query($sql);
		echo $this->db->affected_rows();
	}
	
	/*
	 * Function : retrieve_tokens
	 * desc     : retrieves the tokens for a specific user. This can be used to access the twitter api when the user is offline(not logged in).	 
	 * param	: user_id
	 */
	public function retrieve_tokens_from_db($user_id)
	{
//		$db = Database::instance('default');
//		$sql = "select * from twitter_users where user_id = $user_id";
//		$query = DB::query(Database::SELECT, $sql);
//		$data = $query->execute()->as_array();print_r($data);
//		return $data;	

		$sql = "SELECT * from twitter_users
				WHERE id=".$user_id;
		$query = $this->db->query($sql);
		foreach($query->result() as $row)
		{
			$this->token = $row->token;
			$this->token_secret = $row->token_secret;
		}		
	}
	
	/*
	 * Function : retrieve_tokens_from_session
	 * desc		: sets the token and token_secret from session data
	 */
	public function retrieve_tokens_from_session()
	{
		$this->load->library('session');
		$this->token = $this->session->userdata('token');
		$this->token_secret = $this->session->userdata('token_secret');
	}
	
	/*
	 * Function : create_twitter_object
	 * params 	: consumer_key, consumer_secret
	 * returns	: EpiTwitter object 
	 *
	 */
	public function create_twitter_object($consumer_key, $consumer_secret)
	{
		return new EpiTwitter($consumer_key, $consumer_secret);
	}
	
	
	
	
	/*
	 * Function : get_account_settings
	 * desc		: returns user account info
	 * returns	: an array containing user information
	 */
	
	public function get_account_settings()
	{
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);				
		$twitter->setToken($this->token, $this->token_secret);
		$account = $twitter->get('/account/settings.json');
		$account = $this->object_to_array($account->response);
		
		return $account;
	}
	
	public function get_retweets()
	{
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);				
		$twitter->setToken($this->token, $this->token_secret);
		$tweets = $twitter->get('/statuses/retweets_of_me.json');
		$tweets = $this->object_to_array($tweets->response);
		
		return $tweets;
	}
	
	/*
	 * Function : post_status
	 * desc		: update current user's status
	 * param	: message
	 */
	public function post_status($message)
	{				
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);		
		//$twitter->setToken('1388944016-RGEWLWlL9hld28k60RYxQ6EgusjF1ckd6ye7J7n', 'c1ZqFLMOwIOGjixjSAZtIeN093DZWIPFrW0Iv7fkeko');
		//$twitter->setToken($_SESSION['oauth_tokens']['token'], $_SESSION['oauth_tokens']['token_secret']);
		//$twitter->setToken($this->session->userdata('token'), $this->session->userdata('token_secret'));
		try
		{
//			$twitter->setToken($this->session->userdata('token'), $this->session->userdata('token_secret'));
//			echo "token:".$this->session->userdata('token')."token_secret:".$this->session->userdata('token_secret');
			$twitter->setToken($this->token, $this->token_secret);echo "token:".$this->token."token_secret:".$this->token_secret."\n";
			$twitter->post('/statuses/update.json', array('status' => $message.date('l jS \of F Y h:i:s A')));
		}catch(EpiTwitterException $ex)
		{
			echo "<pre>";print_r($ex);
		}
	}
	
	/*
	 * Function : search_tweets
	 * desc		: returns a collection of relevant tweets matching a specified query
	 * param	: query
	 */
	
	public function search_tweets($query)
	{
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);				
		$twitter->setToken($this->token, $this->token_secret);
		$tweets = $twitter->get('/search/tweets.json', array('q' => $query));
		$tweets = $this->object_to_array($tweets->response);
		
		return $tweets;
	}
	
	/*
	 * Function : get_friends_ids
	 * desc		: gets a cursored collection of friends_ids	 * 
	 * returns	: array
	 */
	 
	public function get_friends_ids()
	{
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);				
		$twitter->setToken($this->token, $this->token_secret);
		
		
//		$friends_ids = $twitter->get('/friends/ids.json', array('screen_name' => "testtwlogin"));
//		$friends_ids = $this->object_to_array($friends_ids->response);
//		
//		return $friends_ids;
		
		$cursor = "-1";
		$count = 1;
		$friends = array();
		do{echo $count++;echo "\n ".$cursor;
		$friends_ids = $twitter->get('/friends/ids.json', array('screen_name' => "testtwlogin", 'count'=> 20, 'cursor' => "-1"));
		$friends_ids = $this->object_to_array($friends_ids->response);
		$friends = array_merge($friends, $friends_ids->ids);
		$cursor = $friends_ids->next_cursor_str;
		}while($cursor != "0");
		
		
		return $friends;
	}
	
	/*
	 * Function : get_friends_list
	 * desc		: gets a cursored collection of user objects for every user the specified user is following
	 * returns 	: array
	 */
	
	public function get_friends_list()
	{
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);				
		$twitter->setToken($this->token, $this->token_secret);
		$friends_list = $twitter->get('/friends/list.json', array('screen_name' => "testtwlogin"));
		$friends_list = $this->object_to_array($friends_list->response);
		
		return $friends_list;	
	}
	
	
	public function get_followers_list()
	{
		$twitter = $this->create_twitter_object($this->consumer_key, $this->consumer_secret);				
		$twitter->setToken($this->token, $this->token_secret);
		$followers_list = $twitter->get('/followers/list.json', array('screen_name' => "testtwlogin"));
		$followers_list = $this->object_to_array($followers_list->response);
		
		return $followers_list;	
	}
	
	public function object_to_array($obj)
	{
		return json_decode(json_encode($obj));
	}
}