<?php

/**
 * ForwardAttachment
 *
 * Plugin to allow users to forward a message as an attachment
 *
 * @version @package_version@
 * @author Roland Liebl
 * @modified by Philip Weir
 */
class forwardattachment extends rcube_plugin
{
	public $task = 'mail';

	function init()
	{
		$this->register_action('plugin.forwardatt', array($this, 'request_action'));

		$rcmail = rcmail::get_instance();
		if ($rcmail->action == '' || $rcmail->action == 'show') {
			$this->add_texts('localization', true);
			$this->include_stylesheet($this->local_skin_path() . '/forwardattachment.css');
			$this->include_script('forwardattachment.js');
			$this->add_button(array('command' => 'plugin.forwardatt', 'type' => 'link', 'class' => 'buttonPas forwardAtt', 'classact' => 'button forwardAtt', 'classsel' => 'button forwardAttSel', 'title' => 'forwardattachment.buttontitle', 'content' => ' ', 'style' => 'display: none;'), 'toolbar');
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
			$a_uid = explode(",",$uid);

			foreach($a_uid as $key => $uid){
				$file = tempnam($temp_dir, 'emlattach');
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

				if(file_put_contents($file, $message)) {
					$attachment = array(
				      'path' => $file,
				      'size' => filesize($file),
				      'name' => $disp_name,
				      'mimetype' => "message/rfc822"
				    );

					// save attachment if valid
					if (($attachment['data'] && $attachment['name']) || ($attachment['path'] && file_exists($attachment['path'])))
						$attachment = $rcmail->plugins->exec_hook('save_attachment', $attachment);

					if ($attachment['status'] && !$attachment['abort']) {
						unset($attachment['data'], $attachment['status'], $attachment['abort']);
						$_SESSION['compose']['attachments'][] = $attachment;
					}
				}
			}
		}

		$rcmail->output->redirect(array('_action' => 'compose', '_id' => $_SESSION['compose']['id']));
		exit;
	}
}

?>