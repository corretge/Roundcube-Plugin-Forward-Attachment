<?php

/**
 * ForwardAttachment
 *
 * Plugin to allow forwarding multiple mails as attachments
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
		$this->register_action('plugin.forwardatt', array($this, 'attach_message'));
		if (rcmail::get_instance()->action == '') {
			$this->include_script('forwardattachment.js');
		}
	}

	function attach_message()
	{
		$rcmail = rcmail::get_instance();
		$imap = $rcmail->imap;
		$uids = get_input_value('_uid', RCUBE_INPUT_POST);
		$temp_dir = $rcmail->config->get('temp_dir');

		if (isset($_POST['_uid'])) {
			$_SESSION['compose'] = array(
				'id' => uniqid(rand()),
				'mailbox' => $imap->get_mailbox_name(),
			);

			$uids = explode(",", $_POST['_uid']);
			foreach ($uids as $key => $uid) {
				$file = tempnam($temp_dir, 'emlattach');
				$message = $imap->get_raw_body($uid);
				$headers = $imap->get_headers($uid);
				$subject = $imap->decode_header($headers->subject);
				$subject = substr($subject, 0, 16);

				if (isset($subject) && $subject != "")
					$disp_name = $subject . ".eml";
				else
					$disp_name = "message_rfc822.eml";

				if (file_put_contents($file, $message)) {
					$attachment = array(
						'path' => $file,
						'size' => filesize($file),
						'name' => $disp_name,
						'mimetype' => "message/rfc822"
						);

					// save attachment if valid
					if (($attachment['data'] && $attachment['name']) || ($attachment['path'] && file_exists($attachment['path'])))
						$attachment = $rcmail->plugins->exec_hook('attachment_save', $attachment);

					if ($attachment['status'] && !$attachment['abort']) {
						unset($attachment['data'], $attachment['status'], $attachment['abort']);
						$_SESSION['compose']['attachments'][] = $attachment;
					}
				}
			}
		}

		$_SESSION['compose']['param']['sent_mbox'] = $rcmail->config->get('sent_mbox');
		$rcmail->output->redirect(array('_action' => 'compose', '_id' => $_SESSION['compose']['id']));
		exit;
	}
}

?>