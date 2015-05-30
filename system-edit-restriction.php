<?php
/*
Plugin Name: System edit restriction
Description: Security!! No-one will be able edit/modify system files(theme+plugins) from Wordpress Dashboard,even admins (So, Only from FTP can be edited)...   It is useful, when you share Admin access to others (P.S.  OTHER MUST-HAVE PLUGINS FOR EVERYONE: http://bitly.com/MWPLUGINS  ) . IF PROBLEMS, JUST REMOVE PLUGIN.
contributors: selnomeria
Version: 1.2
License: GPLv2
*/ if ( ! defined( 'ABSPATH' ) ) exit; //Exit if accessed directly


class SystemEditRestriction {
	protected $fileName = 'login_protection_';
	protected $StartSYMBOL		='<?php //';
	public function __construct()	{
		add_action('activated_plugin', array($this,'sep_activate_redirect'));
		add_action('admin_menu', array($this,'add_menu_buttttton'));
		add_action('admin_init', array($this,'start_admin_restrict_checkerr'));
		register_activation_hook( __FILE__,  array($this, 'sep_activate'));
		register_deactivation_hook( __FILE__,  array($this, 'sep_deactivate'));
	}
	//REDIRECT SETTINGS PAGE (after activation)
	public function sep_activate_redirect($plugin) { if($plugin == plugin_basename( __FILE__ )){ exit( wp_redirect(admin_url( 'admin.php?page=system-edit-restriction-page')) ); } }
	public function sep_activate()	{
			//old_version updating
		$old_dir = ABSPATH.'ALLOWED_IP/';  
		$new_dir =ABSPATH.'wp-content/ALLOWED_IP/'; 
			if (is_dir($old_dir)) {@rename($old_dir,$new_dir);} 
		$old_dir = ABSPATH.'wp-content/ALLOWED_IP/'.str_replace('www.','', $_SERVER['HTTP_HOST']).'/'; 
		$new_dir = ABSPATH.'wp-content/ALLOWED_IP/'.$this->site_nm(); 
			if (is_dir($old_dir)) {@rename($old_dir,$new_dir);} 
	
	}	
	public function sep_deactivate(){unlink($this->allowed_ips_filee());}	
	public function blockedMessage(){return '(HOWEVER,IF YOU BLOCK YOURSELF, enter FTP folder "/WP-CONTENT----ALLOWED_IP/" and add your IP into the file.)';}
	public function domainn()		{return str_replace('www.','', $_SERVER['HTTP_HOST']);	}	
	public function Nonce_checker($value, $action_name)	{
		if ( !isset($value) || !wp_verify_nonce($value, $action_name) ) {die("not allowed due to SYSTEM_EDIT_RESTRICTION");}
	}
	
	public function check_enable_privilegies(){
		//check, if  RESTRICTION enabled
		if (get_option('optin_for_sep_ipss') == 2){
			$allwd_ips = file_get_contents($this->allowed_ips_filee());
			//check - if USER's ip address not found, then RESTRICT!!!
			if (stripos($allwd_ips, $_SERVER['REMOTE_ADDR']) === false){ return false; }	
		}
		return true;
	}
	
	/* not needed, no danger here as i consider.
	public function disable_admin_ajax(){
		if( defined('DOING_AJAX') && DOING_AJAX ) {
			if (!$this->check_enable_privilegies())	{
				define( 'DISALLOW_FILE_MODS', true );
				//from /wp-admin/admin_ajax.php
				$disallowed___core_actions_get = array(	);
				$disallowed___core_actions_post = array();
				if ( ! empty( $_GET['action'] ) ...
			}
		}
	}
	*/
	
	

	
	public function start_admin_restrict_checkerr()	{
		if (!$this->check_enable_privilegies())	{
			define( 'DISALLOW_FILE_MODS', true );
				//remove_menu_page( 'edit-comments.php' );remove_menu_page( 'themes.php' );remove_menu_page( 'plugins.php' );
				//remove_menu_page( 'admin.php?page=mp_st' );remove_menu_page( 'admin.php?page=cp_main' );
				//remove_submenu_page( 'edit.php?post_type=product', 'edit-tags.php?taxonomy=product_category&amp;post_type=product' );

			$restrictions = array('/wp-admin/widgets.php','/wp-admin/widgets.php','/wp-admin/user-new.php',	'/wp-admin/upgrade-functions.php','/wp-admin/upgrade.php',	'/wp-admin/themes.php',	'/wp-admin/theme-install.php',	'/wp-admin/theme-editor.php','/wp-admin/setup-config.php','/wp-admin/plugins.php',	'/wp-admin/plugin-install.php','/wp-admin/options-head.php','/wp-admin/network.php',	'/wp-admin/ms-users.php','/wp-admin/ms-upgrade-network.php','/wp-admin/ms-themes.php',	'/wp-admin/ms-sites.php','/wp-admin/ms-options.php','/wp-admin/ms-edit.php','/wp-admin/ms-delete-site.php','/wp-admin/ms-admin.php','/wp-admin/moderation.php','/wp-admin/menu-header.php','/wp-admin/menu.php','/wp-admin/edit-comments.php',
			//any 3rd party plugins' menu pages, added under "settings"
			'/wp-admin/options-general.php?page='
			);

			foreach ( $restrictions as $restriction ) {
				if ( strpos($_SERVER['REQUEST_URI'],$restriction) !== false) {
					die('no access to this page. error_534 ... <a href="./">Go Back</a> <br/><br/>'.$this->blockedMessage());
				}
			}
		}
	
	}
	
	
	public function allowed_ips_filee()	{
		//initial values
		$bakcup_of_ipfile = get_option("backup_allowed_ips_modify_". $this->domainn() );
		$Value = !empty($bakcup_of_ipfile)?  $bakcup_of_ipfile : $this->StartSYMBOL. '101.101.101.101 (e.g. its James, my friend)|||'.$_SERVER['REMOTE_ADDR'].' (its my pc),';
		//file path
		$pt_folder = ABSPATH.'/wp-content/ALLOWED_IP/'. $this->domainn();		if(!file_exists($pt_folder)){mkdir($pt_folder, 0755, true);}
		$file = $pt_folder .'/ALLOWED_IPs_FOR_WP_MODIFICATION.php';	if(!file_exists($file))		{file_put_contents($file, $Value);}
		return $file;
	}
	
