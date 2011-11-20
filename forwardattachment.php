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
		if (rcmail::get_instance()->action == '')
			$this->include_script('forwardattachment.js');
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
				$message = new rcube_message($uid);
				$this->_rcmail_write_forward_attachment($message);
			}
		}

		$_SESSION['compose']['param']['sent_mbox'] = $rcmail->config->get('sent_mbox');
		$rcmail->output->redirect(array('_action' => 'compose', '_id' => $_SESSION['compose']['id']));
		exit;
	}

	// Creates an attachment from the forwarded message
	// Copied from program/steps/mail/compose.inc
	private function _rcmail_write_forward_attachment(&$message)
	{
		$rcmail = rcmail::get_instance();

		if (strlen($message->subject))
			$name = mb_substr($message->subject, 0, 64) . '.eml';
		else
			$name = 'message_rfc822.eml';

		$mem_limit = parse_bytes(ini_get('memory_limit'));
		$curr_mem = function_exists('memory_get_usage') ? memory_get_usage() : 16*1024*1024; // safe value: 16MB
		$data = $path = null;

		// don't load too big attachments into memory
		if ($mem_limit > 0 && $message->size > $mem_limit - $curr_mem) {
			$temp_dir = unslashify($rcmail->config->get('temp_dir'));
			$path = tempnam($temp_dir, 'rcmAttmnt');
			if ($fp = fopen($path, 'w')) {
				$rcmail->imap->get_raw_body($message->uid, $fp);
				fclose($fp);
			}
			else {
				return;
			}
		}
		else {
			$data = $rcmail->imap->get_raw_body($message->uid);
		}

		$attachment = array(
			'group' => $_SESSION['compose']['id'],
			'name' => $name,
			'mimetype' => 'message/rfc822',
			'data' => $data,
			'path' => $path,
			'size' => $path ? filesize($path) : strlen($data),
		);

		$attachment = $rcmail->plugins->exec_hook('attachment_save', $attachment);

		if ($attachment['status']) {
			unset($attachment['data'], $attachment['status'], $attachment['content_id'], $attachment['abort']);
			$_SESSION['compose']['attachments'][$attachment['id']] = $attachment;
		}
		elseif ($path) {
			@unlink($path);
		}
	}
}

?>