<?php

/**************************************************
/* Source Manager
/* Ver1.0
/**************************************************/

class SourceManager{
	
        // Define Config .
	public $conf	= array(
				"toolVer" => "1.0",
				"git" => array( 
						"cmdPath" => "/usr/bin/git",
						"sourcePath" => "/usr/share/app/server/talkn/",
						"githubSecret" => "abc123"
				),
				"svn" => array( 
						"cmdPath" => "/usr/bin/svn",
						"sourcePath" => "/usr/share/app/server/talkn/",
						"statusCmdMap" => array( 
								"?" => "add", 
								"!" => "delete", 
								"A" => "commit", 
								"D" => "commit", 
								"M" => "commit"
						)
				),
				"slack" => array(
						"userName" => "GUI BOT",
						"token" => "xoxp-85971953777-85910630323-85969698677-8e3468180682c34e9d316ecf691766df",
						"channel"=> "api",
						"method"=> "chat.postMessage"
				)
	);
	
        // Define Execute Command List .
	public $cmd = array(
				"git" => array( 
						"pull" => array( "context" => "", "out" => array() )
				),
				"svn" => array(
						"status" => array( "context" => "", "out" => array() ),
						"add" => array( "context" => "", "out" => array() ),
						"commit" => array( "context" => array(), "out" => array() ),
						"delete" => array( "context" => array(), "out" => array() ),
						"update" => array( "context" => array(), "out" => array() ),
				),
				"slack" => array(
						"planeUrl" => "",
						"urlEncodedUrl" => "",
						"notifSuccessFlg" => false,
						"attachments" =>  array( 
							array( "pretext" => "Message", "text" => "" ),
							array( "pretext" => "Added", "text" => "" ),
							array( "pretext" => "Removed", "text" => "" ),
							array( "pretext" => "Modified", "text" => "" )
						)
				)
	);
	
        // Define Svn Prepare Files
	public $svnPrepareFiles = array();
	
        // Define Svn Commit Files
	public $svnCommitFiles = array();
	
        // Define Executed Command List .
	public $_exec = array();
	
        // Define Input Datas .
	public $env = array();
	public $webhook	= array();
	public $auth = array();
	
        // Define Boot Condition List .
	public $bootCondition = array(
				"auth" => false,
				"existCommit" => false
	);
        
        // Constructor .
        function __construct( ){
	
                // Set Input Datas .
		$this->setEnv();
		$this->setWebhook();
		
                // Set Commands . 
		$this->setCmdSvn();
		$this->setCmdGit();
		$this->setCmdSlack();
		
                // Check Auth .
		$this->checkAuth();
	}
        
	// Set Enviroment Params .
	private function setEnv(){
	
		exec( 'whoami', $this->env['whoami'] );
		exec( 'date', $this->env['date'] );
		$this->env[ 'server' ] = $_SERVER;
	}
        
	// Set Github Webhook Params .
	private function setWebhook(){
		$phpInput  = file_get_contents( 'php://input' );
		$this->webhook = json_decode( $phpInput , true );
		$this->webhook[ 'phpInput' ] = $phpInput;
				
		// Set Is Exist Commit Flg .
		$this->bootCondition[ 'existCommit' ] = ( isset( $this->webhook[ 'commits' ] ) && count( $this->webhook[ 'commits' ] ) > 0 )? true : false ;
	}
        
	// Set Svn Params .
	private function setCmdSvn(){
		
		// Get Conf .
		$conf = $this->conf[ 'svn' ];
		// Svn Status * .
		$this->cmd[ 'svn' ][ 'status' ][ 'context' ] = $conf[ 'cmdPath' ] . ' status ' . $conf[ 'sourcePath' ];
		// Svn Add * .
		$this->cmd[ 'svn' ][ 'add' ][ 'context' ] = $conf[ 'cmdPath' ] . ' add --parents ';
		// Svn Delete * .
		$this->cmd[ 'svn' ][ 'delete' ][ 'context' ] = $conf[ 'cmdPath' ] . ' delete ';
		// Svn Commit * .
		$this->cmd[ 'svn' ][ 'commit' ][ 'context' ] = $conf[ 'cmdPath' ] . " commit -m " . 
								"'" . $this->webhook[ 'commits' ][ 0 ][ 'message' ] . "' " .
								$conf[ 'sourcePath' ];
		// Svn Update * .
		$this->cmd[ 'svn' ][ 'update' ][ 'context' ] = $conf[ 'cmdPath' ] . " update " . $conf[ 'sourcePath' ];
	}
        
	// Set Git Params .
	private function setCmdGit(){
		// conf .
		$conf = $this->conf[ 'git' ];
		// git pull * .
		$this->cmd[ 'git' ][ 'pull' ][ 'context' ] = $conf[ 'cmdPath' ]  . 
								" --git-dir=" . $conf[ 'sourcePath' ] . ".git" . 
								" --work-tree=" . $conf[ 'sourcePath' ] . 
								" pull";
	}
        
	// Set Slack API Params .
	private function setCmdSlack(){
		$this->slack[ 'text'] = __CLASS__ . ' ' . $this->conf[ 'toolVer' ] .  ' at ' . $this->env[ 'date' ][ 0 ];
	}
        