	public function add_menu_buttttton() {
		add_submenu_page('options-general.php', 'System Edit Restrict', 'System Edit Restrict', 'manage_options', 'system-edit-restriction-page', array($this,'sep_output') ); } public function sep_output() { ?>
			<?php
			//IF whitelist updated
			if (!empty($_POST['opt_of_whitelist_ips'])) 
			{
				$this->Nonce_checker($_POST['update_nonce'],'uupnonce');
				
				//update setting
				update_option('optin_for_sep_ipss',$_POST['opt_of_whitelist_ips']);
				//change IP file
					$final	= $_POST['sep_white_IPS'];
					$final	= str_replace("\r\n\r\n",	"",		$final);
					$final	= str_replace("\r\n",		"|||",	$final);
				file_put_contents($this->allowed_ips_filee(), $this->StartSYMBOL .$final );
				//make backup
				update_option("backup_allowed_ips_modify_". $this->domainn() ,  $this->StartSYMBOL .$final);
			}
		
			$allowed_ips 	= str_replace($this->StartSYMBOL, '', file_get_contents($this->allowed_ips_filee()) );
			$whiteip_answer	= get_option('optin_for_sep_ipss');
			$d2 = $whiteip_answer == 2 ? "checked" : '';
			$d1 = $whiteip_answer == 1 || empty($whiteip_answer) ? "checked" : '';
			?>
			<br/><br/>	
			<form method="post" action="">
				<p class="submit">
					
				<div class="white_list_ipps" style="background-color: #1EE41E;padding: 5px; margin:0 0 0 20%;width: 50%;">
					<div style="font-size:1.2em;font-weight:bold;">
						RESTRICT PLUGIN/THEME EDIT&INSTALL from DASHBOARD: (<a href="javascript:alert('1)OFF - No changes. any admin will have a full access. \r\n2) ON - Only the listed IPs can  EDIT&INSTALL PLUGINS or THEMES. Another IP (even if he is admin) cant EDIT&INSTALL them. <?php echo $this->blockedMessage();?> \r\n');">read more!!</a>):
					</div>
		<table style="border:1px solid;"><tbody>
			<tr><td>OFF	</td><td><input onclick="lg_radiod();" type="radio" name="opt_of_whitelist_ips" value="1" <?php echo $d1;?> /></td></tr>
			<tr><td>ON	</td><td><input onclick="lg_radiod();" type="radio" name="opt_of_whitelist_ips" value="2" <?php echo $d2;?> /></td></tr>
		</tbody></table>
					<div style="float:right;">(your IP is <b style="color:red;background-color:yellow;"><?php echo $_SERVER['REMOTE_ADDR'];?></b>)</div>
				<br/><div id="DIV_whiteipielddd" style="overflow-y:auto;">
						<?php	$liness=explode("|||",$allowed_ips); ?>
						<textarea id="whiteips_fieldd" style="width:100%;height:150px;" name="sep_white_IPS"><?php foreach ($liness as $line) {echo $line."\r\n";}?></textarea>
					</div>
					
					<script type="text/javascript">
					function lg_radiod(){
						var valllue = document.querySelector('input[name="opt_of_whitelist_ips"]:checked').value;
						document.getElementById("DIV_whiteipielddd").style.opacity = (valllue != "1") ? "1" : "0.3";
					}
					lg_radiod();
					</script>
				</div>

					<br/><div style="clear:both;"></div>
					<input type="hidden" name="update_nonce" value="<?php echo wp_create_nonce('uupnonce');?>" />
					<input type="submit"  value="SAVE" onclick="return check_sep_ips();" />
					<script type="text/javascript">
					function check_sep_ips(){
						var IPLIST_VALUE=document.getElementById("whiteips_fieldd").value;
						var user_ip="<?php echo $_SERVER['REMOTE_ADDR'];?>";
						
						var TurnedONOFF = document.querySelector('input[name="opt_of_whitelist_ips"]:checked').value;
						if (TurnedONOFF != "1")	{
							if (IPLIST_VALUE.indexOf(user_ip) == -1){
								if(!confirm("YOUR IP(" + user_ip +") is not in list! Are you sure you want to continue?")){return false;}
							}
						}
						return true;
					}
					</script>
				</p> 
	
	
	<?php
	}
}
$GLOBALS['SystemEditProtectionzzz'] = new SystemEditRestriction;