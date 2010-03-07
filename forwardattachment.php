<?php

/**
 * @version 1.0 - 15.08.2009
 * @author Roland 'rosali' Liebl (code) / Sandro Pazzi (images) / Thomas Bruederli (used structure of markasjunk plugin)
 * @website http://myroundcube.googlecode.com
 * @licence GNU GPL
 * @modified by Phil Weir
 **/

class forwardattachment extends rcube_plugin
{
	public $task = 'mail';

	function init()
	{
		$this->register_action('plugin.forwardatt', array($this, 'request_action'));

		$rcmail = rcmail::get_instance();
		if ($rcmail->action == '' || $rcmail->action == 'show') {
			$this->add_texts('localization', true);
			$skin_path = 'skins/'. $this->api->output->config['skin'] .'/forwardattachment.css';
			$skin_path = is_file($this->home .'/'. $skin_path) ? $skin_path : 'skins/default/forwardattachment.css';
			$this->include_stylesheet($skin_path);
			$this->include_script('forwardattachment.js');
			$this->add_button(array('command' => 'plugin.forwardatt', 'title' => 'forwardattachment.buttontitle', 'imagepas' => 'skins/' . $this->api->output->config['skin'] . '/forwardatt_pas.png', 'imageact' => 'skins/' . $this->api->output->config['skin'] . '/forwardatt_act.png'), 'forwardatt');
		}
	}

	function request_action()
	{
		$rcmail = rcmail::get_instance();
		$uids = get_input_value('_uid', RCUBE_INPUT_POST);
		$temp_dir = $rcmail->config->get('temp_dir');

		if(isset($_POST['_uid'])){
			rcmail_compose_cleanup();
			$_SESSION['compose'] = array(
				'id' => uniqid(rand()),
				'mailbox' => $rcmail->imap->get_mailbox_name(),
			);

			$uid = $_POST['_uid'];
			$_FILES = array();
			$a_uid = explode(",",$uid);

			foreach($a_uid as $key => $uid){
				$file = tempnam($temp_dir, 'emlattach')
				$message = $rcmail->imap->get_raw_body($uid);
				$headers = $rcmail->imap->get_headers($uid);

				foreach($headers as $key => $val){
					if($key == 'subject'){
						$subject = (string)substr($rcmail->imap->decode_header($val, TRUE), 0, 16);
						break;
					}
				}

				if(isset($subject) && $subject !="")
					$disp_name = preg_replace("/[^0-9A-Za-z_]/", '_', $subject) . ".eml";
				else
					$disp_name = "message_rfc822.eml";

				if(file_put_contents($file, $message)){
					$_FILES['_attachments']['name'][] = $disp_name;
					$_FILES['_attachments']['type'][] = "message/rfc822";
					$_FILES['_attachments']['tmp_name'][] = $file;
					$_FILES['_attachments']['error'][] = 0;
					$_FILES['_attachments']['size'][] = filesize($file);
				}
			}
		}

		if(is_array($_FILES['_attachments']['tmp_name'])){
			foreach ($_FILES['_attachments']['tmp_name'] as $i => $filepath){
				$_SESSION['compose']['attachments'][] = array('name' => $_FILES['_attachments']['name'][$i],
					'mimetype' => $_FILES['_attachments']['type'][$i],
					'path' => $_FILES['_attachments']['tmp_name'][$i]
					);
			}

			$_FILES = array();
			$rcmail->output->redirect(array('_action' => 'compose', '_id' => $_SESSION['compose']['id']));
			exit;
		}
	}
}

?>