        // Get Is Boot Condition Flg .
	public function isBootCondition(){
		foreach( $this->bootCondition as $name => $bool ){
			if( !$bool )  return false;
		}
		return true;
	}
        
	// Authentication .
        private function checkAuth(){ 
		// Define Auth Flg .
		$authFlg = false;
                // Check UserAgent( Contain Github-Hookshot ) .
                if( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) && strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'GitHub-Hookshot' ) !== false ){
			// Check Github Signature( Webhook Secret Key )
			if( isset( $_SERVER[ 'HTTP_X_HUB_SIGNATURE' ] ) ){
                        	$this->auth[ 'signature' ] = $_SERVER[ 'HTTP_X_HUB_SIGNATURE' ];
                        	list( $this->auth[ 'algo' ], $this->auth[ 'hash' ] ) = explode( '=', $this->auth[ 'signature' ], 2 ); 
                        	// Calculate Hash Based On Payload And The Secret .
                        	$this->auth[ 'payloadHash' ] = hash_hmac( $this->auth[ 'algo' ], $this->webhook[ 'phpInput' ], $this->conf[ 'git' ][ 'githubSecret' ] );
                	        // Check If Hashes Are Equivalent
                        	$authFlg = ( $this->auth[ 'hash' ] !== $this->auth[ 'payloadHash' ] )? false : true ;
			}
                }
		// Set Auth Flg .
		$this->bootCondition[ 'auth' ] = $authFlg;
        }
        
	// Set Svn Preapre( add || delete ) .
	public function setSvnPrepareFiles(){
		foreach( $this->cmd[ 'svn' ][ 'status' ][ 'out' ] as $index => $file ){
			switch( $file[ 0 ] ){
			case "?":
				$fileName = str_replace( " ", "", substr( $file, 1 ) );
				break;
			case "!":
				$fileName = str_replace( " ", "", substr( $file, 3 ) );
				break;
			}
			$this->svnPrepareFiles[] = array( "st" => $file[ 0 ], "fileName" => $fileName );
		}
	}
        
        // Execute System Command .
	public function exec( $command, &$outpoint ){
		$commend = $commend . " 2>&1";
		exec( $command, $outpoint );
		$this->_exec[] = $command;
	}

        // Output Log .
        public function log( $sm ){
		// Response To Github Webhook .
		print_r( $sm );
		// Log To Current Dir .
		error_log( print_r( $sm, true ), 3, __DIR__ . "/" .  __CLASS__ . ".log" );
	}
}

/*********************/
/* Script 
/*********************/

$sm = new SourceManager();
if( $sm->isBootCondition() ){
	$sm->exec( $sm->cmd[ 'git' ][ 'pull' ][ 'context' ], $sm->cmd[ 'git' ][ 'pull' ][ 'out' ] );
	$sm->exec( $sm->cmd[ 'svn' ][ 'status' ][ 'context' ], $sm->cmd[ 'svn' ][ 'status' ][ 'out' ] );
	$sm->setSvnPrepareFiles();
	// Svn Add And Delete Action loop .
	foreach( $sm->svnPrepareFiles as $index => $file ){
		// Get Svn Action .
		$svnAction = $sm->conf[ 'svn' ][ 'statusCmdMap' ][ $file[ 'st' ] ];
		// Execute Svn Command .
		$sm->exec( $sm->cmd[ 'svn' ][ $svnAction ][ 'context' ] . $file[ 'fileName' ], $sm->cmd[ 'svn' ][ $svnAction ][ 'out' ] );
	}
	$sm->exec( $sm->cmd[ 'svn' ][ 'commit' ][ 'context' ], $sm->cmd[ 'svn' ][ 'commit' ][ 'out' ] );
	$sm->exec( $sm->cmd[ 'svn' ][ 'update' ][ 'context' ], $sm->cmd[ 'svn' ][ 'update' ][ 'out' ] );
}

// Response To Github Webhook And Output Log .
$sm->log( $sm );

/*
$params[ 'slack' ][ 'attachments'] = json_encode( $params[ 'slack' ][ 'attachments'] );
$params[ 'planeUrl' ] = "https://slack.com/" .  $params[ 'conf' ][ 'channel' ] . "/" . 
						$params[ 'conf' ][ 'method' ] . "?" .
						"token=" . $params[ 'conf' ][ 'token' ] . 
						"&channel=" .$params[ 'conf' ][ 'channel' ] . 
						"&text=" . $params[ 'slack' ][ 'text' ] . 
						"&attachments=" . $params[ 'slack' ][ 'attachments' ] . 
						"&icon_emoji=:ghost:" . 
//						"&username=" . $params[ 'conf' ][ 'userName' ] . 
						"&as_user=true";
$params[ 'slack' ][ 'urlEncodedUrl' ] = urlencode( $params[ 'slack' ][ 'planeUrl' ] );
$params[ 'slack' ][ 'notifSuccessFlg' ] = file_get_contents( $params[ 'slack' ][ 'planeUrl' ] );
//$params[ 'slack' ][ 'notifSuccessFlg' ] = file_get_contents( $params[ 'slack' ][ 'urlEncodedUrl' ] );
// Response to Github Webhook
*/
?>
